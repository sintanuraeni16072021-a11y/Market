<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tb_pembelian', function (Blueprint $table) {
            $table->id('id_pembelian');

            $table->unsignedBigInteger('id_supplier');
            $table->unsignedBigInteger('id_user');

            $table->dateTime('tanggal');
            $table->decimal('total', 15, 2)->default(0);

            $table->foreign('id_supplier')
                  ->references('id_supplier')
                  ->on('tb_supplier');

            $table->foreign('id_user')
                  ->references('id_user')
                  ->on('tb_user');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tb_pembelian');
    }
};