<?php

namespace App\Http\Controllers;

use App\Models\Barang;
use App\Models\Pelanggan;
use App\Models\Penjualan;
use App\Models\DetailPenjualan;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class KasirController extends Controller
{
    public function index(Request $request)
    {
        $idSekolah = $request->session()->get('id_sekolah') ?? auth()->user()->id_sekolah;

        $barangList = Barang::with('kategori:id_kategori,nama')
            ->where('id_sekolah', $idSekolah)
            ->where('is_active', 1)
            ->where('is_delete', 0)
            ->where('stok', '>', 0)
            ->orderBy('nama')
            ->get()
            ->map(function ($barang) {
                return [
                    'id_barang' => $barang->id_barang,
                    'barcode' => $barang->barcode,
                    'nama' => $barang->nama,
                    'harga_jual' => (float) $barang->harga_jual,
                    'stok' => $barang->stok,
                    'id_kategori' => $barang->id_kategori,
                    'kategori' => $barang->kategori ? ['nama' => $barang->kategori->nama] : null,
                ];
            });

        $kategoriList = \App\Models\Kategori::where('id_sekolah', $idSekolah)
            ->where('is_delete', 0)
            ->orderBy('nama')
            ->get(['id_kategori', 'nama']);

        $pelangganList = Pelanggan::where('id_sekolah', $idSekolah)
            ->where('is_delete', 0)
            ->orderBy('nama_pelanggan')
            ->get(['id_pelanggan', 'nama_pelanggan', 'telepon', 'alamat']);

        return Inertia::render('Kasir', [
            'barangList' => $barangList,
            'kategoriList' => $kategoriList,
            'pelangganList' => $pelangganList,
            'user' => [
                'id_user' => auth()->id(),
                'nama_lengkap' => auth()->user()->nama_lengkap,
                'id_sekolah' => $idSekolah,
            ],
        ]);
    }

    /**
     * Scan barcode barang
     */
    public function scan(Request $request, $barcode)
    {
        $idSekolah = $request->session()->get('id_sekolah')
            ?? auth()->user()->id_sekolah;

        $barang = Barang::where('barcode', $barcode)
            ->where('id_sekolah', $idSekolah)
            ->where('is_active', 1)
            ->where('is_delete', 0)
            ->first();

        if (!$barang) {
            return response()->json([
                'success' => false,
                'message' => 'Barang dengan barcode ' . $barcode . ' tidak ditemukan.'
            ], 404);
        }

        if ($barang->stok <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'Stok barang "' . $barang->nama . '" sedang habis.'
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'Barang ditemukan',
            'barang' => [
                'id_barang' => $barang->id_barang,
                'barcode' => $barang->barcode,
                'nama' => $barang->nama,
                'harga_jual' => (float) $barang->harga_jual,
                'stok' => $barang->stok,
                'id_kategori' => $barang->id_kategori,
            ]
        ]);
    }

    public function bayar(Request $request)
    {
        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.id_barang' => 'required|exists:tb_barang,id_barang',
            'items.*.qty' => 'required|integer|min:1',
            'items.*.harga_jual' => 'required|numeric|min:0',
            'id_pelanggan' => 'nullable|exists:tb_pelanggan,id_pelanggan',
            'subtotal' => 'required|numeric|min:0',
            'discount_type' => 'required|in:persen,nominal',
            'discount_value' => 'required|numeric|min:0',
            'discount_amount' => 'required|numeric|min:0',
            'total' => 'required|numeric|min:0',
            'payment_method' => 'required|in:tunai,kredit',
            'payment_type' => 'required|in:tunai,kredit,qris',
            'cash_received' => 'nullable|numeric|min:0',
            'change' => 'nullable|numeric|min:0',
        ]);

        $idSekolah = $request->session()->get('id_sekolah');
        $idUser = auth()->id();

        return DB::transaction(function () use ($request, $idSekolah, $idUser) {

            // Generate nomor faktur
            $lastPenjualan = Penjualan::where('id_sekolah', $idSekolah)
                ->whereDate('tanggal_penjualan', Carbon::today())
                ->orderBy('id_penjualan', 'desc')
                ->first();

            $counter = $lastPenjualan
                ? (int) substr($lastPenjualan->nomor_faktur, -4) + 1
                : 1;

            $nomorFaktur = 'TRX-' .
                Carbon::today()->format('ymd') .
                str_pad($counter, 4, '0', STR_PAD_LEFT);

            // Create penjualan
            $penjualan = Penjualan::create([
                'id_sekolah' => $idSekolah,
                'id_user' => $idUser,
                'id_pelanggan' => $request->id_pelanggan,
                'nomor_faktur' => $nomorFaktur,
                'tanggal_penjualan' => Carbon::now(),
                'total_faktur' => $request->subtotal,
                'total_bayar' => $request->total,
                'sudah_dibayar' => $request->payment_method === 'tunai'
                    ? $request->total
                    : 0,
                'kembalian' => $request->change ?? 0,
                'status_pembayaran' => $request->payment_method === 'tunai'
                    ? 'sudah bayar'
                    : 'belum bayar',
                'jenis_transaksi' => $request->payment_type,
                'cara_bayar' => $request->payment_type,
                'note' => 'Diskon: ' .
                    ($request->discount_type === 'persen'
                        ? $request->discount_value . '%'
                        : $request->discount_value),
                'created_at' => Carbon::now(),
                'created_by' => $idUser,
                'is_delete' => 0,
            ]);

            // Create detail penjualan & update stok
            foreach ($request->items as $item) {

                $barang = Barang::where('id_barang', $item['id_barang'])
                    ->where('id_sekolah', $idSekolah)
                    ->lockForUpdate()
                    ->first();

                if (!$barang) {
                    throw new \Exception(
                        "Barang ID {$item['id_barang']} tidak ditemukan"
                    );
                }

                if ($barang->stok < $item['qty']) {
                    throw new \Exception(
                        "Stok {$barang->nama} tidak mencukupi (tersedia: {$barang->stok})"
                    );
                }

                DetailPenjualan::create([
                    'id_penjualan' => $penjualan->id_penjualan,
                    'id_barang' => $item['id_barang'],
                    'jumlah_barang' => $item['qty'],
                    'harga_beli' => $barang->harga_beli,
                    'harga_jual' => $item['harga_jual'],
                    'diskon_tipe' => $request->discount_type,
                    'diskon_nilai' => $request->discount_value,
                    'diskon_nominal' => $request->discount_amount,
                    'subtotal' => $item['harga_jual'] * $item['qty']
                        - ($request->discount_amount / count($request->items)),
                ]);

                // Update stok
                $barang->decrement('stok', $item['qty']);

                $barang->update([
                    'updated_at' => Carbon::now(),
                    'updated_by' => $idUser,
                ]);
            }

            \App\Models\ActivityLog::catat(
                'jual',
                'kasir',
                "Transaksi {$nomorFaktur} senilai {$request->total}"
            );

            return response()->json([
                'success' => true,
                'nomor_faktur' => $nomorFaktur,
                'message' => 'Transaksi berhasil disimpan',
            ]);
        });
    }
}