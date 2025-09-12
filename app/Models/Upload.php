<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Upload extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id', 'jenis', 'periode', 'tanggal_upload', 'status', 'target_pangkat'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function detailUploads()
    {
        return $this->hasMany(DetailUpload::class);
    }

    public function hasilSpk()
    {
        return $this->hasOne(HasilSpk::class, 'upload_id');
    }
}
