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

class ReadingLogsAndUiFlagsTest extends TestCase
{
    use RefreshDatabase;

    public function test_first_and_second_reading_logs_auto_detect_reread_and_update_library_entry(): void
    {
        $user = User::factory()->create(['username' => 'lector_diario']);
        $book = Book::factory()->create();
        Sanctum::actingAs($user);

        // Validación: finished_at no puede ser anterior a started_at
        $this->postJson("/api/books/{$book->id}/reading-logs", [
            'started_at' => '2024-05-10',
            'finished_at' => '2024-05-01',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['finished_at']);

        // 1ª lectura: is_reread = false automático, crea library_entry con status = read, read_count = 1
        $this->postJson("/api/books/{$book->id}/reading-logs", [
            'started_at' => '2023-02-01',
            'finished_at' => '2023-02-14',
        ])
            ->assertCreated()
            ->assertJsonPath('data.started_at', '2023-02-01')
            ->assertJsonPath('data.finished_at', '2023-02-14')
            ->assertJsonPath('data.is_reread', false);

        $entry = LibraryEntry::query()
            ->where('user_id', $user->id)
            ->where('book_id', $book->id)
            ->firstOrFail();

        $this->assertSame(ReadingStatus::Read, $entry->status);
        $this->assertSame(1, $entry->read_count);
        $this->assertSame('2023-02-14', $entry->last_read_at?->format('Y-m-d'));

        // 2ª lectura: sin enviar is_reread se marca automáticamente true, read_count = 2, last_read_at actualizado
        $this->postJson("/api/books/{$book->id}/reading-logs", [
            'started_at' => '2025-08-01',
            'finished_at' => '2025-08-20',
        ])
            ->assertCreated()
            ->assertJsonPath('data.is_reread', true)
            ->assertJsonPath('data.finished_at', '2025-08-20');

        $entry->refresh();
        $this->assertSame(2, $entry->read_count);
        $this->assertSame('2025-08-20', $entry->last_read_at?->format('Y-m-d'));

        // Listado privado y público con filtro por año (?year=2025) y por libro (?book_id=...)
        $this->getJson('/api/reading-logs?year=2025')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.finished_at', '2025-08-20');

        $this->getJson("/api/users/lector_diario/reading-logs?book_id={$book->id}")
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.finished_at', '2025-08-20')
            ->assertJsonPath('data.1.finished_at', '2023-02-14');
    }

    public function test_moving_read_book_to_to_read_preserves_read_count_and_filters_with_has_been_read(): void
    {
        $user = User::factory()->create();
        $readBook = Book::factory()->create();
        $unreadBook = Book::factory()->create();
        Sanctum::actingAs($user);

        // Registrar 2 lecturas del primer libro
        $this->postJson("/api/books/{$readBook->id}/reading-logs", [
            'finished_at' => '2022-04-10',
        ])->assertCreated();

        $this->postJson("/api/books/{$readBook->id}/reading-logs", [
            'finished_at' => '2024-11-05',
        ])->assertCreated();

        // Añadir un segundo libro nunca leído en to_read
        $this->postJson('/api/library-entries', [
            'book_id' => $unreadBook->id,
            'status' => ReadingStatus::ToRead->value,
        ])->assertCreated();

        // Mover el libro ya leído a to_read (para futura 3ª lectura)
        $this->postJson('/api/library-entries', [
            'book_id' => $readBook->id,
            'status' => ReadingStatus::ToRead->value,
        ])
            ->assertOk()
            ->assertJsonPath('data.status', 'to_read')
            ->assertJsonPath('data.read_count', 2)
            ->assertJsonPath('data.last_read_at', '2024-11-05');

        // ?has_been_read=1 devuelve el libro leído aunque su estado actual sea to_read
        $this->getJson('/api/library-entries?has_been_read=1')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.book_id', $readBook->id)
            ->assertJsonPath('data.0.status', 'to_read')
            ->assertJsonPath('data.0.read_count', 2);

        // ?has_been_read=0 devuelve solo el nunca leído
        $this->getJson('/api/library-entries?has_been_read=0')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.book_id', $unreadBook->id)
            ->assertJsonPath('data.0.read_count', 0);
    }

    public function test_updating_and_deleting_reading_logs_recalculates_stats_and_enforces_ownership(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $book = Book::factory()->create();
        Sanctum::actingAs($owner);

        $firstLogId = (int) $this->postJson("/api/books/{$book->id}/reading-logs", [
            'started_at' => '2021-01-01',
            'finished_at' => '2021-01-15',
        ])->assertCreated()->json('data.id');

        $secondLogId = (int) $this->postJson("/api/books/{$book->id}/reading-logs", [
            'started_at' => '2023-06-01',
            'finished_at' => '2023-06-20',
        ])->assertCreated()->json('data.id');

        // Otro usuario recibe 403 al intentar editar o borrar
        Sanctum::actingAs($intruder);

        $this->putJson("/api/reading-logs/{$secondLogId}", [
            'finished_at' => '2023-07-01',
        ])->assertForbidden();

        $this->deleteJson("/api/reading-logs/{$secondLogId}")
            ->assertForbidden();

        // El dueño edita la fecha de finalización -> recalcula last_read_at
        Sanctum::actingAs($owner);

        $this->putJson("/api/reading-logs/{$secondLogId}", [
            'finished_at' => '2024-01-10',
        ])
            ->assertOk()
            ->assertJsonPath('data.finished_at', '2024-01-10');

        $entry = LibraryEntry::query()->where('user_id', $owner->id)->where('book_id', $book->id)->firstOrFail();
        $this->assertSame(2, $entry->read_count);
        $this->assertSame('2024-01-10', $entry->last_read_at?->format('Y-m-d'));

        // Eliminar la 2ª lectura -> read_count = 1, last_read_at vuelve a 2021-01-15
        $this->deleteJson("/api/reading-logs/{$secondLogId}")
            ->assertOk();

        $entry->refresh();
        $this->assertSame(1, $entry->read_count);
        $this->assertSame('2021-01-15', $entry->last_read_at?->format('Y-m-d'));

        // Eliminar la última lectura -> el library_entry NO se borra, queda con read_count = 0 y last_read_at = null
        $this->deleteJson("/api/reading-logs/{$firstLogId}")
            ->assertOk();

        $entry->refresh();
        $this->assertSame(0, $entry->read_count);
        $this->assertNull($entry->last_read_at);
    }

    public function test_soft_deleted_book_hides_from_catalog_remains_in_user_history_and_reviews_and_can_be_restored(): void
    {
        Role::findOrCreate('admin', 'web');
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $reader = User::factory()->create();
        $book = Book::factory()->create(['title' => 'Edición Descatalogada']);

        Sanctum::actingAs($reader);

        $this->postJson("/api/books/{$book->id}/reading-logs", [
            'finished_at' => '2024-03-15',
        ])->assertCreated();

        $this->postJson("/api/books/{$book->id}/reviews", [
            'content' => 'Inolvidable.',
        ])->assertCreated();

        // Admin archiva (soft-delete) el libro
        Sanctum::actingAs($admin);
        $this->deleteJson("/api/books/{$book->id}")->assertOk();

        $this->assertSoftDeleted('books', ['id' => $book->id]);

        // Desaparece del catálogo público
        $this->getJson('/api/books')->assertOk()->assertJsonCount(0, 'data');
        $this->getJson("/api/books/{$book->id}")->assertNotFound();

        // Pero el lector conserva su historial, su biblioteca y su reseña con el libro embebido (is_archived = true)
        Sanctum::actingAs($reader);

        $this->getJson('/api/reading-logs')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.book.id', $book->id)
            ->assertJsonPath('data.0.book.is_archived', true);

        $this->getJson('/api/library-entries')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.book.id', $book->id)
            ->assertJsonPath('data.0.book.is_archived', true);

        $this->getJson('/api/reviews')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.book.id', $book->id)
            ->assertJsonPath('data.0.book.is_archived', true);

        // Un usuario no-admin no puede restaurarlo (403)
        $this->postJson("/api/books/{$book->id}/restore")->assertForbidden();

        // Admin restaura el libro
        Sanctum::actingAs($admin);
        $this->postJson("/api/books/{$book->id}/restore")
            ->assertOk()
            ->assertJsonPath('data.id', $book->id)
            ->assertJsonPath('data.is_archived', false);

        $this->getJson("/api/books/{$book->id}")
            ->assertOk()
            ->assertJsonPath('data.is_archived', false);
    }

    public function test_viewer_flags_work_without_n_plus_one(): void
    {
        [$alice, $bob, $carol] = User::factory()->count(3)->create()->all();
        $book1 = Book::factory()->create();
        $book2 = Book::factory()->create();

        // Alice sigue a Bob (pero no a Carol)
        $alice->following()->attach($bob->id);

        $bobReview = Review::factory()->create([
            'user_id' => $bob->id,
            'book_id' => $book1->id,
            'content' => 'Excelente desarrollo de personajes.',
        ]);

        Review::factory()->create([
            'user_id' => $carol->id,
            'book_id' => $book2->id,
            'content' => 'Muy entretenido.',
        ]);

        // Alice le da like a la reseña de Bob
        $alice->likedReviews()->attach($bobReview->id);

        // Invitado: liked_by_viewer y followed_by_viewer son siempre false
        $this->getJson("/api/books/{$book1->id}/reviews")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $bobReview->id)
            ->assertJsonPath('data.0.liked_by_viewer', false)
            ->assertJsonPath('data.0.user.followed_by_viewer', false);

        // Autenticada como Alice: ve liked_by_viewer = true y followed_by_viewer = true en Bob, false en sí misma
        Sanctum::actingAs($alice);

        $this->getJson("/api/books/{$book1->id}/reviews")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.liked_by_viewer', true)
            ->assertJsonPath('data.0.user.followed_by_viewer', true);

        $this->getJson("/api/users/{$bob->username}")
            ->assertOk()
            ->assertJsonPath('data.followed_by_viewer', true);

        $this->getJson("/api/users/{$carol->username}")
            ->assertOk()
            ->assertJsonPath('data.followed_by_viewer', false);

        $this->getJson("/api/users/{$alice->username}")
            ->assertOk()
            ->assertJsonPath('data.followed_by_viewer', false);

        // Verificación de ausencia de N+1 en GET /api/reviews al escalar el número de reseñas
        $queriesForReviews = function (int $extraReviews) use ($alice): int {
            $books = Book::factory()->count($extraReviews)->create();
            foreach ($books as $b) {
                $r = Review::factory()->create(['book_id' => $b->id]);
                $alice->likedReviews()->attach($r->id);
            }

            DB::flushQueryLog();
            DB::enableQueryLog();
            $this->getJson('/api/reviews?per_page=50')->assertOk();
            $count = count(DB::getQueryLog());
            DB::disableQueryLog();

            return $count;
        };

        $this->assertSame($queriesForReviews(2), $queriesForReviews(6));
    }
}
