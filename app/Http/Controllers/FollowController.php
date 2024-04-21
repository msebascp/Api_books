<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class FollowController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            "follower_id" => "required|exists:users,id",
        ]);
        try {
            $user = auth()->user();
            $user->following()->attach($request->follower_id);
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

    public function destroy(Request $request)
    {
        $request->validate([
            "follower_id" => "required|exists:users,id",
        ]);
        try {
            $user = auth()->user();
            $user->following()->detach($request->follower_id);
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
