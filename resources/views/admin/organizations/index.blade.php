@extends('admin.layout')

@section('title', 'Список ЖКХ')
@section('topbar-title', 'Список ЖКХ')
@section('topbar-subtitle', 'Фиксированный справочник организаций Барановичей')

@push('head')
<style>
    .org-toolbar{display:grid;grid-template-columns:minmax(260px,1fr) auto auto;gap:12px;align-items:end}.org-metrics{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px;margin-bottom:16px}.org-note{padding:16px 18px;border-radius:18px;border:1px solid color-mix(in srgb,var(--primary) 30%,var(--border));background:var(--primary-soft);margin-bottom:18px;color:var(--text);font-weight:800}.org-table{min-width:920px}.org-title{font-weight:950}.org-address{color:var(--muted);font-size:13px;margin-top:3px}.reset-link{height:46px;display:inline-flex;align-items:center;justify-content:center;padding:0 18px;border-radius:14px;border:1px solid var(--border);font-weight:950;color:var(--text);text-decoration:none;background:var(--surface)}
    @media(max-width:900px){.org-toolbar,.org-metrics{grid-template-columns:1fr}}
</style>
@endpush

@section('content')
<div class="page-head">
    <div>
        <h1 class="page-title">ЖКХ</h1>
        <p class="page-description">Организации не добавляются вручную. Список фиксируется миграцией, чтобы не плодить случайные ЖКХ в базе.</p>
    </div>
</div>


<div class="org-metrics">
    <div class="metric-card accent"><div class="metric-label">ЖКХ</div><div class="metric-value">{{ $summary['total'] }}</div><div class="metric-hint">активных в системе</div></div>
    <div class="metric-card"><div class="metric-label">Админы</div><div class="metric-value">{{ $summary['admins'] }}</div><div class="metric-hint">назначено</div></div>
    <div class="metric-card"><div class="metric-label">Сотрудники</div><div class="metric-value">{{ $summary['workers'] }}</div><div class="metric-hint">зарегистрированы</div></div>
    <div class="metric-card"><div class="metric-label">Заявки</div><div class="metric-value">{{ $summary['tickets'] }}</div><div class="metric-hint">назначено ЖКХ</div></div>
</div>

<div class="card" style="margin-bottom:18px;"><div class="card-body">
    <form method="GET" action="{{ route('admin.organizations.index') }}" class="org-toolbar">
        <div class="field"><label>Поиск</label><input type="text" name="search" value="{{ request('search') }}" placeholder="Название, район или адрес"></div>
        <button class="btn btn-primary" type="submit">Найти</button><a class="reset-link" href="{{ route('admin.organizations.index') }}">Сбросить</a>
    </form>
</div></div>

<div class="card"><div class="card-body" style="padding:0;overflow:hidden;"><div class="table-responsive"><table class="org-table"><thead><tr><th>ЖКХ</th><th>Город</th><th>Район</th><th>Админы</th><th>Сотрудники</th><th>Заявки</th><th>Статус</th></tr></thead><tbody>
@forelse($organizations as $organization)
    <tr style="cursor:pointer;" onclick="window.location='{{ route('admin.organizations.show', $organization) }}'"><td><div class="org-title"><a href="{{ route('admin.organizations.show', $organization) }}" style="color:inherit;">{{ $organization->name }}</a></div><div class="org-address">{{ $organization->address ?: 'Адрес не указан' }}</div></td><td>@if($organization->city)<span class="pill pill-green no-dot" style="font-size:12px;">{{ $organization->city->name }}</span>@else<span class="txt-muted">—</span>@endif</td><td>{{ $organization->district ?: '—' }}</td><td><b>{{ $organization->admins_count }}</b></td><td><b>{{ $organization->workers_count }}</b></td><td><b>{{ $organization->tickets_count }}</b></td><td><span class="status-pill status-completed">Активно</span></td></tr>
@empty <tr><td colspan="7" class="empty-state">ЖКХ не найдены.</td></tr> @endforelse
</tbody></table></div><div style="padding:18px 22px;">{{ $organizations->links() }}</div></div></div>
@endsection
