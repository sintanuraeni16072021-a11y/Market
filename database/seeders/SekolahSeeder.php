<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Sekolah;

class SekolahSeeder extends Seeder
{
    public function run(): void
    {
        $sekolahList = [
            [
                'id_sekolah' => 1,
                'kode_sekolah' => 'SMKN2TAS',
                'nama_sekolah' => 'SMKN 2 Tasikmalaya',
                'alamat_sekolah' => 'Jl. Cikuray No. 1, Tasikmalaya',
                'website' => 'smkn2tasikmalaya.sch.id',
                'is_active' => 1,
                'created_at' => now(),
            ],
            [
                'id_sekolah' => 2,
                'kode_sekolah' => 'SMAN1BDG',
                'nama_sekolah' => 'SMAN 1 Bandung',
                'alamat_sekolah' => 'Jl. Ir. H. Juanda No. 93, Bandung',
                'website' => 'sman1bdg.sch.id',
                'is_active' => 1,
                'created_at' => now(),
            ],
            [
                'id_sekolah' => 3,
                'kode_sekolah' => 'SMKN1TAS',
                'nama_sekolah' => 'SMKN 1 Tasikmalaya',
                'alamat_sekolah' => 'Jl. Dr. Sukardjo No. 39, Tasikmalaya',
                'website' => 'smkn1tasikmalaya.sch.id',
                'is_active' => 1,
                'created_at' => now(),
            ],
            [
                'id_sekolah' => 4,
                'kode_sekolah' => 'SMKN3TAS',
                'nama_sekolah' => 'SMKN 3 Tasikmalaya',
                'alamat_sekolah' => 'Jl. Tamansari Gobras, Tasikmalaya',
                'website' => 'smkn3tasikmalaya.sch.id',
                'is_active' => 1,
                'created_at' => now(),
            ],
            [
                'id_sekolah' => 5,
                'kode_sekolah' => 'SMKN4TAS',
                'nama_sekolah' => 'SMKN 4 Tasikmalaya',
                'alamat_sekolah' => 'Jl. Depok, Tasikmalaya',
                'website' => 'smkn4tasikmalaya.sch.id',
                'is_active' => 1,
                'created_at' => now(),
            ],
        ];

        foreach ($sekolahList as $sekolah) {
            Sekolah::updateOrCreate(['id_sekolah' => $sekolah['id_sekolah']], $sekolah);
        }
    }
}