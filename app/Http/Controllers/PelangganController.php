<?php

namespace App\Http\Controllers;

use App\Models\Pelanggan;
use App\Models\KelompokPelanggan;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;

class PelangganController extends Controller
{
    public function index(Request $request)
    {
        $idSekolah = $request->session()->get('id_sekolah');
        
        $search = $request->input('search');
        $perPage = $request->input('per_page', 15);

        $query = Pelanggan::with('kelompok:id_kelompok_pelanggan,nama_kelompok')
            ->where('id_sekolah', $idSekolah)
            ->where('is_delete', 0);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('nama_pelanggan', 'like', "%{$search}%")
                  ->orWhere('telepon', 'like', "%{$search}%");
            });
        }

        $pelangganList = $query->orderBy('nama_pelanggan')
            ->paginate($perPage)
            ->withQueryString()
            ->through(function ($pelanggan) {
                return [
                    'id_pelanggan' => $pelanggan->id_pelanggan,
                    'nama_pelanggan' => $pelanggan->nama_pelanggan,
                    'telepon' => $pelanggan->telepon,
                    'alamat' => $pelanggan->alamat,
                    'id_kelompok_pelanggan' => $pelanggan->id_kelompok_pelanggan,
                    'kelompok' => $pelanggan->kelompok ? ['id_kelompok_pelanggan' => $pelanggan->kelompok->id_kelompok_pelanggan, 'nama_kelompok' => $pelanggan->kelompok->nama_kelompok] : null,
                ];
            });

        $kelompokPelangganList = KelompokPelanggan::where('id_sekolah', $idSekolah)
            ->orderBy('nama_kelompok')
            ->get(['id_kelompok_pelanggan', 'nama_kelompok']);

        return Inertia::render('Pelanggan/Index', [
            'pelangganList' => $pelangganList,
            'kelompokPelangganList' => $kelompokPelangganList,
            'filters' => ['search' => $search],
        ]);
    }

    public function store(Request $request)
    {
        $idSekolah = $request->session()->get('id_sekolah');
        $idUser = auth()->id();

        $request->validate([
            'nama_pelanggan' => 'required|string|max:150',
            'telepon' => 'nullable|string|max:20',
            'alamat' => 'nullable|string',
            'id_kelompok_pelanggan' => 'nullable|exists:tb_kelompok_pelanggan,id_kelompok_pelanggan',
        ]);

        Pelanggan::create([
            'id_sekolah' => $idSekolah,
            'id_kelompok_pelanggan' => $request->id_kelompok_pelanggan,
            'nama_pelanggan' => $request->nama_pelanggan,
            'telepon' => $request->telepon,
            'alamat' => $request->alamat,
            'created_at' => Carbon::now(),
            'created_by' => $idUser,
            'is_delete' => 0,
        ]);

        return back()->with('success', 'Pelanggan berhasil ditambahkan');
    }

    public function update(Request $request, $id)
    {
        $idSekolah = $request->session()->get('id_sekolah');
        $idUser = auth()->id();

        $pelanggan = Pelanggan::where('id_pelanggan', $id)
            ->where('id_sekolah', $idSekolah)
            ->where('is_delete', 0)
            ->firstOrFail();

        $request->validate([
            'nama_pelanggan' => 'required|string|max:150',
            'telepon' => 'nullable|string|max:20',
            'alamat' => 'nullable|string',
            'id_kelompok_pelanggan' => 'nullable|exists:tb_kelompok_pelanggan,id_kelompok_pelanggan',
        ]);

        $pelanggan->update([
            'nama_pelanggan' => $request->nama_pelanggan,
            'telepon' => $request->telepon,
            'alamat' => $request->alamat,
            'id_kelompok_pelanggan' => $request->id_kelompok_pelanggan,
            'updated_at' => Carbon::now(),
            'updated_by' => $idUser,
        ]);

        return back()->with('success', 'Pelanggan berhasil diperbarui');
    }

    public function destroy(Request $request, $id)
    {
        $idSekolah = $request->session()->get('id_sekolah');
        $idUser = auth()->id();

        $pelanggan = Pelanggan::where('id_pelanggan', $id)
            ->where('id_sekolah', $idSekolah)
            ->where('is_delete', 0)
            ->firstOrFail();

        // Check if pelanggan has penjualan
        $penjualanCount = \App\Models\Penjualan::where('id_pelanggan', $id)
            ->where('id_sekolah', $idSekolah)
            ->where('is_delete', 0)
            ->count();

        if ($penjualanCount > 0) {
            return back()->with('error', 'Pelanggan tidak dapat dihapus karena masih memiliki transaksi');
        }

        $pelanggan->update([
            'is_delete' => 1,
            'deleted_at' => Carbon::now(),
            'deleted_by' => $idUser,
        ]);

        return back()->with('success', 'Pelanggan berhasil dihapus');
    }
}