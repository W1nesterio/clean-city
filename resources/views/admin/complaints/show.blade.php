@extends('admin.layout')
@section('title', 'Жалоба #' . $complaint->id)

@section('content')
<div class="page-head">
    <div class="page-head-left">
        <div class="crumb">Главная › <a href="{{ route('admin.complaints.index') }}">Жалобы</a> › #{{ $complaint->id }}</div>
        <h1 class="h-page">{{ $complaint->title }}</h1>
        <p class="page-sub">{{ $types[$complaint->type] ?? $complaint->type }} · {{ $complaint->organization->name ?? '—' }}</p>
    </div>
    <div class="page-actions">
        <a href="{{ route('admin.complaints.index') }}" class="btn ghost">← Назад</a>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success" style="margin-bottom:16px;padding:12px 16px;background:var(--primary-soft);border:1px solid var(--primary-soft-2);border-radius:var(--r);color:var(--primary-ink);font-weight:600;">
    {{ session('success') }}
</div>
@endif

<div style="display:grid;grid-template-columns:minmax(0,1fr) 320px;gap:18px;align-items:start;">

    {{-- Main --}}
    <div style="display:flex;flex-direction:column;gap:18px;">

        {{-- Content --}}
        <div class="card">
            <div class="card-head"><h2 class="card-title">Содержание жалобы</h2></div>
            <div class="card-pad">
                @if($complaint->description)
                    <p style="margin:0;line-height:1.65;color:var(--text-soft);">{{ $complaint->description }}</p>
                @else
                    <p style="margin:0;color:var(--muted);">Описание не указано</p>
                @endif

                @if($complaint->ticket)
                <div style="margin-top:14px;padding:12px 14px;background:var(--surface-soft);border-radius:var(--r);border:1px solid var(--line);">
                    <div style="font-size:12px;color:var(--muted);font-weight:600;margin-bottom:4px;">Связанная заявка</div>
                    <a href="{{ route('admin.tickets.show', $complaint->ticket) }}" style="font-weight:700;color:var(--primary);">
                        №{{ $complaint->ticket_id }} · {{ $complaint->ticket->category->name ?? 'Без категории' }}
                    </a>
                </div>
                @endif

                @if($complaint->targetUser)
                <div style="margin-top:14px;padding:12px 14px;background:var(--surface-soft);border-radius:var(--r);border:1px solid var(--line);">
                    <div style="font-size:12px;color:var(--muted);font-weight:600;margin-bottom:4px;">Жалоба на сотрудника</div>
                    <div style="font-weight:700;">{{ $complaint->targetUser->name }}</div>
                    <div style="font-size:12px;color:var(--muted);">{{ $complaint->targetUser->email }}</div>
                </div>
                @endif
            </div>
        </div>

        {{-- Resolution form --}}
        @if(!in_array($complaint->status, ['resolved', 'closed']))
        <div class="card">
            <div class="card-head"><h2 class="card-title">Вынести решение</h2></div>
            <div class="card-pad">
                <form method="POST" action="{{ route('admin.complaints.resolve', $complaint) }}" style="display:flex;flex-direction:column;gap:12px;">
                    @csrf
                    <div class="field">
                        <div class="field-label">Новый статус</div>
                        <select name="status" required>
                            <option value="in_review" @selected($complaint->status === 'in_review')>Рассматривается</option>
                            <option value="resolved">Решено</option>
                            <option value="rejected">Отклонено</option>
                            <option value="closed">Закрыто</option>
                        </select>
                    </div>
                    <div class="field">
                        <div class="field-label">Комментарий / решение</div>
                        <textarea name="resolution" rows="4" placeholder="Опишите принятое решение…" style="resize:vertical;">{{ old('resolution', $complaint->resolution) }}</textarea>
                    </div>
                    <div>
                        <button type="submit" class="btn">Сохранить решение</button>
                    </div>
                </form>
            </div>
        </div>
        @elseif($complaint->resolution)
        <div class="card">
            <div class="card-head"><h2 class="card-title">Принятое решение</h2></div>
            <div class="card-pad">
                <p style="margin:0;line-height:1.65;color:var(--text-soft);">{{ $complaint->resolution }}</p>
                @if($complaint->reviewedBy)
                <div style="margin-top:10px;font-size:12px;color:var(--muted);">
                    Рассмотрено: {{ $complaint->reviewedBy->name }}
                    @if($complaint->reviewed_at)· {{ $complaint->reviewed_at->format('d.m.Y H:i') }}@endif
                </div>
                @endif
            </div>
        </div>
        @endif

    </div>

    {{-- Sidebar --}}
    <div style="display:flex;flex-direction:column;gap:18px;">

        <div class="card">
            <div class="card-head"><h2 class="card-title">Информация</h2></div>
            <div class="card-pad" style="display:flex;flex-direction:column;gap:10px;">

                <div>
                    <div style="font-size:11px;color:var(--muted);font-weight:600;text-transform:uppercase;letter-spacing:.05em;margin-bottom:3px;">Статус</div>
                    @php
                        $pill = match($complaint->status) {
                            'pending'   => 'pill-amber',
                            'in_review' => 'pill-blue',
                            'resolved'  => 'pill-green',
                            'rejected'  => 'pill-red',
                            default     => 'pill-gray',
                        };
                    @endphp
                    <span class="pill {{ $pill }} no-dot">{{ $statuses[$complaint->status] ?? $complaint->status }}</span>
                </div>

                <div>
                    <div style="font-size:11px;color:var(--muted);font-weight:600;text-transform:uppercase;letter-spacing:.05em;margin-bottom:3px;">Тип</div>
                    <div>{{ $types[$complaint->type] ?? $complaint->type }}</div>
                </div>

                <div>
                    <div style="font-size:11px;color:var(--muted);font-weight:600;text-transform:uppercase;letter-spacing:.05em;margin-bottom:3px;">Организация</div>
                    <div style="font-weight:600;">{{ $complaint->organization->name ?? '—' }}</div>
                </div>

                <div>
                    <div style="font-size:11px;color:var(--muted);font-weight:600;text-transform:uppercase;letter-spacing:.05em;margin-bottom:3px;">Подал</div>
                    <div style="font-weight:600;">{{ $complaint->createdBy->name ?? '—' }}</div>
                    <div style="font-size:12px;color:var(--muted);">{{ $complaint->createdBy->email ?? '' }}</div>
                </div>

                <div>
                    <div style="font-size:11px;color:var(--muted);font-weight:600;text-transform:uppercase;letter-spacing:.05em;margin-bottom:3px;">Дата подачи</div>
                    <div>{{ optional($complaint->created_at)->format('d.m.Y H:i') }}</div>
                </div>

            </div>
        </div>

    </div>
</div>
@endsection
