<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Book;
use App\Models\LibraryEntry;

class LibraryEntryObserver
{
    /**
     * Handle the LibraryEntry "saved" event.
     */
    public function saved(LibraryEntry $entry): void
    {
        $shouldRecalculate = ($entry->wasRecentlyCreated && $entry->rating !== null)
            || $entry->wasChanged('rating')
            || ($entry->wasChanged('book_id') && $entry->rating !== null);

        if ($shouldRecalculate) {
            self::recalculateBookRating((int) $entry->book_id);
        }

        if ($entry->wasChanged('book_id')) {
            $originalBookId = (int) ($entry->getOriginal('book_id') ?? 0);

            if ($originalBookId > 0) {
                self::recalculateBookRating($originalBookId);
            }
        }
    }

    /**
     * Handle the LibraryEntry "deleted" event.
     */
    public function deleted(LibraryEntry $entry): void
    {
        if ($entry->rating !== null) {
            self::recalculateBookRating((int) $entry->book_id);
        }
    }

    /**
     * Recalculate and persist avg_rating and ratings_count for the given book ID
     * from library_entries where rating is not null.
     */
    public static function recalculateBookRating(int $bookId): void
    {
        if ($bookId <= 0) {
            return;
        }

        $stats = LibraryEntry::query()
            ->where('book_id', $bookId)
            ->whereNotNull('rating')
            ->selectRaw('COUNT(rating) as ratings_count, COALESCE(AVG(rating), 0) as avg_rating')
            ->first();

        Book::withTrashed()->whereKey($bookId)->update([
            'ratings_count' => (int) ($stats?->ratings_count ?? 0),
            'avg_rating' => round((float) ($stats?->avg_rating ?? 0.0), 2),
        ]);
    }
}
