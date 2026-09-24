<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tb_sekolah', function (Blueprint $table) {
            $table->id('id_sekolah');
            $table->string('kode_sekolah');
            $table->string('nama_sekolah');
            $table->text('alamat_sekolah')->nullable();
            $table->string('website')->nullable();
            $table->boolean('is_active')->default(1);
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tb_sekolah');
    }
};