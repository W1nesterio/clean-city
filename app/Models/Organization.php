<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Organization extends Model
{
    protected $fillable = [
        'name',
        'city_id',
        'district',
        'address',
        'lat',
        'lng',
        'contact_info',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
        'lat' => 'float',
        'lng' => 'float',
    ];

    public function city()
    {
        return $this->belongsTo(City::class);
    }

    public function users()
    {
        return $this->hasMany(User::class, 'organization_id');
    }

    public function workers()
    {
        return $this->hasMany(User::class, 'organization_id')->where('role', 'worker');
    }

    public function admins()
    {
        return $this->hasMany(User::class, 'organization_id')->where('role', 'org_admin');
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class, 'assigned_org_id');
    }

    public function ticketHides()
    {
        return $this->hasMany(TicketHide::class);
    }

    public function localUserBlocks()
    {
        return $this->hasMany(OrganizationUserBlock::class);
    }

    public function complaints()
    {
        return $this->hasMany(OrganizationComplaint::class);
    }

    public function workerRegistrationCodes()
    {
        return $this->hasMany(WorkerRegistrationCode::class);
    }
}
