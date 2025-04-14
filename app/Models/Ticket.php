<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Ticket extends Model
{
    use HasFactory;

    // use SoftDeletes;
    protected $fillable = [
        'user_id',
        'showtime_id',
        'total_price',
        'payment_method_id',
        'promo_code_id',
        'code',
        'total_amount',
        'discount_price',
        'status'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function showtime()
    {
        return $this->belongsTo(Showtime::class)->withTrashed();
    }

    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class)->withTrashed();
    }

    public function ticketDetails()
    {
        return $this->hasMany(TicketDetail::class);
    }

    public function promoCode()
    {
        return $this->belongsTo(Promo_code::class)->withTrashed();
    }

    public function ticketProductDetails()
{
    return $this->hasMany(TicketProductDetail::class);
}

}
