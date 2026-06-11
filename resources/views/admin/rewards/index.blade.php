@extends('admin.layout')
@section('title', 'Вознаграждения')

@section('content')
<div class="page-head">
    <div class="page-head-left">
        <div class="crumb">Главная › Вознаграждения</div>
        <h1 class="h-page">Вознаграждения</h1>
        <p class="page-sub">Купоны и призы, доступные пользователям за баллы</p>
    </div>
    <div class="page-actions">
        <a href="{{ route('admin.rewards.create') }}" class="btn">+ Добавить купон</a>
    </div>
</div>

<div class="table-card">
    <div class="card-head">
        <div>
            <h2 class="card-title">Все вознаграждения</h2>
            <div class="card-sub">{{ $rewards->total() }} {{ trans_choice('запись|записи|записей', $rewards->total()) }}</div>
        </div>
    </div>
    @if($rewards->isEmpty())
        <div class="empty">
            <div class="empty-title">Вознаграждений пока нет</div>
            <div class="empty-sub">Создайте первый купон или приз для мобильного приложения</div>
            <a href="{{ route('admin.rewards.create') }}" class="btn">Создать вознаграждение</a>
        </div>
    @else
    <div class="table-wrap">
        <table class="tbl">
            <thead>
                <tr>
                    <th style="width:60px;">Фото</th>
                    <th>Название</th>
                    <th>Баллы</th>
                    <th>Код</th>
                    <th>Организация</th>
                    <th>Период действия</th>
                    <th>Статус</th>
                    <th class="row-actions">Действия</th>
                </tr>
            </thead>
            <tbody>
                @foreach($rewards as $reward)
                <tr>
                    <td>
                        @if($reward->photo_path)
                            <img src="{{ url(Storage::url($reward->photo_path)) }}" style="width:48px;height:40px;object-fit:cover;border-radius:8px;border:1px solid var(--line);display:block;">
                        @else
                            <div style="width:48px;height:40px;border-radius:8px;border:1px solid var(--line);background:var(--surface-soft);display:grid;place-items:center;color:var(--muted);font-size:18px;">🎁</div>
                        @endif
                    </td>
                    <td>
                        <div style="font-weight:600;">{{ $reward->title }}</div>
                        @if($reward->description)
                        <div class="secondary">{{ Str::limit($reward->description, 70) }}</div>
                        @endif
                    </td>
                    <td>
                        <span style="font-weight:700;color:var(--primary);" class="num">{{ $reward->points_required }}</span>
                        <span class="txt-muted" style="font-size:12px;"> балл.</span>
                    </td>
                    <td>
                        @if($reward->code)
                            <span class="key-chip" style="font-size:12px;">{{ $reward->code }}</span>
                        @else
                            <span class="txt-muted">—</span>
                        @endif
                    </td>
                    <td>
                        @if($reward->organization)
                            <span style="font-size:12px;">{{ $reward->organization->name }}</span>
                        @else
                            <span class="pill pill-green no-dot" style="font-size:11px;">🌐 Платформа</span>
                        @endif
                    </td>
                    <td class="num" style="font-size:13px;white-space:nowrap;">
                        @if($reward->valid_from || $reward->valid_to)
                            {{ $reward->valid_from?->format('d.m.Y') ?? '∞' }} — {{ $reward->valid_to?->format('d.m.Y') ?? '∞' }}
                        @else
                            <span class="txt-muted">Без ограничений</span>
                        @endif
                    </td>
                    <td>
                        @if($reward->active)
                            @if($reward->isValid())
                                <span class="pill pill-green">Активно</span>
                            @else
                                <span class="pill pill-gray">Истекло</span>
                            @endif
                        @else
                            <span class="pill pill-gray">Отключено</span>
                        @endif
                    </td>
                    <td class="row-actions">
                        <a href="{{ route('admin.rewards.edit', $reward) }}" class="btn ghost sm">Редактировать</a>
                        <form method="POST" action="{{ route('admin.rewards.destroy', $reward) }}" style="display:inline-flex;margin-left:4px;" onsubmit="return confirm('Удалить вознаграждение «{{ addslashes($reward->title) }}»?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn danger-ghost sm">Удалить</button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @if($rewards->hasPages())
    <div class="card-foot">{{ $rewards->links() }}</div>
    @endif
    @endif
</div>
@endsection
