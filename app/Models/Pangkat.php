<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pangkat extends Model
{
    protected $table = 'pangkat';
    public $timestamps = false;
    protected $fillable = ['nama_pangkat','golongan','ruang'];
}
