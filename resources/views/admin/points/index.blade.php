@extends('admin.layout')
@section('title', 'Баллы пользователей')

@section('content')
<div class="page-head">
    <div class="page-head-left">
        <div class="crumb">Главная › Баллы</div>
        <h1 class="h-page">Управление баллами</h1>
        <p class="page-sub">Начисляйте, списывайте и устанавливайте баллы жителей</p>
    </div>
</div>

{{-- Search --}}
<form method="GET" class="toolbar" style="margin-bottom:18px;">
    <div class="field" style="max-width:360px;">
        <label>Поиск</label>
        <input name="search" type="text" value="{{ request('search') }}" placeholder="Имя или e-mail пользователя…">
    </div>
    <div style="display:flex;gap:8px;align-self:flex-end;">
        <button type="submit" class="btn btn-primary">Найти</button>
        @if(request('search'))
        <a href="{{ route('admin.points.index') }}" class="btn btn-light">Сбросить</a>
        @endif
    </div>
</form>

<div class="card">
    <div class="card-head">
        <h2 class="card-title">Жители</h2>
        <span class="muted">{{ $users->total() }} {{ trans_choice('пользователь|пользователя|пользователей', $users->total()) }}</span>
    </div>

    @if($users->isEmpty())
        <div class="empty-state">Жители не найдены.</div>
    @else
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Пользователь</th>
                    <th>Баллы</th>
                    <th style="min-width:400px;">Изменить баллы</th>
                    <th class="table-actions">История</th>
                </tr>
            </thead>
            <tbody>
                @foreach($users as $user)
                <tr id="row-{{ $user->id }}">
                    <td>
                        <div style="display:flex;align-items:center;gap:10px;">
                            <div class="avatar" style="width:36px;height:36px;font-size:14px;">{{ mb_substr($user->name, 0, 1) }}</div>
                            <div>
                                <div style="font-weight:850;">{{ $user->name }}</div>
                                <div class="muted" style="font-size:12px;">{{ $user->email }}</div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <span id="balance-{{ $user->id }}" style="font-size:22px;font-weight:950;color:{{ $user->points_balance > 0 ? 'var(--primary)' : 'var(--muted)' }};">{{ $user->points_balance }}</span>
                        <span class="muted" style="font-size:13px;"> балл.</span>
                    </td>
                    <td>
                        <form method="POST" action="{{ route('admin.points.adjust', $user) }}" class="adjust-form" style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                            @csrf
                            <select name="action" style="width:130px;height:38px;font-size:13px;">
                                <option value="add">Начислить +</option>
                                <option value="subtract">Списать −</option>
                                <option value="set">Установить =</option>
                            </select>
                            <input name="amount" type="number" min="0" value="0" style="width:80px;height:38px;font-size:15px;font-weight:900;text-align:center;">
                            <input name="reason" type="text" placeholder="Причина (необязательно)" style="flex:1;min-width:160px;height:38px;font-size:13px;">
                            <button type="submit" class="btn btn-sm btn-primary">Применить</button>
                        </form>
                    </td>
                    <td class="table-actions">
                        <a href="{{ route('admin.points.history', $user) }}" class="btn btn-sm btn-light">История</a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @if($users->hasPages())
    <div class="pagination-wrap">{{ $users->links() }}</div>
    @endif
    @endif
</div>

@push('scripts')
<script>
// AJAX point adjustment for instant feedback
document.querySelectorAll('.adjust-form').forEach(form => {
    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        const btn = this.querySelector('button[type=submit]');
        const orig = btn.textContent;
        btn.disabled = true;
        btn.textContent = '…';

        try {
            const fd = new FormData(this);
            const resp = await fetch(this.action, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                body: fd
            });

            if (resp.ok || resp.redirected) {
                // Reload to show updated balance
                window.location.reload();
            } else {
                alert('Ошибка при обновлении баллов');
                btn.disabled = false;
                btn.textContent = orig;
            }
        } catch {
            btn.disabled = false;
            btn.textContent = orig;
        }
    });
});
</script>
@endpush
@endsection
