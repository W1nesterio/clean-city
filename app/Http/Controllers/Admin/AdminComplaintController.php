<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\AdminAccess;
use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\OrganizationComplaint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminComplaintController extends Controller
{
    use AdminAccess;

    private array $types = [
        'quality'   => 'Качество работы',
        'deadline'  => 'Нарушение сроков',
        'behavior'  => 'Поведение сотрудника',
        'ticket'    => 'По заявке',
        'other'     => 'Другое',
    ];

    private array $statuses = [
        'pending'   => 'На рассмотрении',
        'in_review' => 'Рассматривается',
        'resolved'  => 'Решено',
        'rejected'  => 'Отклонено',
        'closed'    => 'Закрыто',
    ];

    public function index(Request $request)
    {
        $admin = $this->requireOperationalAdmin();

        $query = OrganizationComplaint::with([
            'organization', 'createdBy', 'targetUser', 'ticket.category', 'reviewedBy',
        ]);

        if ($this->isOrgAdmin($admin)) {
            $query->where('organization_id', $admin->organization_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('org_id') && $this->isSuperAdmin($admin)) {
            $query->where('organization_id', $request->org_id);
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->search);
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $statsBase = OrganizationComplaint::query()
            ->when($this->isOrgAdmin($admin), fn ($q) => $q->where('organization_id', $admin->organization_id));

        $stats = [
            'total'     => (clone $statsBase)->count(),
            'pending'   => (clone $statsBase)->where('status', 'pending')->count(),
            'in_review' => (clone $statsBase)->where('status', 'in_review')->count(),
            'resolved'  => (clone $statsBase)->where('status', 'resolved')->count(),
        ];

        $complaints = $query->latest()->paginate(15)->withQueryString();
        $organizations = $this->isSuperAdmin($admin)
            ? Organization::where('active', true)->orderBy('name')->get()
            : collect();

        $types    = $this->types;
        $statuses = $this->statuses;

        return view('admin.complaints.index', compact(
            'complaints', 'stats', 'organizations',
            'admin', 'types', 'statuses'
        ));
    }

    public function show(OrganizationComplaint $complaint)
    {
        $admin = $this->requireOperationalAdmin();
        $this->ensureComplaintVisible($complaint, $admin);

        $complaint->load(['organization', 'createdBy', 'targetUser', 'ticket.category', 'reviewedBy']);

        $types    = $this->types;
        $statuses = $this->statuses;

        return view('admin.complaints.show', compact('complaint', 'types', 'statuses', 'admin'));
    }

    public function resolve(Request $request, OrganizationComplaint $complaint)
    {
        $admin = $this->requireOperationalAdmin();
        $this->ensureComplaintVisible($complaint, $admin);

        $data = $request->validate([
            'status'     => ['required', 'in:in_review,resolved,rejected,closed'],
            'resolution' => ['nullable', 'string', 'max:500'],
        ]);

        $complaint->update([
            'status'              => $data['status'],
            'resolution'          => $data['resolution'] ?? null,
            'reviewed_by_user_id' => Auth::id(),
            'reviewed_at'         => now(),
        ]);

        return redirect()
            ->route('admin.complaints.show', $complaint)
            ->with('success', 'Жалоба обновлена');
    }

    private function ensureComplaintVisible(OrganizationComplaint $complaint, $admin): void
    {
        if ($this->isOrgAdmin($admin) && (int) $complaint->organization_id !== (int) $admin->organization_id) {
            abort(403, 'Жалоба не относится к вашей организации');
        }
    }
}
