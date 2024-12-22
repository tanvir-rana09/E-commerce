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
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null'); 
            $table->json('products');   
            $table->unsignedInteger('subtotal'); 
            $table->unsignedInteger('total_price'); 
            $table->unsignedInteger('total_items'); 
            $table->unsignedInteger('discount_amount')->default(0); 
            $table->unsignedInteger('shipping_cost')->default(0); 
            $table->json('shipping_address'); 
            $table->string('payment_method'); 
            $table->string('payment_number'); 
            $table->string('trx_id'); 
            $table->string('coupon_code')->nullable();
            $table->string('status')->default('pending');
            $table->string('payment_status')->default('pending'); 
            $table->string('delivery_status')->default('pending'); 
            $table->text('order_notes')->nullable(); 
            $table->timestamps(); 
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
