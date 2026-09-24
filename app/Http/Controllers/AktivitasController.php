<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AktivitasController extends Controller
{
    public function index(Request $request)
    {
        $idSekolah = $request->session()->get('id_sekolah');

        $search = $request->input('search');
        $modul = $request->input('modul');
        $perPage = $request->input('per_page', 20);

        $query = ActivityLog::with('user:id_user,nama_lengkap,username')
            ->where('id_sekolah', $idSekolah);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('deskripsi', 'like', "%{$search}%")
                    ->orWhere('aksi', 'like', "%{$search}%")
                    ->orWhereHas('user', fn ($u) => $u->where('nama_lengkap', 'like', "%{$search}%"));
            });
        }

        if ($modul) {
            $query->where('modul', $modul);
        }

        $logList = $query->orderBy('created_at', 'desc')
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn ($l) => [
                'id' => $l->id,
                'aksi' => $l->aksi,
                'modul' => $l->modul,
                'deskripsi' => $l->deskripsi,
                'ip_address' => $l->ip_address,
                'created_at' => $l->created_at,
                'user' => $l->user->nama_lengkap ?? '-',
            ]);

        $modulList = ActivityLog::where('id_sekolah', $idSekolah)
            ->distinct()
            ->orderBy('modul')
            ->pluck('modul');

        return Inertia::render('Aktivitas/Index', [
            'logList' => $logList,
            'modulList' => $modulList,
            'filters' => ['search' => $search, 'modul' => $modul],
        ]);
    }
}
