<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const ROLES = [
        'network_admin',
        'network_manager',
        'region_admin',
        'region_manager',
        'unit_admin',
        'unit_manager',
        'instructor',
        'receptionist',
        'sales',
    ];

    public function up(): void
    {
        DB::table('permissions')->insertOrIgnore([
            'key' => 'exercises.manage',
            'name' => 'Gerenciar exercícios',
            'module' => 'exercises',
            'action' => 'manage',
            'is_system' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $permissionId = DB::table('permissions')->where('key', 'exercises.manage')->value('id');

        if (! $permissionId) {
            return;
        }

        $roleIds = DB::table('roles')->whereIn('name', self::ROLES)->pluck('id');

        foreach ($roleIds as $roleId) {
            DB::table('role_permissions')->insertOrIgnore([
                'role_id' => $roleId,
                'permission_id' => $permissionId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        $permissionId = DB::table('permissions')->where('key', 'exercises.manage')->value('id');

        if (! $permissionId) {
            return;
        }

        DB::table('role_permissions')->where('permission_id', $permissionId)->delete();
        DB::table('permissions')->where('id', $permissionId)->delete();
    }
};
