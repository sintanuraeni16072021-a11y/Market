<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tb_pelanggan', function (Blueprint $table) {
            $table->id('id_pelanggan');

            $table->unsignedBigInteger('id_kelompok_pelanggan')->nullable();

            $table->string('nama');
            $table->string('no_telepon')->nullable();
            $table->text('alamat')->nullable();

            $table->timestamp('created_at')->nullable();

            $table->foreign('id_kelompok_pelanggan')
                  ->references('id_kelompok_pelanggan')
                  ->on('tb_kelompok_pelanggan');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tb_pelanggan');
    }
};