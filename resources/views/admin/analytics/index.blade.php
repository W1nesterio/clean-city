@extends('admin.layout')
@section('title', 'Отчёт')

@push('head')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<style>
    .report-grid { display:grid; grid-template-columns:minmax(0,1fr) 320px; gap:20px; align-items:start; }
    .side-sticky { position:sticky; top:80px; }
    .chart-wrap { position:relative; }
    .kpi5 { display:grid; grid-template-columns:repeat(5,minmax(0,1fr)); gap:1px; background:var(--line); border-radius:var(--r-lg); overflow:hidden; margin-bottom:0; }
    .kpi5-cell { background:var(--surface); padding:18px 16px; }
    .kpi5-label { font-size:11px; font-weight:600; text-transform:uppercase; letter-spacing:.06em; color:var(--muted); }
    .kpi5-val { font-size:28px; font-weight:700; letter-spacing:-0.04em; margin-top:6px; font-variant-numeric:tabular-nums; }
    .kpi5-sub { font-size:12px; color:var(--muted); margin-top:4px; }
    .period-pills { display:flex; gap:6px; flex-wrap:wrap; margin-bottom:16px; }
    .rate-row { display:flex; align-items:center; justify-content:space-between; padding:10px 0; border-bottom:1px solid var(--line-soft); }
    .rate-row:last-child { border-bottom:0; }
    .rate-bar-wrap { flex:1; margin:0 12px; height:6px; border-radius:999px; background:var(--surface-strong); overflow:hidden; }
    .rate-bar { height:100%; border-radius:999px; }
    @media(max-width:1180px){ .report-grid{grid-template-columns:1fr} .side-sticky{position:static} .kpi5{grid-template-columns:repeat(3,1fr)} }
    @media(max-width:760px){ .kpi5{grid-template-columns:1fr 1fr} }
</style>
@endpush

@section('content')
@php
    $completionRate = $summary['total'] > 0 ? round($summary['completed'] / $summary['total'] * 100) : 0;
    $rejectionRate  = $summary['total'] > 0 ? round($summary['rejected'] / $summary['total'] * 100) : 0;
    $activeRate     = $summary['total'] > 0 ? round($summary['active'] / $summary['total'] * 100) : 0;
    $statusColors = [
        'created'=>'#2563EB','moderation'=>'#7C3AED','assigned'=>'#C2670A','accepted'=>'#D97706',
        'in_progress'=>'#DD6B20','problem'=>'#DC2626','postponed'=>'#9CA3AF',
        'completed'=>'#0E7A42','rejected'=>'#C0362F','duplicate'=>'#687874',
    ];
@endphp

<div class="page-head">
    <div class="page-head-left">
        <div class="crumb">Главная › Отчёт</div>
        <h1 class="h-page">Аналитика</h1>
        <p class="page-sub">{{ $organizationName }} · {{ $dateFrom->format('d.m.Y') }} — {{ $dateTo->format('d.m.Y') }}</p>
    </div>
    <div class="page-actions">
        <a class="btn ghost sm" href="{{ route('admin.analytics.export', request()->query()) }}">↓ CSV</a>
        <a class="btn ghost sm" href="{{ route('admin.analytics.pdf', request()->query()) }}" target="_blank">↓ PDF</a>
    </div>
</div>

<div class="report-grid">
    {{-- ====== MAIN COLUMN ====== --}}
    <div style="display:flex;flex-direction:column;gap:18px;">

        {{-- KPI strip --}}
        <div class="table-card">
            <div class="kpi5">
                <div class="kpi5-cell">
                    <div class="kpi5-label">Всего заявок</div>
                    <div class="kpi5-val">{{ $summary['total'] }}</div>
                    <div class="kpi5-sub">за период</div>
                </div>
                <div class="kpi5-cell">
                    <div class="kpi5-label">В очереди</div>
                    <div class="kpi5-val" style="color:var(--c-blue);">{{ $summary['created'] }}</div>
                    <div class="kpi5-sub">новые / проверка</div>
                </div>
                <div class="kpi5-cell">
                    <div class="kpi5-label">В работе</div>
                    <div class="kpi5-val" style="color:var(--c-orange);">{{ $summary['active'] }}</div>
                    <div class="kpi5-sub">{{ $activeRate }}% от всех</div>
                </div>
                <div class="kpi5-cell">
                    <div class="kpi5-label">Выполнено</div>
                    <div class="kpi5-val" style="color:var(--primary);">{{ $summary['completed'] }}</div>
                    <div class="kpi5-sub">{{ $completionRate }}% закрыто</div>
                </div>
                <div class="kpi5-cell">
                    <div class="kpi5-label">Отклонено</div>
                    <div class="kpi5-val" style="color:var(--c-red);">{{ $summary['rejected'] }}</div>
                    <div class="kpi5-sub">{{ $rejectionRate }}% отказов</div>
                </div>
            </div>
        </div>

        {{-- Row: Daily trend + Donut --}}
        <div style="display:grid;grid-template-columns:minmax(0,1.6fr) minmax(0,1fr);gap:18px;align-items:start;">
            <div class="card card-pad">
                <div class="card-head" style="padding:0 0 14px;border-bottom:1px solid var(--line);margin-bottom:16px;">
                    <div>
                        <div class="card-title">Динамика по дням</div>
                        <div class="card-sub">Количество поступивших обращений</div>
                    </div>
                </div>
                <div class="chart-wrap" style="height:200px;">
                    <canvas id="chartDaily"></canvas>
                </div>
            </div>

            <div class="card card-pad">
                <div class="card-head" style="padding:0 0 14px;border-bottom:1px solid var(--line);margin-bottom:16px;">
                    <div>
                        <div class="card-title">Распределение</div>
                        <div class="card-sub">По статусам</div>
                    </div>
                </div>
                <div class="chart-wrap" style="height:180px;">
                    <canvas id="chartDonut"></canvas>
                </div>
                @if($summary['total'] > 0)
                <div style="margin-top:12px;">
                    <div class="rate-row">
                        <span style="font-size:12px;color:var(--muted);min-width:80px;">Выполнено</span>
                        <div class="rate-bar-wrap"><div class="rate-bar" style="width:{{ $completionRate }}%;background:var(--primary);"></div></div>
                        <span style="font-size:12.5px;font-weight:600;min-width:34px;text-align:right;">{{ $completionRate }}%</span>
                    </div>
                    <div class="rate-row">
                        <span style="font-size:12px;color:var(--muted);min-width:80px;">В работе</span>
                        <div class="rate-bar-wrap"><div class="rate-bar" style="width:{{ $activeRate }}%;background:var(--c-orange);"></div></div>
                        <span style="font-size:12.5px;font-weight:600;min-width:34px;text-align:right;">{{ $activeRate }}%</span>
                    </div>
                    <div class="rate-row">
                        <span style="font-size:12px;color:var(--muted);min-width:80px;">Отклонено</span>
                        <div class="rate-bar-wrap"><div class="rate-bar" style="width:{{ $rejectionRate }}%;background:var(--c-red);"></div></div>
                        <span style="font-size:12.5px;font-weight:600;min-width:34px;text-align:right;">{{ $rejectionRate }}%</span>
                    </div>
                </div>
                @endif
            </div>
        </div>

        {{-- Category bar chart --}}
        @if($categoryRows->isNotEmpty())
        <div class="card card-pad">
            <div class="card-head" style="padding:0 0 14px;border-bottom:1px solid var(--line);margin-bottom:16px;">
                <div>
                    <div class="card-title">По категориям</div>
                    <div class="card-sub">Всего / Выполнено / Отклонено</div>
                </div>
            </div>
            <div class="chart-wrap" style="height:{{ max(160, $categoryRows->count() * 36) }}px;">
                <canvas id="chartCategories"></canvas>
            </div>
        </div>
        @endif

        {{-- Workers table --}}
        @if($workerRows->isNotEmpty())
        <div class="table-card">
            <div class="card-head">
                <div>
                    <div class="card-title">Сотрудники</div>
                    <div class="card-sub">{{ $workerRows->count() }} {{ trans_choice('исполнитель|исполнителя|исполнителей', $workerRows->count()) }}</div>
                </div>
            </div>
            <div class="chart-wrap card-pad" style="height:{{ max(180, $workerRows->count() * 40 + 40) }}px;">
                <canvas id="chartWorkers"></canvas>
            </div>
            <div class="table-wrap">
                <table class="tbl">
                    <thead><tr>
                        <th>Сотрудник</th>
                        <th class="num">Назначено</th>
                        <th class="num">В работе</th>
                        <th class="num">Выполнено</th>
                        <th>Эффект.</th>
                    </tr></thead>
                    <tbody>
                    @foreach($workerRows as $row)
                    @php $eff = $row['total'] > 0 ? round($row['completed']/$row['total']*100) : 0; @endphp
                    <tr>
                        <td>
                            <div style="font-weight:600;">{{ $row['name'] }}</div>
                            <div class="secondary">{{ $row['email'] }}</div>
                        </td>
                        <td class="num">{{ $row['total'] }}</td>
                        <td class="num">{{ $row['in_work'] }}</td>
                        <td class="num" style="color:var(--primary);font-weight:600;">{{ $row['completed'] }}</td>
                        <td>
                            <div style="display:flex;align-items:center;gap:8px;">
                                <div style="flex:1;height:5px;border-radius:999px;background:var(--surface-strong);overflow:hidden;min-width:50px;">
                                    <div style="height:100%;width:{{ $eff }}%;background:var(--primary);border-radius:999px;"></div>
                                </div>
                                <span style="font-size:12px;font-weight:600;color:var(--muted);min-width:30px;">{{ $eff }}%</span>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        {{-- Recent tickets --}}
        @if($latestTickets->isNotEmpty())
        <div class="table-card">
            <div class="card-head">
                <div class="card-title">Последние заявки</div>
                <a href="{{ route('admin.tickets.index') }}" class="btn ghost sm">Все заявки</a>
            </div>
            <div class="table-wrap">
                <table class="tbl">
                    <thead><tr><th>№</th><th>Категория</th><th>Статус</th><th>Исполнитель</th><th>Дата</th></tr></thead>
                    <tbody>
                    @foreach($latestTickets as $t)
                    <tr onclick="location.href='{{ route('admin.tickets.show', $t) }}'" style="cursor:pointer;">
                        <td class="num id-cell">#{{ $t->id }}</td>
                        <td>{{ $t->category->name ?? '—' }}</td>
                        <td><span class="status-pill status-{{ $t->status }}">{{ $statusLabels[$t->status] ?? $t->status }}</span></td>
                        <td class="txt-muted">{{ $t->assignedWorker->name ?? '—' }}</td>
                        <td class="txt-muted num">{{ $t->created_at->format('d.m H:i') }}</td>
                    </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif
    </div>

    {{-- ====== SIDE PANEL ====== --}}
    <div class="side-sticky" style="display:flex;flex-direction:column;gap:18px;">
        <div class="card card-pad">
            <div class="card-title" style="margin-bottom:14px;">Параметры отчёта</div>

            <div class="period-pills">
                @foreach($periodLabels as $key => $label)
                    @if($key !== 'custom')
                    <a class="btn {{ $period === $key ? '' : 'ghost' }} sm"
                       href="{{ route('admin.analytics.index', array_merge(request()->except(['period','date_from','date_to']), ['period'=>$key])) }}">{{ $label }}</a>
                    @endif
                @endforeach
            </div>

            <form method="GET" action="{{ route('admin.analytics.index') }}" style="display:flex;flex-direction:column;gap:12px;">
                <input type="hidden" name="period" value="custom">
                <div class="field"><div class="field-label">Дата от</div><input type="date" name="date_from" value="{{ request('date_from', $dateFrom->format('Y-m-d')) }}"></div>
                <div class="field"><div class="field-label">Дата до</div><input type="date" name="date_to" value="{{ request('date_to', $dateTo->format('Y-m-d')) }}"></div>
                <div class="field">
                    <div class="field-label">Категория</div>
                    <select name="category_id">
                        <option value="">Все</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}" @selected((string)request('category_id')===(string)$cat->id)>{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <div class="field-label">Исполнитель</div>
                    <select name="assigned_worker_id">
                        <option value="">Все</option>
                        @foreach($workers as $w)
                            <option value="{{ $w->id }}" @selected((string)request('assigned_worker_id')===(string)$w->id)>{{ $w->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div style="display:flex;gap:8px;">
                    <button class="btn" type="submit" style="flex:1;">Применить</button>
                    <a class="btn ghost" href="{{ route('admin.analytics.index') }}">×</a>
                </div>
            </form>

            <div class="divider"></div>

            <div style="display:flex;flex-direction:column;gap:6px;">
                <a class="btn ghost" style="justify-content:flex-start;gap:8px;" href="{{ route('admin.analytics.export', request()->query()) }}">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                    Скачать CSV
                </a>
                <a class="btn ghost" style="justify-content:flex-start;gap:8px;" href="{{ route('admin.analytics.pdf', request()->query()) }}" target="_blank">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                    Печать / PDF
                </a>
            </div>
        </div>

        {{-- Unhidden/hidden count --}}
        <div class="card card-pad">
            <div class="card-title" style="margin-bottom:12px;">Дополнительно</div>
            <div class="rate-row">
                <span style="font-size:13px;">Без исполнителя</span>
                <span style="font-weight:700;font-size:16px;color:{{ $summary['unassigned']>0 ? 'var(--c-amber)' : 'var(--muted)' }};">{{ $summary['unassigned'] }}</span>
            </div>
            <div class="rate-row">
                <span style="font-size:13px;">Скрытые ЖКХ</span>
                <span style="font-weight:700;font-size:16px;color:{{ $summary['hidden']>0 ? 'var(--c-red)' : 'var(--muted)' }};">{{ $summary['hidden'] }}</span>
            </div>
        </div>

        {{-- Status breakdown --}}
        @if($statusRows->isNotEmpty())
        <div class="card card-pad">
            <div class="card-title" style="margin-bottom:12px;">По статусам</div>
            @foreach($statusRows as $row)
            <div class="rate-row">
                <span class="pill no-dot" style="background:{{ $statusColors[$row['status']] ?? '#687874' }}1a;color:{{ $statusColors[$row['status']] ?? '#687874' }};">{{ $row['label'] }}</span>
                <span style="font-weight:700;font-size:16px;font-variant-numeric:tabular-nums;">{{ $row['count'] }}</span>
            </div>
            @endforeach
        </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
(function() {
    Chart.defaults.font.family = getComputedStyle(document.documentElement).getPropertyValue('--font').trim() || 'Inter, sans-serif';
    Chart.defaults.color = getComputedStyle(document.documentElement).getPropertyValue('--muted').trim() || '#687468';

    const primary  = '#0E7A42';
    const orange   = '#DD6B20';
    const blue     = '#2563EB';
    const red      = '#C0362F';
    const amber    = '#C2670A';
    const surface  = getComputedStyle(document.documentElement).getPropertyValue('--surface-strong').trim() || '#EDF2EA';

    // ── Daily trend ──────────────────────────────────────────
    const dailyData = @json($dailyRows);
    new Chart(document.getElementById('chartDaily'), {
        type: 'bar',
        data: {
            labels: dailyData.map(d => d.label),
            datasets: [{
                label: 'Заявок',
                data: dailyData.map(d => d.count),
                backgroundColor: dailyData.map(d => d.count > 0 ? primary + 'cc' : surface),
                borderRadius: 4,
                borderSkipped: false,
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { display: false }, tooltip: { callbacks: { title: items => items[0].label, label: i => ' ' + i.raw + ' заявок' } } },
            scales: {
                x: { grid: { display: false }, ticks: { maxRotation: 45, font: { size: 11 } } },
                y: { beginAtZero: true, ticks: { stepSize: 1, font: { size: 11 } }, grid: { color: surface } }
            }
        }
    });

    // ── Status donut ─────────────────────────────────────────
    const statusData = @json($statusRows);
    const statusColors = @json($statusColors);
    if (statusData.length > 0) {
        new Chart(document.getElementById('chartDonut'), {
            type: 'doughnut',
            data: {
                labels: statusData.map(d => d.label),
                datasets: [{
                    data: statusData.map(d => d.count),
                    backgroundColor: statusData.map(d => statusColors[d.status] || '#687874'),
                    borderWidth: 2,
                    borderColor: getComputedStyle(document.documentElement).getPropertyValue('--surface').trim() || '#fff',
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                cutout: '64%',
                plugins: {
                    legend: { position: 'bottom', labels: { boxWidth: 10, padding: 8, font: { size: 11 } } },
                    tooltip: { callbacks: { label: i => ' ' + i.label + ': ' + i.raw } }
                }
            }
        });
    }

    // ── Categories horizontal bar ────────────────────────────
    const catData = @json($categoryRows);
    if (catData.length > 0 && document.getElementById('chartCategories')) {
        new Chart(document.getElementById('chartCategories'), {
            type: 'bar',
            data: {
                labels: catData.map(d => d.name),
                datasets: [
                    { label: 'Всего',     data: catData.map(d => d.total),     backgroundColor: blue    + '55', borderRadius: 3 },
                    { label: 'Выполнено', data: catData.map(d => d.completed), backgroundColor: primary + 'cc', borderRadius: 3 },
                    { label: 'Отклонено', data: catData.map(d => d.rejected),  backgroundColor: red    + '99', borderRadius: 3 },
                ]
            },
            options: {
                indexAxis: 'y',
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { position: 'top', labels: { boxWidth: 10, padding: 10, font: { size: 11 } } } },
                scales: {
                    x: { beginAtZero: true, ticks: { stepSize: 1, font: { size: 11 } }, grid: { color: surface } },
                    y: { grid: { display: false }, ticks: { font: { size: 11 } } }
                }
            }
        });
    }

    // ── Workers bar ──────────────────────────────────────────
    const wData = @json($workerRows);
    if (wData.length > 0 && document.getElementById('chartWorkers')) {
        new Chart(document.getElementById('chartWorkers'), {
            type: 'bar',
            data: {
                labels: wData.map(d => d.name),
                datasets: [
                    { label: 'Назначено', data: wData.map(d => d.total),     backgroundColor: blue    + '55', borderRadius: 3 },
                    { label: 'Выполнено', data: wData.map(d => d.completed), backgroundColor: primary + 'cc', borderRadius: 3 },
                    { label: 'В работе',  data: wData.map(d => d.in_work),   backgroundColor: amber  + '99', borderRadius: 3 },
                ]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { position: 'top', labels: { boxWidth: 10, padding: 10, font: { size: 11 } } } },
                scales: {
                    x: { grid: { display: false }, ticks: { font: { size: 11 } } },
                    y: { beginAtZero: true, ticks: { stepSize: 1, font: { size: 11 } }, grid: { color: surface } }
                }
            }
        });
    }
})();
</script>
@endpush
