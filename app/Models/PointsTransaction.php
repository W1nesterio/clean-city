<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PointsTransaction extends Model
{
    protected $fillable = [
        'user_id',
        'amount',
        'balance_after',
        'reason',
        'admin_id',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'balance_after' => 'integer',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }
}
