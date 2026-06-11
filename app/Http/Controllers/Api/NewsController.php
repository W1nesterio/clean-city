<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\News;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class NewsController extends Controller
{
    public function index(Request $request)
    {
        $query = News::with(['photos', 'organization'])
            ->where('active', true)
            ->orderByDesc('published_date')
            ->orderByDesc('id')
            ->limit(50);

        if ($request->filled('city_id')) {
            $cityId = (int) $request->city_id;
            // Platform-wide news (no org) + news from orgs in the selected city
            $query->where(function ($q) use ($cityId) {
                $q->whereNull('organization_id')
                  ->orWhereHas('organization', fn ($o) => $o->where('city_id', $cityId));
            });
        }

        $news = $query->get()->map(fn ($item) => [
            'id'              => $item->id,
            'title'           => $item->title,
            'body'            => $item->body,
            'published_date'  => $item->published_date->format('Y-m-d'),
            'organization_id' => $item->organization_id,
            'city_id'         => $item->organization?->city_id,
            'photos'          => $item->photos->map(fn ($p) => [
                'id'  => $p->id,
                'url' => url(Storage::url($p->path)),
            ])->values(),
        ]);

        return response()->json(['news' => $news]);
    }
}
