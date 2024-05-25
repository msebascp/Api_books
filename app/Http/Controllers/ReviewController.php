<?php

namespace App\Http\Controllers;

use App\Models\ReadBook;
use App\Models\Review;
use App\Models\User;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function index_user(?string $user_id = null): JsonResponse
    {
        try {
            if ($user_id == null) {
                $user = auth()->user();
            } else {
                $user = User::findOrFail($user_id);
            }
            $reviews = $user->reviews;
            //cargar relaciones de book
            $reviews->load('book');
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching reviews',
                'error' => $e->getMessage()
            ], 500);
        }
        return response()->json([
            'success' => true,
            'message' => 'Reviews of user',
            'data' => $reviews
        ]);
    }

    public function index_book($book_id): JsonResponse
    {
        try {
            $reviews = Review::where('book_id', $book_id)->get();
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching reviews',
                'error' => $e->getMessage()
            ], 500);
        }
        return response()->json([
            'success' => true,
            'message' => 'Reviews of book',
            'data' => $reviews
        ]);
    }

    public function show($id): JsonResponse
    {
        try {
            $review = Review::find($id);
            if ($review->has('comments')) {
                $review->load('comments');
            } else {
                $review->comments = [];
            }
            $review->load(['book', 'user']);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching review',
                'error' => $e->getMessage()
            ], 500);
        }
        return response()->json([
            'success' => true,
            'message' => 'Review',
            'data' => $review
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'book_id' => 'required',
            'review' => 'required'
        ]);
        $user = auth()->user();
        $read_book_id = ReadBook::where('user_id', $user->id)->where('book_id', $request->book_id)->first()->id;
        try {
            $review = Review::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'book_id' => $request->book_id
                ],
                [
                    'user_id' => $user->id,
                    'book_id' => $request->book_id,
                    'read_book_id' => $read_book_id,
                    'content' => $request->review
                ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error creating review',
                'error' => $e->getMessage()
            ], 500);
        }
        return response()->json([
            'success' => true,
            'message' => 'Review created',
            'data' => $review
        ]);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $request->validate([
            'book_id' => 'required',
            'review' => 'required'
        ]);
        $user = auth()->user();
        try {
            $review = Review::find($id);
            $review->update([
                'user_id' => $user->id,
                'book_id' => $request->book_id,
                'content' => $request->review
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating review',
                'error' => $e->getMessage()
            ], 500);
        }
        return response()->json([
            'success' => true,
            'message' => 'Review updated',
            'data' => $review
        ]);
    }

    public function destroy($id): JsonResponse
    {
        try {
            $review = Review::find($id);
            $review->delete();
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting review',
                'error' => $e->getMessage()
            ], 500);
        }
        return response()->json([
            'success' => true,
            'message' => 'Review deleted',
            'data' => $review
        ]);
    }
}
