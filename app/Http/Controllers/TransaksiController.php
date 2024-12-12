<?php

namespace App\Http\Controllers;

use App\Models\Transaksi;
use App\Models\Barang;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class TransaksiController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $page = $request->input('page', 1);
        $query = $request->input('query');
        $isPaginated = filter_var($request->input('isPaginated', true), FILTER_VALIDATE_BOOLEAN);
        $sortOrder = $request->input('sort_order', 'desc');

        if (!in_array($sortOrder, ['asc', 'desc'])) {
            $sortOrder = 'desc';
        }

        $transaksis = Transaksi::with('barang')
            ->when($query, function ($queryBuilder) use ($query) {
                return $queryBuilder->whereHas('barang', function ($q) use ($query) {
                    $q->whereRaw('LOWER(nama_barang) LIKE ?', ["%{$query}%"]);
                });
            })
            ->orderBy('tanggal_transaksi', $sortOrder);

        if ($isPaginated) {
            $transaksis = $transaksis->paginate($perPage, ['*'], 'page', $page);
            return response()->json($transaksis);
        } else {
            $transaksis = $transaksis->get();
            return response()->json($transaksis);
        }
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'barang_id' => 'required|exists:barang,id',
            'jumlah_terjual' => 'required|integer|min:1',
            'tanggal_transaksi' => 'required|date',
        ], [
            'barang_id.required' => 'ID barang harus diisi.',
            'barang_id.exists' => 'ID barang yang dipilih tidak valid.',
            'jumlah_terjual.required' => 'Jumlah terjual harus diisi.',
            'jumlah_terjual.integer' => 'Jumlah terjual harus berupa angka bulat.',
            'jumlah_terjual.min' => 'Jumlah terjual harus lebih dari atau sama dengan 1.',
            'tanggal_transaksi.required' => 'Tanggal transaksi harus diisi.',
            'tanggal_transaksi.date' => 'Tanggal transaksi harus berupa tanggal yang valid.',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $barang = Barang::findOrFail($request->barang_id);

        if ($barang->stok < $request->jumlah_terjual) {
            return response()->json(['message' => 'Stok tidak cukup.'], 400);
        }

        $transaksi = Transaksi::create($request->all());

        $barang->stok -= $request->jumlah_terjual;
        $barang->save();

        return response()->json($transaksi, 201);
    }

    public function show($id)
    {
        try {
            $transaksi = Transaksi::findOrFail($id);
            return response()->json($transaksi);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Transaksi not found.'], 404);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $transaksi = Transaksi::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'barang_id' => 'required|exists:barang,id',
                'jumlah_terjual' => 'required|integer|min:1',
                'tanggal_transaksi' => 'required|date',
            ], [
                'barang_id.required' => 'ID barang harus diisi.',
                'barang_id.exists' => 'ID barang yang dipilih tidak valid.',
                'jumlah_terjual.required' => 'Jumlah terjual harus diisi.',
                'jumlah_terjual.integer' => 'Jumlah terjual harus berupa angka bulat.',
                'jumlah_terjual.min' => 'Jumlah terjual harus lebih dari 0.',
                'tanggal_transaksi.required' => 'Tanggal transaksi harus diisi.',
                'tanggal_transaksi.date' => 'Tanggal transaksi harus berupa tanggal yang valid.',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $oldSoldCount = $transaksi->jumlah_terjual;

            $barang = Barang::findOrFail($request->barang_id);

            if ($request->jumlah_terjual > $oldSoldCount) {
                $stockCorrection = $request->jumlah_terjual - $oldSoldCount;
                if ($barang->stok < $stockCorrection) {
                    return response()->json(['message' => 'Stok tidak cukup.'], 400);
                }
                $barang->stok -= $stockCorrection;
            } else {
                $stockCorrection = $oldSoldCount - $request->jumlah_terjual;
                $barang->stok += $stockCorrection;
            }

            $barang->save();
            $transaksi->update($request->all());

            return response()->json($transaksi);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Transaksi not found.'], 404);
        }
    }

    public function destroy($id)
    {
        try {
            $transaksi = Transaksi::findOrFail($id);
            $barangId = $transaksi->barang_id;
            $jumlahTerjual = $transaksi->jumlah_terjual;

            $transaksi->delete();

            $barang = Barang::findOrFail($barangId);
            $barang->stok += $jumlahTerjual;
            $barang->save();

            return response()->json(['message' => 'Transaksi berhasil dihapus dan stok barang diperbarui.']);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Transaksi not found.'], 404);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Terjadi kesalahan: ' . $e->getMessage()], 500);
        }
    }
}
