<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Enums\ReadingStatus;
use App\Models\LibraryEntry;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin LibraryEntry
 */
class LibraryEntryResource extends JsonResource
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
            'book_id' => $this->book_id,
            'status' => $this->status instanceof ReadingStatus ? $this->status->value : $this->status,
            'is_owned' => (bool) $this->is_owned,
            'is_favorite' => (bool) $this->is_favorite,
            'read_at' => $this->read_at?->toISOString(),
            'book' => new BookResource($this->whenLoaded('book')),
            'user' => new UserResource($this->whenLoaded('user')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
