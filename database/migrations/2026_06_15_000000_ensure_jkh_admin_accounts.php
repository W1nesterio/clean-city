<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        $password = Hash::make('password');

        $organizations = DB::table('organizations')
            ->where('active', true)
            ->orderBy('id')
            ->get(['id']);

        foreach ($organizations as $organization) {
            DB::table('users')->updateOrInsert(
                ['email' => 'jes' . $organization->id . '-admin@clean-city.local'],
                [
                    'name' => 'Админ ЖЭС №' . $organization->id,
                    'password' => $password,
                    'role' => 'org_admin',
                    'organization_id' => $organization->id,
                    'banned_at' => null,
                    'updated_at' => $now,
                    'created_at' => $now,
                ]
            );
        }

        $legacyAccounts = [
            'jkh-admin@clean-city.local' => 1,
            'jkh-admin2@clean-city.local' => 2,
        ];

        foreach ($legacyAccounts as $email => $organizationId) {
            if (!DB::table('organizations')->where('id', $organizationId)->where('active', true)->exists()) {
                continue;
            }

            DB::table('users')->updateOrInsert(
                ['email' => $email],
                [
                    'name' => $organizationId === 1 ? 'Админ ЖКХ №1' : 'Админ ЖКХ №2',
                    'password' => $password,
                    'role' => 'org_admin',
                    'organization_id' => $organizationId,
                    'banned_at' => null,
                    'updated_at' => $now,
                    'created_at' => $now,
                ]
            );
        }
    }

    public function down(): void
    {
        // Demo accounts are intentionally kept so existing logins do not break on rollback.
    }
};
