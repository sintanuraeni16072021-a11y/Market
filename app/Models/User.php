<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;

#[Fillable(['id_sekolah', 'id_role', 'username', 'email', 'password', 'nama_lengkap', 'is_active', 'created_at', 'created_by', 'updated_at', 'updated_by', 'deleted_at', 'deleted_by'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements PasskeyUser
{
    use HasFactory, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable;

    protected $table = 'tb_user';
    protected $primaryKey = 'id_user';
    public $timestamps = false;
    protected $with = ['role'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function isSuperAdmin(): bool
    {
        return strtolower($this->role->nama_role ?? '') === 'super admin';
    }

    public function isAdmin(): bool
    {
        return strtolower($this->role->nama_role ?? '') === 'admin';
    }

    public function isKasir(): bool
    {
        return strtolower($this->role->nama_role ?? '') === 'kasir';
    }

    public function hasRole(string|array $roles): bool
    {
        $currentRole = strtolower($this->role->nama_role ?? '');
        $roles = is_array($roles) ? array_map('strtolower', $roles) : [strtolower($roles)];
        return in_array($currentRole, $roles);
    }

    public function sekolah()
    {
        return $this->belongsTo(Sekolah::class, 'id_sekolah', 'id_sekolah');
    }

    public function role()
    {
        return $this->belongsTo(Role::class, 'id_role', 'id_role');
    }

    public function pembelian()
    {
        return $this->hasMany(Pembelian::class, 'id_user', 'id_user');
    }

    public function penjualan()
    {
        return $this->hasMany(Penjualan::class, 'id_user', 'id_user');
    }

    public function getAuthPassword()
    {
        return $this->password;
    }
}