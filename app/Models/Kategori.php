<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Kategori extends Model
{
    protected $table = 'tb_kategori';
    protected $primaryKey = 'id_kategori';
    public $timestamps = false;
    protected $fillable = [
        'id_sekolah',
        'id_kelompok',
        'nama',
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
        return $this->belongsTo(KelompokKategori::class, 'id_kelompok', 'id_kelompok');
    }

    public function barang()
    {
        return $this->hasMany(Barang::class, 'id_kategori', 'id_kategori');
    }
}