<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // tb_penjualan: lengkapi skema sesuai kebutuhan model & controller
        Schema::table('tb_penjualan', function (Blueprint $table) {
            $table->unsignedBigInteger('id_sekolah')->after('id_penjualan');
            $table->string('nomor_faktur', 50)->unique()->after('id_user');
            $table->dateTime('tanggal_penjualan')->nullable()->after('nomor_faktur');
            $table->decimal('total_faktur', 14, 2)->default(0);
            $table->decimal('total_bayar', 14, 2)->default(0);
            $table->decimal('kembalian', 14, 2)->default(0);
            $table->enum('status_pembayaran', ['sudah bayar', 'belum bayar'])->default('belum bayar');
            $table->enum('jenis_transaksi', ['tunai', 'kredit', 'qris'])->default('tunai');
            $table->string('cara_bayar', 50)->nullable();
            $table->text('note')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamp('deleted_at')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->boolean('is_delete')->default(0);

            $table->foreign('id_sekolah')
                ->references('id_sekolah')
                ->on('tb_sekolah');
        });

        Schema::table('tb_penjualan', function (Blueprint $table) {
            $table->dropColumn(['tanggal', 'total']);
        });

        // tb_detail_penjualan: lengkapi skema sesuai kebutuhan model & controller
        Schema::table('tb_detail_penjualan', function (Blueprint $table) {
            $table->integer('jumlah_barang')->default(1)->after('id_barang');
            $table->decimal('harga_beli', 12, 2)->default(0);
            $table->decimal('harga_jual', 12, 2)->default(0);
            $table->enum('diskon_tipe', ['persen', 'nominal'])->default('persen');
            $table->decimal('diskon_nilai', 12, 2)->default(0);
            $table->decimal('diskon_nominal', 12, 2)->default(0);
        });

        Schema::table('tb_detail_penjualan', function (Blueprint $table) {
            $table->dropColumn(['jumlah', 'harga']);
        });

        // tb_pembelian: lengkapi skema sesuai kebutuhan model & controller
        Schema::table('tb_pembelian', function (Blueprint $table) {
            $table->unsignedBigInteger('id_sekolah')->after('id_pembelian');
            $table->string('nomor_faktur', 50)->unique()->after('id_user');
            $table->dateTime('tanggal_faktur')->nullable()->after('nomor_faktur');
            $table->decimal('total_bayar', 14, 2)->default(0);
            $table->enum('status_pembelian', ['draft', 'selesai'])->default('draft');
            $table->enum('jenis_transaksi', ['tunai', 'kredit'])->default('tunai');
            $table->string('cara_bayar', 50)->nullable();
            $table->text('note')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamp('deleted_at')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->boolean('is_delete')->default(0);

            $table->foreign('id_sekolah')
                ->references('id_sekolah')
                ->on('tb_sekolah');
        });

        Schema::table('tb_pembelian', function (Blueprint $table) {
            $table->dropColumn(['tanggal', 'total']);
        });

        // tb_detail_pembelian: lengkapi skema sesuai kebutuhan model & controller
        Schema::table('tb_detail_pembelian', function (Blueprint $table) {
            $table->string('satuan', 20)->default('pcs')->after('id_barang');
            $table->decimal('harga_beli', 12, 2)->default(0)->after('satuan');
        });

        Schema::table('tb_detail_pembelian', function (Blueprint $table) {
            $table->dropColumn(['harga']);
        });
    }

    public function down(): void
    {
        Schema::table('tb_penjualan', function (Blueprint $table) {
            $table->dropForeign(['id_sekolah']);
            $table->dropColumn([
                'id_sekolah', 'nomor_faktur', 'tanggal_penjualan', 'total_faktur',
                'total_bayar', 'kembalian', 'status_pembayaran', 'jenis_transaksi',
                'cara_bayar', 'note', 'created_at', 'created_by', 'updated_at',
                'updated_by', 'deleted_at', 'deleted_by', 'is_delete',
            ]);
            $table->dateTime('tanggal')->nullable();
            $table->decimal('total', 15, 2)->default(0);
        });

        Schema::table('tb_detail_penjualan', function (Blueprint $table) {
            $table->dropColumn([
                'jumlah_barang', 'harga_beli', 'harga_jual',
                'diskon_tipe', 'diskon_nilai', 'diskon_nominal',
            ]);
            $table->integer('jumlah')->default(1);
            $table->decimal('harga', 15, 2)->default(0);
        });

        Schema::table('tb_pembelian', function (Blueprint $table) {
            $table->dropForeign(['id_sekolah']);
            $table->dropColumn([
                'id_sekolah', 'nomor_faktur', 'tanggal_faktur', 'total_bayar',
                'status_pembelian', 'jenis_transaksi', 'cara_bayar', 'note',
                'created_at', 'created_by', 'updated_at', 'updated_by',
                'deleted_at', 'deleted_by', 'is_delete',
            ]);
            $table->dateTime('tanggal')->nullable();
            $table->decimal('total', 15, 2)->default(0);
        });

        Schema::table('tb_detail_pembelian', function (Blueprint $table) {
            $table->dropColumn(['satuan', 'harga_beli']);
            $table->decimal('harga', 15, 2)->default(0);
        });
    }
};
