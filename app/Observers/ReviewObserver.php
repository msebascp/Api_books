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
        self::recalculateForBookId((int) $review->book_id);

        if ($review->wasChanged('book_id')) {
            $originalBookId = $review->getOriginal('book_id');

            if ($originalBookId !== null) {
                self::recalculateForBookId((int) $originalBookId);
            }
        }
    }

    /**
     * Handle the Review "deleted" event.
     */
    public function deleted(Review $review): void
    {
        self::recalculateForBookId((int) $review->book_id);
    }

    /**
     * Recalculate and persist avg_rating and ratings_count for the given book ID
     * without relying on mass assignment ($fillable).
     */
    public static function recalculateForBookId(int $bookId): void
    {
        if ($bookId <= 0) {
            return;
        }

        $stats = Review::query()
            ->where('book_id', $bookId)
            ->selectRaw('COUNT(*) as ratings_count, COALESCE(AVG(rating), 0) as avg_rating')
            ->first();

        Book::withTrashed()->whereKey($bookId)->update([
            'ratings_count' => (int) ($stats?->ratings_count ?? 0),
            'avg_rating' => round((float) ($stats?->avg_rating ?? 0.0), 2),
        ]);
    }
}
