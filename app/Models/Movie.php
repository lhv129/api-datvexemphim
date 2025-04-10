<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Movie extends Model
{
    use HasFactory;
    // use SoftDeletes;
    // protected $dates = ['deleted_at'];

    protected $fillable = [
        'title',
        'description',
        'poster',
        'trailer',
        'duration',
        'rating',
        'release_date',
        'end_date',
        'deleted_at',
        'fileName'
    ];

    public function movies()
    {
        return $this->belongsToMany(Movie::class);
    }
    public function showtimes()
    {
        return $this->hasMany(Showtime::class);
    }
}
