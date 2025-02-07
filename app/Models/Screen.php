<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Screen extends Model
{
    use SoftDeletes;
    use HasFactory;
    protected $fillable =  [
        'name',
        'cinema_id',
    ];
    public $timestamps = true ;

    public function cinema() {
        return $this->belongsTo(Cinema::class,'cinema_id','id');
    }
    public function seat() {
        return $this->hasMany(Seat::class,'screen_id','id');
    }
}
