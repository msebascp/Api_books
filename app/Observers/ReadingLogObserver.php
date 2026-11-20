<?php

declare(strict_types=1);

namespace App\Observers;

use App\Enums\ReadingStatus;
use App\Models\LibraryEntry;
use App\Models\ReadingLog;

class ReadingLogObserver
{
    /**
     * Handle the ReadingLog "created" event.
     */
    public function created(ReadingLog $readingLog): void
    {
        self::recalculateForUserAndBook(
            (int) $readingLog->user_id,
            (int) $readingLog->book_id,
            createOrMarkRead: true,
        );
    }

    /**
     * Handle the ReadingLog "updated" event.
     */
    public function updated(ReadingLog $readingLog): void
    {
        self::recalculateForUserAndBook(
            (int) $readingLog->user_id,
            (int) $readingLog->book_id,
            createOrMarkRead: false,
        );

        if ($readingLog->wasChanged(['user_id', 'book_id'])) {
            $originalUserId = (int) ($readingLog->getOriginal('user_id') ?? 0);
            $originalBookId = (int) ($readingLog->getOriginal('book_id') ?? 0);

            if ($originalUserId > 0 && $originalBookId > 0) {
                self::recalculateForUserAndBook($originalUserId, $originalBookId, createOrMarkRead: false);
            }
        }
    }

    /**
     * Handle the ReadingLog "deleted" event.
     */
    public function deleted(ReadingLog $readingLog): void
    {
        self::recalculateForUserAndBook(
            (int) $readingLog->user_id,
            (int) $readingLog->book_id,
            createOrMarkRead: false,
        );
    }

    /**
     * Recalculate read_count and last_read_at on library_entries without
     * relying on mass assignment ($fillable), and never deleting the entry.
     */
    public static function recalculateForUserAndBook(
        int $userId,
        int $bookId,
        bool $createOrMarkRead = false,
    ): void {
        if ($userId <= 0 || $bookId <= 0) {
            return;
        }

        $stats = ReadingLog::query()
            ->where('user_id', $userId)
            ->where('book_id', $bookId)
            ->selectRaw('COUNT(*) as read_count, MAX(finished_at) as last_read_at')
            ->first();

        $readCount = (int) ($stats?->read_count ?? 0);
        $lastReadAt = $stats?->last_read_at;

        $entry = LibraryEntry::query()
            ->where('user_id', $userId)
            ->where('book_id', $bookId)
            ->first();

        if ($entry === null) {
            if ($createOrMarkRead && $readCount > 0) {
                $newEntry = new LibraryEntry([
                    'user_id' => $userId,
                    'book_id' => $bookId,
                    'status' => ReadingStatus::Read,
                    'is_owned' => false,
                    'is_favorite' => false,
                ]);
                $newEntry->forceFill([
                    'read_count' => $readCount,
                    'last_read_at' => $lastReadAt,
                ])->save();
            }

            return;
        }

        $updates = [
            'read_count' => $readCount,
            'last_read_at' => $lastReadAt,
        ];

        if ($createOrMarkRead && $readCount > 0) {
            $updates['status'] = ReadingStatus::Read->value;
        }

        LibraryEntry::whereKey($entry->id)->update($updates);
    }
}
