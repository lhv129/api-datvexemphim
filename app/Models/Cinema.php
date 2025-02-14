<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Cinema extends Model
{
    use HasFactory;
    use SoftDeletes;
    protected $fillable = [
        'code',
        'name',
        'address',
        'image',
        'contact',
        'province_id',
    ];
    public $timestamps = true ;
    public function province() {
        return $this->belongsTo(Province::class,'province_id','id');
    }
    public function screen() {
        return $this->hasMany(Screen::class,'cinema_id','id');
    }
}
