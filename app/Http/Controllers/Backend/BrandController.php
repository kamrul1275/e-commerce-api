<?php

namespace App\Http\Controllers\Backend;

use App\Models\Brand;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class BrandController extends Controller
{
     /**
     * Display all brands.
     */
    public function index()
    {
        return response()->json(Brand::all());
    }

    /**
     * Store a new brand.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:brands,name',
            'logo' => 'nullable|string',
            'status' => 'boolean',
        ]);

        $brand = Brand::create([
            'name' => $request->name,
            'slug' => Str::slug($request->name),
            'logo' => $request->logo,
            'status' => $request->status ?? true,
        ]);

        return response()->json([
            'message' => 'Brand created successfully',
            'brand' => $brand
        ], 201);
    }

    /**
     * Show a single brand.
     */
    public function show(Brand $brand)
    {
        return response()->json($brand);
    }

    /**
     * Update brand information.
     */
    public function update(Request $request, Brand $brand)
    {
        $request->validate([
            'name' => 'sometimes|string|max:255|unique:brands,name,' . $brand->id,
            'logo' => 'nullable|string',
            'status' => 'boolean',
        ]);

        $brand->update([
            'name' => $request->name ?? $brand->name,
            'slug' => Str::slug($request->name ?? $brand->name),
            'logo' => $request->logo ?? $brand->logo,
            'status' => $request->status ?? $brand->status,
        ]);

        return response()->json([
            'message' => 'Brand updated successfully',
            'brand' => $brand
        ]);
    }

    /**
     * Delete a brand.
     */
    public function destroy(Brand $brand)
    {
        $brand->delete();

        return response()->json([
            'message' => 'Brand deleted successfully'
        ]);
    }
}
