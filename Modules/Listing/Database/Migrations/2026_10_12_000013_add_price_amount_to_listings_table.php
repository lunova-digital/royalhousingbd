<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Listing\Support\Price;

return new class extends Migration
{
    /** The price as a number of Taka for the budget filter; worked out from the price text where that is a plain amount. */
    public function up(): void
    {
        Schema::table('listings', function (Blueprint $table) {
            $table->unsignedBigInteger('price_amount')->nullable()->after('call_for_price')->index();
        });

        foreach (DB::table('listings')->where('call_for_price', false)->get(['id', 'price']) as $row) {
            if (($amount = Price::parse($row->price)) !== null) {
                DB::table('listings')->where('id', $row->id)->update(['price_amount' => $amount]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('listings', function (Blueprint $table) {
            $table->dropColumn('price_amount');
        });
    }
};
