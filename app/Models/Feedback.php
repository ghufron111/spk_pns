<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Feedback extends Model
{
    use HasFactory;
    protected $fillable = [
        'detail_upload_id', 'catatan'
    ];

    public function detailUpload()
    {
        return $this->belongsTo(DetailUpload::class);
    }
}
