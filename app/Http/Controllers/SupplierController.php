<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;

class SupplierController extends Controller
{
    public function index(Request $request)
    {
        $idSekolah = $request->session()->get('id_sekolah');
        
        $search = $request->input('search');
        $perPage = $request->input('per_page', 15);

        $query = Supplier::where('id_sekolah', $idSekolah)
            ->where('is_delete', 0);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                  ->orWhere('no_telepon', 'like', "%{$search}%");
            });
        }

        $supplierList = $query->orderBy('nama')
            ->paginate($perPage)
            ->withQueryString()
            ->through(function ($supplier) {
                return [
                    'id_supplier' => $supplier->id_supplier,
                    'nama' => $supplier->nama,
                    'no_telepon' => $supplier->no_telepon,
                    'alamat_supplier' => $supplier->alamat_supplier,
                ];
            });

        return Inertia::render('Supplier/Index', [
            'supplierList' => $supplierList,
            'filters' => ['search' => $search],
        ]);
    }

    public function store(Request $request)
    {
        $idSekolah = $request->session()->get('id_sekolah');
        $idUser = auth()->id();

        $request->validate([
            'nama' => 'required|string|max:100|unique:tb_supplier,nama,NULL,id_supplier,id_sekolah,' . $idSekolah,
            'no_telepon' => 'nullable|string|max:20',
            'alamat_supplier' => 'nullable|string',
        ]);

        Supplier::create([
            'id_sekolah' => $idSekolah,
            'nama' => $request->nama,
            'no_telepon' => $request->no_telepon,
            'alamat_supplier' => $request->alamat_supplier,
            'created_at' => Carbon::now(),
            'created_by' => $idUser,
            'is_delete' => 0,
        ]);

        return redirect()->route('supplier')->with('success', 'Supplier berhasil ditambahkan');
    }

    public function update(Request $request, $id)
    {
        $idSekolah = $request->session()->get('id_sekolah');
        $idUser = auth()->id();

        $supplier = Supplier::where('id_supplier', $id)
            ->where('id_sekolah', $idSekolah)
            ->where('is_delete', 0)
            ->firstOrFail();

        $request->validate([
            'nama' => 'required|string|max:100|unique:tb_supplier,nama,' . $id . ',id_supplier,id_sekolah,' . $idSekolah,
            'no_telepon' => 'nullable|string|max:20',
            'alamat_supplier' => 'nullable|string',
        ]);

        $supplier->update([
            'nama' => $request->nama,
            'no_telepon' => $request->no_telepon,
            'alamat_supplier' => $request->alamat_supplier,
            'updated_at' => Carbon::now(),
            'updated_by' => $idUser,
        ]);

        return redirect()->route('supplier')->with('success', 'Supplier berhasil diperbarui');
    }

    public function destroy(Request $request, $id)
    {
        $idSekolah = $request->session()->get('id_sekolah');
        $idUser = auth()->id();

        $supplier = Supplier::where('id_supplier', $id)
            ->where('id_sekolah', $idSekolah)
            ->where('is_delete', 0)
            ->firstOrFail();

        // Check if supplier has barang or pembelian
        $barangCount = \App\Models\Barang::where('id_supplier', $id)
            ->where('id_sekolah', $idSekolah)
            ->where('is_delete', 0)
            ->count();

        $pembelianCount = \App\Models\Pembelian::where('id_supplier', $id)
            ->where('id_sekolah', $idSekolah)
            ->where('is_delete', 0)
            ->count();

        if ($barangCount > 0 || $pembelianCount > 0) {
            return redirect()->route('supplier')->with('error', 'Supplier tidak dapat dihapus karena masih memiliki produk atau pembelian');
        }

        $supplier->update([
            'is_delete' => 1,
            'deleted_at' => Carbon::now(),
            'deleted_by' => $idUser,
        ]);

        return redirect()->route('supplier')->with('success', 'Supplier berhasil dihapus');
    }
}