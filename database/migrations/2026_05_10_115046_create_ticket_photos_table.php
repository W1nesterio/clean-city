<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ticket_photos', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('ticket_id');
            $table->string('type'); // before или after
            $table->string('path');

            $table->timestamps();

            $table->index('ticket_id');
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_photos');
    }
};