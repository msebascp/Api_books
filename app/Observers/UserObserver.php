<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\User;

class UserObserver
{
    /**
     * IDs de libros puntuados por el usuario recopilados antes del borrado en cascada.
     *
     * @var array<int, list<int>>
     */
    private static array $affectedBookIdsByUser = [];

    /**
     * Handle the User "deleting" event.
     */
    public function deleting(User $user): void
    {
        $userId = (int) $user->getKey();

        self::$affectedBookIdsByUser[$userId] = $user->libraryEntries()
            ->whereNotNull('rating')
            ->pluck('book_id')
            ->map(static fn (mixed $bookId): int => (int) $bookId)
            ->unique()
            ->values()
            ->all();

        // Revoca tokens de Sanctum (relación polimórfica sin FK física).
        // Spatie Permission limpia model_has_roles y model_has_permissions en sus traits.
        $user->tokens()->delete();
    }

    /**
     * Handle the User "deleted" event.
     */
    public function deleted(User $user): void
    {
        $userId = (int) $user->getKey();
        $bookIds = self::$affectedBookIdsByUser[$userId] ?? [];
        unset(self::$affectedBookIdsByUser[$userId]);

        foreach ($bookIds as $bookId) {
            LibraryEntryObserver::recalculateBookRating($bookId);
        }
    }
}
