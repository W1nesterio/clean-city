<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TicketPhoto extends Model
{
    protected $fillable = [
        'ticket_id',
        'type',
        'path',
    ];

    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }
}