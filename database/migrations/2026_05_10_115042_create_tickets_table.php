<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('category_id');

            $table->unsignedBigInteger('assigned_org_id')->nullable();
            $table->unsignedBigInteger('assigned_worker_id')->nullable();

            $table->string('status')->default('created');
            $table->string('priority')->default('normal');

            $table->decimal('lat', 10, 7);
            $table->decimal('lng', 10, 7);

            $table->string('address_text')->nullable();
            $table->string('description', 200)->nullable();

            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index('category_id');
            $table->index('assigned_org_id');
            $table->index('assigned_worker_id');
            $table->index('status');
            $table->index('priority');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};