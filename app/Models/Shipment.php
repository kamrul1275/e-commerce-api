<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Shipment extends Model
{
      protected $fillable = [
        'order_id', 'carrier', 'tracking_no', 'status', 'shipped_at', 'delivered_at'
    ];

    protected $casts = [
        'shipped_at' => 'datetime',
        'delivered_at' => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    // Calculate estimated delivery time (e.g., 2-5 days)
    public function getEstimatedDeliveryAttribute()
    {
        if (!$this->shipped_at) return null;

        $estimate = $this->shipped_at->copy()->addDays(rand(2, 5));
        return $estimate->toDateString();
    }
}
