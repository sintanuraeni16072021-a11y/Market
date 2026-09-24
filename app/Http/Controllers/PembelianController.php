<?php

namespace App\Http\Controllers;

use App\Models\Pembelian;
use App\Models\DetailPembelian;
use App\Models\Supplier;
use App\Models\Barang;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PembelianController extends Controller
{
    public function index(Request $request)
    {
        $idSekolah = $request->session()->get('id_sekolah');
        
        $search = $request->input('search');
        $tanggalMulai = $request->input('tanggal_mulai');
        $tanggalAkhir = $request->input('tanggal_akhir');
        $supplierId = $request->input('supplier');
        $status = $request->input('status');
        $perPage = $request->input('per_page', 15);

        $query = Pembelian::with(['supplier:id_supplier,nama', 'user:id_user,nama_lengkap'])
            ->where('id_sekolah', $idSekolah)
            ->where('is_delete', 0);

        if ($search) {
            $query->where('nomor_faktur', 'like', "%{$search}%");
        }

        if ($tanggalMulai) {
            $query->whereDate('tanggal_faktur', '>=', $tanggalMulai);
        }

        if ($tanggalAkhir) {
            $query->whereDate('tanggal_faktur', '<=', $tanggalAkhir);
        }

        if ($supplierId) {
            $query->where('id_supplier', $supplierId);
        }

        if ($status) {
            $query->where('status_pembelian', $status);
        }

        $pembelianList = $query->orderBy('tanggal_faktur', 'desc')
            ->paginate($perPage)
            ->withQueryString()
            ->through(function ($pembelian) {
                return [
                    'id_pembelian' => $pembelian->id_pembelian,
                    'nomor_faktur' => $pembelian->nomor_faktur,
                    'tanggal_faktur' => $pembelian->tanggal_faktur,
                    'total_bayar' => (float) $pembelian->total_bayar,
                    'status_pembelian' => $pembelian->status_pembelian,
                    'jenis_transaksi' => $pembelian->jenis_transaksi,
                    'cara_bayar' => $pembelian->cara_bayar,
                    'id_supplier' => $pembelian->id_supplier,
                    'supplier' => $pembelian->supplier ? ['id_supplier' => $pembelian->supplier->id_supplier, 'nama' => $pembelian->supplier->nama] : null,
                    'id_user' => $pembelian->id_user,
                    'user' => $pembelian->user ? ['id_user' => $pembelian->user->id_user, 'nama_lengkap' => $pembelian->user->nama_lengkap] : null,
                ];
            });

        $supplierList = Supplier::where('id_sekolah', $idSekolah)
            ->where('is_delete', 0)
            ->orderBy('nama')
            ->get(['id_supplier', 'nama']);

        $barangList = Barang::where('id_sekolah', $idSekolah)
            ->where('is_active', 1)
            ->where('is_delete', 0)
            ->orderBy('nama')
            ->get(['id_barang', 'barcode', 'nama', 'harga_beli', 'satuan', 'stok']);

        $nomorFaktur = $this->generateNomorFaktur($idSekolah);

        return Inertia::render('Pembelian/Index', [
            'barangList' => $barangList,
            'nomorFaktur' => $nomorFaktur,
            'pembelianList' => $pembelianList,
            'supplierList' => $supplierList,
            'filters' => [
                'search' => $search,
                'tanggal_mulai' => $tanggalMulai,
                'tanggal_akhir' => $tanggalAkhir,
                'supplier' => $supplierId,
                'status' => $status,
            ],
        ]);
    }

    private function generateNomorFaktur($idSekolah): string
    {
        $lastPembelian = Pembelian::where('id_sekolah', $idSekolah)
            ->whereDate('tanggal_faktur', Carbon::today())
            ->orderBy('id_pembelian', 'desc')
            ->first();

        $counter = $lastPembelian ? (int) substr($lastPembelian->nomor_faktur, -4) + 1 : 1;

        return 'BEL-' . Carbon::today()->format('ymd') . str_pad($counter, 4, '0', STR_PAD_LEFT);
    }

    public function store(Request $request)
    {
        $idSekolah = $request->session()->get('id_sekolah');
        $idUser = auth()->id();

        $request->validate([
            'id_supplier' => 'required|exists:tb_supplier,id_supplier',
            'nomor_faktur' => 'required|string|max:50|unique:tb_pembelian,nomor_faktur',
            'tanggal_faktur' => 'required|date',
            'items' => 'required|array|min:1',
            'items.*.id_barang' => 'required|exists:tb_barang,id_barang',
            'items.*.satuan' => 'required|string|max:20',
            'items.*.jumlah' => 'required|integer|min:1',
            'items.*.harga_beli' => 'required|numeric|min:0',
            'jenis_transaksi' => 'required|in:tunai,kredit',
            'cara_bayar' => 'nullable|string|max:50',
            'note' => 'nullable|string',
        ]);

        return DB::transaction(function () use ($request, $idSekolah, $idUser) {
            // Calculate total
            $totalBayar = 0;
            foreach ($request->items as $item) {
                $totalBayar += $item['harga_beli'] * $item['jumlah'];
            }

            // Create pembelian
            $pembelian = Pembelian::create([
                'id_sekolah' => $idSekolah,
                'id_supplier' => $request->id_supplier,
                'id_user' => $idUser,
                'nomor_faktur' => $request->nomor_faktur,
                'tanggal_faktur' => $request->tanggal_faktur,
                'total_bayar' => $totalBayar,
                'status_pembelian' => 'draft',
                'jenis_transaksi' => $request->jenis_transaksi,
                'cara_bayar' => $request->cara_bayar,
                'note' => $request->note,
                'created_at' => Carbon::now(),
                'created_by' => $idUser,
                'is_delete' => 0,
            ]);

            // Create detail pembelian
            foreach ($request->items as $item) {
                DetailPembelian::create([
                    'id_pembelian' => $pembelian->id_pembelian,
                    'id_barang' => $item['id_barang'],
                    'satuan' => $item['satuan'],
                    'jumlah' => $item['jumlah'],
                    'harga_beli' => $item['harga_beli'],
                    'subtotal' => $item['harga_beli'] * $item['jumlah'],
                ]);
            }

            \App\Models\ActivityLog::catat('tambah', 'pembelian', "Faktur {$request->nomor_faktur} dibuat (Draft)");

            return redirect()->route('pembelian')->with('success', 'Pembelian berhasil ditambahkan (status: Draft)');
        });
    }

    public function detail(Request $request, $id)
    {
        $idSekolah = $request->session()->get('id_sekolah');

        $pembelian = Pembelian::with(['supplier:id_supplier,nama,no_telepon,alamat_supplier', 'user:id_user,nama_lengkap', 'detailPembelian.barang:id_barang,nama,barcode,harga_beli,satuan'])
            ->where('id_pembelian', $id)
            ->where('id_sekolah', $idSekolah)
            ->where('is_delete', 0)
            ->firstOrFail();

        return response()->json([
            'id_pembelian' => $pembelian->id_pembelian,
            'id_supplier' => $pembelian->id_supplier,
            'nomor_faktur' => $pembelian->nomor_faktur,
            'tanggal_faktur' => $pembelian->tanggal_faktur,
            'total_bayar' => (float) $pembelian->total_bayar,
            'status_pembelian' => $pembelian->status_pembelian,
            'jenis_transaksi' => $pembelian->jenis_transaksi,
            'cara_bayar' => $pembelian->cara_bayar,
            'note' => $pembelian->note,
            'supplier' => $pembelian->supplier,
            'user' => $pembelian->user,
            'detail_pembelian' => $pembelian->detailPembelian->map(function ($detail) {
                return [
                    'id_detail_pembelian' => $detail->id_detail_pembelian,
                    'id_barang' => $detail->id_barang,
                    'barang' => $detail->barang,
                    'satuan' => $detail->satuan,
                    'jumlah' => $detail->jumlah,
                    'harga_beli' => (float) $detail->harga_beli,
                    'subtotal' => (float) $detail->subtotal,
                ];
            }),
        ]);
    }

    public function update(Request $request, $id)
    {
        $idSekolah = $request->session()->get('id_sekolah');
        $idUser = auth()->id();

        $pembelian = Pembelian::with('detailPembelian')
            ->where('id_pembelian', $id)
            ->where('id_sekolah', $idSekolah)
            ->where('is_delete', 0)
            ->firstOrFail();

        // Only allow editing draft status
        if ($pembelian->status_pembelian !== 'draft') {
            return redirect()->route('pembelian')->with('error', 'Hanya pembelian dengan status Draft yang dapat diedit');
        }

        $request->validate([
            'id_supplier' => 'required|exists:tb_supplier,id_supplier',
            'nomor_faktur' => 'required|string|max:50|unique:tb_pembelian,nomor_faktur,' . $id . ',id_pembelian',
            'tanggal_faktur' => 'required|date',
            'items' => 'required|array|min:1',
            'items.*.id_barang' => 'required|exists:tb_barang,id_barang',
            'items.*.satuan' => 'required|string|max:20',
            'items.*.jumlah' => 'required|integer|min:1',
            'items.*.harga_beli' => 'required|numeric|min:0',
            'jenis_transaksi' => 'required|in:tunai,kredit',
            'cara_bayar' => 'nullable|string|max:50',
            'note' => 'nullable|string',
        ]);

        return DB::transaction(function () use ($request, $pembelian, $idUser) {
            // Calculate total
            $totalBayar = 0;
            foreach ($request->items as $item) {
                $totalBayar += $item['harga_beli'] * $item['jumlah'];
            }

            // Update pembelian
            $pembelian->update([
                'id_supplier' => $request->id_supplier,
                'nomor_faktur' => $request->nomor_faktur,
                'tanggal_faktur' => $request->tanggal_faktur,
                'total_bayar' => $totalBayar,
                'jenis_transaksi' => $request->jenis_transaksi,
                'cara_bayar' => $request->cara_bayar,
                'note' => $request->note,
                'updated_at' => Carbon::now(),
                'updated_by' => $idUser,
            ]);

            // Delete old details and create new ones
            $pembelian->detailPembelian()->delete();
            
            foreach ($request->items as $item) {
                DetailPembelian::create([
                    'id_pembelian' => $pembelian->id_pembelian,
                    'id_barang' => $item['id_barang'],
                    'satuan' => $item['satuan'],
                    'jumlah' => $item['jumlah'],
                    'harga_beli' => $item['harga_beli'],
                    'subtotal' => $item['harga_beli'] * $item['jumlah'],
                ]);
            }

            return redirect()->route('pembelian')->with('success', 'Pembelian berhasil diperbarui');
        });
    }

    public function destroy(Request $request, $id)
    {
        $idSekolah = $request->session()->get('id_sekolah');
        $idUser = auth()->id();

        $pembelian = Pembelian::where('id_pembelian', $id)
            ->where('id_sekolah', $idSekolah)
            ->where('is_delete', 0)
            ->firstOrFail();

        // Only allow deleting draft status
        if ($pembelian->status_pembelian !== 'draft') {
            return redirect()->route('pembelian')->with('error', 'Hanya pembelian dengan status Draft yang dapat dihapus');
        }

        $pembelian->update([
            'is_delete' => 1,
            'deleted_at' => Carbon::now(),
            'deleted_by' => $idUser,
        ]);

        return redirect()->route('pembelian')->with('success', 'Pembelian berhasil dihapus');
    }

    public function selesaikan(Request $request, $id)
    {
        $idSekolah = $request->session()->get('id_sekolah');
        $idUser = auth()->id();

        $pembelian = Pembelian::with('detailPembelian.barang')
            ->where('id_pembelian', $id)
            ->where('id_sekolah', $idSekolah)
            ->where('is_delete', 0)
            ->firstOrFail();

        if ($pembelian->status_pembelian !== 'draft') {
            return redirect()->route('pembelian')->with('error', 'Pembelian sudah diselesaikan');
        }

        return DB::transaction(function () use ($pembelian, $idUser) {
            // Update stok barang
            foreach ($pembelian->detailPembelian as $detail) {
                $barang = $detail->barang;
                if ($barang) {
                    $barang->increment('stok', $detail->jumlah);
                    $barang->update([
                        'updated_at' => Carbon::now(),
                        'updated_by' => $idUser,
                    ]);
                }
            }

            $pembelian->update([
                'status_pembelian' => 'selesai',
                'updated_at' => Carbon::now(),
                'updated_by' => $idUser,
            ]);

            \App\Models\ActivityLog::catat('selesaikan', 'pembelian', "Faktur {$pembelian->nomor_faktur} diselesaikan, stok bertambah");

            return redirect()->route('pembelian')->with('success', 'Pembelian berhasil diselesaikan dan stok barang telah ditambah');
        });
    }
}