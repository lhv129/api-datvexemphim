<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PasswordResetToken extends Model
{
    use HasFactory;
    public $timestamps = false;

    protected $fillable = [
        'email',
        'token',
        'expires_at',
        'created_at'
    ];
}
