<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\DetailUpload;

class PublicDownloadController extends Controller
{
    public function download($hash)
    {
        $detail = DetailUpload::where('hash',$hash)->firstOrFail();
        // Optional: tambahkan pembatasan (expires, role, dsb)
        $path = storage_path('app/private/'.$detail->path_berkas);
        if(!file_exists($path)) abort(404);
        return response()->download($path, basename($path));
    }
}
