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
            $table->id(); // Primary key
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null'); // Foreign key to users table

            // Products - stored as JSON to capture product_id, quantity, price for each item
            $table->json('products');

            // Order summary
            $table->unsignedInteger('subtotal'); // Total before discounts and shipping
            $table->unsignedInteger('total_price'); // Final total after discounts and shipping
            $table->unsignedInteger('total_items'); // Total number of items in the order
            $table->unsignedInteger('discount_amount')->default(0); // Discount applied, if any
            $table->unsignedInteger('shipping_cost')->default(0); // Shipping cost, if applicable

            // Shipping address - structured JSON to capture address details
            $table->json('shipping_address'); 

            // Payment details
            $table->string('payment_method'); // Payment method used, e.g., "bkash"
            $table->string('payment_number'); // User’s payment number for the method
            $table->string('trx_id'); // Transaction ID for payment confirmation

            // Status fields
            $table->string('payment_status')->default('pending'); // Status of the payment (pending, confirmed, failed, etc.)
            $table->string('coupon_code')->nullable(); // Optional coupon code applied
            $table->string('delivery_status')->default('pending'); // Status of delivery (pending, in transit, delivered, etc.)

            // Optional order notes from customer
            $table->text('order_notes')->nullable();

            // Timestamps for order tracking
            $table->timestamps(); 
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
