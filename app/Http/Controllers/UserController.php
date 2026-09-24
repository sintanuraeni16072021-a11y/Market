<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Role;
use App\Models\Sekolah;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $isSuperAdmin = $user->isSuperAdmin();
        $idSekolah = $request->session()->get('id_sekolah') ?? $user->id_sekolah;
        
        $search = $request->input('search');
        $roleId = $request->input('role');
        $filterSekolah = $request->input('id_sekolah');
        $status = $request->input('status');
        $perPage = $request->input('per_page', 15);

        $query = User::with('role:id_role,nama_role', 'sekolah:id_sekolah,nama_sekolah,kode_sekolah');

        if (!$isSuperAdmin) {
            // Admin only sees users in their own school
            $query->where('id_sekolah', $idSekolah);
        } else if ($filterSekolah) {
            $query->where('id_sekolah', $filterSekolah);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('nama_lengkap', 'like', "%{$search}%")
                  ->orWhere('username', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($roleId) {
            $query->where('id_role', $roleId);
        }

        if ($status !== null && $status !== '') {
            $query->where('is_active', (bool)$status);
        }

        $userList = $query->orderBy('nama_lengkap')
            ->paginate($perPage)
            ->withQueryString()
            ->through(function ($u) {
                return [
                    'id_user' => $u->id_user,
                    'username' => $u->username,
                    'email' => $u->email,
                    'nama_lengkap' => $u->nama_lengkap,
                    'is_active' => (bool) $u->is_active,
                    'id_role' => $u->id_role,
                    'role' => $u->role ? ['id_role' => $u->role->id_role, 'nama_role' => $u->role->nama_role] : null,
                    'id_sekolah' => $u->id_sekolah,
                    'sekolah' => $u->sekolah ? ['id_sekolah' => $u->sekolah->id_sekolah, 'nama_sekolah' => $u->sekolah->nama_sekolah, 'kode_sekolah' => $u->sekolah->kode_sekolah] : null,
                ];
            });

        // Filter roles that can be assigned
        $roleQuery = Role::orderBy('id_role');
        if (!$isSuperAdmin) {
            // Regular admin cannot create super admin
            $roleQuery->where('nama_role', '!=', 'super admin');
        }
        $roleList = $roleQuery->get(['id_role', 'nama_role']);

        $sekolahList = Sekolah::where('is_active', 1)
            ->orderBy('nama_sekolah')
            ->get(['id_sekolah', 'nama_sekolah', 'kode_sekolah']);

        return Inertia::render('User/Index', [
            'userList' => $userList,
            'roleList' => $roleList,
            'sekolahList' => $sekolahList,
            'filters' => [
                'search' => $search,
                'role' => $roleId,
                'id_sekolah' => $filterSekolah,
                'status' => $status,
            ],
            'currentUserRole' => $user->role->nama_role ?? '',
            'isSuperAdmin' => $isSuperAdmin,
        ]);
    }

    public function store(Request $request)
    {
        $currentUser = auth()->user();
        $isSuperAdmin = $currentUser->isSuperAdmin();
        $idSekolah = $isSuperAdmin ? $request->id_sekolah : ($request->session()->get('id_sekolah') ?? $currentUser->id_sekolah);

        $request->validate([
            'username' => 'required|string|max:50|unique:tb_user,username,NULL,id_user,id_sekolah,' . $idSekolah,
            'email' => 'nullable|email|max:100',
            'password' => 'required|string|min:6|confirmed',
            'nama_lengkap' => 'required|string|max:100',
            'id_role' => 'required|exists:roles,id_role',
            'id_sekolah' => $isSuperAdmin ? 'required|exists:tb_sekolah,id_sekolah' : 'nullable',
            'is_active' => 'boolean',
        ]);

        // Security check: non-super admin cannot create super admin
        if (!$isSuperAdmin) {
            $selectedRole = Role::find($request->id_role);
            if ($selectedRole && strtolower($selectedRole->nama_role) === 'super admin') {
                return redirect()->back()->with('error', 'Anda tidak memiliki hak untuk membuat akun Super Admin.');
            }
        }

        User::create([
            'id_sekolah' => $idSekolah,
            'id_role' => $request->id_role,
            'username' => trim($request->username),
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'nama_lengkap' => trim($request->nama_lengkap),
            'is_active' => $request->boolean('is_active', true),
            'created_at' => Carbon::now(),
            'created_by' => $currentUser->id_user,
        ]);

        return redirect()->route('user')->with('success', 'User berhasil ditambahkan');
    }

    public function update(Request $request, $id)
    {
        $currentUser = auth()->user();
        $isSuperAdmin = $currentUser->isSuperAdmin();
        $idSekolah = $request->session()->get('id_sekolah') ?? $currentUser->id_sekolah;

        $targetUser = $isSuperAdmin 
            ? User::findOrFail($id)
            : User::where('id_user', $id)->where('id_sekolah', $idSekolah)->firstOrFail();

        $targetSchoolId = $isSuperAdmin ? ($request->id_sekolah ?? $targetUser->id_sekolah) : $idSekolah;

        $request->validate([
            'username' => 'required|string|max:50|unique:tb_user,username,' . $id . ',id_user,id_sekolah,' . $targetSchoolId,
            'email' => 'nullable|email|max:100',
            'password' => 'nullable|string|min:6|confirmed',
            'nama_lengkap' => 'required|string|max:100',
            'id_role' => 'required|exists:roles,id_role',
            'id_sekolah' => $isSuperAdmin ? 'required|exists:tb_sekolah,id_sekolah' : 'nullable',
            'is_active' => 'boolean',
        ]);

        // Security check
        if (!$isSuperAdmin) {
            $selectedRole = Role::find($request->id_role);
            if ($selectedRole && strtolower($selectedRole->nama_role) === 'super admin') {
                return redirect()->back()->with('error', 'Anda tidak memiliki hak untuk mengubah role menjadi Super Admin.');
            }
        }

        $data = [
            'username' => trim($request->username),
            'email' => $request->email,
            'nama_lengkap' => trim($request->nama_lengkap),
            'id_role' => $request->id_role,
            'id_sekolah' => $targetSchoolId,
            'is_active' => $request->boolean('is_active', true),
            'updated_at' => Carbon::now(),
            'updated_by' => $currentUser->id_user,
        ];

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $targetUser->update($data);

        return redirect()->route('user')->with('success', 'User berhasil diperbarui');
    }

    public function destroy(Request $request, $id)
    {
        $currentUser = auth()->user();
        $isSuperAdmin = $currentUser->isSuperAdmin();
        $idSekolah = $request->session()->get('id_sekolah') ?? $currentUser->id_sekolah;

        $targetUser = $isSuperAdmin 
            ? User::findOrFail($id)
            : User::where('id_user', $id)->where('id_sekolah', $idSekolah)->firstOrFail();

        // Prevent deleting self
        if ($targetUser->id_user === $currentUser->id_user) {
            return redirect()->route('user')->with('error', 'Tidak dapat menghapus akun sendiri');
        }

        $targetUser->update([
            'is_active' => false,
            'deleted_at' => Carbon::now(),
            'deleted_by' => $currentUser->id_user,
        ]);

        return redirect()->route('user')->with('success', 'User berhasil dinonaktifkan');
    }

    public function resetPassword(Request $request, $id)
    {
        $currentUser = auth()->user();
        $isSuperAdmin = $currentUser->isSuperAdmin();
        $idSekolah = $request->session()->get('id_sekolah') ?? $currentUser->id_sekolah;

        $targetUser = $isSuperAdmin 
            ? User::findOrFail($id)
            : User::where('id_user', $id)->where('id_sekolah', $idSekolah)->firstOrFail();

        $request->validate([
            'password' => 'required|string|min:6|confirmed',
        ]);

        $targetUser->update([
            'password' => Hash::make($request->password),
            'updated_at' => Carbon::now(),
            'updated_by' => $currentUser->id_user,
        ]);

        return redirect()->route('user')->with('success', 'Password berhasil direset');
    }

    public function toggleStatus(Request $request, $id)
    {
        $currentUser = auth()->user();
        $isSuperAdmin = $currentUser->isSuperAdmin();
        $idSekolah = $request->session()->get('id_sekolah') ?? $currentUser->id_sekolah;

        $targetUser = $isSuperAdmin 
            ? User::findOrFail($id)
            : User::where('id_user', $id)->where('id_sekolah', $idSekolah)->firstOrFail();

        if ($targetUser->id_user === $currentUser->id_user) {
            return redirect()->route('user')->with('error', 'Tidak dapat mengubah status akun sendiri');
        }

        $targetUser->update([
            'is_active' => !$targetUser->is_active,
            'updated_at' => Carbon::now(),
            'updated_by' => $currentUser->id_user,
        ]);

        return redirect()->route('user')->with('success', 'Status user berhasil diubah');
    }
}