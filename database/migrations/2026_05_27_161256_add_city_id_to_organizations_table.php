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
        Schema::table('organizations', function (Blueprint $table) {
            $table->unsignedBigInteger('city_id')->nullable()->after('name');
            $table->foreign('city_id')->references('id')->on('cities')->nullOnDelete();
        });

        // Default existing orgs to Baranovichi (city id 1)
        \Illuminate\Support\Facades\DB::table('organizations')
            ->whereNull('city_id')
            ->update(['city_id' => 1]);
    }

    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->dropForeign(['city_id']);
            $table->dropColumn('city_id');
        });
    }
};
