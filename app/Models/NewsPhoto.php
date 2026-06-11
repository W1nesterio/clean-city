<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class NewsPhoto extends Model
{
    protected $fillable = ['news_id', 'path', 'sort_order'];

    public function news()
    {
        return $this->belongsTo(News::class);
    }

    public function getUrlAttribute(): string
    {
        return url(Storage::url($this->path));
    }
}
