<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Book;
use App\Models\Review;

class ReviewObserver
{
    /**
     * Handle the Review "saved" event.
     */
    public function saved(Review $review): void
    {
        $this->recalculateForBook($review->book);

        if ($review->wasChanged('book_id')) {
            $originalBookId = $review->getOriginal('book_id');

            if ($originalBookId !== null) {
                $this->recalculateForBook(Book::find($originalBookId));
            }
        }
    }

    /**
     * Handle the Review "deleted" event.
     */
    public function deleted(Review $review): void
    {
        $this->recalculateForBook($review->book);
    }

    /**
     * Recalculate and persist avg_rating and ratings_count for the given book.
     */
    private function recalculateForBook(?Book $book): void
    {
        if ($book === null) {
            return;
        }

        $stats = Review::query()
            ->where('book_id', $book->id)
            ->selectRaw('COUNT(*) as ratings_count, COALESCE(AVG(rating), 0) as avg_rating')
            ->first();

        $book->update([
            'ratings_count' => (int) ($stats?->ratings_count ?? 0),
            'avg_rating' => round((float) ($stats?->avg_rating ?? 0.0), 2),
        ]);
    }
}
