@extends('admin.layout')

@section('title', 'Обращение №' . $ticket->id)
@section('topbar-title', 'Обращение №' . $ticket->id)
@section('topbar-subtitle', $ticket->category->name ?? 'Без категории')

@push('head')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<style>
    .ticket-workspace { display:grid; grid-template-columns:minmax(0,1.1fr) minmax(360px,.62fr); gap:18px; align-items:start; }
    .ticket-card-stack { display:flex; flex-direction:column; gap:18px; }
    .ticket-summary-grid { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:12px; }
    .ticket-summary-item { padding:14px; border:1px solid var(--border); border-radius:16px; background:var(--surface-soft); }
    .ticket-summary-item.wide { grid-column:1/-1; }
    .photo-pair { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:14px; }
    .photo-slot { border:1px solid var(--border); border-radius:18px; padding:12px; background:var(--surface-soft); }
    .photo-slot-title { color:var(--muted); font-size:12px; font-weight:900; text-transform:uppercase; letter-spacing:.04em; margin-bottom:10px; }
    .photo-slot img { width:100%; max-height:260px; object-fit:cover; border-radius:14px; display:block; border:1px solid var(--border); }
    .action-stack { display:flex; flex-direction:column; gap:12px; }
    .hide-alert { padding:14px 16px; border-radius:18px; background:#fef3c7; color:#92400e; border:1px solid #fde68a; font-weight:800; margin-bottom:16px; }
    @media(max-width:1180px){.ticket-workspace{grid-template-columns:1fr}.ticket-summary-grid{grid-template-columns:repeat(2,1fr)}}
    @media(max-width:720px){.ticket-summary-grid,.photo-pair{grid-template-columns:1fr}}
    /* Timeline */
    .tl-stack{display:flex;flex-direction:column;gap:0}
    .tl-entry{display:grid;grid-template-columns:20px 2px 1fr;gap:0 12px;position:relative}
    .tl-dot{width:12px;height:12px;border-radius:50%;background:var(--c-gray);border:2px solid var(--surface);box-shadow:0 0 0 1.5px var(--line);margin-top:4px;flex-shrink:0;z-index:1}
    .tl-dot-created{background:var(--c-blue)}.tl-dot-assigned,.tl-dot-accepted{background:var(--c-amber)}.tl-dot-in_progress,.tl-dot-problem,.tl-dot-postponed{background:var(--c-orange)}.tl-dot-completed{background:var(--c-green)}.tl-dot-rejected,.tl-dot-duplicate{background:var(--c-red)}
    .tl-line{width:2px;background:var(--line);grid-column:2;margin-top:16px;min-height:100%}
    .tl-content{grid-column:3;padding-bottom:20px}
    .tl-entry:last-child .tl-line{display:none}
    .tl-meta-row{display:flex;align-items:center;gap:10px}
    .tl-ts{font-size:12px;font-weight:600;color:var(--muted);font-family:var(--mono);white-space:nowrap}
    .tl-who{font-size:12px;color:var(--muted)}
</style>
@endpush

@section('content')
@php
    $beforePhotos = $ticket->photos->where('type', 'before');
    $afterPhotos = $ticket->photos->where('type', 'after');
    $priorityLabel = $ticket->priority === 'high' ? 'Высокий' : ($ticket->priority === 'low' ? 'Низкий' : 'Обычный');
@endphp

<div class="page-head">
    <div>
        <h1 class="page-title">Обращение №{{ $ticket->id }}</h1>
        <p class="page-description">{{ $ticket->created_at->format('d.m.Y H:i') }} · {{ $ticket->assignedOrganization->name ?? 'ЖКХ не назначено' }}</p>
    </div>
    <div style="display:flex; gap:10px; flex-wrap:wrap; align-items:center;">
        <span class="status-pill status-{{ $ticket->status }}" style="height:42px; padding:0 16px; font-size:14px;">{{ $statusLabels[$ticket->status] ?? $ticket->status }}</span>
        <a class="btn btn-light" href="{{ route('admin.tickets.index') }}">К списку</a>
    </div>
</div>

@if($activeHide)
    <div class="hide-alert">Заявка скрыта ЖКХ: {{ $activeHide->reason ?: 'причина не указана' }}</div>
@endif

<div class="ticket-workspace">
    <div class="ticket-card-stack">
        <div class="card"><div class="card-body">
            <h2 class="card-title">Сводка</h2>
            <div class="ticket-summary-grid" style="margin-top:16px;">
                <div class="ticket-summary-item"><div class="info-label">Категория</div><div class="info-value">{{ $ticket->category->name ?? 'Без категории' }}</div></div>
                <div class="ticket-summary-item"><div class="info-label">Приоритет</div><div class="info-value">{{ $priorityLabel }}</div></div>
                <div class="ticket-summary-item"><div class="info-label">Житель</div><div class="info-value">{{ $ticket->user->name ?? '—' }}</div></div>
                <div class="ticket-summary-item wide"><div class="info-label">Описание</div><div class="info-value">{{ $ticket->description ?: 'Описание не указано' }}</div></div>
                <div class="ticket-summary-item wide"><div class="info-label">Адрес</div><div class="info-value">{{ $ticket->address_text ?: 'Адрес не указан, используется точка на карте' }}</div></div>
                <div class="ticket-summary-item wide"><div class="info-label">Координаты</div><div class="info-value">@if(!is_null($ticket->lat) && !is_null($ticket->lng)){{ $ticket->lat }}, {{ $ticket->lng }}@else Координаты не указаны @endif</div></div>
            </div>
        </div></div>

        <div class="card"><div class="card-body">
            <h2 class="card-title">Место и фотографии</h2>
            <div id="ticketMap" class="map-box compact" style="margin-top:16px;"></div>
            <div class="photo-pair" style="margin-top:16px;">
                <div class="photo-slot"><div class="photo-slot-title">Фото до</div>
                    @forelse($beforePhotos as $photo)<img src="{{ asset('storage/' . $photo->path) }}" alt="Фото до">@empty<div class="empty-state">Фото не загружено.</div>@endforelse
                </div>
                <div class="photo-slot"><div class="photo-slot-title">Фото после</div>
                    @forelse($afterPhotos as $photo)<img src="{{ asset('storage/' . $photo->path) }}" alt="Фото после">@empty<div class="empty-state">Пока нет.</div>@endforelse
                </div>
            </div>
        </div></div>

        <div class="card"><div class="card-body">
            <h2 class="card-title">История</h2>
            <div class="tl-stack" style="margin-top:16px;">
                @forelse($ticket->statusHistory as $history)
                <div class="tl-entry">
                    <div class="tl-dot tl-dot-{{ $history->new_status }}"></div>
                    <div class="tl-line"></div>
                    <div class="tl-content">
                        <div class="tl-meta-row">
                            <span class="tl-ts">{{ $history->created_at->format('d.m H:i') }}</span>
                            <span class="tl-who">{{ $history->changedBy->name ?? 'Система' }}</span>
                        </div>
                        <div style="display:flex;gap:6px;flex-wrap:wrap;align-items:center;margin-top:6px;">
                            <span class="status-pill status-{{ $history->old_status ?: 'created' }}">{{ $history->old_status ? ($statusLabels[$history->old_status] ?? $history->old_status) : '—' }}</span>
                            <span style="color:var(--muted);font-size:13px;">→</span>
                            <span class="status-pill status-{{ $history->new_status }}">{{ $statusLabels[$history->new_status] ?? $history->new_status }}</span>
                        </div>
                        @if($history->comment)<div style="margin-top:8px;font-size:13px;color:var(--text-soft);line-height:1.5;">{{ $history->comment }}</div>@endif
                    </div>
                </div>
                @empty
                    <div class="empty-state">История пока пуста.</div>
                @endforelse
            </div>
        </div></div>
    </div>

    <div class="ticket-card-stack">
        <div class="card"><div class="card-body">
            <h2 class="card-title">Назначение</h2>
            <form method="POST" action="{{ route('admin.tickets.assign', $ticket) }}" style="margin-top:14px;">
                @csrf
                <div class="field">
                    <label>ЖКХ</label>
                    <select name="assigned_org_id" id="assignOrganization" required>
                        <option value="">Выберите ЖКХ</option>
                        @foreach($organizations as $organization)
                            <option value="{{ $organization->id }}" @selected($ticket->assigned_org_id == $organization->id)>{{ $organization->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field" style="margin-top:12px;">
                    <label>Сотрудник</label>
                    <input type="search" id="workerSearch" placeholder="Поиск по имени или email" style="margin-bottom:8px;">
                    <select name="assigned_worker_id" id="assignWorker">
                        <option value="">Без конкретного сотрудника</option>
                        @foreach($workers as $worker)
                            <option value="{{ $worker->id }}" data-org="{{ $worker->organization_id }}" data-search="{{ mb_strtolower($worker->name . ' ' . $worker->email) }}" @selected($ticket->assigned_worker_id == $worker->id)>{{ $worker->name }} · {{ $worker->email }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field" style="margin-top:12px;"><label>Комментарий</label><input type="text" name="comment" placeholder="Необязательно"></div>
                <button class="btn btn-primary btn-full" style="margin-top:14px;" type="submit">Сохранить назначение</button>
            </form>
        </div></div>

        <div class="card"><div class="card-body">
            <h2 class="card-title">Статус</h2>
            <form method="POST" action="{{ route('admin.tickets.status', $ticket) }}" style="margin-top:14px;">
                @csrf
                <div class="field"><label>Новый статус</label><select name="status">@foreach($statusLabels as $status => $label)<option value="{{ $status }}" @selected($ticket->status === $status)>{{ $label }}</option>@endforeach</select></div>
                <div class="field" style="margin-top:12px;"><label>Комментарий</label><input type="text" name="comment" placeholder="Причина или примечание"></div>
                <button class="btn btn-primary btn-full" style="margin-top:14px;" type="submit">Изменить статус</button>
            </form>
        </div></div>

        @if($isOrgAdmin)
            <div class="card"><div class="card-body">
                <h2 class="card-title">Задания для жителей</h2>
                <p style="font-size:13px;color:var(--muted);margin:8px 0 14px;">Если включено, жители смогут принять и выполнить эту заявку самостоятельно, заработав баллы.</p>
                <form method="POST" action="{{ route('admin.tickets.toggle-resident', $ticket) }}">
                    @csrf
                    @if($ticket->available_to_residents)
                        <div style="display:flex;align-items:center;gap:10px;margin-bottom:12px;">
                            <span class="pill pill-green" style="font-size:12px;">Доступно жителям</span>
                        </div>
                        <button class="btn btn-light btn-full" type="submit">Закрыть для жителей</button>
                    @else
                        <button class="btn btn-primary btn-full" type="submit">Открыть для жителей</button>
                    @endif
                </form>
            </div></div>
        @endif

        @if($isOrgAdmin)
            <div class="card"><div class="card-body">
                <h2 class="card-title">Очередь ЖКХ</h2>
                @if($activeHide)
                    <form method="POST" action="{{ route('admin.tickets.restore', $ticket) }}">
                        @csrf
                        <button class="btn btn-primary btn-full" type="submit">Восстановить заявку</button>
                    </form>
                @else
                    <form method="POST" action="{{ route('admin.tickets.hide', $ticket) }}">
                        @csrf
                        <div class="field"><label>Причина скрытия</label><input type="text" name="reason" required placeholder="Спам, дубль, не относится к ЖКХ"></div>
                        <button class="btn btn-light btn-full" style="margin-top:12px;" type="submit">Скрыть из очереди</button>
                    </form>
                @endif
            </div></div>
        @endif

        @if($isSuperAdmin)
            <div class="card"><div class="card-body">
                <h2 class="card-title">Контроль платформы</h2>
                <form method="POST" action="{{ route('admin.tickets.delete', $ticket) }}" onsubmit="return confirm('Убрать заявку из системы?')">
                    @csrf
                    <div class="field"><label>Причина</label><input type="text" name="delete_reason" placeholder="Спам, дубль, нарушение"></div>
                    <button class="btn btn-danger btn-full" style="margin-top:12px;" type="submit">Убрать заявку</button>
                </form>
            </div></div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
    const orgSelect = document.getElementById('assignOrganization');
    const workerSelect = document.getElementById('assignWorker');
    const workerSearch = document.getElementById('workerSearch');
    function syncAssignWorkers() {
        const org = orgSelect?.value || '';
        const search = (workerSearch?.value || '').toLowerCase().trim();
        Array.from(workerSelect.options).forEach(option => {
            if (!option.value) { option.hidden = false; return; }
            const byOrg = !org || option.dataset.org === org;
            const bySearch = !search || (option.dataset.search || '').includes(search);
            option.hidden = !(byOrg && bySearch);
        });
        if (workerSelect.selectedOptions[0]?.hidden) workerSelect.value = '';
    }
    orgSelect?.addEventListener('change', syncAssignWorkers);
    workerSearch?.addEventListener('input', syncAssignWorkers);
    syncAssignWorkers();

    const lat = {{ !is_null($ticket->lat) ? (float) $ticket->lat : 'null' }};
    const lng = {{ !is_null($ticket->lng) ? (float) $ticket->lng : 'null' }};
    if (lat !== null && lng !== null && window.L) {
        const map = L.map('ticketMap').setView([lat, lng], 15);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19 }).addTo(map);
        L.marker([lat, lng]).addTo(map).bindPopup('Обращение №{{ $ticket->id }}').openPopup();
    } else {
        document.getElementById('ticketMap').innerHTML = '<div class="empty-state">Координаты не указаны.</div>';
    }
</script>
@endpush
