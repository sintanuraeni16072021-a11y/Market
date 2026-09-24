<?php

namespace App\Http\Controllers;

use App\Models\Penjualan;
use App\Models\Barang;
use App\Models\Pelanggan;
use App\Models\DetailPenjualan;
use App\Models\Sekolah;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();

        // Redirect kasir directly to POS page
        if ($user && $user->isKasir()) {
            return redirect()->route('kasir');
        }

        $idSekolah = $request->session()->get('id_sekolah') ?? $user->id_sekolah;
        $isSuperAdmin = $user && $user->isSuperAdmin();
        $viewMode = $request->input('mode', $isSuperAdmin ? 'all' : 'school'); // 'all' or 'school'

        $today = Carbon::today();

        $queryPenjualan = Penjualan::where('is_delete', 0);
        $queryDetail = DetailPenjualan::join('tb_penjualan', 'tb_detail_penjualan.id_penjualan', '=', 'tb_penjualan.id_penjualan')
            ->where('tb_penjualan.is_delete', 0);
        $queryPelanggan = Pelanggan::where('is_delete', 0);

        if (!$isSuperAdmin || $viewMode === 'school') {
            $queryPenjualan->where('id_sekolah', $idSekolah);
            $queryDetail->where('tb_penjualan.id_sekolah', $idSekolah);
            $queryPelanggan->where('id_sekolah', $idSekolah);
        }

        // Stats for today
        $penjualanHariIni = (clone $queryPenjualan)->whereDate('tanggal_penjualan', $today)->sum('total_bayar');
        $transaksiHariIni = (clone $queryPenjualan)->whereDate('tanggal_penjualan', $today)->count();
        $produkTerjual = (clone $queryDetail)->whereDate('tb_penjualan.tanggal_penjualan', $today)->sum('tb_detail_penjualan.jumlah_barang');
        $pelangganBaru = (clone $queryPelanggan)->whereDate('created_at', $today)->count();

        // Total omset all-time
        $totalOmset = (clone $queryPenjualan)->sum('total_bayar');
        $totalProduk = Barang::where('is_delete', 0)
            ->when(!$isSuperAdmin || $viewMode === 'school', fn($q) => $q->where('id_sekolah', $idSekolah))
            ->count();

        // Chart data - last 7 days
        $chartData = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $total = (clone $queryPenjualan)->whereDate('tanggal_penjualan', $date)->sum('total_bayar');
            
            $chartData[] = [
                'tanggal' => $date->format('d M'),
                'total' => (float) $total,
            ];
        }

        // Recent transactions
        $transaksiTerbaru = (clone $queryPenjualan)
            ->with(['user:id_user,nama_lengkap', 'sekolah:id_sekolah,nama_sekolah'])
            ->orderBy('tanggal_penjualan', 'desc')
            ->limit(8)
            ->get()
            ->map(function ($trx) {
                return [
                    'id_penjualan' => $trx->id_penjualan,
                    'nomor_faktur' => $trx->nomor_faktur,
                    'tanggal_penjualan' => $trx->tanggal_penjualan,
                    'total_bayar' => (float) $trx->total_bayar,
                    'nama_lengkap' => $trx->user->nama_lengkap ?? '-',
                    'nama_sekolah' => $trx->sekolah->nama_sekolah ?? '-',
                ];
            });

        // Top Selling Products
        $topProduk = (clone $queryDetail)
            ->join('tb_barang', 'tb_detail_penjualan.id_barang', '=', 'tb_barang.id_barang')
            ->selectRaw('tb_barang.nama as nama_barang, SUM(tb_detail_penjualan.jumlah_barang) as total_qty, SUM(tb_detail_penjualan.subtotal) as total_nominal')
            ->groupBy('tb_barang.id_barang', 'tb_barang.nama')
            ->orderByDesc('total_qty')
            ->limit(5)
            ->get();

        // Comparison per school for super admin
        $sekolahStats = [];
        if ($isSuperAdmin) {
            $sekolahList = Sekolah::where('is_active', 1)->get();
            foreach ($sekolahList as $sek) {
                $omsetSekolah = Penjualan::where('id_sekolah', $sek->id_sekolah)->where('is_delete', 0)->sum('total_bayar');
                $trxSekolah = Penjualan::where('id_sekolah', $sek->id_sekolah)->where('is_delete', 0)->count();
                $sekolahStats[] = [
                    'id_sekolah' => $sek->id_sekolah,
                    'nama_sekolah' => $sek->nama_sekolah,
                    'kode_sekolah' => $sek->kode_sekolah,
                    'omset' => (float)$omsetSekolah,
                    'total_transaksi' => $trxSekolah,
                ];
            }
        }

        return Inertia::render('Dashboard', [
            'stats' => [
                'penjualan_hari_ini' => (float) $penjualanHariIni,
                'transaksi_hari_ini' => $transaksiHariIni,
                'produk_terjual' => (int) $produkTerjual,
                'pelanggan_baru' => $pelangganBaru,
                'total_omset' => (float) $totalOmset,
                'total_produk' => $totalProduk,
            ],
            'chartData' => $chartData,
            'transaksiTerbaru' => $transaksiTerbaru,
            'topProduk' => $topProduk,
            'sekolahStats' => $sekolahStats,
            'viewMode' => $viewMode,
            'isSuperAdmin' => $isSuperAdmin,
        ]);
    }
}