<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            if (!Schema::hasColumn('organizations', 'district')) {
                $table->string('district')->nullable()->after('name');
            }
            if (!Schema::hasColumn('organizations', 'address')) {
                $table->string('address')->nullable()->after('district');
            }
            if (!Schema::hasColumn('organizations', 'lat')) {
                $table->decimal('lat', 10, 7)->nullable()->after('address');
            }
            if (!Schema::hasColumn('organizations', 'lng')) {
                $table->decimal('lng', 10, 7)->nullable()->after('lat');
            }
        });

        DB::table('users')->where('role', 'admin')->update(['role' => 'super_admin']);
    }

    public function down(): void
    {
        // Колонки не удаляем, чтобы не потерять данные справочника ЖКХ.
    }
};
