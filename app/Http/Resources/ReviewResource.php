<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Review;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Review
 */
class ReviewResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $viewer = $request->user('sanctum');
        $likedByViewer = false;

        if ($viewer instanceof User) {
            if (! array_key_exists('liked_by_viewer', $this->resource->getAttributes())) {
                $this->resource->loadExists(Review::viewerLikeExistsConstraint($viewer));
            }

            $likedByViewer = (bool) $this->resource->getAttribute('liked_by_viewer');
        }

        if (! array_key_exists('reviewer_rating', $this->resource->getAttributes())) {
            $this->resource->loadReviewerRating();
        }

        $rawRating = $this->resource->getAttribute('reviewer_rating');

        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'book_id' => $this->book_id,
            'rating' => $rawRating !== null ? (float) $rawRating : null,
            'content' => $this->content,
            'has_spoilers' => (bool) $this->has_spoilers,
            'liked_by_viewer' => $likedByViewer,
            'likes_count' => $this->whenCounted('likes'),
            'comments_count' => $this->whenCounted('comments'),
            'user' => new UserResource($this->whenLoaded('user')),
            'book' => new BookResource($this->whenLoaded('book')),
            'comments' => CommentResource::collection($this->whenLoaded('comments')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
