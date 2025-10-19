<?php

namespace App\Http\Controllers\Backend;

use App\Models\Product;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class ProductController extends Controller
{
    // List all products
    public function index()
    {
        return Product::with(['category', 'brand', 'images'])->paginate(10);
    }

    // Create new product
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'category_id' => 'required|exists:categories,id',
            'price' => 'required|numeric',
            'stock' => 'required|integer|min:0',
            'sku' => 'required|unique:products,sku'
        ]);

        $product = Product::create($request->all());
        return response()->json(['message' => 'Product created successfully', 'product' => $product]);
    }

    // Show single product
    public function show(Product $product)
    {
        return $product->load(['category', 'brand', 'images']);
    }

    // Update product
    public function update(Request $request, Product $product)
    {
        $product->update($request->all());
        return response()->json(['message' => 'Product updated successfully', 'product' => $product]);
    }

    // Delete product
    public function destroy(Product $product)
    {
        $product->delete();
        return response()->json(['message' => 'Product deleted successfully']);
    }
}
