@extends('admin.layout')

@section('title', 'Заявки')
@section('topbar-title', 'Заявки')
@section('topbar-subtitle', 'Реестр, карта и обработка обращений')

@section('content')
    <div class="metric-grid">
        <div class="metric-card"><div class="metric-label">Всего</div><div class="metric-value">{{ $stats['total'] ?? 0 }}</div><div class="metric-hint">активные в реестре</div></div>
        <div class="metric-card"><div class="metric-label">Новые</div><div class="metric-value">{{ $stats['new'] ?? 0 }}</div><div class="metric-hint">ожидают обработки</div></div>
        <div class="metric-card"><div class="metric-label">В работе</div><div class="metric-value">{{ $stats['active'] ?? 0 }}</div><div class="metric-hint">назначены или выполняются</div></div>
        <div class="metric-card"><div class="metric-label">Выполнено</div><div class="metric-value">{{ $stats['completed'] ?? 0 }}</div><div class="metric-hint">закрытые работы</div></div>
    </div>

    <div class="card">
        <div class="card-head">
            <div>
                <h2 class="card-title">Карта обращений</h2>
                <div class="ticket-meta">Метки показывают заявки из текущей выборки</div>
            </div>
            <a class="btn btn-light" href="{{ route('admin.tickets.map') }}">Открыть крупно</a>
        </div>
        <div class="card-body">
            <div class="map-card"><div id="adminTicketsMap"></div></div>
        </div>
    </div>

    <div class="card">
        <div class="card-head">
            <div>
                <h2 class="card-title">Фильтр</h2>
                <div class="ticket-meta">Поиск по номеру, адресу и описанию</div>
            </div>
        </div>
        <div class="card-body">
            <form class="toolbar" method="GET" action="{{ route('admin.tickets.index') }}">
                <div class="field" style="min-width:260px">
                    <label>Поиск</label>
                    <input name="search" value="{{ request('search') }}" placeholder="№, адрес или описание">
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
                <div class="field">
                    <label>Категория</label>
                    <select name="category_id">
                        <option value="">Все</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" @selected((string)request('category_id') === (string)$category->id)>{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label>Исполнитель</label>
                    <select name="assigned_worker_id">
                        <option value="">Все</option>
                        @foreach($workers as $worker)
                            <option value="{{ $worker->id }}" @selected((string)request('assigned_worker_id') === (string)$worker->id)>{{ $worker->name }}</option>
                        @endforeach
                    </select>
                </div>
                @if($isOrgAdmin)
                    <div class="field">
                        <label>Видимость</label>
                        <select name="visibility">
                            <option value="active" @selected(request('visibility', 'active') === 'active')>В работе</option>
                            <option value="hidden" @selected(request('visibility') === 'hidden')>Скрытые</option>
                            <option value="all" @selected(request('visibility') === 'all')>Все</option>
                        </select>
                    </div>
                @endif
                <div class="field" style="flex:0 0 150px">
                    <label>От</label>
                    <input type="date" name="date_from" value="{{ request('date_from') }}">
                </div>
                <div class="field" style="flex:0 0 150px">
                    <label>До</label>
                    <input type="date" name="date_to" value="{{ request('date_to') }}">
                </div>
                <button class="btn btn-primary" type="submit">Применить</button>
                <a class="btn btn-light" href="{{ route('admin.tickets.index') }}">Сброс</a>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-head">
            <div>
                <h2 class="card-title">Реестр</h2>
                <div class="ticket-meta">{{ $tickets->total() }} записей</div>
            </div>
        </div>
        <div class="table-responsive">
            <table>
                <thead>
                <tr>
                    <th>№</th>
                    <th>Категория</th>
                    <th>Адрес</th>
                    <th>Статус</th>
                    <th>Исполнитель</th>
                    <th>Создана</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @forelse($tickets as $ticket)
                    @php($hidden = $ticket->activeHides && $ticket->activeHides->count() > 0)
                    <tr>
                        <td><strong>№{{ $ticket->id }}</strong></td>
                        <td>{{ $ticket->category->name ?? '—' }}</td>
                        <td>
                            <strong>{{ $ticket->address_text ?: 'Адрес не указан' }}</strong>
                            @if($ticket->description)
                                <div class="ticket-meta">{{ \Illuminate\Support\Str::limit($ticket->description, 70) }}</div>
                            @endif
                            @if($hidden)<div><span class="status-pill status-rejected">Скрыта</span></div>@endif
                        </td>
                        <td><span class="status-pill status-{{ $ticket->status }}">{{ $statusLabels[$ticket->status] ?? $ticket->status }}</span></td>
                        <td>{{ $ticket->assignedWorker->name ?? '—' }}</td>
                        <td>{{ $ticket->created_at ? $ticket->created_at->format('d.m.Y H:i') : '—' }}</td>
                        <td class="table-actions"><a class="btn btn-primary btn-sm" href="{{ route('admin.tickets.show', $ticket) }}">Открыть</a></td>
                    </tr>
                @empty
                    <tr><td colspan="7"><div class="empty-state">Заявок не найдено</div></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="pagination-wrap">{{ $tickets->links() }}</div>
    </div>
@endsection

@push('head')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
@endpush

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
(function(){
    const points = @json($mapTickets ?? []);
    const cityCenter = [53.1327, 26.0139];
    const map = L.map('adminTicketsMap', { scrollWheelZoom: true, preferCanvas: true }).setView(cityCenter, 12);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19, attribution: '&copy; OpenStreetMap' }).addTo(map);
    function distanceKm(a, b) {
        const toRad = v => v * Math.PI / 180;
        const R = 6371;
        const dLat = toRad(b[0] - a[0]);
        const dLng = toRad(b[1] - a[1]);
        const s1 = Math.sin(dLat / 2) ** 2;
        const s2 = Math.cos(toRad(a[0])) * Math.cos(toRad(b[0])) * Math.sin(dLng / 2) ** 2;
        return 2 * R * Math.asin(Math.sqrt(s1 + s2));
    }
    function safeFit(map, bounds, center) {
        if (!bounds.length) {
            map.setView(center, 12);
            return;
        }
        const local = bounds.filter(point => distanceKm(center, point) <= 80);
        if (local.length === 1) {
            map.setView(local[0], 15);
        } else if (local.length > 1) {
            map.fitBounds(local, { padding:[42,42], maxZoom:15 });
        } else {
            map.setView(center, 12);
        }
    }

    function iconFor(status) {
        return L.divIcon({ className:'', html:`<div class="pin-marker pin-${status}"><div class="pin-body"></div><div class="pin-dot"></div></div>`, iconSize:[30,38], iconAnchor:[15,34], popupAnchor:[0,-30] });
    }
    const bounds = [];
    points.forEach(item => {
        if (!item.lat || !item.lng) return;
        bounds.push([item.lat, item.lng]);
        const popup = `<div class="map-popup-title">№${item.id} · ${item.category}</div>`+
            `<div class="map-popup-meta">${item.status_label}${item.worker ? ' · '+item.worker : ''}</div>`+
            `${item.address ? `<div style="margin-bottom:8px">${item.address}</div>` : ''}`+
            `<a class="btn btn-primary btn-sm" href="${item.url}">Открыть</a> `+
            `<a class="btn btn-light btn-sm" target="_blank" href="https://www.google.com/maps/dir/?api=1&destination=${item.lat},${item.lng}">Маршрут</a>`;
        L.marker([item.lat, item.lng], { icon: iconFor(item.status) }).addTo(map).bindPopup(popup);
    });
    safeFit(map, bounds, cityCenter);
    setTimeout(()=>map.invalidateSize(), 250);
    setTimeout(()=>map.invalidateSize(), 800);
})();
</script>
@endpush
