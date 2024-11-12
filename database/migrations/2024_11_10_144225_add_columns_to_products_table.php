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
        Schema::table('products', function (Blueprint $table) {
            $table->integer('rating')->nullable()->default(1)->after('sells');
            $table->string('item_type')->nullable();
            $table->integer('status')->nullable()->default(1); // Corrected this line
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('rating');
            $table->dropColumn('item_type');
            $table->dropColumn('status'); // Dropping all added columns
        });
    }
};
