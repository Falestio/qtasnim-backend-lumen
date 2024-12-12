<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Barang extends Model
{
    use HasFactory;

    protected $table = 'barang';
    protected $primaryKey = 'id';
    protected $fillable = ['nama_barang', 'stok', 'jenis_barang_id'];

    public function transaksi()
    {
        return $this->hasMany(Transaksi::class, 'barang_id', 'id');
    }

    public function jenisBarang()
    {
        return $this->belongsTo(JenisBarang::class, 'jenis_barang_id', 'jenis_barang_id');
    }
}
