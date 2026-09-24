<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    private function notifikasi($idSekolah): array
    {
        if (!$idSekolah) {
            return ['stok_menipis' => [], 'total_stok_menipis' => 0];
        }

        try {
            $stokMenipis = \App\Models\Barang::where('id_sekolah', $idSekolah)
                ->where('is_delete', 0)
                ->where('is_active', 1)
                ->whereColumn('stok', '<=', 'stok_minimum')
                ->orderBy('stok')
                ->limit(6)
                ->get(['id_barang', 'nama', 'stok', 'stok_minimum', 'satuan']);

            return [
                'stok_menipis' => $stokMenipis,
                'total_stok_menipis' => \App\Models\Barang::where('id_sekolah', $idSekolah)
                    ->where('is_delete', 0)
                    ->where('is_active', 1)
                    ->whereColumn('stok', '<=', 'stok_minimum')
                    ->count(),
            ];
        } catch (\Throwable $e) {
            return ['stok_menipis' => [], 'total_stok_menipis' => 0];
        }
    }

    public function share(Request $request): array
    {
        $user = $request->user();
        $idSekolah = $request->session()->get('id_sekolah') ?? $user?->id_sekolah;
        $sekolah = null;

        if ($idSekolah) {
            $sekolahModel = \App\Models\Sekolah::find($idSekolah);
            if ($sekolahModel) {
                $sekolah = [
                    'id_sekolah' => $sekolahModel->id_sekolah,
                    'nama_sekolah' => $sekolahModel->nama_sekolah,
                    'kode_sekolah' => $sekolahModel->kode_sekolah,
                    'alamat' => $sekolahModel->alamat ?? '',
                    'telepon' => $sekolahModel->telepon ?? '',
                ];
            }
        }

        $allSekolah = [];
        if ($user && $user->isSuperAdmin()) {
            $allSekolah = \App\Models\Sekolah::where('is_active', 1)
                ->orderBy('nama_sekolah')
                ->get(['id_sekolah', 'kode_sekolah', 'nama_sekolah']);
        }

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $user ? [
                    'id_user' => $user->id_user,
                    'username' => $user->username,
                    'nama_lengkap' => $user->nama_lengkap,
                    'email' => $user->email,
                    'id_role' => $user->id_role,
                    'id_sekolah' => $user->id_sekolah,
                    'is_active' => (bool) $user->is_active,
                    'role' => $user->role ? [
                        'id_role' => $user->role->id_role,
                        'nama_role' => $user->role->nama_role,
                    ] : null,
                ] : null,
            ],
            'sekolah' => $sekolah,
            'all_sekolah' => $allSekolah,
            'notifikasi' => $this->notifikasi($idSekolah),
            'flash' => [
                'toast' => $request->session()->get('success')
                    ? ['type' => 'success', 'message' => $request->session()->get('success')]
                    : ($request->session()->get('error')
                        ? ['type' => 'error', 'message' => $request->session()->get('error')]
                        : null),
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }
}