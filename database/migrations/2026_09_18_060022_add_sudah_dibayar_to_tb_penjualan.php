<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tb_penjualan', function (Blueprint $table) {
            $table->decimal('sudah_dibayar', 14, 2)->default(0)->after('total_bayar');
        });
    }

    public function down(): void
    {
        Schema::table('tb_penjualan', function (Blueprint $table) {
            $table->dropColumn('sudah_dibayar');
        });
    }
};
