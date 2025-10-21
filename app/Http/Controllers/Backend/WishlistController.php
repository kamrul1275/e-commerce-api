<?php

namespace App\Http\Controllers\Backend;

use App\Models\Wishlist;
use App\Models\WishlistItem;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class WishlistController extends Controller
{
     // Get wishlist for logged user
    public function index(Request $request)
    {
        $wishlist = Wishlist::firstOrCreate(['user_id' => $request->user()->id]);
        return $wishlist->load('items.product');
    }

    // Add to wishlist
    public function add(Request $request)
    {
        $request->validate(['product_id' => 'required|exists:products,id']);
        $wishlist = Wishlist::firstOrCreate(['user_id' => $request->user()->id]);
        $wishlist->items()->firstOrCreate(['product_id' => $request->product_id]);
        return response()->json(['message' => 'Added to wishlist']);
    }

    // Remove from wishlist
    public function remove(WishlistItem $item)
    {
        $item->delete();
        return response()->json(['message' => 'Removed from wishlist']);
    }
}
