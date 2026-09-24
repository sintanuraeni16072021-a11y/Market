<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    protected $table = 'activity_logs';
    protected $fillable = [
        'id_sekolah',
        'id_user',
        'aksi',
        'modul',
        'deskripsi',
        'ip_address',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'id_user', 'id_user');
    }

    public function sekolah()
    {
        return $this->belongsTo(Sekolah::class, 'id_sekolah', 'id_sekolah');
    }

    public static function catat(string $aksi, string $modul, ?string $deskripsi = null): void
    {
        try {
            $user = auth()->user();
            static::create([
                'id_sekolah' => session('id_sekolah') ?? $user?->id_sekolah,
                'id_user' => $user?->id_user,
                'aksi' => $aksi,
                'modul' => $modul,
                'deskripsi' => $deskripsi,
                'ip_address' => request()->ip(),
            ]);
        } catch (\Throwable $e) {
            // Logging tidak boleh menggagalkan transaksi utama
        }
    }
}
