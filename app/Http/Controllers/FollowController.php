<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class FollowController extends Controller
{
    public function index_followers(string $user_id)
    {
        try {
            $user = User::find($user_id);
            $followers = $user->followers;
        } catch (\Exception $e) {
            return response()->json([
                "success" => false,
                "message" => "User not found"
            ]);
        }
        return response()->json([
            "success" => true,
            "message" => "Followers list",
            "data" => $followers
        ]);
    }

    public function index_following(string $user_id)
    {
        try {
            $user = User::find($user_id);
            $following = $user->following;
        } catch (\Exception $e) {
            return response()->json([
                "success" => false,
                "message" => "User not found"
            ]);
        }
        return response()->json([
            "success" => true,
            "message" => "Following list",
            "data" => $following
        ]);
    }

    public function store(string $user_id)
    {
        try {
            $me = auth()->user();
            $me->following()->attach($user_id);
        } catch (\Exception $e) {
            return response()->json([
                "success" => false,
                "message" => "Already followed"
            ]);
        }
        return response()->json([
            "success" => true,
            "message" => "Followed successfully"
        ]);
    }

    public function destroy(string $user_id)
    {
        try {
            $me = auth()->user();
            $me->following()->detach($user_id);
        } catch (\Exception $e) {
            return response()->json([
                "success" => false,
                "message" => "Not followed yet"
            ]);
        }
        return response()->json([
            "success" => true,
            "message" => "Unfollowed successfully"
        ]);
    }
}
