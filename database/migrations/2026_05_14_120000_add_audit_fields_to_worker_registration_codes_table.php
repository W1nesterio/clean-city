<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('worker_registration_codes')) {
            return;
        }

        Schema::table('worker_registration_codes', function (Blueprint $table) {
            if (!Schema::hasColumn('worker_registration_codes', 'issued_to')) {
                $table->string('issued_to', 120)->nullable()->after('code');
            }

            if (!Schema::hasColumn('worker_registration_codes', 'used_at')) {
                $table->timestamp('used_at')->nullable()->after('used_by_user_id');
            }

            if (!Schema::hasColumn('worker_registration_codes', 'revoked_at')) {
                $table->timestamp('revoked_at')->nullable()->after('active');
            }

            if (!Schema::hasColumn('worker_registration_codes', 'revoked_by_user_id')) {
                $table->unsignedBigInteger('revoked_by_user_id')->nullable()->after('revoked_at');
                $table->index('revoked_by_user_id', 'worker_registration_codes_revoked_by_user_id_index');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('worker_registration_codes')) {
            return;
        }

        Schema::table('worker_registration_codes', function (Blueprint $table) {
            if (Schema::hasColumn('worker_registration_codes', 'revoked_by_user_id')) {
                $table->dropIndex('worker_registration_codes_revoked_by_user_id_index');
                $table->dropColumn('revoked_by_user_id');
            }

            foreach (['revoked_at', 'used_at', 'issued_to'] as $column) {
                if (Schema::hasColumn('worker_registration_codes', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
