<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'showtime_id',
        'total_price',
        'payment_method_id',
        'promo_code_id',
        'code',
        'total_amount',
        'status'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function showtime()
    {
        return $this->belongsTo(Showtime::class);
    }

    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function ticketDetails()
    {
        return $this->hasMany(TicketDetail::class);
    }

    public function promoCode()
    {
        return $this->belongsTo(Promo_code::class);
    }

    public function ticketProductDetails()
{
    return $this->hasMany(TicketProductDetail::class);
}

}
