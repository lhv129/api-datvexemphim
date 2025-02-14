<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TicketProductDetail extends Model
{
    use HasFactory;
    protected $fillable = [
        'ticket_id',
        'product_id',
        'quantity',
        'price'
    ];

    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
