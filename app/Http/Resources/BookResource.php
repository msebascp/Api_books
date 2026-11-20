<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Book;
use App\Models\BookReaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

/**
 * @mixin Book
 */
class BookResource extends JsonResource
{
    /**
     * Número de reacciones mostradas en el top global del libro.
     */
    private const int TOP_REACTIONS_LIMIT = 3;

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'cover_path' => $this->cover_path,
            'isbn' => $this->isbn,
            'publication_date' => $this->publication_date?->format('Y-m-d'),
            'pages' => $this->pages,
            'avg_rating' => (float) $this->avg_rating,
            'ratings_count' => (int) $this->ratings_count,
            'viewer_rating' => $this->viewerRatingValue(),
            'list_position' => $this->whenPivotLoaded('book_list_items', fn (): int => (int) $this->pivot->position),
            'is_archived' => $this->trashed(),
            'library_entries_count' => $this->whenCounted('libraryEntries'),
            'reviews_count' => $this->whenCounted('reviews'),
            'reactions' => $this->reactionsPayload(),
            'authors' => AuthorResource::collection($this->whenLoaded('authors')),
            'genres' => GenreResource::collection($this->whenLoaded('genres')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }

    /**
     * Bloque de reacciones estilo Trakt TV.
     *
     * Usa las relaciones precargadas con Book::reactionEagerLoads(); si no lo
     * están (p. ej. un libro anidado en otro recurso), las carga bajo demanda
     * como red de seguridad: 1 consulta agregada + 1 si hay usuario.
     *
     * @return array{total: int, top_reactions: list<array{type: string, emoji: string, count: int}>, user_reactions: list<string>}
     */
    private function reactionsPayload(): array
    {
        /** @var Book $book */
        $book = $this->resource;

        $book->loadMissing('reactionCounts');
        $counts = $book->reactionCounts;

        return [
            'total' => (int) $counts->sum('total'),
            'top_reactions' => $counts
                ->take(self::TOP_REACTIONS_LIMIT)
                ->map(static fn (BookReaction $row): array => [
                    'type' => $row->reaction->value,
                    'emoji' => $row->reaction->emoji(),
                    'count' => (int) $row->getAttribute('total'),
                ])
                ->values()
                ->all(),
            'user_reactions' => $this->viewerReactionValues($book),
        ];
    }

    /**
     * @return list<string>
     */
    private function viewerReactionValues(Book $book): array
    {
        // Guard explícito de Sanctum: en rutas públicas (GET /books) no se
        // ejecuta el middleware auth:sanctum, así que Auth::check() sobre el
        // guard por defecto ('web') no detectaría el Bearer token.
        $viewer = Auth::guard('sanctum')->user();

        if (! $viewer instanceof User) {
            return [];
        }

        if (! $book->relationLoaded('viewerReactions')) {
            $book->load(['viewerReactions' => Book::viewerReactionsConstraint($viewer)]);
        }

        return $book->viewerReactions
            ->map(static fn (BookReaction $reaction): string => $reaction->reaction->value)
            ->values()
            ->all();
    }

    private function viewerRatingValue(): ?float
    {
        /** @var Book $book */
        $book = $this->resource;

        $viewer = Auth::guard('sanctum')->user();

        if (! $viewer instanceof User) {
            return null;
        }

        if (! $book->relationLoaded('viewerLibraryEntry')) {
            $book->load(['viewerLibraryEntry' => Book::viewerLibraryEntryConstraint($viewer)]);
        }

        $rating = $book->viewerLibraryEntry?->rating;

        return $rating !== null ? (float) $rating : null;
    }
}
