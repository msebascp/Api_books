<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Book;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Book
 */
class BookResource extends JsonResource
{
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
            'library_entries_count' => $this->whenCounted('libraryEntries'),
            'reviews_count' => $this->whenCounted('reviews'),
            'authors' => AuthorResource::collection($this->whenLoaded('authors')),
            'genres' => GenreResource::collection($this->whenLoaded('genres')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
