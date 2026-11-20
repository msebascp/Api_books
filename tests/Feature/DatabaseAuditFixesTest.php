<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\ReadingStatus;
use App\Models\Book;
use App\Models\LibraryEntry;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DatabaseAuditFixesTest extends TestCase
{
    use RefreshDatabase;

    public function test_email_is_hidden_publicly_and_visible_only_to_profile_owner(): void
    {
        $alice = User::factory()->create([
            'username' => 'alice',
            'email' => 'alice@private.test',
        ]);
        $bob = User::factory()->create([
            'username' => 'bob',
            'email' => 'bob@private.test',
        ]);

        $alice->following()->attach($bob->id);
        $book = Book::factory()->create();
        Review::factory()->create([
            'user_id' => $alice->id,
            'book_id' => $book->id,
        ]);

        // Invitado anónimo: no ve el email en perfil público, seguidores ni feed de reseñas
        $this->getJson('/api/users/alice')
            ->assertOk()
            ->assertJsonPath('data.username', 'alice')
            ->assertJsonMissingPath('data.email');

        $this->getJson("/api/users/{$bob->id}/followers")
            ->assertOk()
            ->assertJsonPath('data.0.username', 'alice')
            ->assertJsonMissingPath('data.0.email');

        $this->getJson('/api/reviews')
            ->assertOk()
            ->assertJsonPath('data.0.user.username', 'alice')
            ->assertJsonMissingPath('data.0.user.email');

        // Autenticado como otro usuario (Bob): tampoco ve el email de Alice
        Sanctum::actingAs($bob);

        $this->getJson('/api/users/alice')
            ->assertOk()
            ->assertJsonMissingPath('data.email');

        // Autenticado como dueño (Alice): sí ve su propio email en /api/me y en su perfil público
        Sanctum::actingAs($alice);

        $this->getJson('/api/me')
            ->assertOk()
            ->assertJsonPath('data.email', 'alice@private.test');

        $this->getJson('/api/users/alice')
            ->assertOk()
            ->assertJsonPath('data.email', 'alice@private.test');
    }

    public function test_deleting_user_recalculates_affected_books_ratings_and_cleans_tokens_and_roles(): void
    {
        Role::findOrCreate('user', 'web');

        $book1 = Book::factory()->create();
        $book2 = Book::factory()->create();
        [$alice, $bob] = User::factory()->count(2)->create()->all();

        $alice->assignRole('user');
        $alice->createToken('test_device');

        // book1: Alice (5.0) y Bob (3.0) -> avg 4.00, count 2
        LibraryEntry::factory()->create(['user_id' => $alice->id, 'book_id' => $book1->id, 'rating' => 5.0]);
        LibraryEntry::factory()->create(['user_id' => $bob->id, 'book_id' => $book1->id, 'rating' => 3.0]);

        // book2: solo Alice (4.5) -> avg 4.50, count 1
        LibraryEntry::factory()->create(['user_id' => $alice->id, 'book_id' => $book2->id, 'rating' => 4.5]);

        $book1->refresh();
        $book2->refresh();
        $this->assertSame(2, $book1->ratings_count);
        $this->assertSame(4.0, $book1->avg_rating);
        $this->assertSame(1, $book2->ratings_count);
        $this->assertSame(4.5, $book2->avg_rating);

        $aliceId = $alice->id;
        $alice->delete();

        $book1->refresh();
        $book2->refresh();

        // book1 pasa a tener solo la reseña de Bob (3.0, count 1); book2 queda a 0
        $this->assertSame(1, $book1->ratings_count);
        $this->assertSame(3.0, $book1->avg_rating);
        $this->assertSame(0, $book2->ratings_count);
        $this->assertSame(0.0, $book2->avg_rating);

        // Tokens de Sanctum y roles de Spatie limpiados
        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_type' => User::class,
            'tokenable_id' => $aliceId,
        ]);
        $this->assertDatabaseMissing('model_has_roles', [
            'model_type' => User::class,
            'model_id' => $aliceId,
        ]);
    }

    public function test_book_calculated_columns_are_protected_from_mass_assignment_and_readers_relation_is_symmetric(): void
    {
        $book = Book::create([
            'title' => 'Libro Protegido',
            'avg_rating' => 4.95,
            'ratings_count' => 999,
        ]);

        $book->refresh();
        $this->assertSame(0.0, $book->avg_rating);
        $this->assertSame(0, $book->ratings_count);

        $user = User::factory()->create();
        LibraryEntry::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'status' => ReadingStatus::Read,
            'is_owned' => true,
            'is_favorite' => false,
        ]);

        $reader = $book->readers()->firstOrFail();
        $this->assertSame($user->id, $reader->id);
        $this->assertSame('read', $reader->pivot->status);
    }

    public function test_book_rating_must_be_multiple_of_half_point(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();
        Sanctum::actingAs($user);

        // 4.37 no es múltiplo de 0.5 -> 422
        $this->putJson("/api/books/{$book->id}/rating", [
            'rating' => 4.37,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['rating']);

        // 4.5 sí es múltiplo de 0.5 -> 200
        $this->putJson("/api/books/{$book->id}/rating", [
            'rating' => 4.5,
        ])
            ->assertOk()
            ->assertJsonPath('data.viewer_rating', 4.5);

        // En actualización posterior también rechaza valores que no sean múltiplos de 0.5
        $this->putJson("/api/books/{$book->id}/rating", [
            'rating' => 3.2,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['rating']);
    }

    public function test_isbn_is_normalized_preventing_duplicates_with_and_without_hyphens(): void
    {
        Role::findOrCreate('admin', 'web');
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        Sanctum::actingAs($admin);

        // Crear libro con guiones y espacios y letra final en minúscula
        $createRes = $this->postJson('/api/books', [
            'title' => 'Edición con guiones',
            'isbn' => '978-84-376-0494-7',
        ])
            ->assertCreated()
            ->assertJsonPath('data.isbn', '9788437604947');

        $firstBookId = (int) $createRes->json('data.id');

        // Intentar crear otro con el mismo ISBN en distinto formato (con espacios o limpio) -> 422
        $this->postJson('/api/books', [
            'title' => 'Duplicado sin guiones',
            'isbn' => '978 84 376 0494 7',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['isbn']);

        // Crear segundo libro con ISBN-10 terminado en 'x' minúscula -> se normaliza a 'X'
        $secondRes = $this->postJson('/api/books', [
            'title' => 'Edición ISBN-10',
            'isbn' => '0-306-40615-x',
        ])
            ->assertCreated()
            ->assertJsonPath('data.isbn', '030640615X');

        $secondBookId = (int) $secondRes->json('data.id');

        // Intentar actualizar el segundo libro con el ISBN del primero con guiones -> 422
        $this->putJson("/api/books/{$secondBookId}", [
            'isbn' => '978-84-3760494-7',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['isbn']);

        // Actualizar el primer libro con su propio ISBN con guiones -> 200 OK
        $this->putJson("/api/books/{$firstBookId}", [
            'isbn' => '978-84-376-0494-7',
        ])
            ->assertOk()
            ->assertJsonPath('data.isbn', '9788437604947');
    }

    public function test_library_entry_upsert_executes_single_select_and_reading_log_supports_pre_1970_dates(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();
        Sanctum::actingAs($user);

        // Registrar lectura con fecha histórica anterior a 1970 mediante reading-logs
        $this->postJson("/api/books/{$book->id}/reading-logs", [
            'finished_at' => '1967-05-30',
        ])
            ->assertCreated()
            ->assertJsonPath('data.finished_at', '1967-05-30');

        // Cambiar el estado existente a to_read conserva read_count y last_read_at
        // y ejecuta solo 1 SELECT sobre library_entries entre validación y controlador
        DB::flushQueryLog();
        DB::enableQueryLog();

        $this->postJson('/api/library-entries', [
            'book_id' => $book->id,
            'status' => ReadingStatus::ToRead->value,
        ])
            ->assertOk()
            ->assertJsonPath('data.status', 'to_read')
            ->assertJsonPath('data.read_count', 1)
            ->assertJsonPath('data.last_read_at', '1967-05-30');

        $librarySelects = collect(DB::getQueryLog())
            ->filter(static fn (array $q): bool => str_contains(strtolower($q['query']), 'select')
                && str_contains(strtolower($q['query']), 'library_entries'))
            ->count();
        DB::disableQueryLog();

        $this->assertSame(1, $librarySelects);
    }
}
