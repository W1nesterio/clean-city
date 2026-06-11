<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrganizationUserBlock extends Model
{
    protected $fillable = [
        'organization_id',
        'user_id',
        'created_by_user_id',
        'reason',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }
}
