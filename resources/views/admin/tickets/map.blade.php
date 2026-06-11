@extends('admin.layout')
@section('title', 'Карта заявок')

@push('head')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css">
<link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css">
<style>
    /* Override cluster colors to match our green theme */
    .marker-cluster-small { background-color: rgba(14,122,66,.3); }
    .marker-cluster-small div { background-color: rgba(14,122,66,.7); color:#fff; font-weight:700; }
    .marker-cluster-medium { background-color: rgba(194,103,10,.3); }
    .marker-cluster-medium div { background-color: rgba(194,103,10,.7); color:#fff; font-weight:700; }
    .marker-cluster-large { background-color: rgba(192,54,47,.3); }
    .marker-cluster-large div { background-color: rgba(192,54,47,.7); color:#fff; font-weight:700; }
</style>
<style>
    .map-page { display:grid; gap:18px; }
    .map-shell { position:relative; height:620px; border-radius:var(--r-lg); overflow:hidden; border:1px solid var(--line); background:var(--surface); box-shadow:var(--shadow-sm); isolation:isolate; }
    #ticketsMap { width:100%; height:100%; position:relative; z-index:1; }

    /* counter chips — top left */
    .map-counter { position:absolute; z-index:400; left:14px; top:14px; display:flex; gap:8px; flex-wrap:wrap; pointer-events:none; }
    .map-chip { background:rgba(255,255,255,.95); border:1px solid rgba(15,23,42,.1); box-shadow:0 2px 12px rgba(15,23,42,.1); border-radius:999px; padding:7px 13px; font-weight:600; font-size:13px; backdrop-filter:blur(8px); color:var(--text); }

    /* zoom controls — bottom right, above default Leaflet attribution */
    .map-zoom-ctrl { position:absolute; z-index:500; right:14px; bottom:42px; display:flex; flex-direction:column; background:var(--surface); border:1px solid var(--line); border-radius:var(--r); box-shadow:var(--shadow); overflow:hidden; }
    .map-zoom-ctrl button { width:34px; height:34px; border:0; background:transparent; display:grid; place-items:center; font-size:18px; font-weight:400; color:var(--text-soft); cursor:pointer; }
    .map-zoom-ctrl button:hover { background:var(--surface-soft); }
    .map-zoom-ctrl button + button { border-top:1px solid var(--line); }

    /* legend — bottom left */
    .map-legend { position:absolute; z-index:400; left:14px; bottom:14px; background:rgba(255,255,255,.96); border:1px solid var(--line); border-radius:var(--r); box-shadow:var(--shadow); padding:10px 14px; backdrop-filter:blur(8px); min-width:140px; }
    .map-legend-title { font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.06em; color:var(--muted); margin-bottom:8px; }
    .map-legend-row { display:flex; align-items:center; gap:8px; font-size:12.5px; margin-bottom:5px; }
    .map-legend-row:last-child { margin-bottom:0; }
    .map-legend-dot { width:10px; height:10px; border-radius:50%; flex-shrink:0; border:2px solid rgba(255,255,255,.8); box-shadow:0 0 0 1px rgba(0,0,0,.12); }

    @media(max-width:900px){ .map-shell{height:480px} }
</style>
@endpush

@section('content')
<div class="page-head">
    <div class="page-head-left">
        <div class="crumb">Главная › Карта</div>
        <h1 class="h-page">Карта обращений</h1>
        <p class="page-sub">{{ $mapTickets->count() }} {{ trans_choice('метка|метки|меток', $mapTickets->count()) }} на карте</p>
    </div>
</div>

<div class="map-page">
    <form class="card" method="GET" action="{{ route('admin.tickets.map') }}">
        <div class="card-head">
            <h2 class="card-title">Фильтр</h2>
            <div style="display:flex;gap:8px;">
                <button class="btn sm" type="submit">Показать</button>
                <a class="btn ghost sm" href="{{ route('admin.tickets.map') }}">Сбросить</a>
            </div>
        </div>
        <div class="card-body" style="display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px;">
            <div class="field">
                <div class="field-label">Статус</div>
                <select name="status">
                    <option value="">Все</option>
                    @foreach($statusLabels as $value => $label)
                        <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <div class="field-label">Категория</div>
                <select name="category_id">
                    <option value="">Все</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" @selected((string)request('category_id') === (string)$category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field"><div class="field-label">Дата от</div><input type="date" name="date_from" value="{{ request('date_from') }}"></div>
            <div class="field"><div class="field-label">Дата до</div><input type="date" name="date_to" value="{{ request('date_to') }}"></div>
        </div>
    </form>

    <div class="map-shell">
        <div id="ticketsMap"></div>

        {{-- Counters top-left (above zoom panel is avoided) --}}
        <div class="map-counter">
            <div class="map-chip">Заявок: <strong>{{ $mapTickets->count() }}</strong></div>
            @if(request('status'))
                <div class="map-chip">{{ $statusLabels[request('status')] ?? '' }}</div>
            @endif
        </div>

        {{-- Custom zoom controls — bottom-right, above attribution --}}
        <div class="map-zoom-ctrl" id="mapZoom">
            <button type="button" id="zoomIn" title="Приблизить">+</button>
            <button type="button" id="zoomOut" title="Отдалить">−</button>
        </div>

        {{-- Status legend — bottom-left --}}
        <div class="map-legend">
            <div class="map-legend-title">Статусы</div>
            <div class="map-legend-row"><div class="map-legend-dot" style="background:#2563EB;"></div><span>Новая</span></div>
            <div class="map-legend-row"><div class="map-legend-dot" style="background:#C2670A;"></div><span>Назначена</span></div>
            <div class="map-legend-row"><div class="map-legend-dot" style="background:#DD6B20;"></div><span>В работе</span></div>
            <div class="map-legend-row"><div class="map-legend-dot" style="background:#0E7A42;"></div><span>Выполнена</span></div>
            <div class="map-legend-row"><div class="map-legend-dot" style="background:#C0362F;"></div><span>Отклонена</span></div>
            <div class="map-legend-row"><div class="map-legend-dot" style="background:#687874;"></div><span>Дубликат</span></div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js"></script>
<script>
(function(){
    const tickets = @json($mapTickets);
    const cityCenter = [53.1327, 26.0139];
    const map = L.map('ticketsMap', { zoomControl: false, preferCanvas: false }).setView(cityCenter, 12);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19, attribution: '&copy; OpenStreetMap' }).addTo(map);

    // Custom zoom buttons
    document.getElementById('zoomIn').addEventListener('click', () => map.zoomIn());
    document.getElementById('zoomOut').addEventListener('click', () => map.zoomOut());

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
        if (!bounds.length) { map.setView(center, 12); return; }
        const local = bounds.filter(point => distanceKm(center, point) <= 80);
        if (local.length === 1) { map.setView(local[0], 15); }
        else if (local.length > 1) { map.fitBounds(local, { padding:[42,42], maxZoom:15 }); }
        else { map.setView(center, 12); }
    }
    function iconFor(status) {
        return L.divIcon({ className:'', html:`<div class="pin-marker pin-${status||'created'}"><div class="pin-body"></div><div class="pin-dot"></div></div>`, iconSize:[30,38], iconAnchor:[15,34], popupAnchor:[0,-30] });
    }
    function esc(v) { return String(v??'').replace(/[&<>'"]/g, c=>({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#039;','"':'&quot;'}[c])); }

    // Create a cluster group — markers auto-cluster when close together
    const cluster = L.markerClusterGroup({
        maxClusterRadius: 50,
        spiderfyOnMaxZoom: true,
        showCoverageOnHover: false,
        zoomToBoundsOnClick: true,
    });

    const bounds = [];
    tickets.forEach(t => {
        if (!t.lat || !t.lng) return;
        bounds.push([t.lat, t.lng]);
        const routeUrl = `https://www.google.com/maps/dir/?api=1&destination=${t.lat},${t.lng}`;
        const popup = `<div style="font-weight:600;margin-bottom:4px;">№${t.id} · ${esc(t.category)}</div>`+
            `<div style="color:#687468;font-size:12px;margin-bottom:6px;">${esc(t.status_label)}${t.worker?' · '+esc(t.worker):''}</div>`+
            `${t.address?`<div style="margin-bottom:6px;font-size:13px;">${esc(t.address)}</div>`:''}`+
            `<a class="btn sm" href="${t.url}">Открыть</a> `+
            `<a class="btn ghost sm" target="_blank" href="${routeUrl}">Маршрут</a>`;
        const marker = L.marker([t.lat, t.lng], { icon: iconFor(t.status) }).bindPopup(popup);
        cluster.addLayer(marker);
    });

    map.addLayer(cluster);
    safeFit(map, bounds, cityCenter);
    setTimeout(()=>map.invalidateSize(), 250);
    setTimeout(()=>map.invalidateSize(), 800);
})();
</script>
@endpush
