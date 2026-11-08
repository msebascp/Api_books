<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

class FollowController extends Controller
{
    public function followers(Request $request, string $user): AnonymousResourceCollection
    {
        $targetUser = $this->resolveUser($user);

        $followers = $targetUser->followers()
            ->withCount(['libraryEntries', 'reviews', 'followers', 'following'])
            ->paginate($request->integer('per_page', 20));

        return UserResource::collection($followers);
    }

    public function following(Request $request, string $user): AnonymousResourceCollection
    {
        $targetUser = $this->resolveUser($user);

        $following = $targetUser->following()
            ->withCount(['libraryEntries', 'reviews', 'followers', 'following'])
            ->paginate($request->integer('per_page', 20));

        return UserResource::collection($following);
    }

    public function toggleFollow(Request $request, string $user): JsonResponse
    {
        /** @var User $me */
        $me = $request->user();
        $targetUser = $this->resolveUser($user);

        if ($me->id === $targetUser->id) {
            return response()->json([
                'message' => 'No puedes seguirte a ti mismo.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $changes = $me->following()->toggle($targetUser->id);
        $following = count($changes['attached']) > 0;

        $targetUser->loadCount(['libraryEntries', 'reviews', 'followers', 'following']);

        return (new UserResource($targetUser))
            ->additional([
                'following' => $following,
                'message' => $following
                    ? 'Ahora sigues a este usuario.'
                    : 'Has dejado de seguir a este usuario.',
            ])
            ->response();
    }

    private function resolveUser(string $identifier): User
    {
        return User::query()
            ->where('username', $identifier)
            ->when(
                ctype_digit($identifier),
                fn (Builder $query): Builder => $query->orWhere('id', (int) $identifier)
            )
            ->firstOrFail();
    }
}
