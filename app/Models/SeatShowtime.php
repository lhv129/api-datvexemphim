<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SeatShowtime  extends Model
{
    use HasFactory;
    protected $table = 'seat_showtimes';

    protected $fillable = ['seat_id', 'showtime_id', 'order_id', 'status'];

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function seat() {
        return $this->belongsTo(Seat::class, 'seat_id');
    }

    public function showtime() {
        return $this->belongsTo(Showtime::class, 'showtime_id');
    }
}
