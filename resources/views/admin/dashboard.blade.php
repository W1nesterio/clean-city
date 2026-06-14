@extends('admin.layout')

@section('title', auth()->user()->role === 'org_admin' ? 'Обзор ЖКХ' : 'Контроль платформы')
@section('topbar-title', auth()->user()->role === 'org_admin' ? 'Обзор ЖКХ' : 'Контроль платформы')
@section('topbar-subtitle', auth()->user()->role === 'org_admin' ? 'Очередь, сотрудники и состояние работ' : 'Пользователи, купоны, новости и баллы')

@push('head')
<style>
    .dash-grid{display:grid;grid-template-columns:minmax(0,1.2fr) minmax(340px,.8fr);gap:20px;align-items:start}
    .quick-list{display:grid;gap:12px}.quick-item{display:flex;align-items:center;justify-content:space-between;gap:14px;padding:15px;border:1px solid var(--border);border-radius:16px;background:var(--surface-soft)}
    .quick-title{font-weight:950}.quick-sub{color:var(--muted);font-size:13px;margin-top:3px}.org-mini{display:grid;grid-template-columns:1fr repeat(3,90px);gap:12px;align-items:center;padding:14px 0;border-bottom:1px solid var(--border)}
    .org-mini:last-child{border-bottom:0}.org-count{text-align:center}.org-count b{display:block;font-size:20px}.org-count span{color:var(--muted);font-size:12px;font-weight:800}.clean-hero{padding:24px;border-radius:24px;background:linear-gradient(135deg,var(--primary-soft),var(--surface));border:1px solid color-mix(in srgb,var(--primary) 30%,var(--border));box-shadow:var(--shadow);margin-bottom:18px}
    @media(max-width:1100px){.dash-grid{grid-template-columns:1fr}.org-mini{grid-template-columns:1fr 1fr 1fr 1fr}}
</style>
@endpush

@section('content')
@php
    $isSuper = in_array(auth()->user()->role, ['admin', 'super_admin'], true);
    $statusLabels = [
        'created'=>'Новая','moderation'=>'На проверке','assigned'=>'Назначена','accepted'=>'Принята','in_progress'=>'В работе','problem'=>'Проблема','postponed'=>'Отложена','completed'=>'Выполнена','rejected'=>'Отклонена','duplicate'=>'Дубликат'
    ];
@endphp

@if($isSuper)
    <div class="page-head">
        <div>
            <h1 class="page-title">Панель главного администратора</h1>
            <p class="page-description">Здесь нет реестра заявок, карты, сотрудников и разделов ЖКХ. Главный админ контролирует платформенные аккаунты, новости, купоны и баллы.</p>
        </div>
        <a class="btn btn-primary" href="{{ route('admin.users.index') }}">Пользователи</a>
    </div>

    <div class="metric-grid" style="margin-bottom:20px;">
        <div class="metric-card accent"><div class="metric-label">Пользователи</div><div class="metric-value">{{ $summary['users'] }}</div><div class="metric-hint">все аккаунты платформы</div></div>
        <div class="metric-card"><div class="metric-label">Забанены</div><div class="metric-value">{{ $summary['banned'] }}</div><div class="metric-hint">глобальная блокировка</div></div>
        <div class="metric-card"><div class="metric-label">Новости</div><div class="metric-value">{{ $summary['news'] }}</div><div class="metric-hint">активные публикации</div></div>
        <div class="metric-card"><div class="metric-label">Купоны</div><div class="metric-value">{{ $summary['rewards'] }}</div><div class="metric-hint">активные вознаграждения</div></div>
    </div>

    <div class="dash-grid">
        <div class="card"><div class="card-body">
            <h2 class="card-title">Платформенные разделы</h2>
            <div class="quick-list" style="margin-top:14px;">
                <div class="quick-item"><div><div class="quick-title">Пользователи</div><div class="quick-sub">Поиск аккаунтов и глобальная блокировка нарушителей</div></div><a class="btn btn-light btn-sm" href="{{ route('admin.users.index') }}">Открыть</a></div>
                <div class="quick-item"><div><div class="quick-title">Новости</div><div class="quick-sub">Публикации для приложения и сайта</div></div><a class="btn btn-light btn-sm" href="{{ route('admin.news.index') }}">Открыть</a></div>
                <div class="quick-item"><div><div class="quick-title">Купоны</div><div class="quick-sub">Вознаграждения и промокоды для пользователей</div></div><a class="btn btn-light btn-sm" href="{{ route('admin.rewards.index') }}">Открыть</a></div>
                <div class="quick-item"><div><div class="quick-title">Баллы</div><div class="quick-sub">Ручная корректировка баланса жителей</div></div><a class="btn btn-light btn-sm" href="{{ route('admin.points.index') }}">Открыть</a></div>
            </div>
        </div></div>

        <div style="display:grid;gap:20px;">
            <div class="card"><div class="card-body">
                <h2 class="card-title">Последние пользователи</h2>
                <div class="quick-list" style="margin-top:14px;">
                    @forelse($recentUsers as $user)
                        <div class="quick-item"><div><div class="quick-title">{{ $user->name }}</div><div class="quick-sub">{{ $user->email }}</div></div><span class="status-pill">{{ $user->role }}</span></div>
                    @empty <div class="empty-state">Нет пользователей.</div> @endforelse
                </div>
            </div></div>
            <div class="card"><div class="card-body">
                <h2 class="card-title">Блокировки</h2>
                <div class="quick-list" style="margin-top:14px;">
                    @forelse($bannedUsers as $user)
                        <div class="quick-item"><div><div class="quick-title">{{ $user->name }}</div><div class="quick-sub">{{ $user->ban_reason ?: 'Причина не указана' }}</div></div><a class="btn btn-light btn-sm" href="{{ route('admin.users.index', ['search'=>$user->email]) }}">Открыть</a></div>
                    @empty <div class="empty-state">Активных банов нет.</div> @endforelse
                </div>
            </div></div>
        </div>
    </div>
@else
    <div class="page-head">
        <div>
            <h1 class="page-title">Рабочий обзор</h1>
            <p class="page-description">Заявки, скрытые вашей организацией, не попадают в рабочие показатели и отчёты.</p>
        </div>
        <a class="btn btn-primary" href="{{ route('admin.tickets.index') }}">Открыть заявки</a>
    </div>

    <div class="metric-grid" style="margin-bottom:20px;">
        <div class="metric-card accent"><div class="metric-label">Всего</div><div class="metric-value">{{ $summary['total'] }}</div><div class="metric-hint">в работе ЖКХ</div></div>
        <div class="metric-card"><div class="metric-label">Активные</div><div class="metric-value">{{ $summary['active'] }}</div><div class="metric-hint">принятые и в работе</div></div>
        <div class="metric-card"><div class="metric-label">Выполнено</div><div class="metric-value">{{ $summary['completed'] }}</div><div class="metric-hint">закрытые задачи</div></div>
        <div class="metric-card"><div class="metric-label">Скрыто</div><div class="metric-value">{{ $summary['hidden'] }}</div><div class="metric-hint">не входят в отчёт</div></div>
    </div>

    <div class="dash-grid">
        <div class="card"><div class="card-body" style="padding:0;overflow:hidden;">
            <div style="padding:22px 24px 8px;"><h2 class="card-title">Очередь обработки</h2></div>
            <div class="table-responsive"><table class="table-compact"><thead><tr><th>Заявка</th><th>Статус</th><th>Исполнитель</th><th></th></tr></thead><tbody>
                @forelse($attentionTickets as $ticket)
                    <tr><td><b>№{{ $ticket->id }}</b><div class="ticket-meta">{{ $ticket->category->name ?? 'Без категории' }}</div></td><td><span class="status-pill status-{{ $ticket->status }}">{{ $statusLabels[$ticket->status] ?? $ticket->status }}</span></td><td>{{ $ticket->assignedWorker->name ?? '—' }}</td><td class="table-actions"><a class="btn btn-light btn-sm" href="{{ route('admin.tickets.show', $ticket) }}">Открыть</a></td></tr>
                @empty <tr><td colspan="4" class="empty-state">Очередь пуста.</td></tr> @endforelse
            </tbody></table></div>
        </div></div>
        <div class="card"><div class="card-body" style="padding:0;overflow:hidden;">
            <div style="padding:22px 24px 8px;"><h2 class="card-title">Недавно закрыты</h2></div>
            <div class="table-responsive"><table class="table-compact"><thead><tr><th>Заявка</th><th>Статус</th><th></th></tr></thead><tbody>
                @forelse($recentFinishedTickets as $ticket)
                    <tr><td><b>№{{ $ticket->id }}</b><div class="ticket-meta">{{ $ticket->category->name ?? 'Без категории' }}</div></td><td><span class="status-pill status-{{ $ticket->status }}">{{ $statusLabels[$ticket->status] ?? $ticket->status }}</span></td><td class="table-actions"><a class="btn btn-light btn-sm" href="{{ route('admin.tickets.show', $ticket) }}">Открыть</a></td></tr>
                @empty <tr><td colspan="3" class="empty-state">Нет закрытых заявок.</td></tr> @endforelse
            </tbody></table></div>
        </div></div>
    </div>
@endif
@endsection
