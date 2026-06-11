@extends('admin.layout')

@section('title', 'Сотрудники')
@section('topbar-title', 'Сотрудники')
@section('topbar-subtitle', 'Исполнители и ключи доступа')

@section('content')
    @php($selectedOrganization = $organizations->firstWhere('id', $selectedOrganizationId))

    <div class="metric-grid">
        <div class="metric-card"><div class="metric-label">Сотрудников</div><div class="metric-value">{{ $totalWorkers }}</div><div class="metric-hint">в вашем ЖКХ</div></div>
        <div class="metric-card"><div class="metric-label">Активные задачи</div><div class="metric-value">{{ $activeTasks }}</div><div class="metric-hint">в работе у исполнителей</div></div>
        <div class="metric-card"><div class="metric-label">Доступные ключи</div><div class="metric-value">{{ $availableCodes }}</div><div class="metric-hint">можно выдать</div></div>
        <div class="metric-card"><div class="metric-label">Использованные</div><div class="metric-value">{{ $usedCodes }}</div><div class="metric-hint">остаются в истории</div></div>
    </div>

    <div class="grid-main-side">
        <div class="card">
            <div class="card-head">
                <div>
                    <h2 class="card-title">Исполнители</h2>
                    <div class="ticket-meta">{{ $selectedOrganization->name ?? 'ЖКХ не указано' }}</div>
                </div>
                <form class="toolbar" method="GET" action="{{ route('admin.employees.index') }}">
                    <div class="field" style="min-width:260px">
                        <label>Поиск</label>
                        <input name="worker_search" value="{{ request('worker_search') }}" placeholder="Имя или email">
                    </div>
                    <div class="field" style="min-width:160px; flex:0 0 160px">
                        <label>Загрузка</label>
                        <select name="worker_load">
                            <option value="">Все</option>
                            <option value="active" @selected(request('worker_load') === 'active')>С задачами</option>
                            <option value="free" @selected(request('worker_load') === 'free')>Свободные</option>
                        </select>
                    </div>
                    <button class="btn btn-primary" type="submit">Найти</button>
                    <a class="btn btn-light" href="{{ route('admin.employees.index') }}">Сброс</a>
                </form>
            </div>
            <div class="table-responsive">
                <table>
                    <thead>
                    <tr>
                        <th>Сотрудник</th>
                        <th>Активные</th>
                        <th>Назначено</th>
                        <th>Выполнено</th>
                        <th>Создан</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($workers as $worker)
                        <tr>
                            <td>
                                <strong>{{ $worker->name }}</strong>
                                <div class="ticket-meta">{{ $worker->email }}</div>
                            </td>
                            <td><span class="status-pill {{ $worker->active_tickets_count > 0 ? 'status-in_progress' : 'status-duplicate' }}">{{ $worker->active_tickets_count }}</span></td>
                            <td>{{ $worker->assigned_tickets_count }}</td>
                            <td>{{ $worker->completed_tickets_count }}</td>
                            <td>{{ $worker->created_at ? $worker->created_at->format('d.m.Y') : '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5"><div class="empty-state">Сотрудников не найдено</div></td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <div class="pagination-wrap">{{ $workers->links() }}</div>
        </div>

        <div class="card">
            <div class="card-head">
                <div>
                    <h2 class="card-title">Новый ключ</h2>
                    <div class="ticket-meta">Один ключ — один сотрудник</div>
                </div>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.employees.codes.store') }}">
                    @csrf
                    <input type="hidden" name="organization_id" value="{{ $selectedOrganizationId }}">
                    <div class="field">
                        <label>Кому выдан</label>
                        <input name="issued_to" value="{{ old('issued_to') }}" maxlength="120" placeholder="ФИО или бригада">
                    </div>
                    <div class="form-actions">
                        <button class="btn btn-primary" type="submit">Сгенерировать ключ</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="card" style="margin-top:18px">
        <div class="card-head">
            <div>
                <h2 class="card-title">Ключи доступа</h2>
                <div class="ticket-meta">Поиск, статус и история выдачи</div>
            </div>
            <form class="toolbar" method="GET" action="{{ route('admin.employees.index') }}">
                <div class="field" style="min-width:260px">
                    <label>Поиск</label>
                    <input name="code_search" value="{{ request('code_search') }}" placeholder="Ключ, кому выдан, кто использовал">
                </div>
                <div class="field" style="min-width:160px; flex:0 0 160px">
                    <label>Статус</label>
                    <select name="code_status">
                        <option value="">Все</option>
                        <option value="available" @selected(request('code_status') === 'available')>Доступные</option>
                        <option value="used" @selected(request('code_status') === 'used')>Использованные</option>
                        <option value="revoked" @selected(request('code_status') === 'revoked')>Отключённые</option>
                    </select>
                </div>
                <button class="btn btn-primary" type="submit">Найти</button>
                <a class="btn btn-light" href="{{ route('admin.employees.index') }}">Сброс</a>
            </form>
        </div>
        <div class="table-responsive">
            <table>
                <thead>
                <tr>
                    <th>Ключ</th>
                    <th>Выдан</th>
                    <th>Статус</th>
                    <th>Использовал</th>
                    <th>Дата</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @forelse($codes as $code)
                    @php($available = $code->isAvailable())
                    <tr>
                        <td>
                            <span class="key-chip">{{ $code->code }}</span>
                            <button class="copy-link" type="button" onclick="copyWorkerCode('{{ $code->code }}', this)">Копировать</button>
                        </td>
                        <td>{{ $code->issued_to ?: '—' }}</td>
                        <td>
                            @if($available)
                                <span class="status-pill status-completed">Доступен</span>
                            @elseif(!$code->active)
                                <span class="status-pill status-rejected">Отключён</span>
                            @else
                                <span class="status-pill status-duplicate">Использован</span>
                            @endif
                        </td>
                        <td>{{ $code->usedBy->name ?? '—' }}</td>
                        <td>
                            @if($code->used_at)
                                {{ $code->used_at->format('d.m.Y H:i') }}
                            @elseif($code->revoked_at)
                                {{ $code->revoked_at->format('d.m.Y H:i') }}
                            @else
                                {{ $code->created_at ? $code->created_at->format('d.m.Y H:i') : '—' }}
                            @endif
                        </td>
                        <td class="table-actions">
                            @if($available)
                                <form method="POST" action="{{ route('admin.employees.codes.deactivate', $code) }}">
                                    @csrf
                                    <button class="btn btn-light btn-sm" type="submit">Отключить</button>
                                </form>
                            @endif
                            @if((int)$code->used_count === 0 && !$code->used_by_user_id)
                                <form method="POST" action="{{ route('admin.employees.codes.delete', $code) }}" onsubmit="return confirm('Удалить ключ?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-danger btn-sm" type="submit">Удалить</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6"><div class="empty-state">Ключей не найдено</div></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="pagination-wrap">{{ $codes->links() }}</div>
    </div>
@endsection

@push('scripts')
<script>
function copyWorkerCode(code, button) {
    if (navigator.clipboard) navigator.clipboard.writeText(code);
    else {
        const input = document.createElement('input');
        input.value = code; document.body.appendChild(input); input.select(); document.execCommand('copy'); input.remove();
    }
    const old = button.textContent;
    button.textContent = 'Скопировано';
    setTimeout(() => button.textContent = old, 1200);
}
</script>
@endpush
