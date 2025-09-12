<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DetailUpload extends Model
{
    use HasFactory;
    protected $fillable = [
        'upload_id', 'nama_berkas', 'path_berkas', 'status', 'catatan', 'hash'
    ];

    public function upload()
    {
        return $this->belongsTo(Upload::class);
    }
}
