<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Sekolah;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class LoginController extends Controller
{
    public function showLoginForm(Request $request)
    {
        return Inertia::render('auth/Login', [
            'status' => $request->session()->get('status'),
        ]);
    }

    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
            'remember' => 'sometimes|boolean',
        ]);

        $candidates = User::where('username', $request->username)
            ->where('is_active', 1)
            ->get();

        if ($candidates->count() > 1) {
            Log::warning('Login ambigu: username dipakai beberapa akun', [
                'username' => $request->username,
                'ip' => $request->ip(),
            ]);

            throw ValidationException::withMessages([
                'username' => 'Username dipakai lebih dari satu akun, hubungi admin.',
            ]);
        }

        $user = $candidates->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            Log::warning('Login gagal', [
                'username' => $request->username,
                'ip' => $request->ip(),
            ]);

            throw ValidationException::withMessages([
                'username' => 'Username atau password salah.',
            ]);
        }

        $sekolah = Sekolah::find($user->id_sekolah);

        if (!$sekolah || !$sekolah->is_active) {
            throw ValidationException::withMessages([
                'username' => 'Akun sekolah Anda tidak aktif, hubungi admin.',
            ]);
        }

        if (!$user->role || !in_array($user->role->nama_role, ['super admin', 'admin', 'kasir'])) {
            throw ValidationException::withMessages([
                'username' => 'Anda tidak memiliki hak akses.',
            ]);
        }

        Auth::login($user, $request->boolean('remember'));

        \App\Models\ActivityLog::catat('login', 'auth', "User {$user->username} masuk");

        $request->session()->regenerate();
        $request->session()->put('id_sekolah', $user->id_sekolah);
        $request->session()->put('sekolah', $sekolah);

        return redirect()->intended('/dashboard');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        
        return redirect('/login');
    }
}