<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tb_penjualan', function (Blueprint $table) {
            $table->id('id_penjualan');

            $table->unsignedBigInteger('id_pelanggan')->nullable();
            $table->unsignedBigInteger('id_user');

            $table->dateTime('tanggal');
            $table->decimal('total', 15, 2)->default(0);

            $table->foreign('id_pelanggan')
                  ->references('id_pelanggan')
                  ->on('tb_pelanggan');

            $table->foreign('id_user')
                  ->references('id_user')
                  ->on('tb_user');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tb_penjualan');
    }
};