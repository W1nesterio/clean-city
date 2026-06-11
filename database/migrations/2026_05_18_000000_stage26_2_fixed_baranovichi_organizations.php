<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
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

        $now = now();
        $items = [
            1 => ['ЖЭС №1 КУРЭП ЖРЭУ г. Барановичи', 'Барановичи', 'ул. Фабричная, 18', '+375 163 47-52-84'],
            2 => ['ЖЭС №2 КУРЭП ЖРЭУ г. Барановичи', 'Барановичи', 'ул. Мицкевича, 44', '+375 163 42-24-75'],
            3 => ['ЖЭС №3 КУРЭП ЖРЭУ г. Барановичи', 'Барановичи', 'ул. Коммунистическая, 7А', '+375 163 42-97-97'],
            4 => ['ЖЭС №4 КУРЭП ЖРЭУ г. Барановичи', 'Барановичи', 'ул. Наконечникова, 5', '+375 163 46-14-02'],
            5 => ['ЖЭС №5 КУРЭП ЖРЭУ г. Барановичи', 'Барановичи', 'ул. Парковая, 53', '+375 163 40-83-56'],
            6 => ['ЖЭС №6 КУРЭП ЖРЭУ г. Барановичи', 'Барановичи', 'ул. Комарова, 13', '+375 163 45-71-01'],
            7 => ['ЖЭС №7 КУРЭП ЖРЭУ г. Барановичи', 'Барановичи', 'ул. Брестская, 279Б', '+375 163 44-20-07'],
        ];

        foreach ($items as $id => [$name, $district, $address, $contact]) {
            DB::table('organizations')->updateOrInsert(
                ['id' => $id],
                [
                    'name' => $name,
                    'district' => $district,
                    'address' => $address,
                    'contact_info' => $contact,
                    'active' => true,
                    'updated_at' => $now,
                    'created_at' => $now,
                ]
            );
        }

        DB::table('organizations')->whereNotIn('id', array_keys($items))->update(['active' => false, 'updated_at' => $now]);
       if (DB::getDriverName() === 'mysql') {
    DB::statement('ALTER TABLE organizations AUTO_INCREMENT = 8');
}
        DB::table('users')->where('role', 'admin')->update(['role' => 'super_admin', 'updated_at' => $now]);

        foreach ($items as $id => [$name]) {
            DB::table('users')->updateOrInsert(
                ['email' => 'jes' . $id . '-admin@clean-city.local'],
                [
                    'name' => 'Админ ЖЭС №' . $id,
                    'password' => Hash::make('password'),
                    'role' => 'org_admin',
                    'organization_id' => $id,
                    'updated_at' => $now,
                    'created_at' => $now,
                ]
            );
        }

    }

    public function down(): void
    {
        // Справочник ЖКХ намеренно не откатывается автоматически, чтобы не сломать связи пользователей и заявок.
    }
};
