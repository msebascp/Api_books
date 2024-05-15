<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\ReadList;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReadListController extends Controller
{
    public function index(): JsonResponse
    {
        try {
            $user = Auth::user();
            $read_books = $user->read_books->load(['authors:id,name', 'categories:id,name']);
            return response()->json([
                'success' => true,
                'message' => 'User read list',
                'data' => $read_books
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error getting user read list',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function store(string $id): JsonResponse
    {
        try {
            $user = Auth::user();
            $book = Book::findOrFail($id);
            $user->read_books()->attach($book);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error adding book to user read list',
                'error' => $e->getMessage()
            ], 500);
        }
        echo $user->read_books;
        return response()->json([
            'success' => true,
            'message' => 'Book added to user read list'
        ]);
    }

    public function destroy(string $id): JsonResponse
    {
        try {
            $user = Auth::user();
            $book = Book::findOrFail($id);
            $user->read_books()->detach($book);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error removing book from user read list',
                'error' => $e->getMessage()
            ], 500);
        }
        return response()->json([
            'success' => true,
            'message' => 'Book removed from user read list'
        ]);
    }

    public function like(string $id): JsonResponse
    {
        try {
            $user = Auth::user();
            $book = Book::findOrFail($id);
            $user->read_books()->updateExistingPivot($book, ['is_like' => true]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error liking book',
                'error' => $e->getMessage()
            ], 500);
        }
        return response()->json([
            'success' => true,
            'message' => 'Book liked',
            'data' => $book
        ]);
    }

    public function unlike(string $id): JsonResponse
    {
        try {
            $user = Auth::user();
            $book = Book::findOrFail($id);
            $user->read_books()->updateExistingPivot($book, ['is_like' => false]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error unliking book',
                'error' => $e->getMessage()
            ], 500);
        }
        return response()->json([
            'success' => true,
            'message' => 'Book unliked',
            'data' => $book
        ]);
    }

    public function likeBooks(): JsonResponse
    {
        try {
            $user = Auth::user();
            $like_books = $user->like_books->load(['authors:id,name', 'categories:id,name']);
            return response()->json([
                'success' => true,
                'message' => 'User like list',
                'data' => $like_books
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error getting user like list',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
