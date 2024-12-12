<?php

use App\Http\Controllers\JenisBarangController;
use App\Http\Controllers\BarangController;
use App\Http\Controllers\TransaksiController;

/** @var \Laravel\Lumen\Routing\Router $router */

$router->group(['prefix' => 'api/v1'], function () use ($router) {

    $router->get('/barang', 'BarangController@index');
    $router->post('/barang', 'BarangController@store');
    $router->get('/barang/{id}', 'BarangController@show');
    $router->put('/barang/{id}', 'BarangController@update');
    $router->delete('/barang/{id}', 'BarangController@destroy');

    $router->get('/jenis_barang', 'JenisBarangController@index');
    $router->get('/jenis_barang_terjual', 'JenisBarangController@jenisBarangTerjual');
    $router->post('/jenis_barang', 'JenisBarangController@store');
    $router->get('/jenis_barang/{id}', 'JenisBarangController@show');
    $router->put('/jenis_barang/{id}', 'JenisBarangController@update');
    $router->delete('/jenis_barang/{id}', 'JenisBarangController@destroy');
    
    $router->get('/transaksi', 'TransaksiController@index');
    $router->post('/transaksi', 'TransaksiController@store');
    $router->get('/transaksi/{id}', 'TransaksiController@show');
    $router->put('/transaksi/{id}', 'TransaksiController@update');
    $router->delete('/transaksi/{id}', 'TransaksiController@destroy');
});
