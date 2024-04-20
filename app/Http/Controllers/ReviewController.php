<?php

namespace App\Http\Controllers;

use App\Models\Review;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function index(): JsonResponse
    {
        try {
            $reviews = Review::all();
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching reviews',
                'error' => $e->getMessage()
            ], 500);
        }
        return response()->json([
            'success' => true,
            'message' => 'All reviews',
            'data' => $reviews
        ]);
    }

    public function show($id): JsonResponse
    {
        try {
            $review = Review::find($id);
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
            'user_id' => 'required',
            'book_id' => 'required',
            'review' => 'required'
        ]);

        try {
            $review = Review::create($request->all());
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
            'user_id' => 'required',
            'book_id' => 'required',
            'review' => 'required'
        ]);

        try {
            $review = Review::find($id);
            $review->update($request->all());
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
