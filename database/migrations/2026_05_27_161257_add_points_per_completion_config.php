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
        Schema::create('system_settings', function (\Illuminate\Database\Schema\Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('description')->nullable();
            $table->timestamps();
        });

        \Illuminate\Support\Facades\DB::table('system_settings')->insert([
            ['key' => 'points_per_ticket_completion', 'value' => '10', 'description' => 'Баллы за выполнение заявки', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'points_per_ticket_creation',   'value' => '5',  'description' => 'Баллы за создание заявки',   'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('system_settings');
    }
};
