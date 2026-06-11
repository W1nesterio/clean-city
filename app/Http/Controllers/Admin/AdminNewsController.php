<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\AdminAccess;
use App\Http\Controllers\Controller;
use App\Models\News;
use App\Models\NewsPhoto;
use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class AdminNewsController extends Controller
{
    use AdminAccess;

    private function authorizeNews()
    {
        return $this->requireAdmin();
    }

    private function scopeForAdmin($query, $admin)
    {
        if (in_array($admin->role, ['admin', 'super_admin'], true)) {
            return $query;
        }
        // org_admin sees their own + platform-wide (organization_id = null)
        return $query->where(function ($q) use ($admin) {
            $q->whereNull('organization_id')
              ->orWhere('organization_id', $admin->organization_id);
        });
    }

    public function index()
    {
        $admin = $this->authorizeNews();
        $news = $this->scopeForAdmin(News::with(['author', 'organization']), $admin)
            ->withCount('photos')
            ->orderByDesc('published_date')
            ->orderByDesc('id')
            ->paginate(20);
        $isOrgAdmin = $admin->role === 'org_admin';
        return view('admin.news.index', compact('news', 'isOrgAdmin'));
    }

    public function create()
    {
        $admin = $this->authorizeNews();
        $organizations = in_array($admin->role, ['admin', 'super_admin'], true)
            ? Organization::orderBy('name')->get()
            : collect();
        return view('admin.news.create', compact('admin', 'organizations'));
    }

    public function store(Request $request)
    {
        $admin = $this->authorizeNews();

        $data = $request->validate([
            'title'           => ['required', 'string', 'max:255'],
            'body'            => ['nullable', 'string'],
            'published_date'  => ['required', 'date'],
            'active'          => ['nullable', 'boolean'],
            'organization_id' => ['nullable', 'exists:organizations,id'],
            'photos'          => ['nullable', 'array', 'max:10'],
            'photos.*'        => ['image', 'max:5120'],
        ]);

        $orgId = null;
        if ($admin->role === 'org_admin') {
            $orgId = $admin->organization_id;
        } elseif (!empty($data['organization_id'])) {
            $orgId = $data['organization_id'];
        }

        $news = News::create([
            'title'              => $data['title'],
            'body'               => $data['body'] ?? null,
            'published_date'     => $data['published_date'],
            'active'             => $request->boolean('active', true),
            'created_by_user_id' => Auth::id(),
            'organization_id'    => $orgId,
        ]);

        if ($request->hasFile('photos')) {
            foreach ($request->file('photos') as $i => $file) {
                $path = $file->store('news', 'public');
                NewsPhoto::create(['news_id' => $news->id, 'path' => $path, 'sort_order' => $i]);
            }
        }

        return redirect()->route('admin.news.index')->with('success', 'Новость создана');
    }

    public function edit(News $news)
    {
        $admin = $this->authorizeNews();
        $this->checkOwnership($news, $admin);
        $news->load('photos');
        $organizations = in_array($admin->role, ['admin', 'super_admin'], true)
            ? Organization::orderBy('name')->get()
            : collect();
        return view('admin.news.edit', compact('news', 'admin', 'organizations'));
    }

    public function update(Request $request, News $news)
    {
        $admin = $this->authorizeNews();
        $this->checkOwnership($news, $admin);

        $data = $request->validate([
            'title'           => ['required', 'string', 'max:255'],
            'body'            => ['nullable', 'string'],
            'published_date'  => ['required', 'date'],
            'active'          => ['nullable', 'boolean'],
            'organization_id' => ['nullable', 'exists:organizations,id'],
            'photos'          => ['nullable', 'array', 'max:10'],
            'photos.*'        => ['image', 'max:5120'],
            'delete_photos'   => ['nullable', 'array'],
            'delete_photos.*' => ['integer'],
        ]);

        $updateData = [
            'title'          => $data['title'],
            'body'           => $data['body'] ?? null,
            'published_date' => $data['published_date'],
            'active'         => $request->boolean('active', false),
        ];

        if (in_array($admin->role, ['admin', 'super_admin'], true)) {
            $updateData['organization_id'] = $data['organization_id'] ?? null;
        }

        $news->update($updateData);

        if (!empty($data['delete_photos'])) {
            $toDelete = NewsPhoto::whereIn('id', $data['delete_photos'])->where('news_id', $news->id)->get();
            foreach ($toDelete as $photo) {
                Storage::disk('public')->delete($photo->path);
                $photo->delete();
            }
        }

        if ($request->hasFile('photos')) {
            $maxOrder = $news->photos()->max('sort_order') ?? -1;
            foreach ($request->file('photos') as $i => $file) {
                $path = $file->store('news', 'public');
                NewsPhoto::create(['news_id' => $news->id, 'path' => $path, 'sort_order' => $maxOrder + $i + 1]);
            }
        }

        return redirect()->route('admin.news.edit', $news)->with('success', 'Новость сохранена');
    }

    public function destroy(News $news)
    {
        $admin = $this->authorizeNews();
        $this->checkOwnership($news, $admin);

        foreach ($news->photos as $photo) {
            Storage::disk('public')->delete($photo->path);
        }
        $news->delete();

        return redirect()->route('admin.news.index')->with('success', 'Новость удалена');
    }

    private function checkOwnership(News $news, $admin): void
    {
        if (in_array($admin->role, ['admin', 'super_admin'], true)) {
            return;
        }
        // org_admin can only edit their org's news
        if ($news->organization_id !== $admin->organization_id) {
            abort(403);
        }
    }
}
