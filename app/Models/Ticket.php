<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    protected $fillable = [
        'user_id',
        'category_id',
        'assigned_org_id',
        'assigned_worker_id',
        'status',
        'priority',
        'lat',
        'lng',
        'address_text',
        'description',
        'closed_at',
        'deleted_by_user_id',
        'deleted_at',
        'delete_reason',
        'available_to_residents',
    ];

    protected $casts = [
        'lat' => 'decimal:7',
        'lng' => 'decimal:7',
        'closed_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function assignedOrganization()
    {
        return $this->belongsTo(Organization::class, 'assigned_org_id');
    }

    public function assignedWorker()
    {
        return $this->belongsTo(User::class, 'assigned_worker_id');
    }

    public function photos()
    {
        return $this->hasMany(TicketPhoto::class);
    }


    public function hides()
    {
        return $this->hasMany(TicketHide::class);
    }

    public function activeHides()
    {
        return $this->hasMany(TicketHide::class)->where('active', true);
    }

    public function deletedBy()
    {
        return $this->belongsTo(User::class, 'deleted_by_user_id');
    }

    public function statusHistory()
    {
        return $this->hasMany(TicketStatusHistory::class);
    }

    public function claimRequests()
    {
        return $this->hasMany(TicketClaimRequest::class);
    }

    public function pendingClaimRequests()
    {
        return $this->hasMany(TicketClaimRequest::class)->where('status', 'pending');
    }
}