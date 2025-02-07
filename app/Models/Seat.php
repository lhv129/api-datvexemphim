<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Seat extends Model
{
    use SoftDeletes;
    use HasFactory;
    protected $fillable =  [
        'row',
        'number',
        'type',
        'price',
        'status',
        'screen_id',
    ];
    public $timestamps = true ;

    public function screen() {
        return $this->belongsTo(Screen::class,'screen_id','id');
    }
}
