<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TicketHide extends Model
{
    protected $fillable = [
        'ticket_id',
        'organization_id',
        'hidden_by_user_id',
        'reason',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function hiddenBy()
    {
        return $this->belongsTo(User::class, 'hidden_by_user_id');
    }
}
