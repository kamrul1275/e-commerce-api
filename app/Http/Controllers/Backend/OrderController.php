<?php

namespace App\Http\Controllers\Backend;

use App\Models\Order;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class OrderController extends Controller
{
       /**
     * 🧾 Get order summary list (User or Admin)
     */
    public function index(Request $request)
    {
        if ($request->user()->isAdmin()) {
            $orders = Order::with('items')->latest()->paginate(10);
        } else {
            $orders = Order::where('user_id', $request->user()->id)->with('items')->latest()->paginate(10);
        }

        return response()->json($orders);
    }

    /**
     * 🔍 Show single order summary with confirmation details
     */
    public function show(Order $order)
    {
        $order->load('items.product');
        return response()->json($order);
    }

    /**
     * 🚚 Update order tracking status
     */
    public function updateStatus(Request $request, Order $order)
    {
        $request->validate([
            'status' => 'required|in:processing,shipped,delivered,canceled,returned,refunded',
        ]);

        $order->update(['status' => $request->status]);

        return response()->json([
            'message' => 'Order status updated to ' . $request->status,
            'order' => $order
        ]);
    }

    /**
     * ❌ Cancel Order (User)
     */
    public function cancel(Request $request, Order $order)
    {
        if (!in_array($order->status, ['pending', 'processing'])) {
            return response()->json(['message' => 'Order cannot be canceled at this stage.'], 422);
        }

        $order->update(['status' => 'canceled']);

        return response()->json(['message' => 'Order canceled successfully']);
    }

    /**
     * ↩️ Request a refund or return
     */
    public function requestRefund(Request $request, Order $order)
    {
        $request->validate([
            'reason' => 'required|string|max:255',
        ]);

        if ($order->status !== 'delivered') {
            return response()->json(['message' => 'Refund available only after delivery.'], 422);
        }

        $order->update(['status' => 'returned']);

        return response()->json([
            'message' => 'Refund/Return request submitted successfully',
            'order' => $order
        ]);
    }

    /**
     * 🧾 Generate Invoice (HTML or PDF)
     */
    public function invoice(Order $order)
    {
        $order->load('items.product');

        // ✅ Option 1: Return HTML view (no package required)
        return view('invoice.simple', compact('order'));

        // ✅ Option 2: If PDF package installed (uncomment below)
        // $pdf = PDF::loadView('invoice.simple', compact('order'));
        // return $pdf->download('invoice-' . $order->order_no . '.pdf');
    }
}
