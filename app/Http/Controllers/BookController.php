<?php

namespace App\Http\Controllers;

use App\Mail\ForgotPassword;
use App\Models\Book;
use App\Models\CollectBook;
use App\Models\ReadBook;
use App\Models\WatchBook;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

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
            'image_path' => 'nullable|string'
        ]);
        try {
            $book = new Book();
            $book->name = $request->name;
            $book->description = $request->description;
            $book->image_path = $request->image_path;
            $book->save();
            if ($request->has('authors')) {
                $authors = $request->authors;
                unset($request['authors']);
                $book->authors()->attach($authors);
            }
            if ($request->has('categories')) {
                $categories = $request->categories;
                unset($request['categories']);
                $book->categories()->attach($categories);
            }
            $book->save();
            return response()->json([
                'success' => true,
                'message' => 'Book created',
                'data' => $book
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error creating book',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Devuelve la información básica de un libro,
     * incluyendo los autores y categorías asociados a él.
     * Además, se indica si el usuario autenticado ha leído el libro,
     * si lo tiene en su watchlist y si lo tiene en su colección.
     * @param string $id
     * @return JsonResponse
     */
    public function show(string $id): JsonResponse
    {
        $book = Book::with(['authors:id,name', 'categories:id,name', 'reviews.user'])->find($id);
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
        $book->in_watchlist = WatchBook::where('user_id', auth()->id())->where('book_id', $id)->exists();
        $book->in_collectionlist = CollectBook::where('user_id', auth()->id())->where('book_id', $id)->exists();
        return response()->json([
            'success' => true,
            'message' => 'Book found',
            'data' => $book
        ]);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'name' => 'required|string',
            'description' => 'required|string',
            'image_path' => 'nullable|string'
        ]);
        $book = Book::find($id);
        if ($book === null) {
            return response()->json([
                'success' => false,
                'message' => 'Book not found'
            ], 404);
        }
        try {
            $book->update($request->all());
            if ($request->has('authors')) {
                $book->authors()->sync($request->authors);
            }
            if ($request->has('categories')) {
                $book->categories()->sync($request->categories);
            }
            return response()->json([
                'success' => true,
                'message' => 'Book updated',
                'data' => $book
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating book',
                'error' => $e->getMessage()
            ], 500);
        }
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
            return response()->json([
                'success' => true,
                'message' => 'Book deleted'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting book',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function search(string $bookName): JsonResponse
    {
        try {
            $books = Book::where('name', 'like', '%' . $bookName . '%')->get();
            return response()->json([
                'success' => true,
                'message' => 'Books found',
                'data' => $books
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error searching books',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
