<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $fillable = [
        'name',
        'icon',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }
}