@extends('admin.layout')

@section('title', 'Запросы исполнителей')
@section('topbar-title', 'Запросы исполнителей')
@section('topbar-subtitle', 'Сотрудники могут запросить свободную заявку, админ ЖКХ подтверждает назначение')

@section('content')
<div class="metric-grid">
    <div class="metric-card accent">
        <div class="metric-label">Ожидают решения</div>
        <div class="metric-value">{{ $stats['pending'] }}</div>
        <div class="metric-hint">нужно рассмотреть</div>
    </div>
    <div class="metric-card">
        <div class="metric-label">Одобрено</div>
        <div class="metric-value">{{ $stats['approved'] }}</div>
        <div class="metric-hint">заявки назначены</div>
    </div>
    <div class="metric-card">
        <div class="metric-label">Отклонено</div>
        <div class="metric-value">{{ $stats['rejected'] }}</div>
        <div class="metric-hint">не вошли в работу</div>
    </div>
    <div class="metric-card">
        <div class="metric-label">Всего</div>
        <div class="metric-value">{{ $stats['total'] }}</div>
        <div class="metric-hint">история запросов</div>
    </div>
</div>

<div class="card">
    <div class="card-head">
        <div>
            <h2 class="card-title">Фильтр</h2>
            <div class="card-muted">Поиск по номеру заявки, сотруднику, адресу или комментарию.</div>
        </div>
    </div>
    <form class="card-body toolbar" method="GET" action="{{ route('admin.claim-requests.index') }}">
        <div class="field" style="min-width:260px;flex:2">
            <label>Поиск</label>
            <input type="search" name="search" value="{{ request('search') }}" placeholder="№ заявки, сотрудник, адрес">
        </div>
        <div class="field">
            <label>Статус</label>
            <select name="status">
                <option value="">Все</option>
                @foreach($statusLabels as $value => $label)
                    <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="field" style="flex:0 0 auto">
            <label>&nbsp;</label>
            <button class="btn btn-primary" type="submit">Найти</button>
        </div>
        <div class="field" style="flex:0 0 auto">
            <label>&nbsp;</label>
            <a class="btn btn-light" href="{{ route('admin.claim-requests.index') }}">Сбросить</a>
        </div>
    </form>
</div>

<div class="card">
    <div class="card-head">
        <div>
            <h2 class="card-title">Запросы</h2>
            <div class="card-muted">Одобрение закрепляет заявку за сотрудником.</div>
        </div>
    </div>

    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Заявка</th>
                    <th>Сотрудник</th>
                    <th>Категория</th>
                    <th>Адрес</th>
                    <th>Статус</th>
                    <th>Создан</th>
                    <th class="table-actions">Действие</th>
                </tr>
            </thead>
            <tbody>
                @forelse($requests as $requestItem)
                    @php
                        $ticket = $requestItem->ticket;
                        $statusClass = match($requestItem->status) {
                            'approved' => 'status-completed',
                            'rejected', 'cancelled' => 'status-rejected',
                            default => 'status-assigned',
                        };
                    @endphp
                    <tr>
                        <td>
                            @if($ticket)
                                <a href="{{ route('admin.tickets.show', $ticket) }}" style="font-weight:950;text-decoration:none">№{{ $ticket->id }}</a>
                                <div class="muted" style="margin-top:4px">{{ \Illuminate\Support\Str::limit($ticket->description ?: 'Без комментария', 64) }}</div>
                            @else
                                <span class="muted">Заявка удалена</span>
                            @endif
                        </td>
                        <td>
                            <strong>{{ $requestItem->worker->name ?? '—' }}</strong>
                            <div class="muted">{{ $requestItem->worker->email ?? '' }}</div>
                        </td>
                        <td>{{ $ticket?->category?->name ?? '—' }}</td>
                        <td>{{ $ticket?->address_text ?: '—' }}</td>
                        <td><span class="status-pill {{ $statusClass }}">{{ $statusLabels[$requestItem->status] ?? $requestItem->status }}</span></td>
                        <td>{{ optional($requestItem->created_at)->format('d.m.Y H:i') }}</td>
                        <td class="table-actions">
                            @if($requestItem->status === 'pending')
                                <form method="POST" action="{{ route('admin.claim-requests.approve', $requestItem) }}">
                                    @csrf
                                    <button class="btn btn-sm btn-primary" type="submit">Одобрить</button>
                                </form>
                                <form method="POST" action="{{ route('admin.claim-requests.reject', $requestItem) }}" style="margin-left:6px">
                                    @csrf
                                    <input type="hidden" name="resolution" value="Отклонено администратором ЖКХ">
                                    <button class="btn btn-sm btn-light" type="submit">Отклонить</button>
                                </form>
                            @else
                                <div class="muted">{{ $requestItem->reviewer->name ?? 'Обработано' }}</div>
                                @if($requestItem->resolution)<div class="muted">{{ $requestItem->resolution }}</div>@endif
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7"><div class="empty-state">Запросов пока нет</div></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="pagination-wrap">{{ $requests->links() }}</div>
</div>
@endsection
