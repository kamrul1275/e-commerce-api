<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_no')->unique();                 // e.g., ORD-20251019-00001
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('guest_token')->nullable();            // guest checkout support
            $table->enum('status', ['pending', 'processing', 'shipped', 'delivered', 'canceled', 'returned', 'refunded'])
                ->default('pending');
            $table->enum('payment_status', ['unpaid', 'paid', 'refunded', 'partial'])->default('unpaid');
            $table->string('payment_method')->nullable();         // cod, sslcommerz, stripe (later)
            $table->string('shipping_method')->nullable();        // e.g., 'home-delivery'
            // snapshot totals
            $table->decimal('subtotal', 10, 2);
            $table->decimal('discount_total', 10, 2)->default(0);
            $table->decimal('shipping_total', 10, 2)->default(0);
            $table->decimal('tax_total', 10, 2)->default(0);
            $table->decimal('grand_total', 10, 2);
            // shipping & billing snapshots (denormalized for invoice stability)
            $table->json('billing_address');
            $table->json('shipping_address')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
