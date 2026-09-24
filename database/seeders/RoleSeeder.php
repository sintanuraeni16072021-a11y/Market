<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            ['id_role' => 1, 'nama_role' => 'super admin'],
            ['id_role' => 2, 'nama_role' => 'admin'],
            ['id_role' => 3, 'nama_role' => 'kasir'],
        ];

        foreach ($roles as $role) {
            Role::updateOrCreate(['id_role' => $role['id_role']], $role);
        }
    }
}