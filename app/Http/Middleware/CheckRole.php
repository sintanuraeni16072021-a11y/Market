<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$roles
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        $userRole = strtolower($user->role->nama_role ?? '');

        // Normalize expected roles
        $allowedRoles = array_map('strtolower', $roles);

        if (!in_array($userRole, $allowedRoles)) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unauthorized. Akses ditolak untuk role Anda.'], 403);
            }

            // Redirect to appropriate landing page
            if ($userRole === 'kasir') {
                return redirect()->route('kasir')->with('error', 'Anda tidak memiliki hak akses ke halaman tersebut.');
            }

            return redirect()->route('dashboard')->with('error', 'Anda tidak memiliki hak akses ke halaman tersebut.');
        }

        return $next($request);
    }
}
