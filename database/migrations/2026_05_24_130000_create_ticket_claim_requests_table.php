<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('ticket_claim_requests')) {
            return;
        }

        Schema::create('ticket_claim_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ticket_id');
            $table->unsignedBigInteger('worker_id');
            $table->unsignedBigInteger('organization_id');
            $table->string('status', 30)->default('pending')->index();
            $table->string('comment', 255)->nullable();
            $table->unsignedBigInteger('reviewed_by_user_id')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('resolution')->nullable();
            $table->timestamps();

            $table->index('ticket_id');
            $table->index('worker_id');
            $table->index('organization_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_claim_requests');
    }
};
