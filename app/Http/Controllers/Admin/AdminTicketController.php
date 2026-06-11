<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\AdminAccess;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Organization;
use App\Models\Ticket;
use App\Models\TicketHide;
use App\Models\TicketStatusHistory;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class AdminTicketController extends Controller
{
    use AdminAccess;

    private array $statuses = [
        'created' => 'Новая',
        'moderation' => 'На проверке',
        'assigned' => 'Назначена',
        'accepted' => 'Принята',
        'in_progress' => 'В работе',
        'problem' => 'Проблема',
        'postponed' => 'Отложена',
        'completed' => 'Выполнена',
        'rejected' => 'Отклонена',
        'duplicate' => 'Дубликат',
    ];

    public function index(Request $request)
    {
        $admin = $this->requireOperationalAdmin();
        $includeHidden = $this->isOrgAdmin($admin) && in_array($request->input('visibility'), ['hidden', 'all'], true);

        $query = Ticket::with(['category', 'user', 'assignedWorker', 'assignedOrganization', 'photos', 'activeHides.hiddenBy']);
        $this->scopeTicketsForAdmin($query, $admin, $includeHidden);
        $this->applyVisibilityFilter($query, $request, $admin);
        $this->applyFilters($query, $request);

        $statsQuery = Ticket::query();
        $this->scopeTicketsForAdmin($statsQuery, $admin);
        $this->applyFilters($statsQuery, $request, ignoreStatus: true);

        $stats = [
            'total' => (clone $statsQuery)->count(),
            'new' => (clone $statsQuery)->whereIn('status', ['created', 'moderation'])->count(),
            'active' => (clone $statsQuery)->whereIn('status', ['assigned', 'accepted', 'in_progress', 'problem', 'postponed'])->count(),
            'completed' => (clone $statsQuery)->where('status', 'completed')->count(),
            'closed' => (clone $statsQuery)->whereIn('status', ['completed', 'rejected', 'duplicate'])->count(),
            'unassigned' => (clone $statsQuery)->whereNull('assigned_worker_id')->whereNull('assigned_org_id')->count(),
            'hidden' => $this->isOrgAdmin($admin)
                ? TicketHide::where('organization_id', $admin->organization_id)->where('active', true)->count()
                : TicketHide::where('active', true)->count(),
        ];

        $tickets = $query->latest()->paginate(15)->withQueryString();

        $mapQuery = Ticket::with(['category', 'assignedWorker', 'assignedOrganization', 'activeHides.hiddenBy'])
            ->whereNotNull('lat')
            ->whereNotNull('lng');
        $this->scopeTicketsForAdmin($mapQuery, $admin, $includeHidden);
        $this->applyVisibilityFilter($mapQuery, $request, $admin);
        $this->applyFilters($mapQuery, $request);
        $mapTickets = $mapQuery->latest()->limit(400)->get()->map(function (Ticket $ticket) {
            return [
                'id' => $ticket->id,
                'lat' => (float) $ticket->lat,
                'lng' => (float) $ticket->lng,
                'category' => $ticket->category->name ?? 'Без категории',
                'status' => $ticket->status,
                'status_label' => $this->statuses[$ticket->status] ?? $ticket->status,
                'worker' => $ticket->assignedWorker->name ?? null,
                'address' => $ticket->address_text ?: null,
                'description' => $ticket->description ?: null,
                'url' => route('admin.tickets.show', $ticket),
                'created_at' => optional($ticket->created_at)->format('d.m.Y H:i'),
            ];
        })->values();

        $categories = Category::where('active', true)->orderBy('name')->get();
        $organizations = $this->isOrgAdmin($admin)
            ? Organization::where('id', $admin->organization_id)->get()
            : Organization::where('active', true)->orderBy('name')->get();
        $workers = User::where('role', 'worker')
            ->when($this->isOrgAdmin($admin), fn ($q) => $q->where('organization_id', $admin->organization_id))
            ->orderBy('name')
            ->get();
        $statusLabels = $this->statuses;
        $isSuperAdmin = $this->isSuperAdmin($admin);
        $isOrgAdmin = $this->isOrgAdmin($admin);

        return view('admin.tickets.index', compact('tickets', 'mapTickets', 'categories', 'organizations', 'workers', 'statusLabels', 'stats', 'isSuperAdmin', 'isOrgAdmin'));
    }


    public function map(Request $request)
    {
        $admin = $this->requireOperationalAdmin();

        $query = Ticket::with(['category', 'assignedWorker', 'assignedOrganization', 'activeHides.hiddenBy'])
            ->whereNotNull('lat')
            ->whereNotNull('lng');

        $this->scopeTicketsForAdmin($query, $admin);
        $this->applyFilters($query, $request);

        $tickets = $query->latest()->limit(500)->get();

        $mapTickets = $tickets->map(function (Ticket $ticket) {
            return [
                'id' => $ticket->id,
                'lat' => (float) $ticket->lat,
                'lng' => (float) $ticket->lng,
                'category' => $ticket->category->name ?? 'Без категории',
                'status' => $ticket->status,
                'status_label' => $this->statuses[$ticket->status] ?? $ticket->status,
                'worker' => $ticket->assignedWorker->name ?? null,
                'address' => $ticket->address_text ?: null,
                'description' => $ticket->description ?: null,
                'url' => route('admin.tickets.show', $ticket),
                'created_at' => optional($ticket->created_at)->format('d.m.Y H:i'),
            ];
        })->values();

        $categories = Category::where('active', true)->orderBy('name')->get();
        $statusLabels = $this->statuses;

        return view('admin.tickets.map', compact('mapTickets', 'tickets', 'categories', 'statusLabels'));
    }

    public function show(Ticket $ticket)
    {
        $admin = $this->requireOperationalAdmin();
        $this->ensureTicketVisibleToAdmin($ticket, $admin);

        $ticket->load([
            'category',
            'user',
            'assignedWorker',
            'assignedOrganization',
            'photos',
            'statusHistory.changedBy',
            'activeHides.hiddenBy',
        ]);

        $workers = User::where('role', 'worker')
            ->when($this->isOrgAdmin($admin), fn ($q) => $q->where('organization_id', $admin->organization_id))
            ->orderBy('name')
            ->get();
        $organizations = $this->isOrgAdmin($admin)
            ? Organization::where('id', $admin->organization_id)->get()
            : Organization::where('active', true)->orderBy('name')->get();
        $statusLabels = $this->statuses;
        $isSuperAdmin = $this->isSuperAdmin($admin);
        $isOrgAdmin = $this->isOrgAdmin($admin);
        $activeHide = $ticket->activeHides
            ->when($this->isOrgAdmin($admin), fn ($items) => $items->where('organization_id', $admin->organization_id))
            ->first();

        return view('admin.tickets.show', compact('ticket', 'workers', 'organizations', 'statusLabels', 'isSuperAdmin', 'isOrgAdmin', 'activeHide'));
    }

    public function assign(Request $request, Ticket $ticket)
    {
        $admin = $this->requireOperationalAdmin();
        $this->ensureTicketVisibleToAdmin($ticket, $admin);

        $allowedOrgIds = $this->isOrgAdmin($admin) ? [$admin->organization_id] : Organization::pluck('id')->all();

        $data = $request->validate([
            'assigned_org_id' => ['required', 'integer', Rule::in($allowedOrgIds)],
            'assigned_worker_id' => ['nullable', 'integer', 'exists:users,id'],
            'comment' => ['nullable', 'string', 'max:255'],
        ]);

        $worker = null;
        if (!empty($data['assigned_worker_id'])) {
            $worker = User::where('role', 'worker')->findOrFail($data['assigned_worker_id']);

            if ((int) $worker->organization_id !== (int) $data['assigned_org_id']) {
                return back()->withInput()->withErrors(['assigned_worker_id' => 'Исполнитель должен относиться к выбранной организации.']);
            }
        }

        $oldStatus = $ticket->status;

        $ticket->update([
            'assigned_worker_id' => $data['assigned_worker_id'] ?? null,
            'assigned_org_id' => $data['assigned_org_id'],
            'status' => 'assigned',
            'closed_at' => null,
        ]);

        $organization = Organization::find($data['assigned_org_id']);
        $comment = $data['comment']
            ?: 'Назначено: ' . ($organization->name ?? 'организация') . ($worker ? ', исполнитель: ' . $worker->name : '');

        TicketStatusHistory::create([
            'ticket_id' => $ticket->id,
            'old_status' => $oldStatus,
            'new_status' => 'assigned',
            'changed_by_user_id' => Auth::id(),
            'comment' => $comment,
        ]);

        return redirect()->route('admin.tickets.show', $ticket)->with('success', 'Назначение сохранено');
    }

    public function changeStatus(Request $request, Ticket $ticket)
    {
        $admin = $this->requireOperationalAdmin();
        $this->ensureTicketVisibleToAdmin($ticket, $admin);

        $data = $request->validate([
            'status' => ['required', 'string', Rule::in(array_keys($this->statuses))],
            'comment' => ['nullable', 'string', 'max:255'],
        ]);

        $oldStatus = $ticket->status;

        $ticket->update([
            'status' => $data['status'],
            'closed_at' => in_array($data['status'], ['completed', 'rejected', 'duplicate'], true) ? now() : null,
        ]);

        TicketStatusHistory::create([
            'ticket_id' => $ticket->id,
            'old_status' => $oldStatus,
            'new_status' => $data['status'],
            'changed_by_user_id' => Auth::id(),
            'comment' => $data['comment'] ?: 'Статус изменён администратором',
        ]);

        // Award points when admin marks ticket completed
        if ($data['status'] === 'completed' && $oldStatus !== 'completed' && $ticket->user_id) {
            $author = \App\Models\User::find($ticket->user_id);
            if ($author && $author->role === 'resident') {
                \App\Services\PointsService::award(
                    $author,
                    \App\Services\PointsService::pointsForCompletion(),
                    "Выполнена заявка #{$ticket->id}"
                );
            }
        }

        return redirect()->route('admin.tickets.show', $ticket)->with('success', 'Статус обращения изменён');
    }

    public function toggleResidentAvailability(Ticket $ticket)
    {
        $admin = $this->requireOperationalAdmin();
        $this->ensureTicketVisibleToAdmin($ticket, $admin);

        $ticket->update(['available_to_residents' => !$ticket->available_to_residents]);

        $status = $ticket->available_to_residents ? 'доступна жителям' : 'недоступна жителям';
        return redirect()->route('admin.tickets.show', $ticket)->with('success', "Задача теперь {$status}");
    }

    public function hide(Request $request, Ticket $ticket)
    {
        $admin = $this->requireOrgAdmin();
        $this->ensureTicketVisibleToAdmin($ticket, $admin);

        $data = $request->validate([
            'reason' => ['required', 'string', 'max:255'],
        ]);

        TicketHide::updateOrCreate([
            'ticket_id' => $ticket->id,
            'organization_id' => $admin->organization_id,
        ], [
            'hidden_by_user_id' => $admin->id,
            'reason' => $data['reason'],
            'active' => true,
        ]);

        TicketStatusHistory::create([
            'ticket_id' => $ticket->id,
            'old_status' => $ticket->status,
            'new_status' => $ticket->status,
            'changed_by_user_id' => $admin->id,
            'comment' => 'Заявка скрыта ЖКХ: ' . $data['reason'],
        ]);

        return redirect()->route('admin.tickets.index')->with('success', 'Заявка скрыта из рабочей очереди ЖКХ');
    }

    public function restore(Ticket $ticket)
    {
        $admin = $this->requireOrgAdmin();

        if ((int) $ticket->assigned_org_id !== (int) $admin->organization_id) {
            abort(403, 'Заявка не относится к вашей организации');
        }

        TicketHide::where('ticket_id', $ticket->id)
            ->where('organization_id', $admin->organization_id)
            ->where('active', true)
            ->update(['active' => false]);

        TicketStatusHistory::create([
            'ticket_id' => $ticket->id,
            'old_status' => $ticket->status,
            'new_status' => $ticket->status,
            'changed_by_user_id' => $admin->id,
            'comment' => 'Заявка возвращена в рабочую очередь ЖКХ',
        ]);

        return redirect()->route('admin.tickets.show', $ticket)->with('success', 'Заявка восстановлена');
    }

    public function bulkDelete(Request $request)
    {
        $admin = $this->requireSuperAdmin();

        $data = $request->validate([
            'ticket_ids' => ['required', 'array'],
            'ticket_ids.*' => ['integer', 'exists:tickets,id'],
            'delete_reason' => ['nullable', 'string', 'max:255'],
        ]);

        $count = Ticket::whereIn('id', $data['ticket_ids'])
            ->whereNull('deleted_at')
            ->update([
                'deleted_at' => now(),
                'deleted_by_user_id' => $admin->id,
                'delete_reason' => $data['delete_reason'] ?: 'Массовое удаление главным администратором',
            ]);

        return redirect()->route('admin.tickets.index')->with('success', "Заявок убрано: {$count}");
    }

    public function softDelete(Request $request, Ticket $ticket)
    {
        $admin = $this->requireSuperAdmin();

        $data = $request->validate([
            'delete_reason' => ['nullable', 'string', 'max:255'],
        ]);

        $ticket->update([
            'deleted_at' => now(),
            'deleted_by_user_id' => $admin->id,
            'delete_reason' => $data['delete_reason'] ?: 'Удалено главным администратором',
        ]);

        return redirect()->route('admin.tickets.index')->with('success', 'Заявка убрана из системы');
    }

    private function applyFilters($query, Request $request, bool $ignoreStatus = false): void
    {
        if (!$ignoreStatus && $request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('assigned_org_id')) {
            $query->where('assigned_org_id', $request->assigned_org_id);
        }

        if ($request->filled('assigned_worker_id')) {
            $query->where('assigned_worker_id', $request->assigned_worker_id);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        if ($request->filled('photo_after')) {
            if ($request->photo_after === 'yes') {
                $query->whereHas('photos', fn ($q) => $q->where('type', 'after'));
            }

            if ($request->photo_after === 'no') {
                $query->whereDoesntHave('photos', fn ($q) => $q->where('type', 'after'));
            }
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->search);
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                    ->orWhere('address_text', 'like', "%{$search}%");
                if (is_numeric($search)) {
                    $q->orWhere('id', (int) $search);
                }
            });
        }
    }

    private function applyVisibilityFilter($query, Request $request, $admin): void
    {
        if (!$this->isOrgAdmin($admin)) {
            return;
        }

        if ($request->input('visibility') === 'hidden') {
            $query->whereHas('activeHides', fn ($q) => $q->where('organization_id', $admin->organization_id));
        }

        if (!$request->filled('visibility') || $request->input('visibility') === 'active') {
            $query->whereDoesntHave('activeHides', fn ($q) => $q->where('organization_id', $admin->organization_id));
        }
    }
}
