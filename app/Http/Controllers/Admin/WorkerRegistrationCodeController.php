<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\AdminAccess;
use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\WorkerRegistrationCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class WorkerRegistrationCodeController extends Controller
{
    use AdminAccess;

    public function index()
    {
        $admin = $this->requireOrgAdmin();

        $codes = WorkerRegistrationCode::with(['organization', 'createdBy', 'usedBy'])
            ->where('organization_id', $admin->organization_id)
            ->latest()
            ->paginate(20);

        $organizations = Organization::where('active', true)
            ->where('id', $admin->organization_id)
            ->orderBy('name')
            ->get();

        return view('admin.worker_codes.index', compact('codes', 'organizations'));
    }

    public function store(Request $request)
    {
        $admin = $this->requireOrgAdmin();

        $data = $request->validate([
            'organization_id' => ['required', 'integer', 'exists:organizations,id'],
            'code' => ['nullable', 'string', 'max:32', 'regex:/^[A-Za-z0-9\-]+$/', Rule::unique('worker_registration_codes', 'code')],
            'max_uses' => ['required', 'integer', 'min:1', 'max:100'],
            'expires_days' => ['nullable', 'integer', 'min:1', 'max:365'],
        ], [
            'code.regex' => 'Код может содержать только латинские буквы, цифры и дефис.',
        ]);

        $code = $data['code']
            ? strtoupper(trim($data['code']))
            : $this->generateUniqueCode();

        WorkerRegistrationCode::create([
            'code' => $code,
            'organization_id' => $admin->organization_id,
            'created_by_user_id' => Auth::id(),
            'max_uses' => $data['max_uses'],
            'used_count' => 0,
            'expires_at' => isset($data['expires_days']) && $data['expires_days']
                ? now()->addDays((int) $data['expires_days'])
                : null,
            'active' => true,
        ]);

        return redirect()
            ->route('admin.worker-codes.index')
            ->with('success', 'Регистрационный код создан');
    }

    public function deactivate(WorkerRegistrationCode $workerCode)
    {
        $admin = $this->requireOrgAdmin();

        if ((int) $workerCode->organization_id !== (int) $admin->organization_id) {
            abort(403, 'Код не относится к вашей организации');
        }

        $workerCode->update([
            'active' => false,
        ]);

        return redirect()
            ->route('admin.worker-codes.index')
            ->with('success', 'Регистрационный код отключён');
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
