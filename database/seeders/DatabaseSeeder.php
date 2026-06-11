<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('categories')->insert([
            [
                'name' => 'Мусор',
                'icon' => 'trash',
                'active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Переполненная урна',
                'icon' => 'bin',
                'active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Несанкционированная свалка',
                'icon' => 'dump',
                'active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Граффити',
                'icon' => 'graffiti',
                'active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Стекло / опасный мусор',
                'icon' => 'glass',
                'active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $organizationId = DB::table('organizations')->insertGetId([
            'name' => 'Бригада №1',
            'contact_info' => 'Служба уборки города',
            'active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        User::create([
            'name' => 'Администратор',
            'email' => 'admin@clean-city.local',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'organization_id' => null,
        ]);

        User::create([
            'name' => 'Исполнитель',
            'email' => 'worker@clean-city.local',
            'password' => Hash::make('password'),
            'role' => 'worker',
            'organization_id' => $organizationId,
        ]);

        User::create([
            'name' => 'Житель',
            'email' => 'resident@clean-city.local',
            'password' => Hash::make('password'),
            'role' => 'resident',
            'organization_id' => null,
        ]);
    }
}