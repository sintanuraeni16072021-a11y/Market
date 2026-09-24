<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tb_kelompok_pelanggan', function (Blueprint $table) {
            $table->unsignedBigInteger('id_sekolah')->after('id_kelompok_pelanggan');

            $table->foreign('id_sekolah')
                ->references('id_sekolah')
                ->on('tb_sekolah');
        });

        Schema::table('tb_pelanggan', function (Blueprint $table) {
            $table->unsignedBigInteger('id_sekolah')->after('id_pelanggan');
            $table->string('nama_pelanggan', 150)->after('id_kelompok_pelanggan');
            $table->string('telepon', 20)->nullable()->after('nama_pelanggan');
            $table->unsignedBigInteger('created_by')->nullable()->after('created_at');
            $table->timestamp('updated_at')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamp('deleted_at')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->boolean('is_delete')->default(0);

            $table->foreign('id_sekolah')
                ->references('id_sekolah')
                ->on('tb_sekolah');
        });

        Schema::table('tb_pelanggan', function (Blueprint $table) {
            $table->dropColumn(['nama', 'no_telepon']);
        });
    }

    public function down(): void
    {
        Schema::table('tb_pelanggan', function (Blueprint $table) {
            $table->dropForeign(['id_sekolah']);
            $table->dropColumn([
                'id_sekolah', 'nama_pelanggan', 'telepon', 'created_by',
                'updated_at', 'updated_by', 'deleted_at', 'deleted_by', 'is_delete',
            ]);
            $table->string('nama')->after('id_kelompok_pelanggan');
            $table->string('no_telepon')->nullable()->after('nama');
        });

        Schema::table('tb_kelompok_pelanggan', function (Blueprint $table) {
            $table->dropForeign(['id_sekolah']);
            $table->dropColumn('id_sekolah');
        });
    }
};
