<?php

namespace App\Http\Controllers;

use App\Models\Sekolah;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PengaturanController extends Controller
{
    public function index(Request $request)
    {
        $idSekolah = $request->session()->get('id_sekolah');

        $sekolah = Sekolah::find($idSekolah, [
            'id_sekolah', 'kode_sekolah', 'nama_sekolah',
            'alamat_sekolah', 'website', 'is_active',
        ]);

        return Inertia::render('Pengaturan/Index', [
            'sekolah' => $sekolah,
        ]);
    }

    public function update(Request $request)
    {
        $idSekolah = $request->session()->get('id_sekolah');

        $request->validate([
            'nama_sekolah' => 'required|string|max:150',
            'alamat_sekolah' => 'nullable|string',
            'website' => 'nullable|string|max:200',
        ]);

        $sekolah = Sekolah::where('id_sekolah', $idSekolah)->firstOrFail();
        $sekolah->update($request->only(['nama_sekolah', 'alamat_sekolah', 'website']));

        $request->session()->put('sekolah', $sekolah->fresh());

        return back()->with('success', 'Pengaturan sekolah berhasil disimpan.');
    }
}
