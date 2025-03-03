<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Promo_code extends Model
{
    use SoftDeletes;
    use HasFactory;
    protected $fillable =  [
        'code',
        'description',
        'discount_amount',
        'start_date',
        'end_date',
        'status',
    ];
    protected $casts = [
        'status' => 'boolean',
    ];
    public $timestamps = true ;

}
