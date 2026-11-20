<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\ReadingLog;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ReadingLog
 */
class ReadingLogResource extends JsonResource
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
            'started_at' => $this->started_at?->format('Y-m-d'),
            'finished_at' => $this->finished_at?->format('Y-m-d'),
            'is_reread' => (bool) $this->is_reread,
            'book' => new BookResource($this->whenLoaded('book')),
            'user' => new UserResource($this->whenLoaded('user')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
