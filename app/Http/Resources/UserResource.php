<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin User
 */
class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $viewer = $request->user('sanctum');
        $isOwner = ($viewer instanceof User && $viewer->id === $this->id)
            || $request->routeIs('auth.register', 'auth.login');

        $followedByViewer = false;

        if ($viewer instanceof User && ! $isOwner) {
            if (! array_key_exists('followed_by_viewer', $this->resource->getAttributes())) {
                $this->resource->loadExists(User::viewerFollowExistsConstraint($viewer));
            }

            $followedByViewer = (bool) $this->resource->getAttribute('followed_by_viewer');
        }

        return [
            'id' => $this->id,
            'name' => $this->name,
            'username' => $this->username,
            'email' => $this->when($isOwner, $this->email),
            'avatar_path' => $this->avatar_path,
            'bio' => $this->bio,
            'followed_by_viewer' => $followedByViewer,
            'library_entries_count' => $this->whenCounted('libraryEntries'),
            'reviews_count' => $this->whenCounted('reviews'),
            'followers_count' => $this->whenCounted('followers'),
            'following_count' => $this->whenCounted('following'),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
