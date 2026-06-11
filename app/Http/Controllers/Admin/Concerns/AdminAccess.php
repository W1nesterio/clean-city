<?php

namespace App\Http\Controllers\Admin\Concerns;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

trait AdminAccess
{
    protected function requireAdmin(): User
    {
        if (!Auth::check()) {
            redirect()->route('admin.login')->send();
            exit;
        }

        /** @var User $user */
        $user = Auth::user();

        if (!$this->isAnyAdmin($user)) {
            abort(403, 'Доступ запрещён');
        }

        if ($user->banned_at) {
            Auth::logout();
            abort(403, 'Аккаунт заблокирован');
        }

        return $user;
    }

    protected function isAnyAdmin(?User $user = null): bool
    {
        $user ??= Auth::user();

        return $user && in_array($user->role, ['admin', 'super_admin', 'org_admin'], true);
    }

    protected function isSuperAdmin(?User $user = null): bool
    {
        $user ??= Auth::user();

        return $user && in_array($user->role, ['admin', 'super_admin'], true);
    }

    protected function isOrgAdmin(?User $user = null): bool
    {
        $user ??= Auth::user();

        return $user && $user->role === 'org_admin';
    }

    protected function requireSuperAdmin(): User
    {
        $user = $this->requireAdmin();

        if (!$this->isSuperAdmin($user)) {
            abort(403, 'Доступ разрешён только главному администратору');
        }

        return $user;
    }

    protected function requireOrgAdmin(): User
    {
        $user = $this->requireAdmin();

        if (!$this->isOrgAdmin($user)) {
            abort(403, 'Действие доступно только администратору ЖКХ');
        }

        if (!$user->organization_id) {
            abort(403, 'У администратора ЖКХ не указана организация');
        }

        return $user;
    }

    /**
     * Allow both super_admin and org_admin to access operational sections.
     * org_admin must have organization_id set; super_admin sees everything.
     */
    protected function requireOperationalAdmin(): User
    {
        $user = $this->requireAdmin();

        if ($this->isOrgAdmin($user) && !$user->organization_id) {
            abort(403, 'У администратора ЖКХ не указана организация');
        }

        return $user;
    }

    protected function scopeTicketsForAdmin(Builder $query, ?User $user = null, bool $includeHiddenForOrgAdmin = false): Builder
    {
        $user ??= Auth::user();

        $query->whereNull('deleted_at');

        if ($this->isOrgAdmin($user)) {
            $query->where('assigned_org_id', $user->organization_id);

            if (!$includeHiddenForOrgAdmin) {
                $query->whereDoesntHave('activeHides', function ($hideQuery) use ($user) {
                    $hideQuery->where('organization_id', $user->organization_id);
                });
            }
        } else {
            $query->whereDoesntHave('activeHides');
        }

        return $query;
    }

    protected function ensureTicketVisibleToAdmin(Ticket $ticket, ?User $user = null): void
    {
        $user ??= Auth::user();

        if ($ticket->deleted_at) {
            abort(404);
        }

        if ($this->isOrgAdmin($user) && (int) $ticket->assigned_org_id !== (int) $user->organization_id) {
            abort(403, 'Заявка не относится к вашей организации');
        }
    }
}
