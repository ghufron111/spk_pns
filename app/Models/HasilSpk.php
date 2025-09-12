<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HasilSpk extends Model
{
    use HasFactory;
    protected $table = 'hasil_spk';
    protected $fillable = ['upload_id','hasil','catatan'];

    public function upload()
    {
        return $this->belongsTo(Upload::class);
    }
}
