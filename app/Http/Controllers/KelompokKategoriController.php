<?php

namespace App\Http\Controllers;

use App\Models\KelompokKategori;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;

class KelompokKategoriController extends Controller
{
    public function index(Request $request)
    {
        $idSekolah = $request->session()->get('id_sekolah');
        
        $search = $request->input('search');
        $perPage = $request->input('per_page', 15);

        $query = KelompokKategori::where('id_sekolah', $idSekolah);

        if ($search) {
            $query->where('nama_kelompok', 'like', "%{$search}%");
        }

        $kelompokKategoriList = $query->orderBy('nama_kelompok')
            ->paginate($perPage)
            ->withQueryString()
            ->through(function ($kelompok) {
                return [
                    'id_kelompok' => $kelompok->id_kelompok,
                    'nama_kelompok' => $kelompok->nama_kelompok,
                    'created_at' => $kelompok->created_at,
                ];
            });

        return Inertia::render('KelompokKategori/Index', [
            'kelompokKategoriList' => $kelompokKategoriList,
            'filters' => ['search' => $search],
        ]);
    }

    public function store(Request $request)
    {
        $idSekolah = $request->session()->get('id_sekolah');
        $idUser = auth()->id();

        $request->validate([
            'nama_kelompok' => 'required|string|max:100|unique:tb_kelompok_kategori,nama_kelompok,NULL,id_kelompok,id_sekolah,' . $idSekolah,
        ]);

        KelompokKategori::create([
            'id_sekolah' => $idSekolah,
            'nama_kelompok' => $request->nama_kelompok,
            'created_at' => Carbon::now(),
            'created_by' => $idUser,
        ]);

        return redirect()->route('kelompok-kategori')->with('success', 'Kelompok Kategori berhasil ditambahkan');
    }

    public function update(Request $request, $id)
    {
        $idSekolah = $request->session()->get('id_sekolah');
        $idUser = auth()->id();

        $kelompok = KelompokKategori::where('id_kelompok', $id)
            ->where('id_sekolah', $idSekolah)
            ->firstOrFail();

        $request->validate([
            'nama_kelompok' => 'required|string|max:100|unique:tb_kelompok_kategori,nama_kelompok,' . $id . ',id_kelompok,id_sekolah,' . $idSekolah,
        ]);

        $kelompok->update([
            'nama_kelompok' => $request->nama_kelompok,
            'updated_at' => Carbon::now(),
            'updated_by' => $idUser,
        ]);

        return redirect()->route('kelompok-kategori')->with('success', 'Kelompok Kategori berhasil diperbarui');
    }

    public function destroy(Request $request, $id)
    {
        $idSekolah = $request->session()->get('id_sekolah');
        $idUser = auth()->id();

        $kelompok = KelompokKategori::where('id_kelompok', $id)
            ->where('id_sekolah', $idSekolah)
            ->firstOrFail();

        // Check if kelompok has kategori
        $kategoriCount = \App\Models\Kategori::where('id_kelompok', $id)
            ->where('id_sekolah', $idSekolah)
            ->where('is_delete', 0)
            ->count();

        if ($kategoriCount > 0) {
            return redirect()->route('kelompok-kategori')->with('error', 'Kelompok Kategori tidak dapat dihapus karena masih memiliki kategori');
        }

        $kelompok->update([
            'deleted_at' => Carbon::now(),
            'deleted_by' => $idUser,
        ]);

        return redirect()->route('kelompok-kategori')->with('success', 'Kelompok Kategori berhasil dihapus');
    }
}