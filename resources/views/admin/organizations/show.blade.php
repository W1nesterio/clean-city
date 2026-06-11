@extends('admin.layout')
@section('title', $organization->name)

@section('content')
<div class="page-head">
    <div class="page-head-left">
        <div class="crumb">Главная › <a href="{{ route('admin.organizations.index') }}">ЖКХ</a> › {{ $organization->name }}</div>
        <h1 class="h-page">{{ $organization->name }}</h1>
        <p class="page-sub">
            {{ $organization->district ?: '' }}{{ $organization->district && $organization->address ? ' · ' : '' }}{{ $organization->address ?: '' }}
        </p>
    </div>
    <div class="page-actions">
        <a href="{{ route('admin.organizations.index') }}" class="btn ghost">← Назад</a>
    </div>
</div>

{{-- Stats row --}}
<div class="metric-grid" style="margin-bottom:18px;">
    <div class="metric-card accent">
        <div class="metric-label">Всего заявок</div>
        <div class="metric-value">{{ $totalTickets }}</div>
        <div class="metric-hint">назначено ЖКХ</div>
    </div>
    <div class="metric-card">
        <div class="metric-label">Выполнено</div>
        <div class="metric-value">{{ $ticketStats['completed'] ?? 0 }}</div>
        <div class="metric-hint">{{ $totalTickets > 0 ? round(($ticketStats['completed'] / $totalTickets) * 100) : 0 }}%</div>
    </div>
    <div class="metric-card">
        <div class="metric-label">Сотрудники</div>
        <div class="metric-value">{{ $workers->count() }}</div>
        <div class="metric-hint">исполнителей</div>
    </div>
    <div class="metric-card">
        <div class="metric-label">Администраторы</div>
        <div class="metric-value">{{ $admins->count() }}</div>
        <div class="metric-hint">ЖКХ-аккаунтов</div>
    </div>
</div>

<div style="display:grid;grid-template-columns:minmax(0,1fr) 320px;gap:18px;align-items:start;">

    {{-- Left column --}}
    <div style="display:flex;flex-direction:column;gap:18px;">

        {{-- Workers --}}
        <div class="card">
            <div class="card-head">
                <h2 class="card-title">Сотрудники ({{ $workers->count() }})</h2>
            </div>
            @if($workers->isEmpty())
                <div class="card-pad" style="color:var(--muted);font-size:13px;">Сотрудники не зарегистрированы</div>
            @else
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Сотрудник</th>
                            <th>Всего заявок</th>
                            <th>Активных</th>
                            <th>Выполнено</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($workers as $worker)
                        <tr>
                            <td>
                                <div style="font-weight:600;">{{ $worker->name }}</div>
                                <div style="font-size:12px;color:var(--muted);">{{ $worker->email }}</div>
                            </td>
                            <td class="num">{{ $worker->total_tickets }}</td>
                            <td class="num">
                                @if($worker->active_tickets > 0)
                                    <span class="pill pill-amber no-dot">{{ $worker->active_tickets }}</span>
                                @else
                                    <span class="txt-muted">0</span>
                                @endif
                            </td>
                            <td class="num">
                                @if($worker->completed_tickets > 0)
                                    <span class="pill pill-green no-dot">{{ $worker->completed_tickets }}</span>
                                @else
                                    <span class="txt-muted">0</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </div>

        {{-- Recent tickets --}}
        <div class="card">
            <div class="card-head">
                <h2 class="card-title">Последние заявки</h2>
                <a href="{{ route('admin.tickets.index', ['assigned_org_id' => $organization->id]) }}" class="btn ghost sm">Все заявки</a>
            </div>
            @if($recentTickets->isEmpty())
                <div class="card-pad" style="color:var(--muted);font-size:13px;">Заявок нет</div>
            @else
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr><th>№</th><th>Категория</th><th>Статус</th><th>Исполнитель</th><th>Создана</th></tr>
                    </thead>
                    <tbody>
                        @foreach($recentTickets as $ticket)
                        @php
                            $pillMap = [
                                'created'=>'pill-blue','moderation'=>'pill-gray','assigned'=>'pill-amber',
                                'accepted'=>'pill-purple','in_progress'=>'pill-blue','problem'=>'pill-red',
                                'postponed'=>'pill-gray','completed'=>'pill-green','rejected'=>'pill-red','duplicate'=>'pill-gray',
                            ];
                        @endphp
                        <tr>
                            <td><a href="{{ route('admin.tickets.show', $ticket) }}" style="font-weight:600;color:var(--primary);">№{{ $ticket->id }}</a></td>
                            <td>{{ $ticket->category->name ?? '—' }}</td>
                            <td><span class="pill {{ $pillMap[$ticket->status] ?? 'pill-gray' }} no-dot">{{ $statuses[$ticket->status] ?? $ticket->status }}</span></td>
                            <td>{{ $ticket->assignedWorker->name ?? '—' }}</td>
                            <td class="num" style="white-space:nowrap;">{{ optional($ticket->created_at)->format('d.m.Y') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </div>
    </div>

    {{-- Right sidebar --}}
    <div style="display:flex;flex-direction:column;gap:18px;">

        {{-- Info card --}}
        <div class="card">
            <div class="card-head"><h2 class="card-title">Информация</h2></div>
            <div class="card-pad" style="display:flex;flex-direction:column;gap:10px;">
                @if($organization->city)
                <div>
                    <div style="font-size:11px;color:var(--muted);font-weight:600;text-transform:uppercase;letter-spacing:.05em;margin-bottom:2px;">Город</div>
                    <div style="font-weight:600;">{{ $organization->city->name }}</div>
                </div>
                @endif
                @if($organization->district)
                <div>
                    <div style="font-size:11px;color:var(--muted);font-weight:600;text-transform:uppercase;letter-spacing:.05em;margin-bottom:2px;">Район</div>
                    <div>{{ $organization->district }}</div>
                </div>
                @endif
                @if($organization->address)
                <div>
                    <div style="font-size:11px;color:var(--muted);font-weight:600;text-transform:uppercase;letter-spacing:.05em;margin-bottom:2px;">Адрес</div>
                    <div>{{ $organization->address }}</div>
                </div>
                @endif
                @if($organization->phone)
                <div>
                    <div style="font-size:11px;color:var(--muted);font-weight:600;text-transform:uppercase;letter-spacing:.05em;margin-bottom:2px;">Телефон</div>
                    <div>{{ $organization->phone }}</div>
                </div>
                @endif
            </div>
        </div>

        {{-- Status breakdown --}}
        <div class="card">
            <div class="card-head"><h2 class="card-title">По статусам</h2></div>
            <div class="card-pad" style="display:flex;flex-direction:column;gap:8px;">
                @foreach($statuses as $status => $label)
                @php $cnt = $ticketStats[$status] ?? 0; @endphp
                @if($cnt > 0)
                <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;">
                    <span style="font-size:13px;color:var(--text-soft);">{{ $label }}</span>
                    <span style="font-weight:700;font-size:14px;">{{ $cnt }}</span>
                </div>
                @endif
                @endforeach
                @if($totalTickets === 0)
                    <div style="color:var(--muted);font-size:13px;">Нет заявок</div>
                @endif
            </div>
        </div>

        {{-- Admins --}}
        @if($admins->isNotEmpty())
        <div class="card">
            <div class="card-head"><h2 class="card-title">Администраторы ЖКХ</h2></div>
            <div class="card-pad" style="display:flex;flex-direction:column;gap:8px;">
                @foreach($admins as $adm)
                <div>
                    <div style="font-weight:600;font-size:13px;">{{ $adm->name }}</div>
                    <div style="font-size:12px;color:var(--muted);">{{ $adm->email }}</div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

    </div>
</div>
@endsection
