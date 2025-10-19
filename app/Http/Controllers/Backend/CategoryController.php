<?php

namespace App\Http\Controllers\Backend;

use App\Models\Category;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class CategoryController extends Controller
{
    public function index()
    {
        // Load parent and children categories
        $categories = Category::with('children')->whereNull('parent_id')->get();

        return response()->json($categories);
    }

    /**
     * Store a new category.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:categories,name',
            'parent_id' => 'nullable|exists:categories,id',
            'status' => 'boolean',
            'image' => 'nullable|string',
        ]);

        $category = Category::create([
            'name' => $request->name,
            'slug' => Str::slug($request->name),
            'parent_id' => $request->parent_id,
            'image' => $request->image,
            'status' => $request->status ?? true,
        ]);

        return response()->json([
            'message' => 'Category created successfully',
            'category' => $category
        ], 201);
    }

    /**
     * Show a single category.
     */
    public function show(Category $category)
    {
        $category->load('children');
        return response()->json($category);
    }

    /**
     * Update a category.
     */
    public function update(Request $request, Category $category)
    {
        $request->validate([
            'name' => 'sometimes|string|max:255|unique:categories,name,' . $category->id,
            'parent_id' => 'nullable|exists:categories,id',
            'status' => 'boolean',
            'image' => 'nullable|string',
        ]);

        $category->update([
            'name' => $request->name ?? $category->name,
            'slug' => Str::slug($request->name ?? $category->name),
            'parent_id' => $request->parent_id ?? $category->parent_id,
            'image' => $request->image ?? $category->image,
            'status' => $request->status ?? $category->status,
        ]);

        return response()->json([
            'message' => 'Category updated successfully',
            'category' => $category
        ]);
    }

    /**
     * Delete a category.
     */
    public function destroy(Category $category)
    {
        $category->delete();

        return response()->json([
            'message' => 'Category deleted successfully'
        ]);
    }
}
