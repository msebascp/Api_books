<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\CollectBook;
use App\Models\ReadBook;
use App\Models\Review;
use App\Models\User;
use App\Models\WatchBook;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReadBookController extends Controller
{
    public function index(?string $userId = null): JsonResponse
    {
        try {
            if ($userId == null) {
                $user = Auth::user();
            } else {
                $user = User::findOrFail($userId);
            }
            $read_books = $user->read_books;
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

    public function show(string $book_id, ?string $user_id = ""): JsonResponse
    {
        try {
            if ($user_id == null || $user_id == "") {
                $user_id = auth()->user()->id;
            }
            $read_book = ReadBook::where('user_id', $user_id)->where('book_id', $book_id)->first();
            if ($read_book == null) {
                return response()->json([
                    'success' => false,
                    'message' => 'Book not found in user read list',
                ], 404);
            }
            $read_book->load(['book', 'review.comments.user', 'user']);
            return response()->json([
                'success' => true,
                'message' => 'User read book',
                'data' => $read_book
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error getting user read book',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function store(string $id): JsonResponse
    {
        try {
            $user = Auth::user();
            $book = Book::find($id);
            if ($book == null) {
                return response()->json([
                    'success' => false,
                    'message' => 'Book not found',
                ], 404);
            }
            $user->read_books()->attach($book);
            $book->load(['authors:id,name', 'categories:id,name']);
            $book->is_read = true;
            $book->is_like = false;
            if (WatchBook::where('user_id', auth()->id())->where('book_id', $id)->exists()) {
                $book->in_watchlist = false;
                $user->watch_books()->detach($book);
            } else {
                $book->in_watchlist = false;
            }
            $book->in_collectionlist = CollectBook::where('user_id', auth()->id())->where('book_id', $id)->exists();

            return response()->json([
                'success' => true,
                'message' => 'Book added to user read list',
                'data' => $book
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error adding book to user read list',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy(string $id): JsonResponse
    {
        try {
            $user = Auth::user();
            $book = Book::find($id);
            if ($book == null) {
                return response()->json([
                    'success' => false,
                    'message' => 'Book not found',
                ], 404);
            }
            $user->read_books()->detach($book);
            if (Review::where('user_id', auth()->id())->where('book_id', $id)->exists()) {
                Review::where('user_id', auth()->id())->where('book_id', $id)->delete();
            }
            $book->load(['authors:id,name', 'categories:id,name']);
            $book->is_read = false;
            $book->is_like = false;
            $book->in_watchlist = WatchBook::where('user_id', auth()->id())->where('book_id', $id)->exists();
            $book->in_collectionlist = CollectBook::where('user_id', auth()->id())->where('book_id', $id)->exists();

            return response()->json([
                'success' => true,
                'message' => 'Book removed from user read list',
                'data' => $book
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error removing book from user read list',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function like(string $id): JsonResponse
    {
        try {
            ReadBook::where('user_id', auth()->id())->where('book_id', $id)->update(['is_like' => true]);
            $book = Book::findOrFail($id);
            $book->load(['authors:id,name', 'categories:id,name']);
            $book->is_read = true;
            $book->is_like = true;
            $book->in_watchlist = WatchBook::where('user_id', auth()->id())->where('book_id', $id)->exists();
            $book->in_collectionlist = CollectBook::where('user_id', auth()->id())->where('book_id', $id)->exists();

            return response()->json([
                'success' => true,
                'message' => 'Book liked',
                'data' => $book
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error liking book',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function unlike(string $id): JsonResponse
    {
        try {
            ReadBook::where('user_id', auth()->id())->where('book_id', $id)->update(['is_like' => false]);
            $book = Book::find($id);
            if ($book == null) {
                return response()->json([
                    'success' => false,
                    'message' => 'Book not found',
                ], 404);
            }
            $book->load(['authors:id,name', 'categories:id,name']);
            $book->is_read = true;
            $book->is_like = false;
            $book->in_watchlist = WatchBook::where('user_id', auth()->id())->where('book_id', $id)->exists();
            $book->in_collectionlist = CollectBook::where('user_id', auth()->id())->where('book_id', $id)->exists();

            return response()->json([
                'success' => true,
                'message' => 'Book unliked',
                'data' => $book
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error unliking book',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function likeBooks(): JsonResponse
    {
        try {
            $user = Auth::user();
            $like_books = $user->like_books;
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
