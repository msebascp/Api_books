<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class UserController extends Controller
{
    public function show(string $username): UserResource
    {
        $user = User::query()
            ->withCount(['libraryEntries', 'reviews', 'followers', 'following'])
            ->where('username', $username)
            ->when(
                ctype_digit($username),
                fn (Builder $query): Builder => $query->orWhere('id', (int) $username)
            )
            ->firstOrFail();

        return new UserResource($user);
    }
}
