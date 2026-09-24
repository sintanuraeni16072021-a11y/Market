<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DetailPenjualan extends Model
{
    protected $table = 'tb_detail_penjualan';
    protected $primaryKey = 'id_detail_penjualan';
    public $timestamps = false;
    protected $fillable = [
        'id_penjualan',
        'id_barang',
        'jumlah_barang',
        'harga_beli',
        'harga_jual',
        'diskon_tipe',
        'diskon_nilai',
        'diskon_nominal',
        'subtotal'
    ];

    public function penjualan()
    {
        return $this->belongsTo(Penjualan::class, 'id_penjualan', 'id_penjualan');
    }

    public function barang()
    {
        return $this->belongsTo(Barang::class, 'id_barang', 'id_barang');
    }
}