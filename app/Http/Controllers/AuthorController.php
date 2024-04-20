<?php

namespace App\Http\Controllers;

use App\Models\Author;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthorController extends Controller
{
    public function index(): JsonResponse
    {
        try {
            $authors = Author::all();
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error getting authors',
                'error' => $e->getMessage()
            ], 500);
        }
        return response()->json([
            'success' => true,
            'message' => 'All authors',
            'data' => $authors
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string'
        ]);
        try {
            $author = Author::create($request->all());
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error creating author',
                'error' => $e->getMessage()
            ], 500);
        }
        return response()->json([
            'success' => true,
            'message' => 'Author created',
            'data' => $author
        ]);
    }

    public function show(string $id): JsonResponse
    {
        $author = Author::find($id);
        if ($author === null) {
            return response()->json([
                'success' => false,
                'message' => 'Author not found'
            ], 404);
        }
        return response()->json([
            'success' => true,
            'message' => 'Author found',
            'data' => $author
        ]);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $author = Author::find($id);
        if ($author === null) {
            return response()->json([
                'success' => false,
                'message' => 'Author not found'
            ], 404);
        }
        $request->validate([
            'name' => 'required|string'
        ]);
        try {
            $author->update($request->all());
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating author',
                'error' => $e->getMessage()
            ], 500);
        }
        return response()->json([
            'success' => true,
            'message' => 'Author updated',
            'data' => $author
        ]);
    }

    public function destroy(string $id): JsonResponse
    {
        $author = Author::find($id);
        if ($author === null) {
            return response()->json([
                'success' => false,
                'message' => 'Author not found'
            ], 404);
        }
        try {
            $author->delete();
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting author',
                'error' => $e->getMessage()
            ], 500);
        }
        return response()->json([
            'success' => true,
            'message' => 'Author deleted'
        ]);
    }
}
