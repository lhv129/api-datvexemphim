<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Showtime extends Model
{
    use SoftDeletes;
    use HasFactory;
    protected $fillable =  [

        'movie_id',
        'screen_id',
        'start_time',
        'end_time',
        'date',
    ];
    public $timestamps = true ;

    public function movie()
    {
        return $this->belongsTo(Movie::class);
    }

    public function seats() {
        return $this->belongsToMany(Seat::class, 'seat_showtimes')
                    ->withPivot('status')
                    ->withTimestamps();
    }


    public function screen()
    {
        return $this->belongsTo(Screen::class);
    }
}
