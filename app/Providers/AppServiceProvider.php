<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use App\Http\Middleware\RoleMiddleware;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
    // Pastikan alias middleware 'role' terdaftar (fallback jika Kernel belum terbaca di cache route)
    Route::aliasMiddleware('role', RoleMiddleware::class);
    }
}
