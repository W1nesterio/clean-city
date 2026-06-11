<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\AdminAccess;
use App\Http\Controllers\Controller;
use App\Models\TicketClaimRequest;
use App\Models\TicketStatusHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class AdminClaimRequestController extends Controller
{
    use AdminAccess;

    private array $statuses = [
        'pending' => 'Ожидает',
        'approved' => 'Одобрен',
        'rejected' => 'Отклонён',
        'cancelled' => 'Отменён',
    ];

    public function index(Request $request)
    {
        $admin = $this->requireOperationalAdmin();

        $query = TicketClaimRequest::with(['ticket.category', 'ticket.user', 'worker', 'organization', 'reviewer'])
            ->when($this->isOrgAdmin($admin), fn ($q) => $q->where('organization_id', $admin->organization_id))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = trim((string) $request->search);
                $q->where(function ($sub) use ($search) {
                    $sub->whereHas('worker', fn ($w) => $w->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"))
                        ->orWhereHas('ticket', function ($t) use ($search) {
                            $t->where('description', 'like', "%{$search}%")
                                ->orWhere('address_text', 'like', "%{$search}%");
                            if (is_numeric($search)) {
                                $t->orWhere('id', (int) $search);
                            }
                        });
                });
            });

        $statsBase = TicketClaimRequest::query()
            ->when($this->isOrgAdmin($admin), fn ($q) => $q->where('organization_id', $admin->organization_id));
        $stats = [
            'pending' => (clone $statsBase)->where('status', 'pending')->count(),
            'approved' => (clone $statsBase)->where('status', 'approved')->count(),
            'rejected' => (clone $statsBase)->where('status', 'rejected')->count(),
            'total' => (clone $statsBase)->count(),
        ];

        $requests = $query->latest()->paginate(15)->withQueryString();
        $statusLabels = $this->statuses;

        return view('admin.claim_requests.index', compact('requests', 'statusLabels', 'stats'));
    }

    public function approve(Request $request, TicketClaimRequest $claimRequest)
    {
        $admin = $this->requireOperationalAdmin();
        $this->ensureClaimBelongsToAdmin($claimRequest, $admin);

        if ($claimRequest->status !== 'pending') {
            return back()->withErrors(['claim' => 'Запрос уже обработан.']);
        }

        $ticket = $claimRequest->ticket;
        if (!$ticket || $ticket->deleted_at || $ticket->activeHides()->exists()) {
            return back()->withErrors(['claim' => 'Заявка недоступна.']);
        }

        if ($ticket->assigned_worker_id) {
            return back()->withErrors(['claim' => 'Заявка уже закреплена за исполнителем.']);
        }

        if ($ticket->assigned_org_id && (int) $ticket->assigned_org_id !== (int) $admin->organization_id) {
            return back()->withErrors(['claim' => 'Заявка закреплена за другим ЖКХ.']);
        }

        $oldStatus = $ticket->status;
        $ticket->update([
            'assigned_org_id' => $admin->organization_id,
            'assigned_worker_id' => $claimRequest->worker_id,
            'status' => 'assigned',
            'closed_at' => null,
        ]);

        $claimRequest->update([
            'status' => 'approved',
            'reviewed_by_user_id' => $admin->id,
            'reviewed_at' => now(),
            'resolution' => $request->input('resolution') ?: 'Запрос одобрен',
        ]);

        TicketClaimRequest::where('ticket_id', $ticket->id)
            ->where('id', '!=', $claimRequest->id)
            ->where('status', 'pending')
            ->update([
                'status' => 'rejected',
                'reviewed_by_user_id' => $admin->id,
                'reviewed_at' => now(),
                'resolution' => 'Заявка назначена другому исполнителю',
            ]);

        TicketStatusHistory::create([
            'ticket_id' => $ticket->id,
            'old_status' => $oldStatus,
            'new_status' => 'assigned',
            'changed_by_user_id' => $admin->id,
            'comment' => 'Одобрен запрос исполнителя: ' . optional($claimRequest->worker)->name,
        ]);

        return back()->with('success', 'Запрос одобрен, заявка назначена исполнителю');
    }

    public function reject(Request $request, TicketClaimRequest $claimRequest)
    {
        $admin = $this->requireOperationalAdmin();
        $this->ensureClaimBelongsToAdmin($claimRequest, $admin);

        if ($claimRequest->status !== 'pending') {
            return back()->withErrors(['claim' => 'Запрос уже обработан.']);
        }

        $data = $request->validate([
            'resolution' => ['nullable', 'string', 'max:255'],
        ]);

        $claimRequest->update([
            'status' => 'rejected',
            'reviewed_by_user_id' => $admin->id,
            'reviewed_at' => now(),
            'resolution' => $data['resolution'] ?? 'Отклонено администратором ЖКХ',
        ]);

        return back()->with('success', 'Запрос отклонён');
    }

    private function ensureClaimBelongsToAdmin(TicketClaimRequest $claimRequest, $admin): void
    {
        if ($this->isOrgAdmin($admin) && (int) $claimRequest->organization_id !== (int) $admin->organization_id) {
            abort(403, 'Запрос не относится к вашему ЖКХ');
        }
    }
}
