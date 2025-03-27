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

    public function showtimes() {
        return $this->belongsToMany(Showtime::class, 'seat_showtimes')
                    ->withPivot('status')
                    ->withTimestamps();
    }

    public function orders()
    {
        return $this->hasManyThrough(Order::class, SeatShowtime::class, 'seat_id', 'id', 'id', 'order_id');
    }

    public function screen() {
        return $this->belongsTo(Screen::class,'screen_id','id');
    }
}
