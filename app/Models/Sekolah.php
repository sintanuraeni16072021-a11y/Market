<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sekolah extends Model
{
    protected $table = 'tb_sekolah';
    protected $primaryKey = 'id_sekolah';
    public $timestamps = false;
    protected $fillable = [
        'kode_sekolah',
        'nama_sekolah',
        'alamat_sekolah',
        'website',
        'is_active',
        'created_at'
    ];
}