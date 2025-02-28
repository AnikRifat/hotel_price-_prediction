<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddPricePerDayToBookingsTable extends Migration
{
    public function up()
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->decimal('price_per_day', 10, 2)->nullable()->after('sales_price');
        });

        // Optional: Populate existing records (e.g., sales_price / days)
        DB::statement('UPDATE bookings SET price_per_day = sales_price / DATEDIFF(check_out_date, check_in_date) WHERE check_out_date > check_in_date');
    }

    public function down()
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn('price_per_day');
        });
    }
}
