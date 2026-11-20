<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\SyncBookReactionsRequest;
use App\Http\Resources\BookResource;
use App\Models\Book;
use App\Models\BookReaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class BookReactionController extends Controller
{
    /**
     * Sustituye de forma atómica e idempotente el conjunto de reacciones del
     * usuario autenticado sobre el libro (máx. 3). Un array vacío las elimina.
     */
    public function sync(SyncBookReactionsRequest $request, Book $book): BookResource
    {
        /** @var User $user */
        $user = $request->user();
        $reactions = $request->reactions();

        // Reintenta hasta 3 veces ante deadlocks entre peticiones concurrentes
        // del mismo usuario (DELETE + INSERT sobre el mismo rango del PK).
        DB::transaction(function () use ($user, $book, $reactions): void {
            BookReaction::query()
                ->where('user_id', $user->id)
                ->where('book_id', $book->id)
                ->delete();

            if ($reactions === []) {
                return;
            }

            $now = now();
            $rows = [];

            foreach ($reactions as $reaction) {
                $rows[] = [
                    'user_id' => $user->id,
                    'book_id' => $book->id,
                    'reaction' => $reaction->value,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            // Un único INSERT multi-fila en lugar de N consultas.
            BookReaction::query()->insert($rows);
        }, attempts: 3);

        $book->load([
            'authors',
            'genres',
            ...Book::reactionEagerLoads($user),
        ])->loadCount(['libraryEntries', 'reviews']);

        return (new BookResource($book))->additional([
            'message' => 'Reacciones actualizadas correctamente.',
        ]);
    }
}
