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
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->string('room_type', 50);
            $table->string('status', 20);
            $table->date('date_reservation');
            $table->time('time_reservation');
            $table->string('days_of_week', 10);
            $table->date('check_in_date');
            $table->date('check_out_date');
            $table->decimal('sales_price', 10, 2);
            $table->decimal('d2_hotel_occupancy', 5, 2);
            $table->decimal('average_competitor_price', 10, 2)->nullable();
            $table->integer('average_competitor_room_availability')->nullable();
            $table->integer('no_of_reservations')->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
