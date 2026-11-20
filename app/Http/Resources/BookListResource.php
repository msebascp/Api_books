<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\BookList;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin BookList
 */
class BookListResource extends JsonResource
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
            'user_id' => $this->user_id,
            'title' => $this->title,
            'slug' => $this->slug,
            'description' => $this->description,
            'is_public' => (bool) $this->is_public,
            'is_ranked' => (bool) $this->is_ranked,
            'books_count' => $this->whenCounted('books'),
            'user' => new UserResource($this->whenLoaded('user')),
            'books' => BookResource::collection($this->whenLoaded('books')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
