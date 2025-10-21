<?php

namespace App\Http\Controllers\Backend;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\OrderStatusHistory;
use App\Http\Controllers\Controller;

class PaymentController extends Controller
{
       /**
     * 💳 1. Initialize a payment (COD / Online Gateway)
     */
    public function initiate(Request $request, Order $order)
    {
        $request->validate([
            'method' => 'required|in:cod,sslcommerz,stripe,paypal',
        ]);

        if ($order->payment_status !== 'unpaid') {
            return response()->json(['message' => 'Payment already processed'], 400);
        }

        // Start new payment record
        $payment = Payment::create([
            'order_id' => $order->id,
            'amount' => $order->grand_total,
            'status' => 'initiated',
            'method' => $request->method,
        ]);

        if ($request->method === 'cod') {
            // For Cash on Delivery: instant confirm payment as COD
            $payment->update([
                'status' => 'successful',
                'transaction_id' => 'COD-' . strtoupper(Str::random(8)),
            ]);

            $order->update([
                'payment_status' => 'paid',
                'status' => 'processing'
            ]);

            $this->recordStatus($order, 'processing', 'Payment via Cash on Delivery');

            return response()->json([
                'message' => 'Cash on Delivery selected, order confirmed!',
                'order' => $order->load('payments')
            ]);
        }

        // 🔁 For online payments (mock example)
        // Normally, you’ll redirect to gateway or return payment link here.
        return response()->json([
            'message' => 'Payment initiated successfully',
            'payment_id' => $payment->id,
            'method' => $payment->method,
            'amount' => $payment->amount
        ]);
    }

    /**
     * 💰 2. Simulate payment success (For testing / webhook)
     */
    public function success(Request $request, Payment $payment)
    {
        DB::transaction(function () use ($payment, $request) {
            $payment->update([
                'status' => 'successful',
                'transaction_id' => $request->transaction_id ?? strtoupper(Str::random(10)),
                'payload' => $request->all(),
            ]);

            $payment->order->update([
                'payment_status' => 'paid',
                'status' => 'processing'
            ]);

            $this->recordStatus($payment->order, 'processing', 'Payment successful');
        });

        return response()->json(['message' => 'Payment successful', 'payment' => $payment]);
    }

    /**
     * 💥 3. Handle payment failure (e.g., gateway fail)
     */
    public function fail(Request $request, Payment $payment)
    {
        $payment->update([
            'status' => 'failed',
            'payload' => $request->all(),
        ]);

        $payment->order->update(['payment_status' => 'unpaid']);

        $this->recordStatus($payment->order, 'pending', 'Payment failed or canceled');

        return response()->json(['message' => 'Payment failed']);
    }

    /**
     * 💸 4. Refund Payment (admin only)
     */
    public function refund(Request $request, Payment $payment)
    {
        $request->validate(['note' => 'nullable|string|max:255']);

        if ($payment->status !== 'successful') {
            return response()->json(['message' => 'Only successful payments can be refunded'], 400);
        }

        $payment->update(['status' => 'refunded']);
        $payment->order->update(['payment_status' => 'refunded', 'status' => 'refunded']);

        $this->recordStatus($payment->order, 'refunded', $request->note ?? 'Refund issued');

        return response()->json(['message' => 'Payment refunded successfully']);
    }

    /**
     * 📜 5. Payment history per user
     */
    public function history(Request $request)
    {
        $user = $request->user();

        $payments = Payment::whereHas('order', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        })->with('order')->latest()->paginate(10);

        return response()->json($payments);
    }

    /**
     * 🧩 Helper: Record order status change
     */
    private function recordStatus(Order $order, string $status, ?string $note = null)
    {
        OrderStatusHistory::create([
            'order_id' => $order->id,
            'status' => $status,
            'note' => $note,
            'user_id' => $order->user_id
        ]);
    }
}
