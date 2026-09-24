<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tb_user', function (Blueprint $table) {
            $table->id('id_user');

            $table->unsignedBigInteger('id_sekolah');
            $table->unsignedBigInteger('id_role');

            $table->string('username');
            $table->string('password');
            $table->string('nama_lengkap')->nullable();

            $table->boolean('is_active')->default(1);

            $table->rememberToken();

            $table->timestamp('created_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamp('deleted_at')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();

            $table->foreign('id_sekolah')
                  ->references('id_sekolah')
                  ->on('tb_sekolah');

            $table->foreign('id_role')
                  ->references('id_role')
                  ->on('roles');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tb_user');
    }
};