<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RoleMiddleware
{
    /**
     * Pastikan user memiliki salah satu role yang diberikan (dipisahkan dengan '|').
     */
    public function handle(Request $request, Closure $next, string $roles)
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $allowed = collect(explode('|', $roles))->map(fn($r)=>trim($r))->filter();
        if ($allowed->isNotEmpty() && !$allowed->contains(Auth::user()->role)) {
            abort(403, 'Forbidden');
        }
        return $next($request);
    }
}
