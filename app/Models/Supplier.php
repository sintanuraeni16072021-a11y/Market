<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    protected $table = 'tb_supplier';
    protected $primaryKey = 'id_supplier';
    public $timestamps = false;
    protected $fillable = [
        'id_sekolah',
        'nama',
        'no_telepon',
        'alamat_supplier',
        'created_at',
        'created_by',
        'deleted_at',
        'deleted_by',
        'is_delete'
    ];

    public function sekolah()
    {
        return $this->belongsTo(Sekolah::class, 'id_sekolah', 'id_sekolah');
    }

    public function barang()
    {
        return $this->hasMany(Barang::class, 'id_supplier', 'id_supplier');
    }

    public function pembelian()
    {
        return $this->hasMany(Pembelian::class, 'id_supplier', 'id_supplier');
    }
}