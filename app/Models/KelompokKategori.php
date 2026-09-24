<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KelompokKategori extends Model
{
    protected $table = 'tb_kelompok_kategori';
    protected $primaryKey = 'id_kelompok';
    public $timestamps = false;
    protected $fillable = [
        'id_sekolah',
        'nama_kelompok',
        'created_at',
        'created_by'
    ];

    public function sekolah()
    {
        return $this->belongsTo(Sekolah::class, 'id_sekolah', 'id_sekolah');
    }

    public function kategori()
    {
        return $this->hasMany(Kategori::class, 'id_kelompok', 'id_kelompok');
    }
}