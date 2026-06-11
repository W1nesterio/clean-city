<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ticket_status_histories', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('ticket_id');
            $table->string('old_status')->nullable();
            $table->string('new_status');
            $table->unsignedBigInteger('changed_by_user_id')->nullable();
            $table->string('comment')->nullable();

            $table->timestamps();

            $table->index('ticket_id');
            $table->index('changed_by_user_id');
            $table->index('new_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_status_histories');
    }
};