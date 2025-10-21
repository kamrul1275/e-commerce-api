<?php

namespace App\Http\Controllers\Backend;

use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

class CheckoutController extends Controller
{
    public function checkout(Request $request)
    {
        $request->validate([
            'payment_method' => 'required|string',
            'shipping_address' => 'required|array',
            'shipping_address.name' => 'required',
            'shipping_address.phone' => 'required',
            'shipping_address.line1' => 'required',
            'shipping_address.city' => 'required',
        ]);

        $cart = $this->getCart($request);
        if (!$cart || $cart->items->count() == 0) {
            return response()->json(['message' => 'Cart is empty'], 400);
        }

        return DB::transaction(function () use ($cart, $request) {
            $orderNo = 'ORD-' . now()->format('Ymd') . '-' . Str::random(6);

            $order = Order::create([
                'order_no' => $orderNo,
                'user_id' => optional($request->user())->id,
                'guest_token' => $request->user() ? null : $cart->guest_token,
                'status' => 'pending',
                'payment_status' => 'unpaid',
                'payment_method' => $request->payment_method,
                'subtotal' => $cart->subtotal,
                'discount_total' => $cart->discount_total,
                'shipping_total' => $cart->shipping_total,
                'tax_total' => $cart->tax_total,
                'grand_total' => $cart->grand_total,
                'billing_address' => $request->shipping_address,
                'shipping_address' => $request->shipping_address,
            ]);

            foreach ($cart->items as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item->product_id,
                    'name' => $item->product->name,
                    'sku' => $item->product->sku,
                    'unit_price' => $item->unit_price,
                    'qty' => $item->qty,
                    'line_total' => $item->line_total
                ]);

                $item->product->decrement('stock', $item->qty);
            }

            $cart->update(['status' => 'converted']);

            return response()->json([
                'message' => 'Order placed successfully',
                'order' => $order->load('items')
            ], 201);
        });
    }

    private function getCart(Request $request)
    {
        if ($request->user()) {
            return Cart::where('user_id', $request->user()->id)->where('status', 'draft')->with('items.product')->first();
        }

        $token = $request->header('X-Guest-Token');
        return $token ? Cart::where('guest_token', $token)->where('status', 'draft')->with('items.product')->first() : null;
    }
}
