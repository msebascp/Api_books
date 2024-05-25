<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\ReadBook;
use App\Models\User;
use App\Models\WatchBook;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CollectBookController extends Controller
{
    public function index(?string $user_id = null): JsonResponse
    {
        try {
            if ($user_id == null) {
                $user = auth()->user();
            } else {
                $user = User::findOrFail($user_id);
            }
            $collection_books = $user->collection_books;
            return response()->json([
                'success' => true,
                'message' => 'User collection list',
                'data' => $collection_books
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error getting user collection list',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function store(string $id): JsonResponse
    {
        try {
            $user = auth()->user();
            $book = Book::findOrFail($id);
            $user->collection_books()->attach($book);
            $book->load(['authors:id,name', 'categories:id,name']);
            $book->in_collectionlist = true;
            if (ReadBook::where('user_id', auth()->id())->where('book_id', $id)->exists()) {
                $book->is_read = true;
                $is_like = ReadBook::where('user_id', auth()->id())->where('book_id', $id)->value('is_like');
                $book->is_like = boolval($is_like);
            } else {
                $book->is_read = false;
                $book->is_like = false;
            }
            if (WatchBook::where('user_id', auth()->id())->where('book_id', $id)->exists()) {
                $book->in_watchlist = true;
            } else {
                $book->in_watchlist = false;
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error adding book to user collection list',
                'error' => $e->getMessage()
            ], 500);
        }
        return response()->json([
            'success' => true,
            'message' => 'Book added to user collection list',
            'data' => $book
        ]);
    }

    public function destroy(string $id): JsonResponse
    {
        try {
            $user = auth()->user();
            $book = Book::findOrFail($id);
            $user->collection_books()->detach($book);
            $book->load(['authors:id,name', 'categories:id,name']);
            $book->in_collectionlist = false;
            if (ReadBook::where('user_id', auth()->id())->where('book_id', $id)->exists()) {
                $book->is_read = true;
                $is_like = ReadBook::where('user_id', auth()->id())->where('book_id', $id)->value('is_like');
                $book->is_like = boolval($is_like);
            } else {
                $book->is_read = false;
                $book->is_like = false;
            }
            if (WatchBook::where('user_id', auth()->id())->where('book_id', $id)->exists()) {
                $book->in_watchlist = false;
                $user->watch_books()->detach($book);
            } else {
                $book->in_watchlist = false;
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error removing book from user collection list',
                'error' => $e->getMessage()
            ], 500);
        }
        return response()->json([
            'success' => true,
            'message' => 'Book removed from user collection list',
            'data' => $book
        ]);
    }
}
