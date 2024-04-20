<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    public function index(): JsonResponse
    {
        try {
            $comments = Comment::with(['user:id,name', 'book:id,name'])->get();
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error getting comments',
                'error' => $e->getMessage()
            ], 500);
        }
        return response()->json([
            'success' => true,
            'message' => 'All comments',
            'data' => $comments
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'user_id' => 'required|integer',
            'book_id' => 'required|integer',
            'comment' => 'required|string'
        ]);
        try {
            $comment = Comment::create($request->all());
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error creating comment',
                'error' => $e->getMessage()
            ], 500);
        }
        return response()->json([
            'success' => true,
            'message' => 'Comment created',
            'data' => $comment
        ]);
    }

    public function show(string $id): JsonResponse
    {
        $comment = Comment::with(['user:id,name', 'book:id,name'])->find($id);
        if ($comment === null) {
            return response()->json([
                'success' => false,
                'message' => 'Comment not found'
            ], 404);
        }
        return response()->json([
            'success' => true,
            'message' => 'Comment',
            'data' => $comment
        ]);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'user_id' => 'required|integer',
            'book_id' => 'required|integer',
            'comment' => 'required|string'
        ]);
        $comment = Comment::find($id);
        if ($comment === null) {
            return response()->json([
                'success' => false,
                'message' => 'Comment not found'
            ], 404);
        }
        try {
            $comment->update($request->all());
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating comment',
                'error' => $e->getMessage()
            ], 500);
        }
        return response()->json([
            'success' => true,
            'message' => 'Comment updated',
            'data' => $comment
        ]);
    }

    public function destroy(string $id): JsonResponse
    {
        $comment = Comment::find($id);
        if ($comment === null) {
            return response()->json([
                'success' => false,
                'message' => 'Comment not found'
            ], 404);
        }
        try {
            $comment->delete();
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting comment',
                'error' => $e->getMessage()
            ], 500);
        }
        return response()->json([
            'success' => true,
            'message' => 'Comment deleted'
        ]);
    }
}
