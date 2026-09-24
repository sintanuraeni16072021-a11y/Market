<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\KasirController;
use App\Http\Controllers\ProdukController;
use App\Http\Controllers\KategoriController;
use App\Http\Controllers\KelompokKategoriController;
use App\Http\Controllers\PelangganController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\PembelianController;
use App\Http\Controllers\LaporanController;
use App\Http\Controllers\PengaturanController;
use App\Http\Controllers\SekolahController;
use App\Http\Controllers\AktivitasController;
use App\Http\Controllers\BackupController;

Route::get('/', function () {
    return redirect('/login');
});

Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login'])->middleware('throttle:5,1');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

Route::middleware(['auth'])->group(function () {

    // --- Fitur Kasir & POS ---
    // Dapat diakses oleh Kasir, Admin Sekolah, dan Super Admin
    Route::middleware(['role:super admin,admin,kasir'])->group(function () {

        // Halaman Kasir
        Route::get('/kasir', [KasirController::class, 'index'])->name('kasir');

        // Scan Barcode
        Route::get('/kasir/scan/{barcode}', [KasirController::class, 'scan'])
            ->name('kasir.scan');

        // Pembayaran
        Route::post('/kasir/bayar', [KasirController::class, 'bayar'])
            ->name('kasir.bayar');

        // Pelanggan
        Route::get('/pelanggan', [PelangganController::class, 'index'])->name('pelanggan');
        Route::post('/pelanggan', [PelangganController::class, 'store'])->name('pelanggan.store');
        Route::put('/pelanggan/{id}', [PelangganController::class, 'update'])->name('pelanggan.update');
        Route::delete('/pelanggan/{id}', [PelangganController::class, 'destroy'])->name('pelanggan.destroy');

    });


    // --- Fitur Manajemen Toko ---
    // Super Admin & Admin Sekolah
    Route::middleware(['role:super admin,admin'])->group(function () {

        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        // Produk
        Route::get('/produk', [ProdukController::class, 'index'])->name('produk');
        Route::post('/produk', [ProdukController::class, 'store'])->name('produk.store');
        Route::put('/produk/{id}', [ProdukController::class, 'update'])->name('produk.update');
        Route::delete('/produk/{id}', [ProdukController::class, 'destroy'])->name('produk.destroy');
        Route::patch('/produk/{id}/toggle-status', [ProdukController::class, 'toggleStatus'])->name('produk.toggle-status');

        // Kategori
        Route::get('/kategori', [KategoriController::class, 'index'])->name('kategori');
        Route::post('/kategori', [KategoriController::class, 'store'])->name('kategori.store');
        Route::put('/kategori/{id}', [KategoriController::class, 'update'])->name('kategori.update');
        Route::delete('/kategori/{id}', [KategoriController::class, 'destroy'])->name('kategori.destroy');

        // Kelompok Kategori
        Route::get('/kelompok-kategori', [KelompokKategoriController::class, 'index'])->name('kelompok-kategori');
        Route::post('/kelompok-kategori', [KelompokKategoriController::class, 'store'])->name('kelompok-kategori.store');
        Route::put('/kelompok-kategori/{id}', [KelompokKategoriController::class, 'update'])->name('kelompok-kategori.update');
        Route::delete('/kelompok-kategori/{id}', [KelompokKategoriController::class, 'destroy'])->name('kelompok-kategori.destroy');

        // Supplier
        Route::get('/supplier', [SupplierController::class, 'index'])->name('supplier');
        Route::post('/supplier', [SupplierController::class, 'store'])->name('supplier.store');
        Route::put('/supplier/{id}', [SupplierController::class, 'update'])->name('supplier.update');
        Route::delete('/supplier/{id}', [SupplierController::class, 'destroy'])->name('supplier.destroy');

        // Pembelian / Restock
        Route::get('/pembelian', [PembelianController::class, 'index'])->name('pembelian');
        Route::post('/pembelian', [PembelianController::class, 'store'])->name('pembelian.store');
        Route::get('/pembelian/{id}/json', [PembelianController::class, 'detail'])->name('pembelian.json');
        Route::put('/pembelian/{id}', [PembelianController::class, 'update'])->name('pembelian.update');
        Route::delete('/pembelian/{id}', [PembelianController::class, 'destroy'])->name('pembelian.destroy');
        Route::patch('/pembelian/{id}/selesaikan', [PembelianController::class, 'selesaikan'])->name('pembelian.selesai');

        // User Management
        Route::get('/user', [UserController::class, 'index'])->name('user');
        Route::post('/user', [UserController::class, 'store'])->name('user.store');
        Route::put('/user/{id}', [UserController::class, 'update'])->name('user.update');
        Route::delete('/user/{id}', [UserController::class, 'destroy'])->name('user.destroy');
        Route::patch('/user/{id}/reset-password', [UserController::class, 'resetPassword'])->name('user.reset-password');
        Route::patch('/user/{id}/toggle-status', [UserController::class, 'toggleStatus'])->name('user.toggle-status');

        // Laporan
        Route::get('/laporan', [LaporanController::class, 'index'])->name('laporan');
        Route::get('/laporan/export', [LaporanController::class, 'export'])->name('laporan.export');

        // Log aktivitas
        Route::get('/aktivitas', [AktivitasController::class, 'index'])->name('aktivitas');

        // Backup database
        Route::get('/backup', [BackupController::class, 'unduh'])->name('backup.unduh');

        // Pengaturan
        Route::get('/pengaturan', [PengaturanController::class, 'index'])->name('pengaturan');
        Route::put('/pengaturan', [PengaturanController::class, 'update'])->name('pengaturan.update');
    });


    // --- Fitur Khusus Super Admin ---
    // Master Sekolah & Switcher
    Route::middleware(['role:super admin'])->group(function () {

        Route::get('/sekolah', [SekolahController::class, 'index'])->name('sekolah');
        Route::post('/sekolah', [SekolahController::class, 'store'])->name('sekolah.store');
        Route::put('/sekolah/{id}', [SekolahController::class, 'update'])->name('sekolah.update');
        Route::delete('/sekolah/{id}', [SekolahController::class, 'destroy'])->name('sekolah.destroy');
        Route::patch('/sekolah/{id}/toggle-status', [SekolahController::class, 'toggleStatus'])->name('sekolah.toggle-status');
        Route::post('/sekolah/switch', [SekolahController::class, 'switchSekolah'])->name('sekolah.switch');

    });
});

require __DIR__.'/settings.php';