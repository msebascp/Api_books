<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index(): JsonResponse
    {
        try {
            $categories = Category::all();
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get categories'
            ], 500);
        }
        return response()->json([
            'success' => true,
            'message' => 'All categories',
            'data' => $categories
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string'
        ]);
        try {
            $category = Category::create($request->all());
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create category'
            ], 500);
        }
        return response()->json([
            'success' => true,
            'message' => 'Category created',
            'data' => $category
        ]);
    }

    public function show(string $id): JsonResponse
    {
        $category = Category::find($id);
        if ($category === null) {
            return response()->json([
                'success' => false,
                'message' => 'Category not found'
            ], 404);
        }
        return response()->json([
            'success' => true,
            'message' => 'Category found',
            'data' => $category
        ]);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $category = Category::find($id);
        if ($category === null) {
            return response()->json([
                'success' => false,
                'message' => 'Category not found'
            ], 404);
        }
        $request->validate([
            'name' => 'required|string'
        ]);
        try {
            $category->update($request->all());
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update category'
            ], 500);
        }
        return response()->json([
            'success' => true,
            'message' => 'Category updated',
            'data' => $category
        ]);
    }

    public function destroy(string $id): JsonResponse
    {
        $category = Category::find($id);
        if ($category === null) {
            return response()->json([
                'success' => false,
                'message' => 'Category not found'
            ], 404);
        }
        try {
            $category->delete();
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete category'
            ], 500);
        }
        return response()->json([
            'success' => true,
            'message' => 'Category deleted'
        ]);
    }
}
