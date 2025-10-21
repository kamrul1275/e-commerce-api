<?php

namespace App\Http\Controllers\Backend;

use App\Models\Shipment;
use Illuminate\Support\Str;
use App\Models\DeliveryZone;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use App\Http\Controllers\Controller;

class ShipmentController extends Controller
{
     /**
     * 🚚 List all shipments (Admin)
     */
    public function index()
    {
        $shipments = Shipment::with('order')->latest()->paginate(10);
        return response()->json($shipments);
    }

    /**
     * 🚀 Create / assign new shipment to order
     */
    public function store(Request $request, Order $order)
    {
        $request->validate([
            'carrier' => 'nullable|string',
            'zone_id' => 'nullable|exists:delivery_zones,id'
        ]);

        if ($order->status !== 'processing') {
            return response()->json(['message' => 'Order not ready for shipment'], 400);
        }

        $zone = $request->zone_id ? DeliveryZone::find($request->zone_id) : null;

        $shipment = Shipment::create([
            'order_id' => $order->id,
            'carrier' => $request->carrier ?? 'Redx',
            'tracking_no' => strtoupper(Str::random(10)),
            'status' => 'shipped',
            'shipped_at' => Carbon::now(),
        ]);

        // Update order status
        $order->update(['status' => 'shipped']);

        return response()->json([
            'message' => 'Shipment created successfully',
            'shipment' => $shipment,
            'estimated_delivery' => $zone ? now()->addDays($zone->estimated_days)->toDateString() : now()->addDays(3)->toDateString()
        ]);
    }

    /**
     * 🔄 Update shipment status (Admin or Delivery Partner)
     */
    public function updateStatus(Request $request, Shipment $shipment)
    {
        $request->validate([
            'status' => 'required|in:pending,shipped,in_transit,delivered,failed'
        ]);

        $shipment->update([
            'status' => $request->status,
            'delivered_at' => $request->status === 'delivered' ? Carbon::now() : $shipment->delivered_at
        ]);

        // Auto update order status when delivered
        if ($request->status === 'delivered') {
            $shipment->order->update(['status' => 'delivered']);
        }

        return response()->json([
            'message' => 'Shipment status updated to ' . $request->status,
            'shipment' => $shipment
        ]);
    }

    /**
     * 🔍 Track shipment by tracking number
     */
    public function track($trackingNo)
    {
        $shipment = Shipment::where('tracking_no', $trackingNo)->with('order')->first();

        if (!$shipment) {
            return response()->json(['message' => 'Tracking number not found'], 404);
        }

        return response()->json([
            'tracking_no' => $shipment->tracking_no,
            'status' => $shipment->status,
            'carrier' => $shipment->carrier,
            'shipped_at' => $shipment->shipped_at,
            'delivered_at' => $shipment->delivered_at,
            'estimated_delivery' => $shipment->estimated_delivery,
        ]);
    }

    /**
     * 📦 Delivery Zones management (Admin)
     */
    public function zones()
    {
        return response()->json(DeliveryZone::all());
    }

    public function addZone(Request $request)
    {
        $request->validate([
            'name' => 'required|string|unique:delivery_zones,name',
            'rate' => 'required|numeric|min:0',
            'estimated_days' => 'required|integer|min:1'
        ]);

        $zone = DeliveryZone::create($request->all());

        return response()->json(['message' => 'Delivery zone created successfully', 'zone' => $zone]);
    }
}
