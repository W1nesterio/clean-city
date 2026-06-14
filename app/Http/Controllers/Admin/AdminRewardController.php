<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\AdminAccess;
use App\Http\Controllers\Controller;
use App\Models\Reward;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class AdminRewardController extends Controller
{
    use AdminAccess;

    private function authorizeRewards()
    {
        return $this->requireAdmin();
    }

    public function index()
    {
        $admin = $this->authorizeRewards();

        $query = Reward::with(['author', 'organization'])->orderByDesc('id');

        if (in_array($admin->role, ['admin', 'super_admin'], true)) {
            $query->whereNull('organization_id');
        } elseif ($admin->role === 'org_admin') {
            $query->where(function ($q) use ($admin) {
                $q->where('organization_id', $admin->organization_id)
                  ->orWhereNull('organization_id');
            });
        }

        $rewards = $query->paginate(20);
        $organizations = collect();

        return view('admin.rewards.index', compact('rewards', 'admin', 'organizations'));
    }

    public function create()
    {
        $admin = $this->authorizeRewards();
        $organizations = collect();

        return view('admin.rewards.create', compact('admin', 'organizations'));
    }

    public function store(Request $request)
    {
        $admin = $this->authorizeRewards();

        $data = $request->validate([
            'title'           => ['required', 'string', 'max:255'],
            'description'     => ['nullable', 'string'],
            'photo'           => ['nullable', 'image', 'max:5120'],
            'points_required' => ['required', 'integer', 'min:0'],
            'code'            => ['nullable', 'string', 'max:100'],
            'valid_from'      => ['nullable', 'date'],
            'valid_to'        => ['nullable', 'date', 'after_or_equal:valid_from'],
            'active'          => ['nullable', 'boolean'],
            'organization_id' => ['nullable', 'integer', 'exists:organizations,id'],
        ]);

        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('rewards', 'public');
        }

        $orgId = match ($admin->role) {
            'org_admin'  => $admin->organization_id,
            default      => null,
        };

        Reward::create([
            'title'               => $data['title'],
            'description'         => $data['description'] ?? null,
            'photo_path'          => $photoPath,
            'points_required'     => $data['points_required'],
            'code'                => $data['code'] ?? null,
            'valid_from'          => $data['valid_from'] ?? null,
            'valid_to'            => $data['valid_to'] ?? null,
            'active'              => $request->boolean('active', true),
            'created_by_user_id'  => Auth::id(),
            'organization_id'     => $orgId,
        ]);

        return redirect()->route('admin.rewards.index')->with('success', 'Вознаграждение добавлено');
    }

    public function edit(Reward $reward)
    {
        $admin = $this->authorizeRewards();
        $this->checkOwnership($reward, $admin);

        $organizations = collect();

        return view('admin.rewards.edit', compact('reward', 'admin', 'organizations'));
    }

    public function update(Request $request, Reward $reward)
    {
        $admin = $this->authorizeRewards();
        $this->checkOwnership($reward, $admin);

        $data = $request->validate([
            'title'           => ['required', 'string', 'max:255'],
            'description'     => ['nullable', 'string'],
            'photo'           => ['nullable', 'image', 'max:5120'],
            'points_required' => ['required', 'integer', 'min:0'],
            'code'            => ['nullable', 'string', 'max:100'],
            'valid_from'      => ['nullable', 'date'],
            'valid_to'        => ['nullable', 'date', 'after_or_equal:valid_from'],
            'active'          => ['nullable', 'boolean'],
            'delete_photo'    => ['nullable', 'boolean'],
            'organization_id' => ['nullable', 'integer', 'exists:organizations,id'],
        ]);

        $photoPath = $reward->photo_path;

        if ($request->boolean('delete_photo') && $photoPath) {
            Storage::disk('public')->delete($photoPath);
            $photoPath = null;
        }

        if ($request->hasFile('photo')) {
            if ($photoPath) Storage::disk('public')->delete($photoPath);
            $photoPath = $request->file('photo')->store('rewards', 'public');
        }

        $orgId = match ($admin->role) {
            'org_admin' => $reward->organization_id, // cannot change
            default     => null,
        };

        $reward->update([
            'title'           => $data['title'],
            'description'     => $data['description'] ?? null,
            'photo_path'      => $photoPath,
            'points_required' => $data['points_required'],
            'code'            => $data['code'] ?? null,
            'valid_from'      => $data['valid_from'] ?? null,
            'valid_to'        => $data['valid_to'] ?? null,
            'active'          => $request->boolean('active', false),
            'organization_id' => $orgId,
        ]);

        return redirect()->route('admin.rewards.edit', $reward)->with('success', 'Вознаграждение сохранено');
    }

    public function destroy(Reward $reward)
    {
        $admin = $this->authorizeRewards();
        $this->checkOwnership($reward, $admin);

        if ($reward->photo_path) {
            Storage::disk('public')->delete($reward->photo_path);
        }
        $reward->delete();

        return redirect()->route('admin.rewards.index')->with('success', 'Вознаграждение удалено');
    }

    private function checkOwnership(Reward $reward, $admin): void
    {
        if (in_array($admin->role, ['admin', 'super_admin'], true)) {
            if ($reward->organization_id !== null) {
                abort(403, 'Главный администратор работает только с общими купонами платформы');
            }
            return;
        }

        if ($admin->role === 'org_admin' && (int) $reward->organization_id !== (int) $admin->organization_id) {
            abort(403, 'Нет доступа к этому вознаграждению');
        }
    }
}
