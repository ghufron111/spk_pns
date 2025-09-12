<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BatasWaktu extends Model
{
    protected $table = 'batas_waktu';
    protected $fillable = ['label','mulai','selesai','aktif'];
    protected $casts = [
        'mulai' => 'date',
        'selesai' => 'date',
        'aktif' => 'boolean',
    ];
}
