@extends('admin.layout')
@section('title', 'Жалобы')

@section('content')
<div class="page-head">
    <div class="page-head-left">
        <div class="crumb">Главная › Жалобы</div>
        <h1 class="h-page">Жалобы</h1>
        <p class="page-sub">Обращения от жителей и исполнителей по работе организаций</p>
    </div>
</div>

{{-- Stats --}}
<div class="metric-grid" style="margin-bottom:18px;">
    <div class="metric-card accent">
        <div class="metric-label">Всего</div>
        <div class="metric-value">{{ $stats['total'] }}</div>
    </div>
    <div class="metric-card">
        <div class="metric-label">На рассмотрении</div>
        <div class="metric-value">{{ $stats['pending'] }}</div>
        <div class="metric-hint">ожидают ответа</div>
    </div>
    <div class="metric-card">
        <div class="metric-label">Рассматривается</div>
        <div class="metric-value">{{ $stats['in_review'] }}</div>
    </div>
    <div class="metric-card">
        <div class="metric-label">Решено</div>
        <div class="metric-value">{{ $stats['resolved'] }}</div>
    </div>
</div>

{{-- Filters --}}
<div class="card" style="margin-bottom:18px;">
    <div class="card-head"><h2 class="card-title">Фильтр</h2></div>
    <div class="card-body">
        <form method="GET" action="{{ route('admin.complaints.index') }}" style="display:flex;flex-wrap:wrap;gap:12px;align-items:flex-end;">
            <div class="field" style="min-width:220px;flex:1;">
                <div class="field-label">Поиск</div>
                <input name="search" value="{{ request('search') }}" placeholder="Заголовок или описание">
            </div>
            <div class="field">
                <div class="field-label">Статус</div>
                <select name="status">
                    <option value="">Все</option>
                    @foreach($statuses as $val => $label)
                        <option value="{{ $val }}" @selected(request('status') === $val)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <div class="field-label">Тип</div>
                <select name="type">
                    <option value="">Все</option>
                    @foreach($types as $val => $label)
                        <option value="{{ $val }}" @selected(request('type') === $val)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            @if($organizations->isNotEmpty())
            <div class="field">
                <div class="field-label">ЖКХ</div>
                <select name="org_id">
                    <option value="">Все</option>
                    @foreach($organizations as $org)
                        <option value="{{ $org->id }}" @selected((string)request('org_id') === (string)$org->id)>{{ $org->name }}</option>
                    @endforeach
                </select>
            </div>
            @endif
            <button class="btn" type="submit">Применить</button>
            <a class="btn ghost" href="{{ route('admin.complaints.index') }}">Сбросить</a>
        </form>
    </div>
</div>

{{-- Table --}}
<div class="card">
    <div class="card-head">
        <h2 class="card-title">Жалобы</h2>
        <div class="card-sub">{{ $complaints->total() }} {{ trans_choice('запись|записи|записей', $complaints->total()) }}</div>
    </div>

    @if($complaints->isEmpty())
        <div class="card-pad" style="text-align:center;padding:48px 24px;color:var(--muted);">
            <div style="font-size:36px;margin-bottom:12px;">✓</div>
            <div style="font-weight:700;font-size:16px;">Жалоб нет</div>
            <div style="font-size:13px;margin-top:4px;">Ни одной жалобы не поступало</div>
        </div>
    @else
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Жалоба</th>
                    <th>Тип</th>
                    <th>Организация</th>
                    <th>От кого</th>
                    <th>Статус</th>
                    <th>Дата</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($complaints as $complaint)
                @php
                    $statusPill = match($complaint->status) {
                        'pending'   => 'pill-amber',
                        'in_review' => 'pill-blue',
                        'resolved'  => 'pill-green',
                        'rejected'  => 'pill-red',
                        default     => 'pill-gray',
                    };
                @endphp
                <tr>
                    <td>
                        <div style="font-weight:600;max-width:280px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                            {{ $complaint->title }}
                        </div>
                        @if($complaint->ticket)
                            <div style="font-size:12px;color:var(--muted);">Заявка №{{ $complaint->ticket_id }}</div>
                        @endif
                    </td>
                    <td>{{ $types[$complaint->type] ?? $complaint->type }}</td>
                    <td>{{ $complaint->organization->name ?? '—' }}</td>
                    <td>
                        <div style="font-size:13px;">{{ $complaint->createdBy->name ?? '—' }}</div>
                        <div style="font-size:11px;color:var(--muted);">{{ $complaint->createdBy->email ?? '' }}</div>
                    </td>
                    <td>
                        <span class="pill {{ $statusPill }} no-dot">{{ $statuses[$complaint->status] ?? $complaint->status }}</span>
                    </td>
                    <td class="num" style="white-space:nowrap;">{{ optional($complaint->created_at)->format('d.m.Y') }}</td>
                    <td>
                        <a href="{{ route('admin.complaints.show', $complaint) }}" class="btn ghost sm">Открыть</a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @if($complaints->hasPages())
    <div class="card-foot">{{ $complaints->links() }}</div>
    @endif
    @endif
</div>
@endsection
