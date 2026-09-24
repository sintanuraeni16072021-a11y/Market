<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_sekolah')->nullable();
            $table->unsignedBigInteger('id_user')->nullable();
            $table->string('aksi', 50);
            $table->string('modul', 50);
            $table->text('deskripsi')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->index(['id_sekolah', 'created_at']);
            $table->index('id_user');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
