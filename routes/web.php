<?php

use Illuminate\Support\Facades\Route;


// Auth
Route::get('/login', [App\Http\Controllers\Auth\LoginController::class, 'showLoginForm'])->name('login');
Route::get('/register', [App\Http\Controllers\RegisterController::class, 'show'])->name('register');
Route::post('/register', [App\Http\Controllers\RegisterController::class, 'store'])->name('register.store');
Route::get('/', function () {
    return redirect('/login');
});
Route::post('/login', [App\Http\Controllers\Auth\LoginController::class, 'login']);
Route::post('/logout', [App\Http\Controllers\Auth\LoginController::class, 'logout'])->name('logout');

// Public hash download (optional secure shared link)
Route::get('/d/{hash}', [App\Http\Controllers\PublicDownloadController::class, 'download'])->name('public.download');

// Pegawai
Route::middleware(['auth', \App\Http\Middleware\RoleMiddleware::class . ':pegawai'])->prefix('pegawai')->name('pegawai.')->group(function () {    Route::get('/dashboard', [App\Http\Controllers\PegawaiController::class, 'dashboard'])->name('dashboard');
    Route::get('/upload', [App\Http\Controllers\PegawaiController::class, 'uploadForm'])->name('upload');
    Route::post('/upload', [App\Http\Controllers\PegawaiController::class, 'uploadStore'])->name('upload.store');
    Route::get('/berkas', [App\Http\Controllers\PegawaiController::class, 'berkas'])->name('berkas');
    Route::post('/notifikasi/read-all', [App\Http\Controllers\PegawaiController::class, 'notifikasiMarkAll'])->name('notifikasi.readall');
    Route::post('/notifikasi/clear-all', [App\Http\Controllers\PegawaiController::class, 'notifikasiClearAll'])->name('notifikasi.clearall');
    Route::get('/download/{detailId}', [App\Http\Controllers\PegawaiController::class, 'downloadFile'])->name('download');
});

// Admin
Route::middleware(['auth', \App\Http\Middleware\RoleMiddleware::class . ':admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [App\Http\Controllers\AdminController::class, 'dashboard'])->name('dashboard');
    Route::post('/notifikasi/clear-all', [App\Http\Controllers\AdminController::class, 'notifikasiClearAll'])->name('notifikasi.clearall');
    Route::post('/notifikasi/delete-selected', [App\Http\Controllers\AdminController::class, 'notifikasiMarkAndDelete'])->name('notifikasi.deleteselected');
    Route::get('/validasi', [App\Http\Controllers\AdminController::class, 'validasiIndex'])->name('validasi.index');
    Route::get('/validasi/{id}', [App\Http\Controllers\AdminController::class, 'validasi'])->name('validasi');
    Route::post('/validasi/{id}', [App\Http\Controllers\AdminController::class, 'validasi'])->name('validasi');
    Route::get('/spk', [App\Http\Controllers\AdminController::class, 'spkIndex'])->name('spk.index');
    Route::post('/spk-run', [App\Http\Controllers\AdminController::class, 'spkRun'])->name('spk.run');
    Route::get('/spk/pengaturan', [App\Http\Controllers\AdminController::class, 'spkSettings'])->name('spk.settings');
    Route::post('/spk/pengaturan', [App\Http\Controllers\AdminController::class, 'spkSettingsUpdate'])->name('spk.settings.update');
    Route::get('/periode/pengaturan', [App\Http\Controllers\AdminController::class, 'periodeSettings'])->name('periode.settings');
    Route::post('/periode/pengaturan', [App\Http\Controllers\AdminController::class, 'periodeSettingsStore'])->name('periode.settings.store');
    Route::get('/dokumen/pengaturan', [App\Http\Controllers\AdminController::class, 'dokumenSettings'])->name('dokumen.settings');
    Route::post('/dokumen/pengaturan', [App\Http\Controllers\AdminController::class, 'dokumenSettingsStore'])->name('dokumen.settings.store');
    Route::get('/upload/{uploadId}', [App\Http\Controllers\AdminController::class, 'detailUpload'])->name('upload.detail');
    Route::post('/upload/{uploadId}/batch-validasi', [App\Http\Controllers\AdminController::class, 'validasiBatch'])->name('upload.validasi.batch');
    Route::get('/preview/{detailId}', [App\Http\Controllers\AdminController::class, 'previewFile'])->name('upload.preview');
    Route::get('/download/{detailId}', [App\Http\Controllers\AdminController::class, 'downloadFile'])->name('upload.download');
});

// Pimpinan
Route::middleware(['auth', \App\Http\Middleware\RoleMiddleware::class . ':pimpinan'])->prefix('pimpinan')->name('pimpinan.')->group(function () {
    Route::get('/dashboard', [App\Http\Controllers\PimpinanController::class, 'dashboard'])->name('dashboard');
    Route::get('/validasi/{id}', [App\Http\Controllers\PimpinanController::class, 'validasi'])->name('validasi');
    Route::post('/validasi/{id}', [App\Http\Controllers\PimpinanController::class, 'validasi'])->name('validasi');
    Route::get('/approvals', [App\Http\Controllers\PimpinanController::class, 'approvals'])->name('approvals.index');
    Route::get('/considerations', [App\Http\Controllers\PimpinanController::class, 'considerations'])->name('considerations');
    Route::get('/pegawai', [App\Http\Controllers\PimpinanController::class, 'pegawaiIndex'])->name('pegawai.index');
    Route::post('/approvals/{id}/approve', [App\Http\Controllers\PimpinanController::class, 'approve'])->name('approvals.approve');
    Route::post('/approvals/{id}/reject', [App\Http\Controllers\PimpinanController::class, 'reject'])->name('approvals.reject');
});
