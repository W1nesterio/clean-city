<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Admin\Concerns\AdminAccess;
use App\Models\Organization;
use App\Models\User;
use App\Models\WorkerRegistrationCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminEmployeeController extends Controller
{
    use AdminAccess;
    public function index(Request $request)
    {
        $admin = $this->requireOperationalAdmin();

        $selectedOrganizationId = $admin->organization_id;

        $organizations = Organization::query()
            ->withCount([
                'workers as workers_count' => fn ($query) => $query->where('role', 'worker'),
                'workerRegistrationCodes as total_codes_count',
                'workerRegistrationCodes as active_codes_count' => function ($query) {
                    $query->where('active', true)
                        ->whereColumn('used_count', '<', 'max_uses')
                        ->where(function ($expiresQuery) {
                            $expiresQuery->whereNull('expires_at')
                                ->orWhere('expires_at', '>', now());
                        });
                },
            ])
            ->when($this->isOrgAdmin($admin), fn ($query) => $query->where('id', $admin->organization_id))
            ->orderBy('name')
            ->get();

        $workersQuery = User::query()
            ->where('role', 'worker')
            ->with('organization')
            ->withCount([
                'assignedTickets as assigned_tickets_count',
                'assignedTickets as active_tickets_count' => fn ($query) => $query->whereNotIn('status', ['completed', 'rejected', 'duplicate']),
                'assignedTickets as completed_tickets_count' => fn ($query) => $query->where('status', 'completed'),
            ])
            ->latest();

        if ($selectedOrganizationId) {
            $workersQuery->where('organization_id', $selectedOrganizationId);
        }

        if ($request->filled('worker_search')) {
            $workerSearch = trim((string) $request->input('worker_search'));
            $workersQuery->where(function ($query) use ($workerSearch) {
                $query->where('name', 'like', "%{$workerSearch}%")
                    ->orWhere('email', 'like', "%{$workerSearch}%");
            });
        }

        if ($request->filled('worker_load')) {
            if ($request->input('worker_load') === 'active') {
                $workersQuery->having('active_tickets_count', '>', 0);
            } elseif ($request->input('worker_load') === 'free') {
                $workersQuery->having('active_tickets_count', '=', 0);
            }
        }

        $workers = $workersQuery->paginate(10, ['*'], 'workers_page')->withQueryString();

        $codesQuery = WorkerRegistrationCode::query()
            ->with(['organization', 'createdBy', 'usedBy'])
            ->latest();

        if ($selectedOrganizationId) {
            $codesQuery->where('organization_id', $selectedOrganizationId);
        }

        if ($request->filled('code_search')) {
            $codeSearch = trim((string) $request->input('code_search'));
            $codesQuery->where(function ($query) use ($codeSearch) {
                $query->where('code', 'like', "%{$codeSearch}%")
                    ->orWhere('issued_to', 'like', "%{$codeSearch}%")
                    ->orWhereHas('usedBy', function ($userQuery) use ($codeSearch) {
                        $userQuery->where('name', 'like', "%{$codeSearch}%")
                            ->orWhere('email', 'like', "%{$codeSearch}%");
                    });
            });
        }

        if ($request->filled('code_status')) {
            $status = $request->input('code_status');
            if ($status === 'available') {
                $codesQuery->where('active', true)
                    ->whereColumn('used_count', '<', 'max_uses')
                    ->where(function ($query) {
                        $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
                    });
            } elseif ($status === 'used') {
                $codesQuery->where(function ($query) {
                    $query->where('used_count', '>', 0)->orWhereNotNull('used_by_user_id');
                });
            } elseif ($status === 'revoked') {
                $codesQuery->where('active', false);
            }
        }

        $codes = $codesQuery->paginate(10, ['*'], 'codes_page')->withQueryString();

        $workerStatsBase = User::where('role', 'worker')->when($this->isOrgAdmin($admin), fn ($query) => $query->where('organization_id', $admin->organization_id));
        $totalWorkers = (clone $workerStatsBase)->count();
        $activeWorkers = (clone $workerStatsBase)->whereNotNull('organization_id')->count();
        $activeTasks = (clone $workerStatsBase)
            ->whereHas('assignedTickets', fn ($query) => $query->whereNotIn('status', ['completed', 'rejected', 'duplicate']))
            ->count();
        $availableCodes = WorkerRegistrationCode::query()
            ->when($this->isOrgAdmin($admin), fn ($query) => $query->where('organization_id', $admin->organization_id))
            ->where('active', true)
            ->whereColumn('used_count', '<', 'max_uses')
            ->where(function ($query) {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->count();
        $usedCodes = WorkerRegistrationCode::query()
            ->when($this->isOrgAdmin($admin), fn ($query) => $query->where('organization_id', $admin->organization_id))
            ->where('used_count', '>', 0)
            ->count();

        return view('admin.employees.index', compact(
            'organizations',
            'workers',
            'codes',
            'selectedOrganizationId',
            'totalWorkers',
            'activeWorkers',
            'activeTasks',
            'availableCodes',
            'usedCodes'
        ));
    }

    public function storeCode(Request $request)
    {
        $admin = $this->requireOperationalAdmin();

        $data = $request->validate([
            'organization_id' => ['required', 'integer', 'exists:organizations,id'],
            'issued_to' => ['nullable', 'string', 'max:120'],
        ]);

        if ($this->isOrgAdmin($admin)) {
            $data['organization_id'] = $admin->organization_id;
        }

        $code = $this->generateUniqueCode();

        WorkerRegistrationCode::create([
            'code' => $code,
            'issued_to' => $data['issued_to'] ?? null,
            'organization_id' => $data['organization_id'],
            'created_by_user_id' => $admin->id,
            'max_uses' => 1,
            'used_count' => 0,
            'expires_at' => null,
            'active' => true,
        ]);

        return redirect()
            ->route('admin.employees.index')
            ->with('success', "Ключ {$code} создан");
    }

    public function deactivateCode(WorkerRegistrationCode $workerCode)
    {
        $admin = $this->requireOperationalAdmin();

        if ($this->isOrgAdmin($admin) && (int) $workerCode->organization_id !== (int) $admin->organization_id) {
            abort(403, 'Ключ не относится к вашей организации');
        }

        $workerCode->update([
            'active' => false,
            'revoked_at' => now(),
            'revoked_by_user_id' => $admin->id,
        ]);

        return redirect()
            ->route('admin.employees.index')
            ->with('success', 'Ключ отключён');
    }


    public function deleteCode(WorkerRegistrationCode $workerCode)
    {
        $admin = $this->requireOperationalAdmin();

        if ($this->isOrgAdmin($admin) && (int) $workerCode->organization_id !== (int) $admin->organization_id) {
            abort(403, 'Ключ не относится к вашей организации');
        }

        if ((int) $workerCode->used_count > 0 || $workerCode->used_by_user_id) {
            return back()->withErrors(['code' => 'Использованный ключ оставлен в истории и не удаляется.']);
        }

        $workerCode->delete();

        return redirect()
            ->route('admin.employees.index')
            ->with('success', 'Ключ удалён');
    }

    private function generateUniqueCode(): string
    {
        do {
            $code = 'JKH-' . $this->randomCodePart() . '-' . $this->randomCodePart();
        } while (WorkerRegistrationCode::where('code', $code)->exists());

        return $code;
    }

    private function randomCodePart(): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $part = '';

        for ($i = 0; $i < 4; $i++) {
            $part .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }

        return $part;
    }

}