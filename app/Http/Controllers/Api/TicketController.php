<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\TicketPhoto;
use App\Models\TicketStatusHistory;
use App\Models\TicketClaimRequest;
use App\Models\User;
use App\Services\PointsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class TicketController extends Controller
{
    private array $statuses = [
        'created',
        'moderation',
        'assigned',
        'accepted',
        'in_progress',
        'completed',
        'rejected',
        'duplicate',
        'problem',
        'postponed',
    ];

    public function store(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lng' => ['required', 'numeric', 'between:-180,180'],
            'address_text' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:200'],
            'priority' => ['nullable', Rule::in(['low', 'normal', 'high'])],
            'photo_before' => ['required', 'image', 'max:5120'],
        ]);

        $ticket = Ticket::create([
            'user_id' => $user->id,
            'category_id' => $data['category_id'],
            'status' => 'created',
            'priority' => $data['priority'] ?? 'normal',
            'lat' => $data['lat'],
            'lng' => $data['lng'],
            'address_text' => $data['address_text'] ?? null,
            'description' => $data['description'] ?? null,
        ]);

        $path = $request->file('photo_before')->store('tickets', 'public');

        TicketPhoto::create([
            'ticket_id' => $ticket->id,
            'type' => 'before',
            'path' => $path,
        ]);

        $this->writeHistory($ticket, null, 'created', $user->id, 'Заявка создана');

        // Award points for ticket creation
        $creationPoints = PointsService::pointsForCreation();
        if ($creationPoints > 0) {
            PointsService::award($user, $creationPoints, "Создание заявки #{$ticket->id}");
        }

        return response()->json([
            'message' => 'Заявка создана успешно',
            'ticket' => $this->loadTicket($ticket),
        ], 201);
    }

    public function myTickets(Request $request)
    {
        $tickets = Ticket::with(['category', 'photos', 'assignedOrganization'])
            ->where('user_id', $request->user()->id)
            ->whereNull('deleted_at')
            ->latest()
            ->get();

        return response()->json([
            'tickets' => $tickets,
        ]);
    }

    public function show(Request $request, Ticket $ticket)
    {
        if (!$this->canView($request, $ticket)) {
            return response()->json(['message' => 'Нет доступа к заявке'], 403);
        }

        return response()->json([
            'ticket' => $this->loadTicket($ticket),
        ]);
    }

    public function destroy(Request $request, Ticket $ticket)
    {
        $user = $request->user();

        if ($user->role !== 'resident' || (int) $ticket->user_id !== (int) $user->id) {
            return response()->json(['message' => 'Удаление доступно только автору заявки'], 403);
        }

        if ($ticket->deleted_at) {
            return response()->json(['message' => 'Заявка уже удалена из истории']);
        }

        $ticket->deleted_at = now();
        $ticket->deleted_by_user_id = $user->id;
        $ticket->delete_reason = 'Удалено пользователем из истории';
        $ticket->save();

        $this->writeHistory(
            $ticket,
            $ticket->status,
            $ticket->status,
            $user->id,
            'Заявка удалена пользователем из истории'
        );

        return response()->json(['message' => 'Заявка удалена из истории']);
    }

    public function workerTickets(Request $request)
    {
        $user = $request->user();

        if (!in_array($user->role, ['worker', 'admin', 'super_admin', 'org_admin'], true)) {
            return response()->json(['message' => 'Доступ только для исполнителя или администратора'], 403);
        }

        $tickets = Ticket::with(['category', 'photos', 'user', 'assignedOrganization', 'assignedWorker'])
            ->when($user->role === 'worker', function ($query) use ($user) {
                $query->where(function ($q) use ($user) {
                    $q->where('assigned_worker_id', $user->id)
                        ->orWhere(function ($pool) use ($user) {
                            $pool->where('assigned_org_id', $user->organization_id)
                                ->whereNull('assigned_worker_id');
                        });
                });
            })
            ->whereNull('deleted_at')
            ->whereDoesntHave('activeHides')
            ->whereIn('status', ['assigned', 'accepted', 'in_progress', 'problem', 'postponed', 'completed'])
            ->latest()
            ->get();

        return response()->json([
            'tickets' => $tickets,
        ]);
    }


    public function availableTickets(Request $request)
    {
        $user = $request->user();

        if ($user->role !== 'worker' || !$user->organization_id) {
            return response()->json(['message' => 'Доступно только сотруднику ЖКХ'], 403);
        }

        $tickets = Ticket::with(['category', 'photos', 'assignedOrganization', 'assignedWorker'])
            ->whereNull('deleted_at')
            ->whereDoesntHave('activeHides')
            ->whereNull('assigned_worker_id')
            ->whereIn('status', ['created', 'moderation', 'assigned', 'accepted', 'problem', 'postponed'])
            ->where(function ($query) use ($user) {
                $query->whereNull('assigned_org_id')
                    ->orWhere('assigned_org_id', $user->organization_id);
            })
            ->whereDoesntHave('claimRequests', function ($query) use ($user) {
                $query->where('worker_id', $user->id)->where('status', 'pending');
            })
            ->latest()
            ->limit(200)
            ->get();

        return response()->json([
            'tickets' => $tickets,
        ]);
    }

    public function claimRequest(Request $request, Ticket $ticket)
    {
        $user = $request->user();

        if ($user->role !== 'worker' || !$user->organization_id) {
            return response()->json(['message' => 'Доступно только сотруднику ЖКХ'], 403);
        }

        $data = $request->validate([
            'comment' => ['nullable', 'string', 'max:255'],
        ]);

        if (!$this->ticketCanBeRequestedByWorker($ticket, $user)) {
            return response()->json(['message' => 'Эту заявку сейчас нельзя запросить'], 422);
        }

        $existing = TicketClaimRequest::where('ticket_id', $ticket->id)
            ->where('worker_id', $user->id)
            ->where('status', 'pending')
            ->first();

        if ($existing) {
            return response()->json([
                'message' => 'Запрос уже отправлен',
                'claim_request' => $existing,
            ]);
        }

        $claim = TicketClaimRequest::create([
            'ticket_id' => $ticket->id,
            'worker_id' => $user->id,
            'organization_id' => $user->organization_id,
            'status' => 'pending',
            'comment' => $data['comment'] ?? null,
        ]);

        return response()->json([
            'message' => 'Запрос отправлен администратору ЖКХ',
            'claim_request' => $claim->load(['ticket.category', 'worker', 'organization']),
        ], 201);
    }

    public function changeStatus(Request $request, Ticket $ticket)
    {
        if (!$this->canWorkWithTicket($request, $ticket)) {
            return response()->json(['message' => 'Нет доступа для изменения статуса'], 403);
        }

        $data = $request->validate([
            'status' => ['required', Rule::in($this->statuses)],
            'comment' => ['nullable', 'string', 'max:255'],
        ]);

        $oldStatus = $ticket->status;
        $ticket->status = $data['status'];

        if ($request->user()->role === 'worker' && in_array($data['status'], ['accepted', 'in_progress'], true)) {
            $ticket->assigned_worker_id = $request->user()->id;
            if (!$ticket->assigned_org_id) {
                $ticket->assigned_org_id = $request->user()->organization_id;
            }
        }

        $wasAlreadyCompleted = $oldStatus === 'completed';

        if ($data['status'] === 'completed') {
            $ticket->closed_at = now();
        }

        $ticket->save();

        $this->writeHistory(
            $ticket,
            $oldStatus,
            $data['status'],
            $request->user()->id,
            $data['comment'] ?? null
        );

        if ($data['status'] === 'completed' && !$wasAlreadyCompleted) {
            $this->awardCompletionPoints($ticket, $request->user());
        }

        return response()->json([
            'message' => 'Статус заявки изменён',
            'ticket' => $this->loadTicket($ticket),
        ]);
    }

    public function uploadAfterPhoto(Request $request, Ticket $ticket)
    {
        if (!$this->canWorkWithTicket($request, $ticket)) {
            return response()->json(['message' => 'Нет доступа для закрытия заявки'], 403);
        }

        $data = $request->validate([
            'photo_after' => ['required', 'image', 'max:5120'],
            'comment' => ['nullable', 'string', 'max:255'],
        ]);

        $path = $request->file('photo_after')->store('tickets', 'public');

        TicketPhoto::create([
            'ticket_id' => $ticket->id,
            'type' => 'after',
            'path' => $path,
        ]);

        $oldStatus = $ticket->status;
        $wasAlreadyCompleted = $oldStatus === 'completed';
        $ticket->status = 'completed';
        $ticket->closed_at = now();

        if ($request->user()->role === 'worker') {
            $ticket->assigned_worker_id = $request->user()->id;
            if (!$ticket->assigned_org_id) {
                $ticket->assigned_org_id = $request->user()->organization_id;
            }
        }

        $ticket->save();

        $this->writeHistory(
            $ticket,
            $oldStatus,
            'completed',
            $request->user()->id,
            $data['comment'] ?? 'Заявка закрыта с фото после выполнения'
        );

        if (!$wasAlreadyCompleted) {
            $this->awardCompletionPoints($ticket, $request->user());
        }

        return response()->json([
            'message' => 'Заявка закрыта успешно',
            'ticket' => $this->loadTicket($ticket),
        ]);
    }

    public function adminTickets(Request $request)
    {
        if (!in_array($request->user()->role, ['admin', 'super_admin', 'org_admin'], true)) {
            return response()->json(['message' => 'Доступ только для администратора'], 403);
        }

        $tickets = Ticket::with(['category', 'photos', 'user', 'assignedOrganization', 'assignedWorker'])
            ->whereNull('deleted_at')
            ->whereDoesntHave('activeHides')
            ->when($request->user()->role === 'org_admin', fn ($query) => $query->where('assigned_org_id', $request->user()->organization_id))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->status))
            ->when($request->filled('category_id'), fn ($query) => $query->where('category_id', $request->category_id))
            ->latest()
            ->paginate(20);

        return response()->json($tickets);
    }

    public function assign(Request $request, Ticket $ticket)
    {
        if (!in_array($request->user()->role, ['admin', 'super_admin', 'org_admin'], true)) {
            return response()->json(['message' => 'Доступ только для администратора'], 403);
        }

        $data = $request->validate([
            'assigned_org_id' => ['nullable', 'integer', 'exists:organizations,id'],
            'assigned_worker_id' => ['nullable', 'integer', 'exists:users,id'],
            'comment' => ['nullable', 'string', 'max:255'],
        ]);

        $oldStatus = $ticket->status;

        $ticket->assigned_org_id = $data['assigned_org_id'] ?? null;
        $ticket->assigned_worker_id = $data['assigned_worker_id'] ?? null;
        $ticket->status = 'assigned';
        $ticket->save();

        $this->writeHistory(
            $ticket,
            $oldStatus,
            'assigned',
            $request->user()->id,
            $data['comment'] ?? 'Назначен исполнитель'
        );

        return response()->json([
            'message' => 'Исполнитель назначен',
            'ticket' => $this->loadTicket($ticket),
        ]);
    }


    public function residentAvailableTasks(Request $request)
    {
        $user = $request->user();

        if ($user->role !== 'resident') {
            return response()->json(['message' => 'Доступно только для жителей'], 403);
        }

        $tickets = Ticket::with(['category', 'photos', 'assignedOrganization'])
            ->whereNull('deleted_at')
            ->where('available_to_residents', true)
            ->whereNull('assigned_worker_id')
            ->whereIn('status', ['created', 'moderation', 'assigned'])
            ->latest()
            ->limit(100)
            ->get();

        return response()->json(['tickets' => $tickets]);
    }

    public function residentAccept(Request $request, Ticket $ticket)
    {
        $user = $request->user();

        if ($user->role !== 'resident') {
            return response()->json(['message' => 'Доступно только для жителей'], 403);
        }

        if (!$ticket->available_to_residents || $ticket->deleted_at) {
            return response()->json(['message' => 'Задача недоступна'], 422);
        }

        if ($ticket->assigned_worker_id) {
            return response()->json(['message' => 'Задача уже взята'], 422);
        }

        $oldStatus = $ticket->status;
        $ticket->assigned_worker_id = $user->id;
        $ticket->status = 'accepted';
        $ticket->save();

        $this->writeHistory($ticket, $oldStatus, 'accepted', $user->id, 'Взята жителем');

        return response()->json([
            'message' => 'Задача принята',
            'ticket'  => $this->loadTicket($ticket),
        ]);
    }

    private function ticketCanBeRequestedByWorker(Ticket $ticket, User $worker): bool
    {
        if ($ticket->deleted_at || $ticket->activeHides()->exists()) {
            return false;
        }

        if ($ticket->assigned_worker_id) {
            return false;
        }

        if (!in_array($ticket->status, ['created', 'moderation', 'assigned', 'accepted', 'problem', 'postponed'], true)) {
            return false;
        }

        if ($ticket->assigned_org_id && (int) $ticket->assigned_org_id !== (int) $worker->organization_id) {
            return false;
        }

        return true;
    }

    private function canView(Request $request, Ticket $ticket): bool
    {
        $user = $request->user();

        if ($ticket->deleted_at) {
            return false;
        }

        if (in_array($user->role, ['admin', 'super_admin'], true)) {
            return true;
        }

        if ($user->role === 'org_admin') {
            return (int) $ticket->assigned_org_id === (int) $user->organization_id
                && !$ticket->activeHides()->where('organization_id', $user->organization_id)->exists();
        }

        if ($user->role === 'resident') {
            return (int) $ticket->user_id === (int) $user->id;
        }

        if ($user->role === 'worker') {
            if ($ticket->activeHides()->exists()) {
                return false;
            }

            return (int) $ticket->assigned_worker_id === (int) $user->id
                || ($ticket->assigned_worker_id === null && (int) $ticket->assigned_org_id === (int) $user->organization_id);
        }

        return false;
    }

    private function canWorkWithTicket(Request $request, Ticket $ticket): bool
    {
        $user = $request->user();

        if ($ticket->deleted_at || $ticket->activeHides()->exists()) {
            return false;
        }

        if (in_array($user->role, ['admin', 'super_admin'], true)) {
            return true;
        }

        if ($user->role === 'org_admin') {
            return (int) $ticket->assigned_org_id === (int) $user->organization_id;
        }

        // Resident working on a task they accepted
        if ($user->role === 'resident') {
            return $ticket->available_to_residents
                && (int) $ticket->assigned_worker_id === (int) $user->id;
        }

        if ($user->role !== 'worker') {
            return false;
        }

        return (int) $ticket->assigned_worker_id === (int) $user->id
            || ($ticket->assigned_worker_id === null && (int) $ticket->assigned_org_id === (int) $user->organization_id);
    }

    private function loadTicket(Ticket $ticket): Ticket
    {
        return $ticket->load([
            'category',
            'photos',
            'statusHistory.changedBy',
            'user',
            'assignedOrganization',
            'assignedWorker',
        ]);
    }

    private function awardCompletionPoints(Ticket $ticket, ?User $actor): void
    {
        $completionPoints = PointsService::pointsForCompletion();
        if ($completionPoints <= 0) {
            return;
        }

        if ($ticket->user_id) {
            $author = User::find($ticket->user_id);
            if ($author && $author->role === 'resident') {
                PointsService::award($author, $completionPoints, "Выполнена заявка #{$ticket->id}");
            }
        }

        $worker = null;
        if ($actor && $actor->role === 'worker') {
            $worker = $actor;
        } elseif ($ticket->assigned_worker_id) {
            $worker = User::find($ticket->assigned_worker_id);
        }

        if ($worker && $worker->role === 'worker') {
            PointsService::award($worker, $completionPoints, "Выполнение заявки #{$ticket->id}");
        }
    }

    private function writeHistory(Ticket $ticket, ?string $oldStatus, string $newStatus, ?int $changedByUserId, ?string $comment): void
    {
        TicketStatusHistory::create([
            'ticket_id' => $ticket->id,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'changed_by_user_id' => $changedByUserId,
            'comment' => $comment,
        ]);
    }
}
