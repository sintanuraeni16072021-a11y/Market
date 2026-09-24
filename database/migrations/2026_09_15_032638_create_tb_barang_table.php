<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tb_barang', function (Blueprint $table) {

            $table->id('id_barang');

            $table->unsignedBigInteger('id_sekolah');
            $table->string('barcode');
            $table->string('nama');

            $table->unsignedBigInteger('id_kategori');
            $table->unsignedBigInteger('id_kelompok_kategori');
            $table->unsignedBigInteger('id_supplier');

            $table->string('satuan')->nullable();

            $table->decimal('harga_beli', 15, 2);
            $table->decimal('harga_jual', 15, 2);

            $table->integer('stok')->default(0);

            $table->boolean('is_active')->default(1);

            $table->timestamp('created_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();

            $table->timestamp('updated_at')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->timestamp('deleted_at')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();

            $table->boolean('is_delete')->default(0);

            // RELASI
            $table->foreign('id_sekolah')
                  ->references('id_sekolah')
                  ->on('tb_sekolah');

            $table->foreign('id_kategori')
                  ->references('id_kategori')
                  ->on('tb_kategori');

            $table->foreign('id_kelompok_kategori')
                  ->references('id_kelompok')
                  ->on('tb_kelompok_kategori');

            $table->foreign('id_supplier')
                  ->references('id_supplier')
                  ->on('tb_supplier');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tb_barang');
    }
};      