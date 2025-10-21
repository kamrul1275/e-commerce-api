<?php

namespace App\Http\Controllers\Backend;

use App\Models\Cart;
use App\Models\Product;
use App\Models\CartItem;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class CartController extends Controller
{
       // 🛒 Get current user's or guest's cart
    public function index(Request $request)
    {
        $cart = $this->getCart($request);
        return $cart ? $cart->load('items.product') : response()->json(['message' => 'Cart is empty']);
    }

    // ➕ Add product to cart
    public function add(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'qty' => 'required|integer|min:1',
        ]);

        $cart = $this->getOrCreateCart($request);
        $product = Product::findOrFail($request->product_id);

        // Calculate line total
        $unitPrice = $product->discount_price ?? $product->price;

        $item = $cart->items()->firstOrNew(['product_id' => $product->id]);
        $item->qty = ($item->exists ? $item->qty : 0) + $request->qty;
        $item->unit_price = $unitPrice;
        $item->line_total = $item->qty * $unitPrice;
        $item->save();

        $this->recalculateTotals($cart);

        return response()->json(['message' => 'Product added to cart', 'cart' => $cart->load('items.product')]);
    }

    // 🔁 Update item quantity or remove if qty=0
    public function updateItem(Request $request, CartItem $item)
    {
        $request->validate(['qty' => 'required|integer|min:0']);
        if ($request->qty == 0) {
            $cart = $item->cart;
            $item->delete();
            $this->recalculateTotals($cart);
            return response()->json(['message' => 'Item removed']);
        }
        $item->qty = $request->qty;
        $item->line_total = $item->qty * $item->unit_price;
        $item->save();

        $this->recalculateTotals($item->cart);

        return response()->json(['message' => 'Cart updated', 'cart' => $item->cart->load('items.product')]);
    }

    // 🧮 Helper functions
    private function getCart(Request $request)
    {
        if ($request->user()) {
            return Cart::where('user_id', $request->user()->id)->where('status', 'draft')->first();
        }

        if ($token = $request->header('X-Guest-Token')) {
            return Cart::where('guest_token', $token)->where('status', 'draft')->first();
        }

        return null;
    }

    private function getOrCreateCart(Request $request)
    {
        $cart = $this->getCart($request);
        if ($cart) return $cart;

        $data = ['status' => 'draft'];
        if ($request->user()) {
            $data['user_id'] = $request->user()->id;
        } else {
            $data['guest_token'] = $request->header('X-Guest-Token') ?: Str::uuid()->toString();
        }
        return Cart::create($data);
    }

    private function recalculateTotals(Cart $cart)
    {
        $subtotal = $cart->items()->sum('line_total');
        $cart->update([
            'subtotal' => $subtotal,
            'discount_total' => 0,
            'shipping_total' => 0,
            'tax_total' => 0,
            'grand_total' => $subtotal
        ]);
    }
}
