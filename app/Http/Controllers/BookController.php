<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\CollectBook;
use App\Models\ReadBook;
use App\Models\WatchBook;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BookController extends Controller
{
    public function index(): JsonResponse
    {
        try {
            $books = Book::all();
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error getting books',
                'error' => $e->getMessage()
            ], 500);
        }
        return response()->json([
            'success' => true,
            'message' => 'All books',
            'data' => $books
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string',
            'description' => 'required|string',
            'image_path' => 'required|string|nullable'
        ]);
        try {
            $book = Book::create($request->all());
            if ($request->has('authors')) {
                $book->authors()->attach($request->authors);
            }
            if ($request->has('categories')) {
                $book->categories()->attach($request->categories);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error creating book',
                'error' => $e->getMessage()
            ], 500);
        }
        return response()->json([
            'success' => true,
            'message' => 'Book created',
            'data' => $book
        ]);
    }

    public function show(string $id): JsonResponse
    {
        $book = Book::with(['authors:id,name', 'categories:id,name'])->find($id);
        if ($book === null) {
            return response()->json([
                'success' => false,
                'message' => 'Book not found'
            ], 404);
        }
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
        if (CollectBook::where('user_id', auth()->id())->where('book_id', $id)->exists()) {
            $book->in_collectionlist = true;
        } else {
            $book->in_collectionlist = false;
        }
        return response()->json([
            'success' => true,
            'message' => 'Book found',
            'data' => $book
        ]);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $book = Book::find($id);
        if ($book === null) {
            return response()->json([
                'success' => false,
                'message' => 'Book not found'
            ], 404);
        }
        $request->validate([
            'name' => 'required|string',
            'description' => 'required|string',
            'image_path' => 'required|string|nullable'
        ]);
        try {
            $book->update($request->all());
            if ($request->has('authors')) {
                $book->authors()->sync($request->authors);
            }
            if ($request->has('categories')) {
                $book->categories()->sync($request->categories);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating book',
                'error' => $e->getMessage()
            ], 500);
        }
        return response()->json([
            'success' => true,
            'message' => 'Book updated',
            'data' => $book
        ]);
    }

    public function destroy(string $id): JsonResponse
    {
        $book = Book::find($id);
        if ($book === null) {
            return response()->json([
                'success' => false,
                'message' => 'Book not found'
            ], 404);
        }
        try {
            $book->delete();
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting book',
                'error' => $e->getMessage()
            ], 500);
        }
        return response()->json([
            'success' => true,
            'message' => 'Book deleted'
        ]);
    }
}
