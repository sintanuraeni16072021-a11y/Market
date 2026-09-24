<?php

namespace App\Http\Controllers;

use App\Models\Penjualan;
use App\Models\Pembelian;
use App\Models\Barang;
use App\Models\Pelanggan;
use App\Models\DetailPenjualan;
use App\Models\Sekolah;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;

class LaporanController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $isSuperAdmin = $user->isSuperAdmin();
        $idSekolah = $request->session()->get('id_sekolah') ?? $user->id_sekolah;
        $filterSekolah = $isSuperAdmin ? $request->input('id_sekolah') : $idSekolah;
        
        $jenisLaporan = $request->input('jenis', 'penjualan');
        $tanggalMulai = $request->input('tanggal_mulai', Carbon::today()->format('Y-m-d'));
        $tanggalAkhir = $request->input('tanggal_akhir', Carbon::today()->format('Y-m-d'));

        $data = [];
        
        switch ($jenisLaporan) {
            case 'penjualan':
                $data = $this->getLaporanPenjualan($filterSekolah, $tanggalMulai, $tanggalAkhir);
                break;
            case 'pembelian':
                $data = $this->getLaporanPembelian($filterSekolah, $tanggalMulai, $tanggalAkhir);
                break;
            case 'stok':
                $data = $this->getLaporanStok($filterSekolah);
                break;
            case 'pelanggan':
                $data = $this->getLaporanPelanggan($filterSekolah, $tanggalMulai, $tanggalAkhir);
                break;
            case 'produk_terlaris':
                $data = $this->getLaporanProdukTerlaris($filterSekolah, $tanggalMulai, $tanggalAkhir);
                break;
            case 'laba_rugi':
                $data = $this->getLaporanLabaRugi($filterSekolah, $tanggalMulai, $tanggalAkhir);
                break;
        }

        $sekolahList = $isSuperAdmin 
            ? Sekolah::where('is_active', 1)->orderBy('nama_sekolah')->get(['id_sekolah', 'nama_sekolah', 'kode_sekolah'])
            : [];

        return Inertia::render('Laporan/Index', [
            'jenisLaporan' => $jenisLaporan,
            'tanggalMulai' => $tanggalMulai,
            'tanggalAkhir' => $tanggalAkhir,
            'data' => $data,
            'sekolahList' => $sekolahList,
            'filterSekolah' => $filterSekolah,
            'isSuperAdmin' => $isSuperAdmin,
        ]);
    }

    private function getLaporanPenjualan($idSekolah, $tanggalMulai, $tanggalAkhir)
    {
        $query = Penjualan::with(['user:id_user,nama_lengkap', 'pelanggan:id_pelanggan,nama_pelanggan', 'sekolah:id_sekolah,nama_sekolah'])
            ->where('is_delete', 0)
            ->whereBetween('tanggal_penjualan', [$tanggalMulai . ' 00:00:00', $tanggalAkhir . ' 23:59:59']);

        if ($idSekolah) {
            $query->where('id_sekolah', $idSekolah);
        }

        $penjualan = $query->orderBy('tanggal_penjualan', 'desc')
            ->get()
            ->map(function ($p) {
                return [
                    'id_penjualan' => $p->id_penjualan,
                    'nomor_faktur' => $p->nomor_faktur,
                    'tanggal_penjualan' => $p->tanggal_penjualan,
                    'total_faktur' => (float) $p->total_faktur,
                    'total_bayar' => (float) $p->total_bayar,
                    'kembalian' => (float) $p->kembalian,
                    'status_pembayaran' => $p->status_pembayaran,
                    'jenis_transaksi' => $p->jenis_transaksi,
                    'cara_bayar' => $p->cara_bayar,
                    'kasir' => $p->user->nama_lengkap ?? '-',
                    'pelanggan' => $p->pelanggan->nama_pelanggan ?? '-',
                    'sekolah' => $p->sekolah->nama_sekolah ?? '-',
                ];
            });

        $summary = [
            'total_transaksi' => $penjualan->count(),
            'total_omzet' => (float) $penjualan->sum('total_bayar'),
            'total_diskon' => (float) ($penjualan->sum('total_faktur') - $penjualan->sum('total_bayar')),
            'tunai' => (float) $penjualan->where('jenis_transaksi', 'tunai')->sum('total_bayar'),
            'kredit' => (float) $penjualan->where('jenis_transaksi', 'kredit')->sum('total_bayar'),
            'qris' => (float) $penjualan->where('jenis_transaksi', 'qris')->sum('total_bayar'),
        ];

        return ['list' => $penjualan, 'summary' => $summary];
    }

    private function getLaporanPembelian($idSekolah, $tanggalMulai, $tanggalAkhir)
    {
        $query = Pembelian::with(['supplier:id_supplier,nama', 'user:id_user,nama_lengkap', 'sekolah:id_sekolah,nama_sekolah'])
            ->where('is_delete', 0)
            ->whereBetween('tanggal_faktur', [$tanggalMulai . ' 00:00:00', $tanggalAkhir . ' 23:59:59']);

        if ($idSekolah) {
            $query->where('id_sekolah', $idSekolah);
        }

        $pembelian = $query->orderBy('tanggal_faktur', 'desc')
            ->get()
            ->map(function ($p) {
                return [
                    'id_pembelian' => $p->id_pembelian,
                    'nomor_faktur' => $p->nomor_faktur,
                    'tanggal_faktur' => $p->tanggal_faktur,
                    'total_bayar' => (float) $p->total_bayar,
                    'supplier' => $p->supplier->nama ?? '-',
                    'user' => $p->user->nama_lengkap ?? '-',
                    'sekolah' => $p->sekolah->nama_sekolah ?? '-',
                ];
            });

        $summary = [
            'total_pembelian' => $pembelian->count(),
            'total_pengeluaran' => (float) $pembelian->sum('total_bayar'),
        ];

        return ['list' => $pembelian, 'summary' => $summary];
    }

    private function getLaporanStok($idSekolah)
    {
        $query = Barang::with(['kategori:id_kategori,nama', 'sekolah:id_sekolah,nama_sekolah'])
            ->where('is_delete', 0);

        if ($idSekolah) {
            $query->where('id_sekolah', $idSekolah);
        }

        $barang = $query->orderBy('nama')
            ->get()
            ->map(function ($b) {
                return [
                    'id_barang' => $b->id_barang,
                    'barcode' => $b->barcode,
                    'nama' => $b->nama,
                    'kategori' => $b->kategori->nama ?? '-',
                    'stok' => (int) $b->stok,
                    'harga_beli' => (float) $b->harga_beli,
                    'harga_jual' => (float) $b->harga_jual,
                    'nilai_stok' => (float) ($b->stok * $b->harga_beli),
                    'sekolah' => $b->sekolah->nama_sekolah ?? '-',
                ];
            });

        $summary = [
            'total_produk' => $barang->count(),
            'total_nilai_stok' => (float) $barang->sum('nilai_stok'),
            'stok_habis' => $barang->where('stok', 0)->count(),
            'stok_rendah' => $barang->where('stok', '<=', 5)->where('stok', '>', 0)->count(),
        ];

        return ['list' => $barang, 'summary' => $summary];
    }

    private function getLaporanPelanggan($idSekolah, $tanggalMulai, $tanggalAkhir)
    {
        $query = Pelanggan::with(['sekolah:id_sekolah,nama_sekolah'])
            ->where('is_delete', 0);

        if ($idSekolah) {
            $query->where('id_sekolah', $idSekolah);
        }

        $pelanggan = $query->orderBy('nama_pelanggan')
            ->get()
            ->map(function ($p) use ($tanggalMulai, $tanggalAkhir) {
                $penjualanQuery = Penjualan::where('id_pelanggan', $p->id_pelanggan)
                    ->where('is_delete', 0)
                    ->whereBetween('tanggal_penjualan', [$tanggalMulai . ' 00:00:00', $tanggalAkhir . ' 23:59:59']);

                $totalTransaksi = $penjualanQuery->count();
                $totalBelanja = $penjualanQuery->sum('total_bayar');

                return [
                    'id_pelanggan' => $p->id_pelanggan,
                    'nama_pelanggan' => $p->nama_pelanggan,
                    'telepon' => $p->telepon,
                    'alamat' => $p->alamat,
                    'total_transaksi' => $totalTransaksi,
                    'total_belanja' => (float) $totalBelanja,
                    'sekolah' => $p->sekolah->nama_sekolah ?? '-',
                ];
            });

        $summary = [
            'total_pelanggan' => $pelanggan->count(),
            'pelanggan_aktif' => $pelanggan->where('total_transaksi', '>', 0)->count(),
        ];

        return ['list' => $pelanggan, 'summary' => $summary];
    }

    private function getLaporanProdukTerlaris($idSekolah, $tanggalMulai, $tanggalAkhir)
    {
        $query = DetailPenjualan::join('tb_penjualan', 'tb_detail_penjualan.id_penjualan', '=', 'tb_penjualan.id_penjualan')
            ->join('tb_barang', 'tb_detail_penjualan.id_barang', '=', 'tb_barang.id_barang')
            ->where('tb_penjualan.is_delete', 0)
            ->where('tb_barang.is_delete', 0)
            ->whereBetween('tb_penjualan.tanggal_penjualan', [$tanggalMulai . ' 00:00:00', $tanggalAkhir . ' 23:59:59']);

        if ($idSekolah) {
            $query->where('tb_penjualan.id_sekolah', $idSekolah);
        }

        $produk = $query->selectRaw('tb_barang.id_barang, tb_barang.nama, tb_barang.barcode, tb_barang.harga_jual, SUM(tb_detail_penjualan.jumlah_barang) as total_terjual, SUM(tb_detail_penjualan.subtotal) as total_penjualan')
            ->groupBy('tb_barang.id_barang', 'tb_barang.nama', 'tb_barang.barcode', 'tb_barang.harga_jual')
            ->orderBy('total_terjual', 'desc')
            ->get()
            ->map(function ($p) {
                return [
                    'id_barang' => $p->id_barang,
                    'barcode' => $p->barcode,
                    'nama' => $p->nama,
                    'harga_jual' => (float) $p->harga_jual,
                    'total_terjual' => (int) $p->total_terjual,
                    'total_penjualan' => (float) $p->total_penjualan,
                ];
            });

        $summary = [
            'total_produk_terjual' => (int) $produk->sum('total_terjual'),
            'total_omzet' => (float) $produk->sum('total_penjualan'),
        ];

        return ['list' => $produk, 'summary' => $summary];
    }

    private function getLaporanLabaRugi($idSekolah, $tanggalMulai, $tanggalAkhir)
    {
        $query = DetailPenjualan::join('tb_penjualan', 'tb_detail_penjualan.id_penjualan', '=', 'tb_penjualan.id_penjualan')
            ->where('tb_penjualan.is_delete', 0)
            ->whereBetween('tb_penjualan.tanggal_penjualan', [$tanggalMulai . ' 00:00:00', $tanggalAkhir . ' 23:59:59']);

        if ($idSekolah) {
            $query->where('tb_penjualan.id_sekolah', $idSekolah);
        }

        $harian = $query->selectRaw('DATE(tb_penjualan.tanggal_penjualan) as tanggal, SUM(tb_detail_penjualan.subtotal) as omzet, SUM(tb_detail_penjualan.jumlah_barang * tb_detail_penjualan.harga_beli) as hpp')
            ->groupBy('tanggal')
            ->orderBy('tanggal')
            ->get()
            ->map(function ($r) {
                $omzet = (float) $r->omzet;
                $hpp = (float) $r->hpp;
                return [
                    'tanggal' => $r->tanggal,
                    'omzet' => $omzet,
                    'hpp' => $hpp,
                    'laba_kotor' => $omzet - $hpp,
                    'margin' => $omzet > 0 ? round(($omzet - $hpp) / $omzet * 100, 1) : 0,
                ];
            });

        $summary = [
            'total_omzet' => (float) $harian->sum('omzet'),
            'total_hpp' => (float) $harian->sum('hpp'),
            'total_laba' => (float) $harian->sum('laba_kotor'),
        ];

        return ['list' => $harian, 'summary' => $summary];
    }

    public function export(Request $request)
    {
        $user = auth()->user();
        $isSuperAdmin = $user->isSuperAdmin();
        $idSekolah = $request->session()->get('id_sekolah') ?? $user->id_sekolah;
        $filterSekolah = $isSuperAdmin ? $request->input('id_sekolah') : $idSekolah;

        $jenis = $request->input('jenis', 'penjualan');
        $tanggalMulai = $request->input('tanggal_mulai', Carbon::today()->format('Y-m-d'));
        $tanggalAkhir = $request->input('tanggal_akhir', Carbon::today()->format('Y-m-d'));

        $data = match ($jenis) {
            'pembelian' => $this->getLaporanPembelian($filterSekolah, $tanggalMulai, $tanggalAkhir),
            'stok' => $this->getLaporanStok($filterSekolah),
            'pelanggan' => $this->getLaporanPelanggan($filterSekolah, $tanggalMulai, $tanggalAkhir),
            'produk_terlaris' => $this->getLaporanProdukTerlaris($filterSekolah, $tanggalMulai, $tanggalAkhir),
            'laba_rugi' => $this->getLaporanLabaRugi($filterSekolah, $tanggalMulai, $tanggalAkhir),
            default => $this->getLaporanPenjualan($filterSekolah, $tanggalMulai, $tanggalAkhir),
        };

        $filename = "laporan-{$jenis}-{$tanggalMulai}_{$tanggalAkhir}.csv";

        return response()->streamDownload(function () use ($data) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // BOM agar Excel baca UTF-8
            $list = $data['list'] instanceof \Illuminate\Support\Collection ? $data['list']->all() : (array) $data['list'];
            if (!empty($list)) {
                $first = reset($list);
                $firstArr = is_array($first) ? $first : (array) $first;
                fputcsv($out, array_keys($firstArr), ';');
                foreach ($list as $row) {
                    $arr = is_array($row) ? $row : (array) $row;
                    fputcsv($out, array_map(fn ($v) => is_array($v) ? json_encode($v) : $v, array_values($arr)), ';');
                }
            } else {
                fputcsv($out, ['Tidak ada data pada periode ini'], ';');
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}