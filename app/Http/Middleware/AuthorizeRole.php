<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Auth;

class AuthorizeRole
{
    public function handle(Request $request, Closure $next, $roles)
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }
        $allowed = collect(explode('|', $roles))->map(fn($r)=>trim($r));
        $pass = $allowed->contains(function($r){
            return Gate::allows($r);
        });
        if (!$pass) {
            abort(403,'Forbidden');
        }
        return $next($request);
    }
}
