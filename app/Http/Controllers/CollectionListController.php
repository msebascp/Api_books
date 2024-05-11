<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\User;
use Illuminate\Http\Request;

class CollectionListController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|integer',
            'book_id' => 'required|integer'
        ]);

        try {
            $user = User::findOrFail($request->user_id);
            $book = Book::findOrFail($request->book_id);
            $user->collection_books()->attach($book);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error adding book to user collection',
                'error' => $e->getMessage()
            ], 500);
        }
        echo $user->collection_books;
        return response()->json([
            'success' => true,
            'message' => 'Book added to user collection'
        ]);
    }
}
