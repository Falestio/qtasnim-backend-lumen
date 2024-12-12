<?php

namespace App\Http\Controllers;

use App\Models\Barang;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class BarangController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $page = $request->input('page', 1);
        $query = $request->input('query');
        $sortBy = $request->input('sort_by', 'updated_at');
        $sortOrder = $request->input('sort_order', 'desc');
        $isPaginated = filter_var($request->input('isPaginated', true), FILTER_VALIDATE_BOOLEAN);

        $barangs = Barang::with('jenisBarang')
            ->when($query, function ($queryBuilder) use ($query) {
                return $queryBuilder->whereRaw('LOWER(nama_barang) LIKE ?', ["%{$query}%"]);
            });

        if ($sortBy === 'nama_barang') {
            $barangs->orderByRaw('LOWER(nama_barang) ' . strtoupper($sortOrder));
        } elseif ($sortBy === 'created_at') {
            $barangs->orderBy('created_at', strtoupper($sortOrder));
        } else {
            $barangs->orderBy('created_at', 'desc');
        }

        if ($isPaginated) {
            $barangs = $barangs->paginate($perPage, ['*'], 'page', $page);
            return response()->json($barangs);
        } else {
            $barangs = $barangs->get();
            return response()->json($barangs);
        }
    }


    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nama_barang' => 'required',
            'stok' => 'required|integer',
            'jenis_barang_id' => 'required|exists:jenis_barang,jenis_barang_id',
        ], [
            'nama_barang.required' => 'Nama barang harus diisi.',
            'stok.required' => 'Stok harus diisi.',
            'stok.integer' => 'Stok harus berupa angka bulat.',
            'jenis_barang_id.required' => 'Jenis barang harus dipilih.',
            'jenis_barang_id.exists' => 'Jenis barang yang dipilih tidak valid.',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $barang = Barang::create($request->all());

        return response()->json($barang, 201);
    }

    public function show($id)
    {
        try {
            $barang = Barang::findOrFail($id);
            return response()->json($barang);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Barang not found.'], 404);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $barang = Barang::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'nama_barang' => 'required',
                'stok' => 'required|integer',
                'jenis_barang_id' => 'required|exists:jenis_barang,jenis_barang_id',
            ], [
                'nama_barang.required' => 'Nama barang harus diisi.',
                'stok.required' => 'Stok harus diisi.',
                'stok.integer' => 'Stok harus berupa angka bulat.',
                'jenis_barang_id.required' => 'Jenis barang harus dipilih.',
                'jenis_barang_id.exists' => 'Jenis barang yang dipilih tidak valid.',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $barang->update($request->all());

            return response()->json($barang);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Barang not found.'], 404);
        }
    }

    public function destroy(Request $request, $id)
    {
        try {
            $barang = Barang::findOrFail($id);

            $isForce = $request->input('isForce', false);

            if (!$isForce) {
                $transaksiTerkait = $barang->transaksi;

                if ($transaksiTerkait->isNotEmpty()) {
                    return response()->json([
                        'message' => 'Ada transaksi yang berkaitan dengan barang ini.',
                        'transaksi_terkait' => $transaksiTerkait
                    ], 409);
                }
            }

            $barang->delete();
            return response()->json(['message' => 'Barang berhasil dihapus.']);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Barang not found.'], 404);
        }
    }
}
