<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReadListController extends Controller
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
            $user->read_books()->attach($book);
        } catch (Exception $e) {
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
}
