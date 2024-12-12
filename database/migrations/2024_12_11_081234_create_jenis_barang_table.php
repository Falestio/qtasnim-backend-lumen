<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('jenis_barang', function (Blueprint $table) {
            $table->id('jenis_barang_id');
            $table->string('jenis_barang');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('jenis_barang');
    }
};
