<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TicketDetail extends Model
{
    use HasFactory;

    use SoftDeletes;
    protected $fillable = [
        'ticket_id',
        'seat_id',
        'price'
    ];

    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }

    public function seat()
    {
        return $this->belongsTo(Seat::class)->withTrashed();
    }
}
