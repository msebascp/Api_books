<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\ReadingStatus;
use App\Models\Book;
use App\Models\LibraryEntry;
use App\Models\ReadingLog;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BookRatingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_rating_unread_book_auto_creates_reading_log_and_updates_book_stats(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();
        $today = now()->toDateString();

        Sanctum::actingAs($user);

        $this->putJson("/api/books/{$book->id}/rating", ['rating' => 4.5])
            ->assertOk()
            ->assertJsonPath('data.avg_rating', 4.5)
            ->assertJsonPath('data.ratings_count', 1)
            ->assertJsonPath('data.viewer_rating', 4.5);

        $entry = LibraryEntry::query()
            ->where('user_id', $user->id)
            ->where('book_id', $book->id)
            ->firstOrFail();

        $this->assertSame(ReadingStatus::Read, $entry->status);
        $this->assertSame(1, $entry->read_count);
        $this->assertSame($today, $entry->last_read_at?->format('Y-m-d'));
        $this->assertSame(4.5, $entry->rating);

        $this->assertDatabaseHas('reading_logs', [
            'user_id' => $user->id,
            'book_id' => $book->id,
            'is_reread' => 0,
        ]);

        // Cambiar la puntuación de un libro ya leído actualiza el rating sin duplicar reading_logs
        $this->putJson("/api/books/{$book->id}/rating", ['rating' => 5.0])
            ->assertOk()
            ->assertJsonPath('data.avg_rating', 5)
            ->assertJsonPath('data.ratings_count', 1)
            ->assertJsonPath('data.viewer_rating', 5);

        $this->assertSame(
            1,
            ReadingLog::query()->where('user_id', $user->id)->where('book_id', $book->id)->count()
        );
    }

    public function test_sending_zero_or_null_rating_on_unread_book_does_not_create_entry_or_log(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();
        Sanctum::actingAs($user);

        $this->putJson("/api/books/{$book->id}/rating", ['rating' => 0])
            ->assertOk()
            ->assertJsonPath('data.avg_rating', 0)
            ->assertJsonPath('data.ratings_count', 0)
            ->assertJsonPath('data.viewer_rating', null);

        $this->putJson("/api/books/{$book->id}/rating", ['rating' => null])
            ->assertOk()
            ->assertJsonPath('data.viewer_rating', null);

        $this->assertSame(0, LibraryEntry::query()->count());
        $this->assertSame(0, ReadingLog::query()->count());
    }

    public function test_clearing_rating_with_zero_recalculates_book_stats_and_leaves_written_review_intact(): void
    {
        [$alice, $bob] = User::factory()->count(2)->create()->all();
        $book = Book::factory()->create();

        Sanctum::actingAs($alice);
        $this->putJson("/api/books/{$book->id}/rating", ['rating' => 5.0])->assertOk();
        $reviewRes = $this->postJson("/api/books/{$book->id}/reviews", [
            'content' => 'Una lectura imprescindible.',
        ])
            ->assertCreated()
            ->assertJsonPath('data.rating', 5);

        $reviewId = (int) $reviewRes->json('data.id');

        Sanctum::actingAs($bob);
        $this->putJson("/api/books/{$book->id}/rating", ['rating' => 3.0])
            ->assertOk()
            ->assertJsonPath('data.avg_rating', 4)
            ->assertJsonPath('data.ratings_count', 2);

        // Alice borra su puntuación enviando 0 -> avg pasa a 3.0 (1 voto), pero su reseña escrita sigue intacta con rating null
        Sanctum::actingAs($alice);
        $this->putJson("/api/books/{$book->id}/rating", ['rating' => 0])
            ->assertOk()
            ->assertJsonPath('data.avg_rating', 3)
            ->assertJsonPath('data.ratings_count', 1)
            ->assertJsonPath('data.viewer_rating', null);

        $this->getJson("/api/reviews/{$reviewId}")
            ->assertOk()
            ->assertJsonPath('data.content', 'Una lectura imprescindible.')
            ->assertJsonPath('data.rating', null);
    }

    public function test_validation_rejects_invalid_ratings_and_empty_or_whitespace_reviews(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();
        Sanctum::actingAs($user);

        foreach ([4.3, -0.5, 5.5] as $invalidRating) {
            $this->putJson("/api/books/{$book->id}/rating", ['rating' => $invalidRating])
                ->assertUnprocessable()
                ->assertJsonValidationErrors(['rating']);
        }

        $this->putJson("/api/books/{$book->id}/rating", [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['rating']);

        // Reseñas sin content o solo con espacios en blanco son rechazadas con 422
        foreach (['', '     ', " \n\t "] as $emptyContent) {
            $this->postJson("/api/books/{$book->id}/reviews", ['content' => $emptyContent])
                ->assertUnprocessable()
                ->assertJsonValidationErrors(['content']);
        }

        $review = Review::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'content' => 'Texto inicial válido.',
        ]);

        $this->putJson("/api/reviews/{$review->id}", ['content' => "   \t  "])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['content']);
    }

    public function test_deleting_user_recalculates_book_avg_rating_and_ratings_count_from_library_entries(): void
    {
        [$alice, $bob] = User::factory()->count(2)->create()->all();
        $book = Book::factory()->create();

        Sanctum::actingAs($alice);
        $this->putJson("/api/books/{$book->id}/rating", ['rating' => 5.0])->assertOk();

        Sanctum::actingAs($bob);
        $this->putJson("/api/books/{$book->id}/rating", ['rating' => 2.0])->assertOk();

        $book->refresh();
        $this->assertSame(2, $book->ratings_count);
        $this->assertSame(3.5, $book->avg_rating);

        $alice->delete();

        $book->refresh();
        $this->assertSame(1, $book->ratings_count);
        $this->assertSame(2.0, $book->avg_rating);
    }

    public function test_viewer_rating_in_book_resource_and_reviewer_rating_in_review_resource_avoid_n_plus_one(): void
    {
        $viewer = User::factory()->create();
        Sanctum::actingAs($viewer);

        $queriesForBooksAndReviews = function (int $count) use ($viewer): array {
            $books = Book::factory()->count($count)->create();

            foreach ($books as $book) {
                $entry = LibraryEntry::factory()->create([
                    'user_id' => $viewer->id,
                    'book_id' => $book->id,
                    'status' => ReadingStatus::Read,
                    'read_count' => 1,
                ]);
                $entry->forceFill(['rating' => 4.5])->save();

                Review::factory()->create([
                    'user_id' => $viewer->id,
                    'book_id' => $book->id,
                    'content' => 'Reseña con puntuación asociada.',
                ]);
            }

            DB::flushQueryLog();
            DB::enableQueryLog();
            $this->getJson('/api/books?per_page=50')
                ->assertOk()
                ->assertJsonPath('data.0.viewer_rating', 4.5);
            $bookQueries = count(DB::getQueryLog());

            DB::flushQueryLog();
            $this->getJson('/api/reviews?per_page=50')
                ->assertOk()
                ->assertJsonPath('data.0.rating', 4.5)
                ->assertJsonPath('data.0.book.viewer_rating', 4.5);
            $reviewQueries = count(DB::getQueryLog());
            DB::disableQueryLog();

            return [$bookQueries, $reviewQueries];
        };

        $this->assertSame($queriesForBooksAndReviews(2), $queriesForBooksAndReviews(6));
    }
}
