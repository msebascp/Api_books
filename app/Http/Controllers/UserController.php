<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function show(Request $request, string $username): UserResource
    {
        $viewer = $this->viewer($request);

        $query = User::query()
            ->withCount(['libraryEntries', 'reviews', 'followers', 'following']);

        if ($viewer !== null) {
            $query->withExists(User::viewerFollowExistsConstraint($viewer));
        }

        $user = $query
            ->where(function (Builder $q) use ($username): void {
                $q->where('username', $username)
                    ->when(
                        ctype_digit($username),
                        fn (Builder $sub): Builder => $sub->orWhere('id', (int) $username)
                    );
            })
            ->firstOrFail();

        return new UserResource($user);
    }
}
