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
            if ($user == null) {
                return response()->json([
                    "success" => false,
                    "message" => "User not found"
                ]);
            }
            $followers = $user->followers;
            return response()->json([
                "success" => true,
                "message" => "Followers list",
                "data" => $followers
            ]);
        } catch (\Exception $e) {
            return response()->json([
                "success" => false,
                "message" => "User not found"
            ]);
        }
    }

    public function index_following(string $user_id)
    {
        try {
            $user = User::find($user_id);
            if ($user == null) {
                return response()->json([
                    "success" => false,
                    "message" => "User not found"
                ]);
            }
            $following = $user->following;
            return response()->json([
                "success" => true,
                "message" => "Following list",
                "data" => $following
            ]);
        } catch (\Exception $e) {
            return response()->json([
                "success" => false,
                "message" => "User not found"
            ]);
        }
    }

    public function store(string $user_id)
    {
        try {
            $me = auth()->user();
            $me->following()->attach($user_id);
            return response()->json([
                "success" => true,
                "message" => "Followed successfully"
            ]);
        } catch (\Exception $e) {
            return response()->json([
                "success" => false,
                "message" => "Already followed"
            ]);
        }
    }

    public function destroy(string $user_id)
    {
        try {
            $me = auth()->user();
            $me->following()->detach($user_id);
            return response()->json([
                "success" => true,
                "message" => "Unfollowed successfully"
            ]);
        } catch (\Exception $e) {
            return response()->json([
                "success" => false,
                "message" => "Not followed yet"
            ]);
        }
    }
}
