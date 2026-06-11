<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Reward extends Model
{
    protected $fillable = [
        'title',
        'description',
        'photo_path',
        'points_required',
        'code',
        'valid_from',
        'valid_to',
        'active',
        'created_by_user_id',
        'organization_id',
    ];

    protected function casts(): array
    {
        return [
            'valid_from' => 'date',
            'valid_to' => 'date',
            'active' => 'boolean',
            'points_required' => 'integer',
        ];
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function getPhotoUrlAttribute(): ?string
    {
        return $this->photo_path ? url(Storage::url($this->photo_path)) : null;
    }

    public function isValid(): bool
    {
        if (!$this->active) {
            return false;
        }
        $today = now()->toDateString();
        if ($this->valid_from && $this->valid_from->toDateString() > $today) {
            return false;
        }
        if ($this->valid_to && $this->valid_to->toDateString() < $today) {
            return false;
        }
        return true;
    }
}
