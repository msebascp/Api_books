<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\User;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WatchListUserBookController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'user_id' => 'required|integer',
            'book_id' => 'required|integer'
        ]);

        try {
            $user = User::findOrFail($request->user_id);
            $book = Book::findOrFail($request->book_id);
            $user->watch_books()->attach($book);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error adding book to user watch list',
                'error' => $e->getMessage()
            ], 500);
        }
        echo $user->watch_books;
        return response()->json([
            'success' => true,
            'message' => 'Book added to user watch list'
        ]);
    }
}
