<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('worker_registration_codes')) {
            return;
        }

        Schema::create('worker_registration_codes', function (Blueprint $table) {
            $table->id();
            $table->string('code', 32)->unique();
            $table->string('issued_to', 120)->nullable();
            $table->unsignedBigInteger('organization_id');
            $table->unsignedBigInteger('created_by_user_id')->nullable();
            $table->unsignedBigInteger('used_by_user_id')->nullable();
            $table->timestamp('used_at')->nullable();
            $table->unsignedInteger('max_uses')->default(1);
            $table->unsignedInteger('used_count')->default(0);
            $table->timestamp('expires_at')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamp('revoked_at')->nullable();
            $table->unsignedBigInteger('revoked_by_user_id')->nullable();
            $table->timestamps();

            $table->index('organization_id');
            $table->index('created_by_user_id');
            $table->index('used_by_user_id');
            $table->index('revoked_by_user_id');
            $table->index('active');
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('worker_registration_codes');
    }
};
