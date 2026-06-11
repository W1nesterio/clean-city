<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Аналитический отчёт ЖКХ — {{ $organizationName }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { background: #f5f7f5; color: #0f1f14; font-family: DejaVu Sans, Arial, sans-serif; font-size: 12px; line-height: 1.5; }

        .actions { position: sticky; top: 0; z-index: 100; background: #0a4a28; padding: 10px 20px; display: flex; align-items: center; gap: 10px; }
        .btn-print { display: inline-flex; align-items: center; height: 34px; padding: 0 16px; background: #16a34a; color: #fff; border: 0; border-radius: 6px; font-weight: 800; font-size: 12px; cursor: pointer; }
        .btn-back { display: inline-flex; align-items: center; height: 34px; padding: 0 16px; background: transparent; color: #a7f3c8; border: 1px solid #2d6a45; border-radius: 6px; font-size: 12px; text-decoration: none; }

        .page { max-width: 1040px; margin: 24px auto 40px; background: #fff; }

        /* ── Cover ── */
        .cover { background: linear-gradient(135deg, #0a4a28 0%, #0b653a 60%, #13814c 100%); color: #fff; padding: 40px 40px 32px; }
        .cover-brand { font-size: 11px; font-weight: 900; text-transform: uppercase; letter-spacing: .12em; color: #80dfaa; margin-bottom: 6px; }
        .cover-org { font-size: 26px; font-weight: 900; line-height: 1.2; }
        .cover-sub { margin-top: 6px; color: #a7f3c8; font-size: 13px; }
        .cover-meta { margin-top: 24px; display: flex; gap: 32px; flex-wrap: wrap; }
        .cover-meta-item label { display: block; font-size: 10px; text-transform: uppercase; letter-spacing: .1em; color: #80dfaa; font-weight: 900; }
        .cover-meta-item span { font-size: 13px; font-weight: 700; }

        /* ── Body ── */
        .body { padding: 32px 40px 40px; }

        .section { margin-top: 30px; }
        .section-title { font-size: 14px; font-weight: 900; text-transform: uppercase; letter-spacing: .07em; color: #0a4a28; border-bottom: 2px solid #0a4a28; padding-bottom: 6px; margin-bottom: 14px; }
        .section-subtitle { font-size: 11px; color: #6b7280; margin-top: -10px; margin-bottom: 14px; }

        /* ── KPI grid ── */
        .kpi-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 8px; }
        .kpi { border: 1px solid #d1fae5; border-radius: 8px; padding: 12px 10px; text-align: center; background: #f0fdf4; }
        .kpi.alert { background: #fff7ed; border-color: #fed7aa; }
        .kpi.danger { background: #fef2f2; border-color: #fecaca; }
        .kpi label { display: block; font-size: 9px; font-weight: 900; text-transform: uppercase; letter-spacing: .08em; color: #6b7280; }
        .kpi b { display: block; font-size: 22px; font-weight: 900; color: #0a4a28; margin-top: 4px; }
        .kpi.alert b { color: #c2410c; }
        .kpi.danger b { color: #b91c1c; }
        .kpi small { font-size: 10px; color: #9ca3af; }

        /* ── Rate bar ── */
        .rate-row { display: flex; align-items: center; gap: 10px; margin-bottom: 8px; }
        .rate-label { width: 150px; font-size: 11px; font-weight: 700; flex-shrink: 0; }
        .rate-track { flex: 1; height: 10px; background: #e5e7eb; border-radius: 4px; overflow: hidden; }
        .rate-fill { height: 10px; border-radius: 4px; }
        .rate-fill.green { background: #16a34a; }
        .rate-fill.orange { background: #ea580c; }
        .rate-fill.red { background: #dc2626; }
        .rate-fill.blue { background: #2563eb; }
        .rate-val { width: 40px; font-size: 11px; font-weight: 900; text-align: right; }

        /* ── Daily chart ── */
        .daily-chart { display: flex; align-items: flex-end; gap: 3px; height: 80px; margin-bottom: 6px; }
        .daily-bar-wrap { display: flex; flex-direction: column; align-items: center; flex: 1; min-width: 0; }
        .daily-bar { width: 100%; background: #16a34a; border-radius: 2px 2px 0 0; transition: background .2s; }
        .daily-bar.zero { background: #e5e7eb; }
        .daily-label { font-size: 8px; color: #9ca3af; margin-top: 2px; white-space: nowrap; overflow: hidden; }
        .daily-val { font-size: 8px; color: #4b5563; font-weight: 700; margin-bottom: 1px; }

        /* ── Tables ── */
        table { width: 100%; border-collapse: collapse; font-size: 11px; }
        th { background: #f0fdf4; border: 1px solid #d1fae5; padding: 7px 9px; text-align: left; font-size: 9.5px; font-weight: 900; text-transform: uppercase; letter-spacing: .06em; color: #374151; }
        td { border: 1px solid #e5e7eb; padding: 7px 9px; vertical-align: top; }
        tr:nth-child(even) td { background: #f9fafb; }
        .td-num { text-align: right; font-weight: 700; }
        .td-rate { text-align: right; }
        .pct { font-size: 10px; color: #9ca3af; }
        .pill { display: inline-block; padding: 2px 7px; border-radius: 10px; font-size: 9.5px; font-weight: 800; }
        .pill-green { background: #d1fae5; color: #065f46; }
        .pill-gray { background: #f3f4f6; color: #6b7280; }
        .pill-orange { background: #ffedd5; color: #9a3412; }
        .pill-red { background: #fee2e2; color: #991b1b; }
        .pill-blue { background: #dbeafe; color: #1e40af; }
        .pill-amber { background: #fef3c7; color: #92400e; }

        /* ── Forecast box ── */
        .forecast-box { border: 1px solid #d1fae5; border-left: 4px solid #0a4a28; padding: 16px 18px; border-radius: 6px; background: #f0fdf4; margin-top: 12px; }
        .forecast-box h3 { font-size: 12px; font-weight: 900; text-transform: uppercase; letter-spacing: .06em; color: #0a4a28; margin-bottom: 10px; }
        .forecast-row { display: flex; gap: 8px; margin-bottom: 6px; align-items: flex-start; }
        .forecast-icon { font-size: 14px; flex-shrink: 0; line-height: 1.4; }
        .forecast-text { font-size: 11px; line-height: 1.6; color: #374151; }
        .forecast-text strong { color: #0a4a28; }

        .warn-box { border: 1px solid #fecaca; border-left: 4px solid #dc2626; padding: 12px 16px; border-radius: 6px; background: #fef2f2; margin-top: 12px; }
        .warn-box h3 { font-size: 12px; font-weight: 900; text-transform: uppercase; letter-spacing: .06em; color: #991b1b; margin-bottom: 8px; }
        .warn-text { font-size: 11px; color: #374151; line-height: 1.6; }

        .footer { margin-top: 36px; padding-top: 12px; border-top: 1px solid #e5e7eb; display: flex; justify-content: space-between; color: #9ca3af; font-size: 10px; }

        @media print {
            body { background: #fff; }
            .actions { display: none; }
            .page { margin: 0; max-width: none; }
            .body { padding: 14mm 18mm; }
            .cover { padding: 14mm 18mm 10mm; }
            .section { page-break-inside: avoid; }
            a { color: #0a4a28; text-decoration: none; }
        }
    </style>
</head>
<body>
<div class="actions">
    <button class="btn-print" onclick="window.print()">Печать / Сохранить PDF</button>
    <a class="btn-back" href="{{ route('admin.analytics.index', request()->query()) }}">← Назад к аналитике</a>
</div>

<div class="page">

    {{-- ── Cover ── --}}
    <div class="cover">
        <div class="cover-brand">Чистый город · Аналитический отчёт ЖКХ</div>
        <div class="cover-org">{{ $organizationName }}</div>
        <div class="cover-sub">Обращения граждан — полная аналитика и прогноз</div>
        <div class="cover-meta">
            <div class="cover-meta-item">
                <label>Период</label>
                <span>{{ $dateFrom->format('d.m.Y') }} — {{ $dateTo->format('d.m.Y') }}</span>
            </div>
            <div class="cover-meta-item">
                <label>Дней в периоде</label>
                <span>{{ $dateFrom->diffInDays($dateTo) + 1 }}</span>
            </div>
            <div class="cover-meta-item">
                <label>Сформирован</label>
                <span>{{ now()->format('d.m.Y H:i') }}</span>
            </div>
            <div class="cover-meta-item">
                <label>Всего обращений</label>
                <span>{{ $summary['total'] }}</span>
            </div>
        </div>
    </div>

    <div class="body">

        {{-- ── 1. KPI ── --}}
        <div class="section">
            <div class="section-title">1. Ключевые показатели периода</div>
            <div class="kpi-grid">
                <div class="kpi">
                    <label>Всего</label>
                    <b>{{ $summary['total'] }}</b>
                    <small>обращений</small>
                </div>
                <div class="kpi">
                    <label>Новые</label>
                    <b>{{ $summary['created'] }}</b>
                    <small>ожидают</small>
                </div>
                <div class="kpi">
                    <label>В работе</label>
                    <b>{{ $summary['active'] }}</b>
                    <small>активны</small>
                </div>
                <div class="kpi">
                    <label>Выполнено</label>
                    <b>{{ $summary['completed'] }}</b>
                    <small>{{ $summary['completion_rate'] }}%</small>
                </div>
                <div class="kpi {{ $summary['rejected'] > 0 ? 'alert' : '' }}">
                    <label>Отклонено</label>
                    <b>{{ $summary['rejected'] }}</b>
                    <small>{{ $summary['rejection_rate'] }}%</small>
                </div>
                <div class="kpi {{ $summary['problem'] + $summary['postponed'] > 0 ? 'danger' : '' }}">
                    <label>Проблемы</label>
                    <b>{{ $summary['problem'] + $summary['postponed'] }}</b>
                    <small>{{ $summary['problem_rate'] }}%</small>
                </div>
                <div class="kpi {{ $summary['unassigned'] > 0 ? 'alert' : '' }}">
                    <label>Без ЖКХ</label>
                    <b>{{ $summary['unassigned'] }}</b>
                    <small>не назначено</small>
                </div>
            </div>
        </div>

        {{-- ── 2. Rates ── --}}
        <div class="section">
            <div class="section-title">2. Эффективность обработки</div>
            <div class="section-subtitle">Процентные показатели относительно общего числа обращений в периоде</div>

            @php $total = max(1, $summary['total']); @endphp
            <div class="rate-row">
                <div class="rate-label">Выполнено</div>
                <div class="rate-track"><div class="rate-fill green" style="width:{{ $summary['completion_rate'] }}%"></div></div>
                <div class="rate-val">{{ $summary['completion_rate'] }}%</div>
            </div>
            <div class="rate-row">
                <div class="rate-label">В активной работе</div>
                <div class="rate-track"><div class="rate-fill blue" style="width:{{ round($summary['active'] / $total * 100) }}%"></div></div>
                <div class="rate-val">{{ round($summary['active'] / $total * 100) }}%</div>
            </div>
            <div class="rate-row">
                <div class="rate-label">Ожидают назначения</div>
                <div class="rate-track"><div class="rate-fill orange" style="width:{{ round($summary['created'] / $total * 100) }}%"></div></div>
                <div class="rate-val">{{ round($summary['created'] / $total * 100) }}%</div>
            </div>
            <div class="rate-row">
                <div class="rate-label">Проблемы / Отложены</div>
                <div class="rate-track"><div class="rate-fill red" style="width:{{ $summary['problem_rate'] }}%"></div></div>
                <div class="rate-val">{{ $summary['problem_rate'] }}%</div>
            </div>
            <div class="rate-row">
                <div class="rate-label">Отклонено</div>
                <div class="rate-track"><div class="rate-fill red" style="width:{{ $summary['rejection_rate'] }}%"></div></div>
                <div class="rate-val">{{ $summary['rejection_rate'] }}%</div>
            </div>
        </div>

        {{-- ── 3. Daily dynamics ── --}}
        <div class="section">
            <div class="section-title">3. Динамика по дням</div>
            @php $maxCount = max(1, ...array_column($dailyRows ?? [[]], 'count')); @endphp
            <div class="daily-chart">
                @forelse($dailyRows as $row)
                    <div class="daily-bar-wrap">
                        <div class="daily-val">{{ $row['count'] > 0 ? $row['count'] : '' }}</div>
                        <div class="daily-bar {{ $row['count'] == 0 ? 'zero' : '' }}" style="height:{{ max(4, round($row['count'] / $maxCount * 68)) }}px"></div>
                        <div class="daily-label">{{ $row['label'] }}</div>
                    </div>
                @empty
                    <div style="color:#9ca3af;font-size:11px;padding:20px 0;">Нет данных для отображения</div>
                @endforelse
            </div>
            @if(count($dailyRows) > 0)
                @php
                    $nonZero = array_filter(array_column($dailyRows, 'count'), fn($c) => $c > 0);
                    $avgPerDay = count($nonZero) > 0 ? round(array_sum($nonZero) / count($nonZero), 1) : 0;
                    $peakRow = $dailyRows[array_search(max(array_column($dailyRows, 'count')), array_column($dailyRows, 'count'))];
                @endphp
                <div style="font-size:11px;color:#6b7280;margin-top:6px;">
                    Среднее в рабочий день: <strong>{{ $avgPerDay }}</strong> обращений · Пик: <strong>{{ $peakRow['label'] }} ({{ $peakRow['count'] }})</strong>
                </div>
            @endif
        </div>

        {{-- ── 4. Status breakdown ── --}}
        <div class="section">
            <div class="section-title">4. Распределение по статусам</div>
            <table>
                <thead>
                    <tr>
                        <th>Статус</th>
                        <th style="width:80px;text-align:right;">Кол-во</th>
                        <th style="width:80px;text-align:right;">Доля</th>
                        <th>Интерпретация</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $statusNotes = [
                            'created'    => 'Ожидают обработки / первичной проверки',
                            'moderation' => 'На проверке у оператора',
                            'assigned'   => 'Назначены в ЖКХ, ожидают принятия исполнителем',
                            'accepted'   => 'Приняты исполнителем, скоро начнётся работа',
                            'in_progress'=> 'Активно выполняются сейчас',
                            'problem'    => '⚠ Возникли трудности — требуется внимание',
                            'postponed'  => '⚠ Отложены — необходимо уточнить сроки',
                            'completed'  => 'Работа завершена, закрыты',
                            'rejected'   => 'Отклонены как несоответствующие или дублирующие',
                            'duplicate'  => 'Зафиксированы как дубликаты',
                        ];
                        $pillClass = [
                            'created' => 'pill-blue', 'moderation' => 'pill-blue',
                            'assigned' => 'pill-amber', 'accepted' => 'pill-amber',
                            'in_progress' => 'pill-amber', 'problem' => 'pill-red',
                            'postponed' => 'pill-orange', 'completed' => 'pill-green',
                            'rejected' => 'pill-gray', 'duplicate' => 'pill-gray',
                        ];
                    @endphp
                    @forelse($statusRows as $row)
                    <tr>
                        <td><span class="pill {{ $pillClass[$row['status']] ?? 'pill-gray' }}">{{ $row['label'] }}</span></td>
                        <td class="td-num">{{ $row['count'] }}</td>
                        <td class="td-rate"><span class="pct">{{ round($row['count'] / max(1, $summary['total']) * 100, 1) }}%</span></td>
                        <td style="color:#4b5563;font-size:10.5px;">{{ $statusNotes[$row['status']] ?? '' }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="4" style="color:#9ca3af;">Нет данных по статусам</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- ── 5. Category analysis ── --}}
        <div class="section">
            <div class="section-title">5. Анализ по категориям обращений</div>
            <div class="section-subtitle">Позволяет определить наиболее проблемные направления коммунального хозяйства</div>
            <table>
                <thead>
                    <tr>
                        <th>Категория</th>
                        <th style="width:60px;text-align:right;">Всего</th>
                        <th style="width:60px;text-align:right;">В работе</th>
                        <th style="width:70px;text-align:right;">Выполнено</th>
                        <th style="width:70px;text-align:right;">Отклонено</th>
                        <th style="width:80px;text-align:right;">% выполнения</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($categoryRows as $row)
                    @php $catRate = $row['total'] > 0 ? round($row['completed'] / $row['total'] * 100) : 0; @endphp
                    <tr>
                        <td><strong>{{ $row['name'] }}</strong>{{ !$row['active'] ? ' <span class="pct">(неактивна)</span>' : '' }}</td>
                        <td class="td-num">{{ $row['total'] }}</td>
                        <td class="td-num">{{ $row['in_work'] }}</td>
                        <td class="td-num" style="color:#16a34a;">{{ $row['completed'] }}</td>
                        <td class="td-num" style="color:#dc2626;">{{ $row['rejected'] }}</td>
                        <td class="td-rate">
                            <span style="font-weight:700;color:{{ $catRate >= 70 ? '#16a34a' : ($catRate >= 40 ? '#d97706' : '#dc2626') }};">{{ $catRate }}%</span>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" style="color:#9ca3af;">Нет данных по категориям</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- ── 6. Worker performance ── --}}
        @if(count($workerRows) > 0)
        <div class="section">
            <div class="section-title">6. Производительность исполнителей</div>
            <div class="section-subtitle">Оценка нагрузки и эффективности сотрудников ЖКХ</div>
            <table>
                <thead>
                    <tr>
                        <th>Сотрудник</th>
                        <th>Организация</th>
                        <th style="width:65px;text-align:right;">Назначено</th>
                        <th style="width:65px;text-align:right;">В работе</th>
                        <th style="width:75px;text-align:right;">Выполнено</th>
                        <th style="width:80px;text-align:right;">% выполнения</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($workerRows->sortByDesc('completed') as $row)
                    @php $wRate = $row['total'] > 0 ? round($row['completed'] / $row['total'] * 100) : 0; @endphp
                    <tr>
                        <td>
                            <strong>{{ $row['name'] }}</strong><br>
                            <span class="pct">{{ $row['email'] }}</span>
                        </td>
                        <td style="font-size:11px;">{{ $row['organization'] }}</td>
                        <td class="td-num">{{ $row['total'] }}</td>
                        <td class="td-num">{{ $row['in_work'] }}</td>
                        <td class="td-num" style="color:#16a34a;">{{ $row['completed'] }}</td>
                        <td class="td-rate">
                            <span style="font-weight:700;color:{{ $wRate >= 70 ? '#16a34a' : ($wRate >= 40 ? '#d97706' : '#dc2626') }};">{{ $wRate }}%</span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif

        {{-- ── 7. Organizations ── --}}
        @if(!$isOrgAdmin && count($organizationRows) > 0)
        <div class="section">
            <div class="section-title">7. Сводка по организациям ЖКХ</div>
            <table>
                <thead>
                    <tr>
                        <th>Организация</th>
                        <th style="width:65px;text-align:right;">Сотрудников</th>
                        <th style="width:65px;text-align:right;">Назначено</th>
                        <th style="width:65px;text-align:right;">В работе</th>
                        <th style="width:75px;text-align:right;">Выполнено</th>
                        <th style="width:65px;text-align:right;">Скрыто</th>
                        <th style="width:80px;text-align:right;">% выполнения</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($organizationRows as $row)
                    @php $oRate = $row['total'] > 0 ? round($row['completed'] / $row['total'] * 100) : 0; @endphp
                    <tr>
                        <td>
                            <strong>{{ $row['name'] }}</strong>
                            @if(!$row['active'])<span class="pill pill-gray" style="margin-left:4px;">неактивна</span>@endif
                        </td>
                        <td class="td-num">{{ $row['workers'] }}</td>
                        <td class="td-num">{{ $row['total'] }}</td>
                        <td class="td-num">{{ $row['in_work'] }}</td>
                        <td class="td-num" style="color:#16a34a;">{{ $row['completed'] }}</td>
                        <td class="td-num" style="{{ $row['hidden'] > 0 ? 'color:#c2410c;' : '' }}">{{ $row['hidden'] }}</td>
                        <td class="td-rate">
                            <span style="font-weight:700;color:{{ $oRate >= 70 ? '#16a34a' : ($oRate >= 40 ? '#d97706' : '#dc2626') }};">{{ $oRate }}%</span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif

        {{-- ── 8. Problem tickets ── --}}
        @if(count($problemTickets) > 0)
        <div class="section">
            <div class="section-title">8. Проблемные и отложенные обращения</div>
            <div class="section-subtitle">Требуют приоритетного рассмотрения и принятия управленческих решений</div>
            <table>
                <thead>
                    <tr>
                        <th style="width:50px;">№</th>
                        <th style="width:95px;">Дата</th>
                        <th>Категория</th>
                        <th>Статус</th>
                        <th>ЖКХ</th>
                        <th>Исполнитель</th>
                        <th>Описание</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($problemTickets as $ticket)
                    <tr>
                        <td class="td-num">№{{ $ticket->id }}</td>
                        <td style="white-space:nowrap;">{{ optional($ticket->created_at)->format('d.m.Y') }}</td>
                        <td>{{ $ticket->category->name ?? '—' }}</td>
                        <td>
                            <span class="pill {{ $ticket->status === 'problem' ? 'pill-red' : 'pill-orange' }}">
                                {{ $statusLabels[$ticket->status] ?? $ticket->status }}
                            </span>
                        </td>
                        <td style="font-size:10.5px;">{{ optional($ticket->assignedOrganization)->name ?? '—' }}</td>
                        <td style="font-size:10.5px;">{{ optional($ticket->assignedWorker)->name ?? '—' }}</td>
                        <td style="font-size:10.5px;color:#4b5563;">{{ Str::limit($ticket->description ?? '', 60) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif

        {{-- ── 9. Recent tickets ── --}}
        <div class="section">
            <div class="section-title">{{ $isOrgAdmin || count($problemTickets) > 0 ? '9' : '8' }}. Реестр последних обращений</div>
            <div class="section-subtitle">Последние {{ count($latestTickets) }} обращений за отчётный период</div>
            <table>
                <thead>
                    <tr>
                        <th style="width:50px;">№</th>
                        <th style="width:95px;">Дата</th>
                        <th>Категория</th>
                        <th>Статус</th>
                        <th>ЖКХ</th>
                        <th>Исполнитель</th>
                        <th>Приоритет</th>
                        <th>Адрес / Описание</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $priorityLabels = ['high' => 'Высокий', 'normal' => 'Обычный', 'low' => 'Низкий'];
                        $priorityClass = ['high' => 'pill-red', 'normal' => 'pill-gray', 'low' => 'pill-gray'];
                    @endphp
                    @forelse($latestTickets as $ticket)
                    <tr>
                        <td class="td-num">№{{ $ticket->id }}</td>
                        <td style="white-space:nowrap;font-size:10.5px;">{{ optional($ticket->created_at)->format('d.m.Y H:i') }}</td>
                        <td>{{ $ticket->category->name ?? '—' }}</td>
                        <td><span class="pill {{ $pillClass[$ticket->status] ?? 'pill-gray' }}">{{ $statusLabels[$ticket->status] ?? $ticket->status }}</span></td>
                        <td style="font-size:10.5px;">{{ optional($ticket->assignedOrganization)->name ?? '—' }}</td>
                        <td style="font-size:10.5px;">{{ optional($ticket->assignedWorker)->name ?? '—' }}</td>
                        <td><span class="pill {{ $priorityClass[$ticket->priority ?? 'normal'] ?? 'pill-gray' }}">{{ $priorityLabels[$ticket->priority ?? 'normal'] ?? '—' }}</span></td>
                        <td style="font-size:10.5px;color:#4b5563;">{{ Str::limit($ticket->address_text ?: ($ticket->description ?? ''), 55) }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="8" style="color:#9ca3af;">Нет обращений за выбранный период</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- ── 10. Forecast & Recommendations ── --}}
        <div class="section">
            <div class="section-title">10. Прогноз и рекомендации</div>
            <div class="section-subtitle">Автоматически сформированные выводы на основе данных периода</div>

            @php
                $days = $dateFrom->diffInDays($dateTo) + 1;
                $avgDaily = $days > 0 ? round($summary['total'] / $days, 1) : 0;
                $projectedMonth = round($avgDaily * 30);
                $criticalCategories = $categoryRows->filter(fn($r) => $r['total'] > 0 && $r['completed'] / max(1, $r['total']) < 0.4 && $r['total'] >= 3)->pluck('name');
                $topCategory = $categoryRows->sortByDesc('total')->first();
                $overloadedWorkers = $workerRows->filter(fn($r) => $r['in_work'] >= 5);
            @endphp

            <div class="forecast-box">
                <h3>Прогнозные показатели</h3>

                <div class="forecast-row">
                    <div class="forecast-icon">📊</div>
                    <div class="forecast-text">
                        <strong>Средняя нагрузка:</strong> {{ $avgDaily }} обращений/день.
                        При сохранении темпа — ожидается около <strong>{{ $projectedMonth }}</strong> обращений в следующем месяце.
                    </div>
                </div>

                @if($topCategory)
                <div class="forecast-row">
                    <div class="forecast-icon">🔝</div>
                    <div class="forecast-text">
                        <strong>Наиболее частая категория:</strong> «{{ $topCategory['name'] }}» — {{ $topCategory['total'] }} обращений
                        ({{ round($topCategory['total'] / max(1, $summary['total']) * 100) }}% от общего числа).
                        Рекомендуется усилить ресурсы по данному направлению.
                    </div>
                </div>
                @endif

                @if($summary['completion_rate'] >= 70)
                <div class="forecast-row">
                    <div class="forecast-icon">✅</div>
                    <div class="forecast-text">
                        <strong>Уровень выполнения {{ $summary['completion_rate'] }}% — высокий.</strong>
                        Текущие процессы обработки обращений функционируют эффективно.
                    </div>
                </div>
                @elseif($summary['completion_rate'] >= 40)
                <div class="forecast-row">
                    <div class="forecast-icon">⚠</div>
                    <div class="forecast-text">
                        <strong>Уровень выполнения {{ $summary['completion_rate'] }}% — удовлетворительный.</strong>
                        Рекомендуется проанализировать загруженность исполнителей и оптимизировать распределение задач.
                    </div>
                </div>
                @else
                <div class="forecast-row">
                    <div class="forecast-icon">🚨</div>
                    <div class="forecast-text">
                        <strong>Уровень выполнения {{ $summary['completion_rate'] }}% — критически низкий.</strong>
                        Необходимы срочные меры: пересмотр ресурсов, контроль исполнения, возможное привлечение дополнительных сотрудников.
                    </div>
                </div>
                @endif

                @if($summary['unassigned'] > 0)
                <div class="forecast-row">
                    <div class="forecast-icon">⏳</div>
                    <div class="forecast-text">
                        <strong>{{ $summary['unassigned'] }} обращений</strong> не назначены ни в одну организацию ЖКХ.
                        Это задерживает начало работы — требуется оперативное назначение.
                    </div>
                </div>
                @endif

                @if($summary['problem'] + $summary['postponed'] > 0)
                <div class="forecast-row">
                    <div class="forecast-icon">🔴</div>
                    <div class="forecast-text">
                        <strong>{{ $summary['problem'] + $summary['postponed'] }} обращений</strong> имеют проблемный/отложенный статус
                        ({{ $summary['problem_rate'] }}%).
                        Каждое из них несёт риск перехода в просроченное состояние — рекомендуется индивидуальный разбор.
                    </div>
                </div>
                @endif

                @if($overloadedWorkers->count() > 0)
                <div class="forecast-row">
                    <div class="forecast-icon">👷</div>
                    <div class="forecast-text">
                        <strong>Перегруженные исполнители (≥5 в работе):</strong>
                        {{ $overloadedWorkers->pluck('name')->implode(', ') }}.
                        Рекомендуется перераспределить часть задач.
                    </div>
                </div>
                @endif

                @if($criticalCategories->count() > 0)
                <div class="forecast-row">
                    <div class="forecast-icon">📌</div>
                    <div class="forecast-text">
                        <strong>Категории с низким процентом выполнения (&lt;40%):</strong>
                        {{ $criticalCategories->implode(', ') }}.
                        Требуют дополнительного анализа причин задержек.
                    </div>
                </div>
                @endif
            </div>
        </div>

        {{-- Footer --}}
        <div class="footer">
            <span>Чистый город · {{ $organizationName }}</span>
            <span>{{ $dateFrom->format('d.m.Y') }} — {{ $dateTo->format('d.m.Y') }} · Сформирован {{ now()->format('d.m.Y H:i') }}</span>
        </div>
    </div>
</div>
</body>
</html>
