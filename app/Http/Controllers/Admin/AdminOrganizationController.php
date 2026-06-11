<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\AdminAccess;
use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Http\Request;

class AdminOrganizationController extends Controller
{
    use AdminAccess;

    public function index(Request $request)
    {
        $this->requireSuperAdmin();

        $query = Organization::withCount([
            'admins',
            'workers',
            'tickets' => fn ($q) => $q->whereNull('deleted_at'),
        ]);

        if ($request->filled('search')) {
            $search = trim((string) $request->search);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('district', 'like', "%{$search}%")
                    ->orWhere('address', 'like', "%{$search}%");
            });
        }

        $organizations = $query->with('city')->where('active', true)->orderBy('id')->paginate(20)->withQueryString();

        $summary = [
            'total' => Organization::where('active', true)->count(),
            'admins' => Organization::where('active', true)->withCount('admins')->get()->sum('admins_count'),
            'workers' => Organization::where('active', true)->withCount('workers')->get()->sum('workers_count'),
            'tickets' => Organization::where('active', true)->withCount(['tickets' => fn ($q) => $q->whereNull('deleted_at')])->get()->sum('tickets_count'),
        ];

        return view('admin.organizations.index', compact('organizations', 'summary'));
    }

    public function show(Organization $organization)
    {
        $this->requireSuperAdmin();

        $organization->load('city');

        $workers = User::where('role', 'worker')
            ->where('organization_id', $organization->id)
            ->withCount([
                'assignedTickets as total_tickets',
                'assignedTickets as active_tickets' => fn ($q) => $q->whereNotIn('status', ['completed', 'rejected', 'duplicate']),
                'assignedTickets as completed_tickets' => fn ($q) => $q->where('status', 'completed'),
            ])
            ->latest()
            ->get();

        $admins = User::where('role', 'org_admin')
            ->where('organization_id', $organization->id)
            ->latest()
            ->get();

        $statuses = [
            'created' => 'Новая', 'assigned' => 'Назначена', 'accepted' => 'Принята',
            'in_progress' => 'В работе', 'completed' => 'Выполнена',
            'rejected' => 'Отклонена', 'duplicate' => 'Дубликат',
        ];

        $ticketStats = collect($statuses)->mapWithKeys(fn ($label, $status) => [
            $status => Ticket::where('assigned_org_id', $organization->id)
                ->where('status', $status)
                ->whereNull('deleted_at')
                ->count()
        ]);

        $totalTickets = Ticket::where('assigned_org_id', $organization->id)
            ->whereNull('deleted_at')->count();

        $recentTickets = Ticket::with(['category', 'assignedWorker'])
            ->where('assigned_org_id', $organization->id)
            ->whereNull('deleted_at')
            ->latest()
            ->limit(10)
            ->get();

        return view('admin.organizations.show', compact(
            'organization', 'workers', 'admins',
            'ticketStats', 'totalTickets', 'recentTickets', 'statuses'
        ));
    }
}
