<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\AdminAccess;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Organization;
use App\Models\Ticket;
use App\Models\TicketHide;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class AdminAnalyticsController extends Controller
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

    private array $periodLabels = [
        'today' => 'Сегодня',
        'week' => 'Неделя',
        'month' => 'Месяц',
        'half_year' => 'Полгода',
        'year' => 'Год',
        'custom' => 'Период',
    ];

    public function index(Request $request)
    {
        return view('admin.analytics.index', $this->buildReportData($request));
    }

    public function pdf(Request $request)
    {
        return response()
            ->view('admin.analytics.pdf', $this->buildReportData($request))
            ->header('Content-Type', 'text/html; charset=UTF-8');
    }

    public function export(Request $request)
    {
        $admin = $this->requireOperationalAdmin();
        [$dateFrom, $dateTo] = $this->resolvePeriod($request);

        $query = Ticket::query()->with(['category', 'assignedOrganization', 'assignedWorker']);
        $this->applyFilters($query, $request, $dateFrom, $dateTo, $admin);

        $tickets = $query->latest()->get();
        $statusLabels = $this->statuses;
        $filename = 'clean_city_report_' . now()->format('Y_m_d_H_i') . '.csv';

        return response()->streamDownload(function () use ($tickets, $statusLabels) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, ['№', 'Дата', 'Категория', 'Статус', 'ЖКХ', 'Исполнитель', 'Адрес', 'Описание'], ';');

            foreach ($tickets as $ticket) {
                fputcsv($handle, [
                    $ticket->id,
                    optional($ticket->created_at)->format('d.m.Y H:i'),
                    $ticket->category->name ?? '',
                    $statusLabels[$ticket->status] ?? $ticket->status,
                    $ticket->assignedOrganization->name ?? '',
                    $ticket->assignedWorker->name ?? '',
                    $ticket->address_text ?? '',
                    $ticket->description ?? '',
                ], ';');
            }
            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function buildReportData(Request $request): array
    {
        $admin = $this->requireOperationalAdmin();
        [$dateFrom, $dateTo, $period] = $this->resolvePeriod($request);

        $baseQuery = Ticket::query();
        $this->applyFilters($baseQuery, $request, $dateFrom, $dateTo, $admin);

        $total = (clone $baseQuery)->count();
        $completed = (clone $baseQuery)->where('status', 'completed')->count();
        $rejected = (clone $baseQuery)->where('status', 'rejected')->count();
        $problemCount = (clone $baseQuery)->where('status', 'problem')->count();
        $postponedCount = (clone $baseQuery)->where('status', 'postponed')->count();

        $summary = [
            'total' => $total,
            'created' => (clone $baseQuery)->whereIn('status', ['created', 'moderation'])->count(),
            'active' => (clone $baseQuery)->whereIn('status', ['assigned', 'accepted', 'in_progress', 'problem', 'postponed'])->count(),
            'completed' => $completed,
            'rejected' => $rejected,
            'problem' => $problemCount,
            'postponed' => $postponedCount,
            'unassigned' => (clone $baseQuery)->whereNull('assigned_org_id')->whereNull('assigned_worker_id')->count(),
            'hidden' => $this->hiddenCount($admin),
            'completion_rate' => $total > 0 ? round(($completed / $total) * 100, 1) : 0,
            'rejection_rate' => $total > 0 ? round(($rejected / $total) * 100, 1) : 0,
            'problem_rate' => $total > 0 ? round((($problemCount + $postponedCount) / $total) * 100, 1) : 0,
        ];

        $statusRows = collect($this->statuses)
            ->map(fn ($label, $status) => [
                'status' => $status,
                'label' => $label,
                'count' => (clone $baseQuery)->where('status', $status)->count(),
            ])
            ->filter(fn ($row) => $row['count'] > 0)
            ->values();

        $categoryRows = Category::query()
            ->orderBy('name')
            ->get()
            ->map(fn (Category $category) => [
                'name' => $category->name,
                'active' => $category->active,
                'total' => $this->countFor($request, $dateFrom, $dateTo, $admin, fn ($q) => $q->where('category_id', $category->id)),
                'in_work' => $this->countFor($request, $dateFrom, $dateTo, $admin, fn ($q) => $q->whereIn('status', ['assigned', 'accepted', 'in_progress', 'problem', 'postponed'])->where('category_id', $category->id)),
                'completed' => $this->countFor($request, $dateFrom, $dateTo, $admin, fn ($q) => $q->where('status', 'completed')->where('category_id', $category->id)),
                'rejected' => $this->countFor($request, $dateFrom, $dateTo, $admin, fn ($q) => $q->where('status', 'rejected')->where('category_id', $category->id)),
            ])
            ->filter(fn ($row) => $row['total'] > 0 || $row['in_work'] > 0 || $row['completed'] > 0 || $row['rejected'] > 0)
            ->values();

        $organizations = $this->isOrgAdmin($admin)
            ? Organization::where('id', $admin->organization_id)->get()
            : Organization::orderBy('name')->get();

        $organizationRows = $organizations->map(fn (Organization $organization) => [
            'name' => $organization->name,
            'active' => $organization->active,
            'workers' => User::where('role', 'worker')->where('organization_id', $organization->id)->count(),
            'total' => $this->countFor($request, $dateFrom, $dateTo, $admin, fn ($q) => $q->where('assigned_org_id', $organization->id)),
            'in_work' => $this->countFor($request, $dateFrom, $dateTo, $admin, fn ($q) => $q->where('assigned_org_id', $organization->id)->whereIn('status', ['assigned', 'accepted', 'in_progress', 'problem', 'postponed'])),
            'completed' => $this->countFor($request, $dateFrom, $dateTo, $admin, fn ($q) => $q->where('assigned_org_id', $organization->id)->where('status', 'completed')),
            'hidden' => TicketHide::where('organization_id', $organization->id)->where('active', true)->count(),
        ])->filter(fn ($row) => $row['total'] > 0 || $row['hidden'] > 0 || $row['workers'] > 0)->values();

        $workerRows = User::where('role', 'worker')
            ->with('organization')
            ->when($this->isOrgAdmin($admin), fn ($q) => $q->where('organization_id', $admin->organization_id))
            ->orderBy('name')
            ->get()
            ->map(fn (User $worker) => [
                'name' => $worker->name,
                'email' => $worker->email,
                'organization' => $worker->organization->name ?? '—',
                'total' => $this->countFor($request, $dateFrom, $dateTo, $admin, fn ($q) => $q->where('assigned_worker_id', $worker->id)),
                'in_work' => $this->countFor($request, $dateFrom, $dateTo, $admin, fn ($q) => $q->where('assigned_worker_id', $worker->id)->whereIn('status', ['assigned', 'accepted', 'in_progress', 'problem', 'postponed'])),
                'completed' => $this->countFor($request, $dateFrom, $dateTo, $admin, fn ($q) => $q->where('assigned_worker_id', $worker->id)->where('status', 'completed')),
            ])->filter(fn ($row) => $row['total'] > 0 || $row['in_work'] > 0 || $row['completed'] > 0)->values();

        $latestTicketsQuery = Ticket::with(['category', 'assignedOrganization', 'assignedWorker', 'user']);
        $this->applyFilters($latestTicketsQuery, $request, $dateFrom, $dateTo, $admin);
        $latestTickets = $latestTicketsQuery->latest()->limit(25)->get();

        $problemTicketsQuery = Ticket::with(['category', 'assignedOrganization', 'assignedWorker']);
        $this->applyFilters($problemTicketsQuery, $request, $dateFrom, $dateTo, $admin);
        $problemTickets = $problemTicketsQuery->whereIn('status', ['problem', 'postponed'])->latest()->limit(20)->get();

        $dailyRows = $this->dailyRows($request, $dateFrom, $dateTo, $admin);
        $categories = Category::where('active', true)->orderBy('name')->get();
        $availableOrganizations = $this->isOrgAdmin($admin)
            ? Organization::where('id', $admin->organization_id)->get()
            : Organization::where('active', true)->orderBy('name')->get();
        $workers = User::where('role', 'worker')
            ->when($this->isOrgAdmin($admin), fn ($q) => $q->where('organization_id', $admin->organization_id))
            ->orderBy('name')
            ->get();

        return [
            'summary' => $summary,
            'statusRows' => $statusRows,
            'categoryRows' => $categoryRows,
            'organizationRows' => $organizationRows,
            'workerRows' => $workerRows,
            'latestTickets' => $latestTickets,
            'problemTickets' => $problemTickets,
            'dailyRows' => $dailyRows,
            'categories' => $categories,
            'availableOrganizations' => $availableOrganizations,
            'workers' => $workers,
            'statusLabels' => $this->statuses,
            'periodLabels' => $this->periodLabels,
            'period' => $period,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'isOrgAdmin' => $this->isOrgAdmin($admin),
            'isSuperAdmin' => $this->isSuperAdmin($admin),
            'organizationName' => $admin->organization->name ?? 'ЖКХ',
        ];
    }

    private function countFor(Request $request, Carbon $dateFrom, Carbon $dateTo, $admin, callable $callback): int
    {
        $query = Ticket::query();
        $this->applyFilters($query, $request, $dateFrom, $dateTo, $admin);
        $callback($query);
        return $query->count();
    }

    private function applyFilters(Builder $query, Request $request, Carbon $dateFrom, Carbon $dateTo, $admin): void
    {
        $this->scopeTicketsForAdmin($query, $admin);
        $query->whereBetween('created_at', [$dateFrom->copy()->startOfDay(), $dateTo->copy()->endOfDay()]);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }
        if ($request->filled('assigned_org_id') && !$this->isOrgAdmin($admin)) {
            $query->where('assigned_org_id', $request->assigned_org_id);
        }
        if ($request->filled('assigned_worker_id')) {
            $query->where('assigned_worker_id', $request->assigned_worker_id);
        }
    }

    private function hiddenCount($admin): int
    {
        return TicketHide::query()
            ->where('active', true)
            ->when($this->isOrgAdmin($admin), fn ($q) => $q->where('organization_id', $admin->organization_id))
            ->count();
    }

    private function dailyRows(Request $request, Carbon $dateFrom, Carbon $dateTo, $admin): array
    {
        $days = [];
        $cursor = $dateFrom->copy()->startOfDay();
        $limit = 31;

        while ($cursor->lte($dateTo) && count($days) < $limit) {
            $dayStart = $cursor->copy()->startOfDay();
            $dayEnd = $cursor->copy()->endOfDay();
            $query = Ticket::query();
            $this->applyFilters($query, $request, $dayStart, $dayEnd, $admin);
            $days[] = [
                'label' => $cursor->format('d.m'),
                'count' => $query->count(),
            ];
            $cursor->addDay();
        }

        $max = max(1, ...array_column($days, 'count'));
        return array_map(fn ($row) => $row + ['height' => max(8, (int) round(($row['count'] / $max) * 100))], $days);
    }

    private function resolvePeriod(Request $request): array
    {
        $period = $request->input('period', 'month');
        $today = now();

        if ($period === 'custom') {
            $from = $request->filled('date_from') ? Carbon::parse($request->date_from)->startOfDay() : $today->copy()->startOfMonth();
            $to = $request->filled('date_to') ? Carbon::parse($request->date_to)->endOfDay() : $today->copy()->endOfDay();

            if ($from->greaterThan($to)) {
                [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
            }
            return [$from, $to, 'custom'];
        }

        $dateFrom = match ($period) {
            'today' => $today->copy()->startOfDay(),
            'week' => $today->copy()->subDays(6)->startOfDay(),
            'half_year' => $today->copy()->subMonths(6)->startOfDay(),
            'year' => $today->copy()->subYear()->startOfDay(),
            default => $today->copy()->startOfMonth(),
        };

        return [$dateFrom, $today->copy()->endOfDay(), array_key_exists($period, $this->periodLabels) ? $period : 'month'];
    }
}
