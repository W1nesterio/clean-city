<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class City extends Model
{
    protected $fillable = ['name', 'region', 'active'];

    protected $casts = ['active' => 'boolean'];

    public function organizations()
    {
        return $this->hasMany(Organization::class);
    }
}
