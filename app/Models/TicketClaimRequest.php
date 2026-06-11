<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TicketClaimRequest extends Model
{
    protected $fillable = [
        'ticket_id',
        'worker_id',
        'organization_id',
        'status',
        'comment',
        'reviewed_by_user_id',
        'reviewed_at',
        'resolution',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
    ];

    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }

    public function worker()
    {
        return $this->belongsTo(User::class, 'worker_id');
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id');
    }
}
