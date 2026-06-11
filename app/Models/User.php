<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'organization_id',
        'avatar_path',
        'banned_at',
        'ban_reason',
        'banned_by_user_id',
        'points_balance',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $appends = [
        'avatar_url',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'banned_at' => 'datetime',
        ];
    }

    public function getAvatarUrlAttribute(): ?string
    {
        return $this->avatar_path ? url(Storage::url($this->avatar_path)) : null;
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }

    public function assignedTickets()
    {
        return $this->hasMany(Ticket::class, 'assigned_worker_id');
    }


    public function bannedBy()
    {
        return $this->belongsTo(User::class, 'banned_by_user_id');
    }

    public function localBlocks()
    {
        return $this->hasMany(OrganizationUserBlock::class);
    }

    public function apiTokens()
    {
        return $this->hasMany(ApiToken::class);
    }

    public function createdWorkerRegistrationCodes()
    {
        return $this->hasMany(WorkerRegistrationCode::class, 'created_by_user_id');
    }

    public function usedWorkerRegistrationCodes()
    {
        return $this->hasMany(WorkerRegistrationCode::class, 'used_by_user_id');
    }

    public function ticketClaimRequests()
    {
        return $this->hasMany(TicketClaimRequest::class, 'worker_id');
    }

    public function pointsTransactions()
    {
        return $this->hasMany(PointsTransaction::class);
    }
}
