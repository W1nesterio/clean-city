<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cities', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('region')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        DB::table('cities')->insert([
            ['name' => 'Барановичи', 'region' => 'Брестская область',   'active' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Брест',      'region' => 'Брестская область',   'active' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Минск',      'region' => 'Минская область',     'active' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Гродно',     'region' => 'Гродненская область', 'active' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Витебск',    'region' => 'Витебская область',   'active' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Могилёв',    'region' => 'Могилёвская область', 'active' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Гомель',     'region' => 'Гомельская область',  'active' => 1, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('cities');
    }
};
