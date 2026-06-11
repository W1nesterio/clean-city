@extends('admin.layout')

@section('title', 'Коды регистрации дворников')
@section('topbar-title', 'Коды регистрации')
@section('topbar-subtitle', 'Безопасная регистрация сотрудников ЖКХ по одноразовым кодам')

@section('content')
    <div class="page-head">
        <div>
            <h1 class="page-title">Регистрационные коды дворников</h1>
            <p class="page-description">
                Обычные жители регистрируются сами. Сотрудник ЖКХ может создать аккаунт исполнителя только по коду,
                который выпустил администратор и привязал к конкретной организации.
            </p>
        </div>
        <a class="btn btn-light" href="{{ route('admin.tickets.index') }}">К обращениям</a>
    </div>

    <div class="grid-two">
        <div class="card">
            <div class="card-body">
                <h2 class="card-title">Создать новый код</h2>
                <div class="card-muted" style="margin-bottom:18px;">
                    Код можно оставить пустым — система сгенерирует безопасный вариант формата JKH-XXXX-XXXX.
                </div>

                <form method="POST" action="{{ route('admin.worker-codes.store') }}">
                    @csrf

                    <div class="field">
                        <label>ЖКХ / организация</label>
                        <select name="organization_id" required>
                            @foreach($organizations as $organization)
                                <option value="{{ $organization->id }}" @selected(old('organization_id') == $organization->id)>
                                    {{ $organization->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="field" style="margin-top:14px;">
                        <label>Код вручную, необязательно</label>
                        <input name="code" value="{{ old('code') }}" placeholder="Например: JKH-TEST-0001">
                    </div>

                    <div class="info-grid" style="margin-top:14px;">
                        <div class="field">
                            <label>Количество использований</label>
                            <input type="number" name="max_uses" min="1" max="100" value="{{ old('max_uses', 1) }}" required>
                        </div>
                        <div class="field">
                            <label>Срок действия, дней</label>
                            <input type="number" name="expires_days" min="1" max="365" value="{{ old('expires_days', 30) }}" placeholder="30">
                        </div>
                    </div>

                    <div class="form-actions">
                        <button class="btn btn-primary" type="submit">Создать код</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <h2 class="card-title">Как это работает</h2>
                <div class="info-grid" style="margin-top:16px; grid-template-columns:1fr;">
                    <div class="info-item">
                        <div class="info-label">1. Админ</div>
                        <div class="info-value">Создаёт код и передаёт его сотруднику нужного ЖКХ.</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">2. Дворник</div>
                        <div class="info-value">В приложении выбирает регистрацию сотрудника ЖКХ и вводит код.</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">3. Система</div>
                        <div class="info-value">Создаёт пользователя с ролью worker и привязывает к организации из кода.</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card" style="margin-top:22px;">
        <div class="card-body">
            <h2 class="card-title">Список кодов</h2>
            <div class="card-muted" style="margin-bottom:16px;">Использованные одноразовые коды автоматически отключаются.</div>

            <div style="overflow-x:auto;">
                <table>
                    <thead>
                    <tr>
                        <th>Код</th>
                        <th>Организация</th>
                        <th>Статус</th>
                        <th>Использования</th>
                        <th>Срок</th>
                        <th>Создан</th>
                        <th>Использовал</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($codes as $code)
                        @php
                            $available = $code->isAvailable();
                        @endphp
                        <tr>
                            <td>
                                <div style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
                                    <strong style="font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;">{{ $code->code }}</strong>
                                    <button class="btn btn-light btn-sm" type="button" onclick="navigator.clipboard?.writeText('{{ $code->code }}')">Копировать</button>
                                </div>
                            </td>
                            <td>{{ $code->organization->name ?? '—' }}</td>
                            <td>
                                @if($available)
                                    <span class="status-pill status-completed">Активен</span>
                                @elseif(!$code->active)
                                    <span class="status-pill status-rejected">Отключён</span>
                                @elseif($code->expires_at && $code->expires_at->isPast())
                                    <span class="status-pill status-rejected">Просрочен</span>
                                @else
                                    <span class="status-pill status-duplicate">Использован</span>
                                @endif
                            </td>
                            <td>{{ $code->used_count }} / {{ $code->max_uses }}</td>
                            <td>{{ $code->expires_at ? $code->expires_at->format('d.m.Y H:i') : 'Без срока' }}</td>
                            <td>
                                <div>{{ $code->created_at ? $code->created_at->format('d.m.Y H:i') : '—' }}</div>
                                <div class="ticket-meta">{{ $code->createdBy->name ?? '—' }}</div>
                            </td>
                            <td>{{ $code->usedBy->name ?? '—' }}</td>
                            <td class="table-actions">
                                @if($code->active)
                                    <form method="POST" action="{{ route('admin.worker-codes.deactivate', $code) }}">
                                        @csrf
                                        <button class="btn btn-light btn-sm" type="submit">Отключить</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8">
                                <div class="empty-state">Кодов пока нет. Создайте первый код для регистрации сотрудника.</div>
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <div class="pagination-wrap">
                {{ $codes->links() }}
            </div>
        </div>
    </div>
@endsection
