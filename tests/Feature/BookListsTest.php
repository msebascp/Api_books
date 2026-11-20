<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\ReadingStatus;
use App\Models\Book;
use App\Models\LibraryEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BookListsTest extends TestCase
{
    use RefreshDatabase;

    public function test_crud_unique_slug_per_user_and_privacy_controls(): void
    {
        $alice = User::factory()->create(['username' => 'alice_lists']);
        $bob = User::factory()->create(['username' => 'bob_lists']);

        // Bob sigue a Alice
        $bob->following()->attach($alice->id);

        Sanctum::actingAs($alice);

        // 1ª lista pública con título "Imprescindibles Sci-Fi" -> slug "imprescindibles-sci-fi"
        $publicListId = (int) $this->postJson('/api/lists', [
            'title' => 'Imprescindibles Sci-Fi',
            'description' => 'Mis favoritos del género.',
            'is_public' => true,
            'is_ranked' => true,
        ])
            ->assertCreated()
            ->assertJsonPath('data.slug', 'imprescindibles-sci-fi')
            ->assertJsonPath('data.is_public', true)
            ->assertJsonPath('data.is_ranked', true)
            ->assertJsonPath('data.books_count', 0)
            ->json('data.id');

        // 2ª lista del mismo usuario con el mismo título -> slug "imprescindibles-sci-fi-2" (privada)
        $privateListId = (int) $this->postJson('/api/lists', [
            'title' => 'Imprescindibles Sci-Fi',
            'is_public' => false,
        ])
            ->assertCreated()
            ->assertJsonPath('data.slug', 'imprescindibles-sci-fi-2')
            ->assertJsonPath('data.is_public', false)
            ->json('data.id');

        // Bob crea una lista con el mismo título -> para su user_id recibe "imprescindibles-sci-fi"
        Sanctum::actingAs($bob);
        $this->postJson('/api/lists', [
            'title' => 'Imprescindibles Sci-Fi',
        ])
            ->assertCreated()
            ->assertJsonPath('data.slug', 'imprescindibles-sci-fi');

        // Bob ve solo la lista pública de Alice en /api/users/alice_lists/lists (y ve followed_by_viewer = true)
        $this->getJson('/api/users/alice_lists/lists')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $publicListId)
            ->assertJsonPath('data.0.user.followed_by_viewer', true);

        // Bob no puede ver la lista privada de Alice ni sus libros, ni editarla o borrarla (403)
        $this->getJson("/api/lists/{$privateListId}")->assertForbidden();
        $this->getJson("/api/lists/{$privateListId}/books")->assertForbidden();
        $this->putJson("/api/lists/{$publicListId}", ['title' => 'Hack'])->assertForbidden();
        $this->deleteJson("/api/lists/{$publicListId}")->assertForbidden();

        // Alice sí ve sus 2 listas (pública y privada) en GET /api/lists y puede ver su lista privada
        Sanctum::actingAs($alice);

        $this->getJson('/api/lists')
            ->assertOk()
            ->assertJsonCount(2, 'data');

        $this->getJson("/api/lists/{$privateListId}")
            ->assertOk()
            ->assertJsonPath('data.id', $privateListId);

        // Al actualizar el título se regenera el slug
        $this->putJson("/api/lists/{$privateListId}", [
            'title' => 'Lecturas de Verano',
        ])
            ->assertOk()
            ->assertJsonPath('data.slug', 'lecturas-de-verano');

        $this->deleteJson("/api/lists/{$privateListId}")->assertOk();
        $this->assertDatabaseMissing('book_lists', ['id' => $privateListId]);
    }

    public function test_adding_books_duplicate_rejection_removing_soft_deleted_book_compaction_and_reordering(): void
    {
        $owner = User::factory()->create();
        [$b1, $b2, $b3] = Book::factory()->count(3)->create()->all();
        $archivedBook = Book::factory()->create();
        $archivedBook->delete();

        Sanctum::actingAs($owner);

        $listId = (int) $this->postJson('/api/lists', [
            'title' => 'Mi Top',
            'is_ranked' => true,
        ])->assertCreated()->json('data.id');

        // No se puede añadir un libro ya archivado (deleted_at IS NOT NULL) -> 422
        $this->postJson("/api/lists/{$listId}/books", ['book_id' => $archivedBook->id])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['book_id']);

        // Añadir b1, b2, b3 -> posiciones 1, 2, 3
        $this->postJson("/api/lists/{$listId}/books", ['book_id' => $b1->id])->assertOk();
        $this->postJson("/api/lists/{$listId}/books", ['book_id' => $b2->id])->assertOk();
        $this->postJson("/api/lists/{$listId}/books", ['book_id' => $b3->id])
            ->assertOk()
            ->assertJsonPath('data.books_count', 3);

        // Duplicado rechazado con 422
        $this->postJson("/api/lists/{$listId}/books", ['book_id' => $b2->id])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['book_id']);

        // Si b2 se archiva (soft-delete) después de estar en la lista, sigue en la lista (is_archived = true) y el dueño puede retirarlo
        $b2->delete();

        $this->getJson("/api/lists/{$listId}/books")
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('data.1.id', $b2->id)
            ->assertJsonPath('data.1.is_archived', true)
            ->assertJsonPath('data.1.list_position', 2);

        // Retirar b2 (soft-deleted) recompacta las posiciones restantes a 1..2
        $this->deleteJson("/api/lists/{$listId}/books/{$b2->id}")
            ->assertOk();

        $this->getJson("/api/lists/{$listId}/books")
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.id', $b1->id)
            ->assertJsonPath('data.0.list_position', 1)
            ->assertJsonPath('data.1.id', $b3->id)
            ->assertJsonPath('data.1.list_position', 2);

        // Reordenar falla con 422 si faltan o sobran IDs o si hay duplicados
        $this->putJson("/api/lists/{$listId}/books/reorder", [
            'book_ids' => [$b1->id],
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['book_ids']);

        $this->putJson("/api/lists/{$listId}/books/reorder", [
            'book_ids' => [$b1->id, $b3->id, 99999],
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['book_ids']);

        // Reordenación válida invirtiendo [b3, b1]
        $this->putJson("/api/lists/{$listId}/books/reorder", [
            'book_ids' => [$b3->id, $b1->id],
        ])->assertOk();

        $this->getJson("/api/lists/{$listId}/books")
            ->assertOk()
            ->assertJsonPath('data.0.id', $b3->id)
            ->assertJsonPath('data.0.list_position', 1)
            ->assertJsonPath('data.1.id', $b1->id)
            ->assertJsonPath('data.1.list_position', 2);
    }

    public function test_all_five_sorting_modes_including_unrated_books_last_on_rating_asc(): void
    {
        $owner = User::factory()->create();
        $unratedBook = Book::factory()->create(['title' => 'Sin votos']);
        $lowRatedBook = Book::factory()->create(['title' => 'Bajo']);
        $highRatedBook = Book::factory()->create(['title' => 'Alto']);

        // Puntuar lowRatedBook (2.5) y highRatedBook (4.5); unratedBook queda con ratings_count = 0
        $e1 = LibraryEntry::factory()->create(['user_id' => $owner->id, 'book_id' => $lowRatedBook->id, 'status' => ReadingStatus::Read]);
        $e1->forceFill(['rating' => 2.5])->save();

        $e2 = LibraryEntry::factory()->create(['user_id' => $owner->id, 'book_id' => $highRatedBook->id, 'status' => ReadingStatus::Read]);
        $e2->forceFill(['rating' => 4.5])->save();

        $list = $owner->bookLists()->create(['title' => 'Lista Ordenable']);

        // Insertar con timestamps escalonados y posiciones custom: 1=unrated, 2=highRated, 3=lowRated
        DB::table('book_list_items')->insert([
            ['book_list_id' => $list->id, 'book_id' => $unratedBook->id, 'position' => 1, 'created_at' => '2025-01-01 10:00:00'],
            ['book_list_id' => $list->id, 'book_id' => $highRatedBook->id, 'position' => 2, 'created_at' => '2025-01-02 10:00:00'],
            ['book_list_id' => $list->id, 'book_id' => $lowRatedBook->id, 'position' => 3, 'created_at' => '2025-01-03 10:00:00'],
        ]);

        // 1. sort=custom (por defecto): [unrated, high, low]
        $this->getJson("/api/lists/{$list->id}/books")
            ->assertOk()
            ->assertJsonPath('data.0.id', $unratedBook->id)
            ->assertJsonPath('data.1.id', $highRatedBook->id)
            ->assertJsonPath('data.2.id', $lowRatedBook->id);

        // 2. sort=added_desc: [low, high, unrated]
        $this->getJson("/api/lists/{$list->id}/books?sort=added_desc")
            ->assertOk()
            ->assertJsonPath('data.0.id', $lowRatedBook->id)
            ->assertJsonPath('data.1.id', $highRatedBook->id)
            ->assertJsonPath('data.2.id', $unratedBook->id);

        // 3. sort=added_asc: [unrated, high, low]
        $this->getJson("/api/lists/{$list->id}/books?sort=added_asc")
            ->assertOk()
            ->assertJsonPath('data.0.id', $unratedBook->id)
            ->assertJsonPath('data.1.id', $highRatedBook->id)
            ->assertJsonPath('data.2.id', $lowRatedBook->id);

        // 4. sort=rating_desc: [high (4.5), low (2.5), unrated (0.0)]
        $this->getJson("/api/lists/{$list->id}/books?sort=rating_desc")
            ->assertOk()
            ->assertJsonPath('data.0.id', $highRatedBook->id)
            ->assertJsonPath('data.1.id', $lowRatedBook->id)
            ->assertJsonPath('data.2.id', $unratedBook->id);

        // 5. sort=rating_asc: los libros con ratings_count = 0 van al final -> [low (2.5), high (4.5), unrated (0 votos)]
        $this->getJson("/api/lists/{$list->id}/books?sort=rating_asc")
            ->assertOk()
            ->assertJsonPath('data.0.id', $lowRatedBook->id)
            ->assertJsonPath('data.1.id', $highRatedBook->id)
            ->assertJsonPath('data.2.id', $unratedBook->id);

        // sort inválido -> 422
        $this->getJson("/api/lists/{$list->id}/books?sort=invalid")
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['sort']);
    }

    public function test_list_books_endpoint_exposes_list_position_and_viewer_rating_without_n_plus_one(): void
    {
        $viewer = User::factory()->create();
        Sanctum::actingAs($viewer);

        $queriesForListSize = function (int $bookCount) use ($viewer): int {
            $list = $viewer->bookLists()->create(['title' => "Lista {$bookCount}"]);
            $books = Book::factory()->count($bookCount)->create();

            foreach ($books as $index => $book) {
                DB::table('book_list_items')->insert([
                    'book_list_id' => $list->id,
                    'book_id' => $book->id,
                    'position' => $index + 1,
                    'created_at' => now(),
                ]);

                $entry = LibraryEntry::factory()->create([
                    'user_id' => $viewer->id,
                    'book_id' => $book->id,
                    'status' => ReadingStatus::Read,
                    'read_count' => 1,
                ]);
                $entry->forceFill(['rating' => 4.0])->save();
            }

            DB::flushQueryLog();
            DB::enableQueryLog();
            $this->getJson("/api/lists/{$list->id}/books?per_page=50")
                ->assertOk()
                ->assertJsonPath('data.0.list_position', 1)
                ->assertJsonPath('data.0.viewer_rating', 4);
            $count = count(DB::getQueryLog());
            DB::disableQueryLog();

            return $count;
        };

        $this->assertSame($queriesForListSize(2), $queriesForListSize(6));
    }
}
