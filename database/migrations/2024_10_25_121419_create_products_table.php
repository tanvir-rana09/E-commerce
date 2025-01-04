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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string("name")->unique();
            $table->string("slug")->unique();
            $table->float("price");
            $table->unsignedInteger("stock");
            $table->text('banner');
            $table->integer('sells')->default(0);
            $table->integer('rating')->default(0);
            $table->string('gender')->nullable();
            $table->integer('status')->nullable()->default(1);
            $table->text('images')->nullable();
            $table->string('sku')->unique();
            $table->json('size')->nullable();
            $table->float('discount')->default(0);
            $table->json('colors')->nullable();
            $table->text('short_desc')->nullable();
            $table->text('long_desc')->nullable();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->unsignedBigInteger('subcategory_id')->nullable();
            $table->foreign('category_id')->references('id')->on('categories')->onDelete('set null')->onUpdate('cascade');
            $table->foreign('subcategory_id')->references('id')->on('categories')->onDelete('set null')->onUpdate('cascade');
            $table->index("name");
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
