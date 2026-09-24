<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pelanggan extends Model
{
    protected $table = 'tb_pelanggan';
    protected $primaryKey = 'id_pelanggan';
    public $timestamps = false;
    protected $fillable = [
        'id_sekolah',
        'id_kelompok_pelanggan',
        'nama_pelanggan',
        'telepon',
        'alamat',
        'created_at',
        'created_by',
        'updated_at',
        'updated_by',
        'deleted_at',
        'deleted_by',
        'is_delete'
    ];

    public function kelompok()
    {
        return $this->belongsTo(KelompokPelanggan::class, 'id_kelompok_pelanggan', 'id_kelompok_pelanggan');
    }

    public function penjualan()
    {
        return $this->hasMany(Penjualan::class, 'id_pelanggan', 'id_pelanggan');
    }
}