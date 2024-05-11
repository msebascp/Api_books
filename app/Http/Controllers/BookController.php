<?php

namespace App\Http\Controllers;

use App\Models\Book;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BookController extends Controller
{
    public function index(): JsonResponse
    {
        $user = auth()->user();
        try {
            $books = Book::with(['authors:id,name', 'categories:id,name'])->get();
            // Añadir el campo is_read a cada libro

            $books->each(function ($book) use ($user) {
                $readListUserBook = $book->readListUserBooks->firstWhere('user_id', $user->id);
                $book->is_read = $readListUserBook !== null;
                $book->is_like = $readListUserBook ? $readListUserBook->is_like : false;
            });
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
