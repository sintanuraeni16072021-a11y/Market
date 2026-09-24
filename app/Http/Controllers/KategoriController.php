<?php

namespace App\Http\Controllers;

use App\Models\Kategori;
use App\Models\KelompokKategori;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;

class KategoriController extends Controller
{
    public function index(Request $request)
    {
        $idSekolah = $request->session()->get('id_sekolah');
        
        $search = $request->input('search');
        $perPage = $request->input('per_page', 15);

        $query = Kategori::with('kelompok:id_kelompok,nama_kelompok')
            ->where('id_sekolah', $idSekolah)
            ->where('is_delete', 0);

        if ($search) {
            $query->where('nama', 'like', "%{$search}%");
        }

        $kategoriList = $query->orderBy('nama')
            ->paginate($perPage)
            ->withQueryString()
            ->through(function ($kategori) {
                return [
                    'id_kategori' => $kategori->id_kategori,
                    'nama' => $kategori->nama,
                    'id_kelompok' => $kategori->id_kelompok,
                    'kelompok' => $kategori->kelompok ? ['id_kelompok' => $kategori->kelompok->id_kelompok, 'nama_kelompok' => $kategori->kelompok->nama_kelompok] : null,
                ];
            });

        $kelompokKategoriList = KelompokKategori::where('id_sekolah', $idSekolah)
            ->orderBy('nama_kelompok')
            ->get(['id_kelompok', 'nama_kelompok']);

        return Inertia::render('Kategori/Index', [
            'kategoriList' => $kategoriList,
            'kelompokKategoriList' => $kelompokKategoriList,
            'filters' => ['search' => $search],
        ]);
    }

    public function store(Request $request)
    {
        $idSekolah = $request->session()->get('id_sekolah');
        $idUser = auth()->id();

        $request->validate([
            'nama' => 'required|string|max:100|unique:tb_kategori,nama,NULL,id_kategori,id_sekolah,' . $idSekolah,
            'id_kelompok' => 'required|exists:tb_kelompok_kategori,id_kelompok',
        ]);

        Kategori::create([
            'id_sekolah' => $idSekolah,
            'id_kelompok' => $request->id_kelompok,
            'nama' => $request->nama,
            'created_at' => Carbon::now(),
            'created_by' => $idUser,
            'is_delete' => 0,
        ]);

        return redirect()->route('kategori')->with('success', 'Kategori berhasil ditambahkan');
    }

    public function update(Request $request, $id)
    {
        $idSekolah = $request->session()->get('id_sekolah');
        $idUser = auth()->id();

        $kategori = Kategori::where('id_kategori', $id)
            ->where('id_sekolah', $idSekolah)
            ->where('is_delete', 0)
            ->firstOrFail();

        $request->validate([
            'nama' => 'required|string|max:100|unique:tb_kategori,nama,' . $id . ',id_kategori,id_sekolah,' . $idSekolah,
            'id_kelompok' => 'required|exists:tb_kelompok_kategori,id_kelompok',
        ]);

        $kategori->update([
            'nama' => $request->nama,
            'id_kelompok' => $request->id_kelompok,
            'updated_at' => Carbon::now(),
            'updated_by' => $idUser,
        ]);

        return redirect()->route('kategori')->with('success', 'Kategori berhasil diperbarui');
    }

    public function destroy(Request $request, $id)
    {
        $idSekolah = $request->session()->get('id_sekolah');
        $idUser = auth()->id();

        $kategori = Kategori::where('id_kategori', $id)
            ->where('id_sekolah', $idSekolah)
            ->where('is_delete', 0)
            ->firstOrFail();

        // Check if kategori has barang
        $barangCount = \App\Models\Barang::where('id_kategori', $id)
            ->where('id_sekolah', $idSekolah)
            ->where('is_delete', 0)
            ->count();

        if ($barangCount > 0) {
            return redirect()->route('kategori')->with('error', 'Kategori tidak dapat dihapus karena masih memiliki produk');
        }

        $kategori->update([
            'is_delete' => 1,
            'deleted_at' => Carbon::now(),
            'deleted_by' => $idUser,
        ]);

        return redirect()->route('kategori')->with('success', 'Kategori berhasil dihapus');
    }
}