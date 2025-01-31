<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Genre extends Model
{
    use HasFactory;
    // use SoftDeletes;
    // protected $dates = ['deleted_at'];

    protected $fillable = [
        'name',
        'deleted_at',
    ];

    public function genres()
    {
        return $this->belongsToMany(Genre::class);
    }
}
