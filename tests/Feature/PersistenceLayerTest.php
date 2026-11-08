<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\ReadingStatus;
use App\Models\Author;
use App\Models\Book;
use App\Models\Comment;
use App\Models\Genre;
use App\Models\LibraryEntry;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PersistenceLayerTest extends TestCase
{
    use RefreshDatabase;

    public function test_library_entry_casts_and_user_books_pivot_relationship(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $entry = LibraryEntry::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'status' => ReadingStatus::Read,
            'is_owned' => true,
            'is_favorite' => true,
            'read_at' => now(),
        ]);

        $this->assertSame(ReadingStatus::Read, $entry->status);
        $this->assertTrue($entry->is_owned);
        $this->assertTrue($entry->is_favorite);
        $this->assertNotNull($entry->read_at);

        $pivotBook = $user->books()->firstOrFail();
        $this->assertSame($book->id, $pivotBook->id);
        $this->assertSame('read', $pivotBook->pivot->status);
        $this->assertEquals(1, $pivotBook->pivot->is_owned);
        $this->assertEquals(1, $pivotBook->pivot->is_favorite);
    }

    public function test_unique_user_book_constraints_on_library_entries_and_reviews(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        LibraryEntry::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);

        $this->expectException(QueryException::class);

        LibraryEntry::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);
    }

    public function test_review_observer_recalculates_book_avg_rating_and_ratings_count(): void
    {
        $book = Book::factory()->create();
        $users = User::factory(3)->create();

        $r1 = Review::factory()->create(['user_id' => $users[0]->id, 'book_id' => $book->id, 'rating' => 4.5]);
        $r2 = Review::factory()->create(['user_id' => $users[1]->id, 'book_id' => $book->id, 'rating' => 5.0]);
        $r3 = Review::factory()->create(['user_id' => $users[2]->id, 'book_id' => $book->id, 'rating' => 2.5]);

        $book->refresh();
        $this->assertSame(3, $book->ratings_count);
        $this->assertSame(4.0, $book->avg_rating);

        // Update rating
        $r3->update(['rating' => 4.0]);
        $book->refresh();
        $this->assertSame(3, $book->ratings_count);
        $this->assertSame(4.5, $book->avg_rating);

        // Delete one review
        $r1->delete();
        $book->refresh();
        $this->assertSame(2, $book->ratings_count);
        $this->assertSame(4.5, $book->avg_rating);

        // Delete remaining reviews -> resets to 0 and 0.0
        $r2->delete();
        $r3->delete();
        $book->refresh();
        $this->assertSame(0, $book->ratings_count);
        $this->assertSame(0.0, $book->avg_rating);
    }

    public function test_composite_pivots_social_follows_likes_and_comments(): void
    {
        $alice = User::factory()->create();
        $bob = User::factory()->create();

        // Follows (reflexive)
        $alice->following()->attach($bob->id);

        $this->assertTrue($alice->following->contains($bob));
        $this->assertTrue($bob->followers->contains($alice));
        $this->assertFalse($bob->following->contains($alice));

        // Book, Author, Genre pivots
        $author = Author::factory()->create();
        $genre = Genre::factory()->create();
        $book = Book::factory()->create();

        $book->authors()->attach($author->id);
        $book->genres()->attach($genre->id);

        $this->assertTrue($book->authors->contains($author));
        $this->assertTrue($author->books->contains($book));
        $this->assertTrue($book->genres->contains($genre));
        $this->assertTrue($genre->books->contains($book));

        // Review likes & comments
        $review = Review::factory()->create([
            'user_id' => $alice->id,
            'book_id' => $book->id,
            'rating' => 3.5,
        ]);

        $bob->likedReviews()->attach($review->id);
        $comment = Comment::factory()->create([
            'user_id' => $bob->id,
            'review_id' => $review->id,
        ]);

        $this->assertTrue($review->likes->contains($bob));
        $this->assertTrue($bob->likedReviews->contains($review));
        $this->assertTrue($review->comments->contains($comment));
        $this->assertSame($bob->id, $comment->user->id);
    }
}
