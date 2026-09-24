<?php

namespace App\Http\Controllers;

use App\Models\Sekolah;
use App\Models\User;
use App\Models\Barang;
use App\Models\Penjualan;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;

class SekolahController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        $status = $request->input('status');
        $perPage = $request->input('per_page', 10);

        $query = Sekolah::query();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('nama_sekolah', 'like', "%{$search}%")
                  ->orWhere('kode_sekolah', 'like', "%{$search}%")
                  ->orWhere('alamat_sekolah', 'like', "%{$search}%");
            });
        }

        if ($status !== null && $status !== '') {
            $query->where('is_active', (bool)$status);
        }

        $sekolahList = $query->orderBy('nama_sekolah')
            ->paginate($perPage)
            ->withQueryString()
            ->through(function ($sekolah) {
                $userCount = User::where('id_sekolah', $sekolah->id_sekolah)->where('is_active', 1)->count();
                $productCount = Barang::where('id_sekolah', $sekolah->id_sekolah)->where('is_delete', 0)->count();
                $totalPenjualan = Penjualan::where('id_sekolah', $sekolah->id_sekolah)->where('is_delete', 0)->sum('total_bayar');

                return [
                    'id_sekolah' => $sekolah->id_sekolah,
                    'kode_sekolah' => $sekolah->kode_sekolah,
                    'nama_sekolah' => $sekolah->nama_sekolah,
                    'alamat_sekolah' => $sekolah->alamat_sekolah,
                    'website' => $sekolah->website,
                    'is_active' => (bool)$sekolah->is_active,
                    'created_at' => $sekolah->created_at,
                    'user_count' => $userCount,
                    'product_count' => $productCount,
                    'total_penjualan' => (float)$totalPenjualan,
                ];
            });

        return Inertia::render('Sekolah/Index', [
            'sekolahList' => $sekolahList,
            'filters' => [
                'search' => $search,
                'status' => $status,
            ],
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'kode_sekolah' => 'required|string|max:50|unique:tb_sekolah,kode_sekolah',
            'nama_sekolah' => 'required|string|max:150',
            'alamat_sekolah' => 'nullable|string',
            'website' => 'nullable|string|max:100',
            'is_active' => 'boolean',
        ]);

        Sekolah::create([
            'kode_sekolah' => strtoupper(trim($request->kode_sekolah)),
            'nama_sekolah' => trim($request->nama_sekolah),
            'alamat_sekolah' => $request->alamat_sekolah,
            'website' => $request->website,
            'is_active' => $request->boolean('is_active', true),
            'created_at' => Carbon::now(),
        ]);

        return redirect()->back()->with('success', 'Sekolah berhasil ditambahkan.');
    }

    public function update(Request $request, $id)
    {
        $sekolah = Sekolah::findOrFail($id);

        $request->validate([
            'kode_sekolah' => 'required|string|max:50|unique:tb_sekolah,kode_sekolah,' . $id . ',id_sekolah',
            'nama_sekolah' => 'required|string|max:150',
            'alamat_sekolah' => 'nullable|string',
            'website' => 'nullable|string|max:100',
            'is_active' => 'boolean',
        ]);

        $sekolah->update([
            'kode_sekolah' => strtoupper(trim($request->kode_sekolah)),
            'nama_sekolah' => trim($request->nama_sekolah),
            'alamat_sekolah' => $request->alamat_sekolah,
            'website' => $request->website,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->back()->with('success', 'Data sekolah berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $sekolah = Sekolah::findOrFail($id);
        
        // Prevent deleting if school has related data
        $hasUsers = User::where('id_sekolah', $id)->exists();
        $hasTransactions = Penjualan::where('id_sekolah', $id)->exists();

        if ($hasUsers || $hasTransactions) {
            // Soft-deactivate instead
            $sekolah->update(['is_active' => 0]);
            return redirect()->back()->with('success', 'Sekolah memiliki relasi data, status diubah menjadi non-aktif.');
        }

        $sekolah->delete();
        return redirect()->back()->with('success', 'Sekolah berhasil dihapus.');
    }

    public function toggleStatus($id)
    {
        $sekolah = Sekolah::findOrFail($id);
        $sekolah->is_active = !$sekolah->is_active;
        $sekolah->save();

        return redirect()->back()->with('success', 'Status sekolah berhasil diperbarui.');
    }

    public function switchSekolah(Request $request)
    {
        $request->validate([
            'id_sekolah' => 'required|exists:tb_sekolah,id_sekolah',
        ]);

        $sekolah = Sekolah::where('id_sekolah', $request->id_sekolah)
            ->where('is_active', 1)
            ->firstOrFail();
        $request->session()->put('id_sekolah', $sekolah->id_sekolah);
        $request->session()->put('sekolah', $sekolah);

        return redirect()->back()->with('success', "Beralih ke {$sekolah->nama_sekolah}.");
    }
}
