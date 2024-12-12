<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('barang', function (Blueprint $table) {
            $table->id();
            $table->string('nama_barang');
            $table->integer('stok');
            $table->unsignedBigInteger('jenis_barang_id');
            $table->timestamps();

            $table->foreign('jenis_barang_id')->references('jenis_barang_id')->on('jenis_barang')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('barang');
    }
};
