<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'phone')) {
                $table->string('phone', 40)->nullable()->after('organization_id');
            }

            if (!Schema::hasColumn('users', 'position')) {
                $table->string('position', 120)->nullable()->after('phone');
            }

            if (!Schema::hasColumn('users', 'contact_info')) {
                $table->string('contact_info', 255)->nullable()->after('position');
            }

            if (!Schema::hasColumn('users', 'avatar_path')) {
                $table->string('avatar_path', 255)->nullable()->after('contact_info');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            foreach (['avatar_path', 'contact_info', 'position', 'phone'] as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
