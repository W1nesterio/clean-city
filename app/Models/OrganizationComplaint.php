<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrganizationComplaint extends Model
{
    protected $fillable = [
        'organization_id',
        'created_by_user_id',
        'target_user_id',
        'ticket_id',
        'type',
        'status',
        'title',
        'description',
        'reviewed_by_user_id',
        'reviewed_at',
        'resolution',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
    ];

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function reviewedBy()
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id');
    }

    public function targetUser()
    {
        return $this->belongsTo(User::class, 'target_user_id');
    }

    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }
}
