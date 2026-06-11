<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class News extends Model
{
    protected $table = 'news';

    protected $fillable = [
        'title',
        'body',
        'published_date',
        'active',
        'created_by_user_id',
        'organization_id',
    ];

    protected function casts(): array
    {
        return [
            'published_date' => 'date',
            'active' => 'boolean',
        ];
    }

    public function photos()
    {
        return $this->hasMany(NewsPhoto::class)->orderBy('sort_order');
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }
}
