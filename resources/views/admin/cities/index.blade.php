@extends('admin.layout')
@section('title', 'Города и населённые пункты')

@section('content')
<div class="page-head">
    <div class="page-head-left">
        <div class="crumb">Главная › Города</div>
        <h1 class="h-page">Города и населённые пункты</h1>
        <p class="page-sub">Привязывают ЖКХ-организации к географии для фильтрации в приложении</p>
    </div>
</div>

<div style="display:grid;grid-template-columns:minmax(0,1fr) 360px;gap:18px;align-items:start;">

    {{-- Cities list --}}
    <div class="table-card">
        <div class="card-head"><h2 class="card-title">Все города ({{ $cities->count() }})</h2></div>
        @if($cities->isEmpty())
        <div class="empty">
            <div class="empty-title">Городов нет</div>
        </div>
        @else
        <div class="table-wrap">
            <table class="tbl">
                <thead>
                    <tr>
                        <th>Название</th>
                        <th>Регион / Область</th>
                        <th>Организаций ЖКХ</th>
                        <th class="row-actions">Действия</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($cities as $city)
                    <tr>
                        <td style="font-weight:600;">{{ $city->name }}</td>
                        <td class="txt-muted">{{ $city->region ?? '—' }}</td>
                        <td class="num">{{ $city->organizations_count }}</td>
                        <td class="row-actions">
                            <form method="POST" action="{{ route('admin.cities.destroy', $city) }}"
                                  onsubmit="return confirm('Удалить город «{{ addslashes($city->name) }}»? Организации не удалятся, но потеряют привязку.')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn danger-ghost sm">Удалить</button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>

    {{-- Add city form --}}
    <div class="card">
        <div class="card-head"><h2 class="card-title">Добавить город</h2></div>
        <div class="card-body">
            @if(session('success'))
                <div class="alert alert-success" style="margin-bottom:14px;padding:10px 14px;background:var(--c-green-soft);border-radius:var(--r);font-size:13px;color:var(--c-green);">
                    {{ session('success') }}
                </div>
            @endif
            <form method="POST" action="{{ route('admin.cities.store') }}" style="display:flex;flex-direction:column;gap:14px;">
                @csrf
                <div class="field">
                    <label for="name">Название *</label>
                    <input id="name" name="name" type="text" placeholder="Барановичи" required>
                    @error('name')<div class="field-error">{{ $message }}</div>@enderror
                </div>
                <div class="field">
                    <label for="region">Регион / Область</label>
                    <input id="region" name="region" type="text" placeholder="Брестская область">
                </div>
                <button type="submit" class="btn" style="width:100%;justify-content:center;">Добавить город</button>
            </form>
        </div>
    </div>
</div>
@endsection
