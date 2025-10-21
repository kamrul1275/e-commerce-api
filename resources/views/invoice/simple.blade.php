<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $order->order_no }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; margin: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f5f5f5; }
    </style>
</head>
<body>
    <h2>Invoice #{{ $order->order_no }}</h2>
    <p><strong>Date:</strong> {{ $order->created_at->format('d M Y') }}</p>
    <p><strong>Status:</strong> {{ ucfirst($order->status) }}</p>

    <h3>Billing Address</h3>
    <p>
        {{ $order->billing_address['name'] ?? '' }} <br>
        {{ $order->billing_address['phone'] ?? '' }} <br>
        {{ $order->billing_address['line1'] ?? '' }}, {{ $order->billing_address['city'] ?? '' }}
    </p>

    <h3>Order Items</h3>
    <table>
        <thead>
            <tr>
                <th>Product</th>
                <th>SKU</th>
                <th>Qty</th>
                <th>Unit Price</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
        @foreach($order->items as $item)
            <tr>
                <td>{{ $item->name }}</td>
                <td>{{ $item->sku }}</td>
                <td>{{ $item->qty }}</td>
                <td>{{ number_format($item->unit_price, 2) }}</td>
                <td>{{ number_format($item->line_total, 2) }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <h3>Totals</h3>
    <p>
        Subtotal: {{ number_format($order->subtotal, 2) }}<br>
        Discount: {{ number_format($order->discount_total, 2) }}<br>
        Shipping: {{ number_format($order->shipping_total, 2) }}<br>
        Tax: {{ number_format($order->tax_total, 2) }}<br>
        <strong>Grand Total: {{ number_format($order->grand_total, 2) }}</strong>
    </p>
</body>
</html>
