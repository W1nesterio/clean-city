<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            if (!Schema::hasColumn('tickets', 'needs_help')) {
                $table->boolean('needs_help')->default(false)->after('description');
            }
        });

        if (Schema::hasColumn('tickets', 'needs_help')) {
            DB::table('tickets')
                ->where('description', 'like', '%Нужна помощь%')
                ->update(['needs_help' => true]);

            DB::statement("UPDATE tickets SET description = TRIM(REPLACE(description, 'Нужна помощь с уточнением места.', '')) WHERE description LIKE '%Нужна помощь с уточнением места.%'");
            DB::statement("UPDATE tickets SET description = NULL WHERE description = ''");
        }

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'phone')) {
                $table->dropColumn('phone');
            }
            if (Schema::hasColumn('users', 'position')) {
                $table->dropColumn('position');
            }
            if (Schema::hasColumn('users', 'contact_info')) {
                $table->dropColumn('contact_info');
            }
        });
    }

    public function down(): void
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
        });

        Schema::table('tickets', function (Blueprint $table) {
            if (Schema::hasColumn('tickets', 'needs_help')) {
                $table->dropColumn('needs_help');
            }
        });
    }
};
