<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\RateBookRequest;
use App\Http\Resources\BookResource;
use App\Models\Book;
use App\Models\LibraryEntry;
use App\Models\ReadingLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class BookRatingController extends Controller
{
    /**
     * Establece, actualiza o elimina (si se envía 0 o null) la puntuación del
     * usuario autenticado sobre un libro. Si puntúa (0.5–5.0) un libro no leído,
     * registra automáticamente una lectura con fecha de hoy.
     */
    public function upsert(RateBookRequest $request, Book $book): BookResource
    {
        /** @var User $user */
        $user = $request->user();
        $rating = $request->ratingValue();

        DB::transaction(function () use ($user, $book, $rating): void {
            $entry = LibraryEntry::query()
                ->where('user_id', $user->id)
                ->where('book_id', $book->id)
                ->first();

            if ($rating === null) {
                if ($entry !== null && $entry->rating !== null) {
                    $entry->forceFill(['rating' => null])->save();
                }

                return;
            }

            if ($entry === null || $entry->read_count === 0) {
                ReadingLog::create([
                    'user_id' => $user->id,
                    'book_id' => $book->id,
                    'finished_at' => now()->toDateString(),
                    'is_reread' => false,
                ]);

                $entry = LibraryEntry::query()
                    ->where('user_id', $user->id)
                    ->where('book_id', $book->id)
                    ->firstOrFail();
            }

            $entry->forceFill(['rating' => $rating])->save();
        });

        $book->refresh();
        $book->load([
            'authors',
            'genres',
            ...Book::reactionEagerLoads($user),
        ])->loadCount(['libraryEntries', 'reviews']);

        return (new BookResource($book))->additional([
            'message' => $rating !== null
                ? 'Puntuación actualizada correctamente.'
                : 'Puntuación eliminada correctamente.',
        ]);
    }
}
