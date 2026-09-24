<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Sekolah;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $superAdminRole = Role::where('nama_role', 'super admin')->first();
        $adminRole = Role::where('nama_role', 'admin')->first();
        $kasirRole = Role::where('nama_role', 'kasir')->first();

        $users = [
            // Super Admin
            [
                'id_sekolah' => 1,
                'id_role' => $superAdminRole?->id_role ?? 1,
                'username' => 'superadmin',
                'email' => 'superadmin@pos.test',
                'password' => Hash::make('password'),
                'nama_lengkap' => 'Super Administrator (Pusat)',
                'is_active' => 1,
            ],
            // Admin & Kasir Sekolah 1
            [
                'id_sekolah' => 1,
                'id_role' => $adminRole?->id_role ?? 2,
                'username' => 'admin_smkn2',
                'email' => 'admin.smkn2@pos.test',
                'password' => Hash::make('password'),
                'nama_lengkap' => 'Admin SMKN 2 Tasikmalaya',
                'is_active' => 1,
            ],
            [
                'id_sekolah' => 1,
                'id_role' => $kasirRole?->id_role ?? 3,
                'username' => 'kasir_smkn2',
                'email' => 'kasir.smkn2@pos.test',
                'password' => Hash::make('password'),
                'nama_lengkap' => 'Kasir SMKN 2 Tasikmalaya',
                'is_active' => 1,
            ],
            // Admin & Kasir Sekolah 2
            [
                'id_sekolah' => 2,
                'id_role' => $adminRole?->id_role ?? 2,
                'username' => 'admin_sman1',
                'email' => 'admin.sman1@pos.test',
                'password' => Hash::make('password'),
                'nama_lengkap' => 'Admin SMAN 1 Bandung',
                'is_active' => 1,
            ],
            [
                'id_sekolah' => 2,
                'id_role' => $kasirRole?->id_role ?? 3,
                'username' => 'kasir_sman1',
                'email' => 'kasir.sman1@pos.test',
                'password' => Hash::make('password'),
                'nama_lengkap' => 'Kasir SMAN 1 Bandung',
                'is_active' => 1,
            ],
            // Admin & Kasir SMKN 1 Tasikmalaya
            [
                'id_sekolah' => 3,
                'id_role' => $adminRole?->id_role ?? 2,
                'username' => 'admin_smkn1',
                'email' => 'admin.smkn1@pos.test',
                'password' => Hash::make('password'),
                'nama_lengkap' => 'Admin SMKN 1 Tasikmalaya',
                'is_active' => 1,
            ],
            [
                'id_sekolah' => 3,
                'id_role' => $kasirRole?->id_role ?? 3,
                'username' => 'kasir_smkn1',
                'email' => 'kasir.smkn1@pos.test',
                'password' => Hash::make('password'),
                'nama_lengkap' => 'Kasir SMKN 1 Tasikmalaya',
                'is_active' => 1,
            ],
            // Admin & Kasir SMKN 3 Tasikmalaya
            [
                'id_sekolah' => 4,
                'id_role' => $adminRole?->id_role ?? 2,
                'username' => 'admin_smkn3',
                'email' => 'admin.smkn3@pos.test',
                'password' => Hash::make('password'),
                'nama_lengkap' => 'Admin SMKN 3 Tasikmalaya',
                'is_active' => 1,
            ],
            [
                'id_sekolah' => 4,
                'id_role' => $kasirRole?->id_role ?? 3,
                'username' => 'kasir_smkn3',
                'email' => 'kasir.smkn3@pos.test',
                'password' => Hash::make('password'),
                'nama_lengkap' => 'Kasir SMKN 3 Tasikmalaya',
                'is_active' => 1,
            ],
            // Admin & Kasir SMKN 4 Tasikmalaya
            [
                'id_sekolah' => 5,
                'id_role' => $adminRole?->id_role ?? 2,
                'username' => 'admin_smkn4',
                'email' => 'admin.smkn4@pos.test',
                'password' => Hash::make('password'),
                'nama_lengkap' => 'Admin SMKN 4 Tasikmalaya',
                'is_active' => 1,
            ],
            [
                'id_sekolah' => 5,
                'id_role' => $kasirRole?->id_role ?? 3,
                'username' => 'kasir_smkn4',
                'email' => 'kasir.smkn4@pos.test',
                'password' => Hash::make('password'),
                'nama_lengkap' => 'Kasir SMKN 4 Tasikmalaya',
                'is_active' => 1,
            ],
            // Default aliases
            [
                'id_sekolah' => 1,
                'id_role' => $adminRole?->id_role ?? 2,
                'username' => 'admin',
                'email' => 'admin@pos.test',
                'password' => Hash::make('password'),
                'nama_lengkap' => 'Administrator',
                'is_active' => 1,
            ],
            [
                'id_sekolah' => 1,
                'id_role' => $kasirRole?->id_role ?? 3,
                'username' => 'kasir',
                'email' => 'kasir@pos.test',
                'password' => Hash::make('password'),
                'nama_lengkap' => 'Kasir Utama',
                'is_active' => 1,
            ],
        ];

        foreach ($users as $user) {
            User::updateOrCreate(['username' => $user['username']], $user);
        }
    }
}