<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tb_kategori', function (Blueprint $table) {
            $table->unsignedBigInteger('id_sekolah')->default(1)->after('id_kategori');

            $table->foreign('id_sekolah')
                ->references('id_sekolah')
                ->on('tb_sekolah');
        });
    }

    public function down(): void
    {
        Schema::table('tb_kategori', function (Blueprint $table) {
            $table->dropForeign(['id_sekolah']);
            $table->dropColumn('id_sekolah');
        });
    }
};