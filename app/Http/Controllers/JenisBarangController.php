<?php

namespace App\Http\Controllers;

use App\Models\JenisBarang;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class JenisBarangController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $page = $request->input('page', 1);
        $isPaginated = filter_var($request->input('isPaginated', true), FILTER_VALIDATE_BOOLEAN);

        if ($isPaginated) {
            $jenisBarangs = JenisBarang::paginate($perPage, ['*'], 'page', $page);
            return response()->json($jenisBarangs);
        } else {
            $jenisBarangs = JenisBarang::all();
            return response()->json($jenisBarangs);
        }
    }

    public function jenisBarangTerjual(Request $request)
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $sortOrder = $request->input('sort_order', 'desc');

        if (!in_array($sortOrder, ['asc', 'desc'])) {
            $sortOrder = 'desc';
        }

        $jenisBarangList = JenisBarang::with(['barang.transaksi' => function ($query) use ($startDate, $endDate) {
            if ($startDate) {
                $query->where('tanggal_transaksi', '>=', $startDate);
            }
            if ($endDate) {
                $query->where('tanggal_transaksi', '<=', $endDate);
            }
        }])
            ->select('jenis_barang_id', 'jenis_barang')
            ->get();

        $result = $jenisBarangList->map(function ($jenis) {
            $totalSold = $jenis->barang->sum(function ($barang) {
                return $barang->transaksi->sum('jumlah_terjual');
            });

            return [
                'jenis_barang' => $jenis->jenis_barang,
                'total_terjual' => $totalSold,
            ];
        });

        if ($sortOrder === 'asc') {
            $result = $result->sortBy('total_terjual');
        } else {
            $result = $result->sortByDesc('total_terjual');
        }

        $result = $result->values();

        return response()->json($result);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'jenis_barang' => 'required',
        ], [
            'jenis_barang.required' => 'Jenis barang harus diisi.',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $jenisBarang = JenisBarang::create($request->all());

        return response()->json($jenisBarang, 201);
    }

    public function show($id)
    {
        try {
            $jenisBarang = JenisBarang::findOrFail($id);
            return response()->json($jenisBarang);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Jenis barang not found.'], 404);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $jenisBarang = JenisBarang::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'jenis_barang' => 'required',
            ], [
                'jenis_barang.required' => 'Jenis barang harus diisi.',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $jenisBarang->update($request->all());
            return response()->json($jenisBarang);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Jenis barang not found.'], 404);
        }
    }

    public function destroy(Request $request, $id)
    {
        try {
            $jenisBarang = JenisBarang::findOrFail($id);
            $isForce = $request->input('isForce', false);

            if (!$isForce) {
                $barangTerkait = $jenisBarang->barang;

                if ($barangTerkait->isNotEmpty()) {
                    return response()->json([
                        'message' => 'Ada barang yang berkaitan dengan jenis barang ini.',
                        'barang_terkait' => $barangTerkait
                    ], 409);
                }
            }

            $jenisBarang->delete();
            return response()->json(['message' => 'Jenis barang berhasil dihapus.']);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Jenis barang not found.'], 404);
        }
    }
}
