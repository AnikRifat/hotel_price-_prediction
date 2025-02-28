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
        Schema::create('competitors_room_prices', function (Blueprint $table) {
            $table->id();
            $table->string('competitor_hotel_name', 100);
            $table->string('room_type', 50);
            $table->decimal('price', 10, 2);
            $table->date('check_date');
            $table->string('competitor_url', 255)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('competitors_room_prices');
    }
};
