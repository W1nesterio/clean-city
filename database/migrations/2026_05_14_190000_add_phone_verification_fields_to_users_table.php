<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'pending_phone')) {
                $table->string('pending_phone', 40)->nullable()->after('phone');
            }

            if (!Schema::hasColumn('users', 'phone_verified_at')) {
                $table->timestamp('phone_verified_at')->nullable()->after('pending_phone');
            }

            if (!Schema::hasColumn('users', 'phone_verification_code_hash')) {
                $table->string('phone_verification_code_hash')->nullable()->after('phone_verified_at');
            }

            if (!Schema::hasColumn('users', 'phone_verification_expires_at')) {
                $table->timestamp('phone_verification_expires_at')->nullable()->after('phone_verification_code_hash');
            }

            if (!Schema::hasColumn('users', 'phone_verification_attempts')) {
                $table->unsignedTinyInteger('phone_verification_attempts')->default(0)->after('phone_verification_expires_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            foreach ([
                'pending_phone',
                'phone_verified_at',
                'phone_verification_code_hash',
                'phone_verification_expires_at',
                'phone_verification_attempts',
            ] as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
