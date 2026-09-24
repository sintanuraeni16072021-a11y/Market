<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tb_retur', function (Blueprint $table) {
            $table->id('id_retur');
            $table->unsignedBigInteger('id_sekolah');
            $table->enum('tipe', ['penjualan', 'pembelian']);
            $table->unsignedBigInteger('id_referensi')->comment('id_penjualan atau id_pembelian');
            $table->string('nomor_retur', 50)->unique();
            $table->dateTime('tanggal_retur');
            $table->decimal('total_nilai', 14, 2)->default(0);
            $table->text('alasan')->nullable();
            $table->unsignedBigInteger('id_user');
            $table->timestamp('created_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamp('deleted_at')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->boolean('is_delete')->default(0);

            $table->foreign('id_sekolah')->references('id_sekolah')->on('tb_sekolah');
            $table->foreign('id_user')->references('id_user')->on('tb_user');
            $table->index(['id_sekolah', 'tipe']);
        });

        Schema::create('tb_detail_retur', function (Blueprint $table) {
            $table->id('id_detail_retur');
            $table->unsignedBigInteger('id_retur');
            $table->unsignedBigInteger('id_barang');
            $table->integer('jumlah');
            $table->decimal('harga', 12, 2)->default(0);
            $table->decimal('subtotal', 14, 2)->default(0);

            $table->foreign('id_retur')->references('id_retur')->on('tb_retur')->onDelete('cascade');
            $table->foreign('id_barang')->references('id_barang')->on('tb_barang');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tb_detail_retur');
        Schema::dropIfExists('tb_retur');
    }
};
