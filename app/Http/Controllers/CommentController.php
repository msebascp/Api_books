<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Review;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    public function index_review(string $review_id): JsonResponse
    {
        try {
            $comments = Review::findOrFail($review_id)->comments;
            $comments->load('user');
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Comments not found',
                'error' => $e->getMessage()
            ], 404);
        }
        return response()->json([
            'success' => true,
            'message' => 'Comments',
            'data' => $comments
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'review_id' => 'required|integer',
            'comment' => 'required|string'
        ]);
        try {
            $comment = new Comment();
            $comment->user_id = auth()->user()->id;
            $comment->review_id = $request->get('review_id');
            $comment->content = $request->get('comment');
            $comment->load('user');
            return response()->json([
                'success' => true,
                'message' => 'Comment created',
                'data' => $comment
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error creating comment',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show(string $id): JsonResponse
    {
        $comment = Comment::with(['user'])->find($id);
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
            $comment->update([
                'user_id' => auth()->user()->id,
                'book_id' => $request->get('book_id'),
                'content' => $request->get('comment')
            ]);
            return response()->json([
                'success' => true,
                'message' => 'Comment updated',
                'data' => $comment
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating comment',
                'error' => $e->getMessage()
            ], 500);
        }
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
            return response()->json([
                'success' => true,
                'message' => 'Comment deleted'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting comment',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
