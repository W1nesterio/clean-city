<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TicketStatusHistory extends Model
{
    protected $fillable = [
        'ticket_id',
        'old_status',
        'new_status',
        'changed_by_user_id',
        'comment',
    ];

    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }

    public function changedBy()
    {
        return $this->belongsTo(User::class, 'changed_by_user_id');
    }
}