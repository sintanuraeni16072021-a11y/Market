<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tb_supplier', function (Blueprint $table) {
            $table->id('id_supplier');

            $table->unsignedBigInteger('id_sekolah');
            $table->string('nama');
            $table->string('no_telepon')->nullable();
            $table->text('alamat_supplier')->nullable();

            $table->timestamp('created_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamp('deleted_at')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->boolean('is_delete')->default(0);

            $table->foreign('id_sekolah')
                  ->references('id_sekolah')
                  ->on('tb_sekolah');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tb_supplier');
    }
};