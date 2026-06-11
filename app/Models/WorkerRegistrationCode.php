<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkerRegistrationCode extends Model
{
    protected $fillable = [
        'code',
        'issued_to',
        'organization_id',
        'created_by_user_id',
        'used_by_user_id',
        'used_at',
        'max_uses',
        'used_count',
        'expires_at',
        'active',
        'revoked_at',
        'revoked_by_user_id',
    ];

    protected $casts = [
        'active' => 'boolean',
        'expires_at' => 'datetime',
        'used_at' => 'datetime',
        'revoked_at' => 'datetime',
        'max_uses' => 'integer',
        'used_count' => 'integer',
    ];

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function usedBy()
    {
        return $this->belongsTo(User::class, 'used_by_user_id');
    }

    public function revokedBy()
    {
        return $this->belongsTo(User::class, 'revoked_by_user_id');
    }

    public function isAvailable(): bool
    {
        if (!$this->active) {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        return $this->used_count < $this->max_uses;
    }
}
