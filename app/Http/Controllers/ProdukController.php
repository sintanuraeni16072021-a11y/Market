<?php

namespace App\Http\Controllers;

use App\Models\Barang;
use App\Models\Kategori;
use App\Models\KelompokKategori;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ProdukController extends Controller
{
    public function index(Request $request)
    {
        $idSekolah = $request->session()->get('id_sekolah');
        
        $search = $request->input('search');
        $kategoriId = $request->input('kategori');
        $status = $request->input('status');
        $perPage = $request->input('per_page', 15);

        $query = Barang::with(['kategori:id_kategori,nama', 'kelompokKategori:id_kelompok,nama_kelompok', 'supplier:id_supplier,nama'])
            ->where('id_sekolah', $idSekolah)
            ->where('is_delete', 0);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                  ->orWhere('barcode', 'like', "%{$search}%");
            });
        }

        if ($kategoriId) {
            $query->where('id_kategori', $kategoriId);
        }

        if ($status !== null && $status !== '') {
            $query->where('is_active', (bool)$status);
        }

        $barangList = $query->orderBy('nama')
            ->paginate($perPage)
            ->withQueryString()
            ->through(function ($barang) {
                return [
                    'id_barang' => $barang->id_barang,
                    'barcode' => $barang->barcode,
                    'nama' => $barang->nama,
                    'harga_jual' => (float) $barang->harga_jual,
                    'harga_beli' => (float) $barang->harga_beli,
                    'stok' => $barang->stok,
                    'stok_minimum' => (int) ($barang->stok_minimum ?? 0),
                    'satuan' => $barang->satuan,
                    'is_active' => (bool) $barang->is_active,
                    'id_kategori' => $barang->id_kategori,
                    'kategori' => $barang->kategori ? ['id_kategori' => $barang->kategori->id_kategori, 'nama' => $barang->kategori->nama] : null,
                    'id_kelompok_kategori' => $barang->id_kelompok_kategori,
                    'kelompok_kategori' => $barang->kelompokKategori ? ['id_kelompok' => $barang->kelompokKategori->id_kelompok, 'nama_kelompok' => $barang->kelompokKategori->nama_kelompok] : null,
                    'id_supplier' => $barang->id_supplier,
                    'supplier' => $barang->supplier ? ['id_supplier' => $barang->supplier->id_supplier, 'nama' => $barang->supplier->nama] : null,
                ];
            });

        $kategoriList = Kategori::where('id_sekolah', $idSekolah)
            ->where('is_delete', 0)
            ->orderBy('nama')
            ->get(['id_kategori', 'nama']);

        $kelompokKategoriList = KelompokKategori::where('id_sekolah', $idSekolah)
            ->orderBy('nama_kelompok')
            ->get(['id_kelompok', 'nama_kelompok']);

        $supplierList = Supplier::where('id_sekolah', $idSekolah)
            ->where('is_delete', 0)
            ->orderBy('nama')
            ->get(['id_supplier', 'nama']);

        return Inertia::render('Produk/Index', [
            'barangList' => $barangList,
            'kategoriList' => $kategoriList,
            'kelompokKategoriList' => $kelompokKategoriList,
            'supplierList' => $supplierList,
            'filters' => [
                'search' => $search,
                'kategori' => $kategoriId,
                'status' => $status,
            ],
        ]);
    }

    public function store(Request $request)
    {
        $idSekolah = $request->session()->get('id_sekolah');
        $idUser = auth()->id();

        $request->validate([
            'barcode' => 'nullable|string|max:50|unique:tb_barang,barcode,NULL,id_barang,id_sekolah,' . $idSekolah,
            'nama' => 'required|string|max:150',
            'id_kategori' => 'required|exists:tb_kategori,id_kategori',
            'id_kelompok_kategori' => 'required|exists:tb_kelompok_kategori,id_kelompok',
            'id_supplier' => 'nullable|exists:tb_supplier,id_supplier',
            'satuan' => 'required|string|max:20',
            'harga_beli' => 'required|numeric|min:0',
            'harga_jual' => 'required|numeric|min:0',
            'stok' => 'required|integer|min:0',
            'stok_minimum' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ]);

        Barang::create([
            'id_sekolah' => $idSekolah,
            'barcode' => $request->barcode,
            'nama' => $request->nama,
            'id_kategori' => $request->id_kategori,
            'id_kelompok_kategori' => $request->id_kelompok_kategori,
            'id_supplier' => $request->id_supplier,
            'satuan' => $request->satuan,
            'harga_beli' => $request->harga_beli,
            'harga_jual' => $request->harga_jual,
            'stok' => $request->stok,
            'stok_minimum' => $request->input('stok_minimum', 0),
            'is_active' => $request->boolean('is_active', true),
            'created_at' => Carbon::now(),
            'created_by' => $idUser,
            'is_delete' => 0,
        ]);

        \App\Models\ActivityLog::catat('tambah', 'produk', "Produk {$request->nama} ditambahkan");

        return redirect()->route('produk')->with('success', 'Produk berhasil ditambahkan');
    }

    public function update(Request $request, $id)
    {
        $idSekolah = $request->session()->get('id_sekolah');
        $idUser = auth()->id();

        $barang = Barang::where('id_barang', $id)
            ->where('id_sekolah', $idSekolah)
            ->where('is_delete', 0)
            ->firstOrFail();

        $request->validate([
            'barcode' => 'nullable|string|max:50|unique:tb_barang,barcode,' . $id . ',id_barang,id_sekolah,' . $idSekolah,
            'nama' => 'required|string|max:150',
            'id_kategori' => 'required|exists:tb_kategori,id_kategori',
            'id_kelompok_kategori' => 'required|exists:tb_kelompok_kategori,id_kelompok',
            'id_supplier' => 'nullable|exists:tb_supplier,id_supplier',
            'satuan' => 'required|string|max:20',
            'harga_beli' => 'required|numeric|min:0',
            'harga_jual' => 'required|numeric|min:0',
            'stok' => 'required|integer|min:0',
            'stok_minimum' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ]);

        $barang->update([
            'barcode' => $request->barcode,
            'nama' => $request->nama,
            'id_kategori' => $request->id_kategori,
            'id_kelompok_kategori' => $request->id_kelompok_kategori,
            'id_supplier' => $request->id_supplier,
            'satuan' => $request->satuan,
            'harga_beli' => $request->harga_beli,
            'harga_jual' => $request->harga_jual,
            'stok' => $request->stok,
            'stok_minimum' => $request->input('stok_minimum', 0),
            'is_active' => $request->boolean('is_active', true),
            'updated_at' => Carbon::now(),
            'updated_by' => $idUser,
        ]);

        \App\Models\ActivityLog::catat('ubah', 'produk', "Produk {$barang->nama} diperbarui");

        return redirect()->route('produk')->with('success', 'Produk berhasil diperbarui');
    }

    public function destroy(Request $request, $id)
    {
        $idSekolah = $request->session()->get('id_sekolah');
        $idUser = auth()->id();

        $barang = Barang::where('id_barang', $id)
            ->where('id_sekolah', $idSekolah)
            ->where('is_delete', 0)
            ->firstOrFail();

        $barang->update([
            'is_delete' => 1,
            'deleted_at' => Carbon::now(),
            'deleted_by' => $idUser,
        ]);

        \App\Models\ActivityLog::catat('hapus', 'produk', "Produk {$barang->nama} dihapus");

        return redirect()->route('produk')->with('success', 'Produk berhasil dihapus');
    }

    public function toggleStatus(Request $request, $id)
    {
        $idSekolah = $request->session()->get('id_sekolah');
        $idUser = auth()->id();

        $barang = Barang::where('id_barang', $id)
            ->where('id_sekolah', $idSekolah)
            ->where('is_delete', 0)
            ->firstOrFail();

        $barang->update([
            'is_active' => !$barang->is_active,
            'updated_at' => Carbon::now(),
            'updated_by' => $idUser,
        ]);

        return redirect()->route('produk')->with('success', 'Status produk berhasil diubah');
    }
}