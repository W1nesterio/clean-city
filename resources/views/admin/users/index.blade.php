@extends('admin.layout')

@section('title', 'Пользователи')
@section('topbar-title', 'Пользователи')
@section('topbar-subtitle', 'Поиск и блокировка аккаунтов')

@push('head')
<style>
    .users-toolbar{display:grid;grid-template-columns:1.4fr 180px 170px auto auto;gap:12px;align-items:end}
    .user-metrics{display:grid;grid-template-columns:repeat(6,minmax(0,1fr));gap:14px;margin-bottom:16px}
    .user-metric{background:var(--surface);border:1px solid var(--border);box-shadow:var(--shadow);border-radius:20px;padding:16px 18px}
    .user-metric strong{display:block;font-size:26px;line-height:1;letter-spacing:-.04em;color:var(--text)}
    .user-metric span{display:block;margin-top:8px;font-size:12px;font-weight:900;text-transform:uppercase;letter-spacing:.06em;color:var(--muted)}
    .users-table-wrap{overflow-x:auto}.users-table{min-width:980px}.user-cell{display:flex;align-items:center;gap:12px;min-width:230px}
    .user-avatar{width:42px;height:42px;border-radius:16px;background:#e8f7ee;color:var(--primary-dark);display:grid;place-items:center;font-weight:950;flex:0 0 auto}.user-name{font-weight:950;color:var(--text)}.user-email{color:var(--muted);font-size:13px;margin-top:2px;word-break:break-all}
    .ban-form{display:grid;grid-template-columns:minmax(190px,1fr) auto;gap:8px;align-items:center;min-width:310px}.ban-form input{height:40px}.state-note{font-size:13px;color:var(--muted);margin-top:4px;max-width:260px}.banned-row{background:color-mix(in srgb,#fee2e2 38%,var(--surface))}.reset-link{height:46px;display:inline-flex;align-items:center;justify-content:center;padding:0 18px;border-radius:14px;border:1px solid var(--border);font-weight:950;color:var(--text);text-decoration:none;background:var(--surface)}
    @media(max-width:1280px){.users-toolbar{grid-template-columns:1fr 1fr}.user-metrics{grid-template-columns:repeat(3,1fr)}}
    @media(max-width:720px){.users-toolbar,.user-metrics{grid-template-columns:1fr}.ban-form{grid-template-columns:1fr;min-width:0}}
</style>
@endpush

@section('content')
<div class="page-head">
    <div>
        <h1 class="page-title">Пользователи</h1>
        <p class="page-description">Главный админ здесь ищет пользователей и блокирует спам. Операционная работа с заявками и ЖКХ вынесена из этого раздела.</p>
    </div>
</div>

<div class="user-metrics">
    <div class="user-metric"><strong>{{ $summary['total'] }}</strong><span>Всего</span></div>
    <div class="user-metric"><strong>{{ $summary['residents'] }}</strong><span>Жители</span></div>
    <div class="user-metric"><strong>{{ $summary['workers'] }}</strong><span>Сотрудники</span></div>
    <div class="user-metric"><strong>{{ $summary['org_admins'] }}</strong><span>Админы ЖКХ</span></div>
    <div class="user-metric"><strong>{{ $summary['super_admins'] }}</strong><span>Главные</span></div>
    <div class="user-metric"><strong>{{ $summary['banned'] }}</strong><span>Бан</span></div>
</div>

<div class="card" style="margin-bottom:18px;"><div class="card-body">
    <form method="GET" action="{{ route('admin.users.index') }}" class="users-toolbar">
        <div class="field"><label>Поиск</label><input type="text" name="search" value="{{ request('search') }}" placeholder="Имя или email"></div>
        <div class="field"><label>Роль</label><select name="role"><option value="">Все</option>@foreach($roleLabels as $role=>$label)<option value="{{ $role }}" @selected(request('role')===$role)>{{ $label }}</option>@endforeach</select></div>
        <div class="field"><label>Статус</label><select name="state"><option value="">Все</option><option value="active" @selected(request('state')==='active')>Активные</option><option value="banned" @selected(request('state')==='banned')>Забанены</option></select></div>
        <button class="btn btn-primary" type="submit">Найти</button><a class="reset-link" href="{{ route('admin.users.index') }}">Сбросить</a>
    </form>
</div></div>

<div class="card"><div class="card-body" style="padding:0;overflow:hidden;">
    <div class="users-table-wrap"><table class="users-table"><thead><tr><th>Пользователь</th><th>Роль</th><th>Статус</th><th>Действие</th></tr></thead><tbody>
    @forelse($users as $user)
        @php $isLockedSuperAdmin = in_array($user->role, ['admin','super_admin'], true); @endphp
        <tr class="{{ $user->banned_at ? 'banned-row' : '' }}">
            <td><div class="user-cell"><div class="user-avatar">{{ mb_substr($user->name ?: $user->email,0,1) }}</div><div><div class="user-name">{{ $user->name }}</div><div class="user-email">{{ $user->email }}</div><div class="ticket-meta">создан: {{ $user->created_at?->format('d.m.Y H:i') }}</div></div></div></td>
            <td><span class="status-pill">{{ $roleLabels[$user->role] ?? $user->role }}</span></td>
            <td>@if($user->banned_at)<span class="status-pill status-rejected">Забанен</span><div class="state-note">{{ $user->ban_reason }}</div>@else<span class="status-pill status-completed">Активен</span>@endif</td>
            <td>
                @if($isLockedSuperAdmin)
                    <span class="status-pill">Защищён</span>
                    <div class="state-note">Главный админ не редактируется из списка.</div>
                @elseif($user->banned_at)
                    <form method="POST" action="{{ route('admin.users.unban', $user) }}">@csrf<button class="btn btn-primary btn-sm" type="submit">Разбанить</button></form>
                @else
                    <form method="POST" action="{{ route('admin.users.ban', $user) }}" class="ban-form">@csrf<input type="text" name="ban_reason" required placeholder="Причина бана"><button class="btn btn-danger btn-sm" type="submit">Забанить</button></form>
                @endif
            </td>
        </tr>
    @empty <tr><td colspan="4" class="empty-state">Пользователи не найдены.</td></tr> @endforelse
    </tbody></table></div>
    <div style="padding:18px 22px;">{{ $users->links() }}</div>
</div></div>
@endsection
