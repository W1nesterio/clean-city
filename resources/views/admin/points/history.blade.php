@extends('admin.layout')
@section('title', 'История баллов')

@section('content')
<div class="page-head">
    <div class="page-head-left">
        <div class="crumb">Главная › <a href="{{ route('admin.points.index') }}">Баллы</a> › {{ $user->name }}</div>
        <h1 class="h-page">История баллов</h1>
        <p class="page-sub">{{ $user->name }} — {{ $user->email }}</p>
    </div>
    <div class="page-actions">
        <a href="{{ route('admin.points.index') }}" class="btn ghost">← Назад</a>
        <div style="text-align:right;">
            <div class="txt-muted" style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.08em;margin-bottom:2px;">Баланс</div>
            <div style="font-size:28px;font-weight:700;color:var(--primary);letter-spacing:-.03em;" class="num">{{ $user->points_balance }} <span style="font-size:14px;color:var(--muted);">балл.</span></div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-head">
        <h2 class="card-title">Транзакции</h2>
        <span class="muted">{{ $transactions->total() }} {{ trans_choice('запись|записи|записей', $transactions->total()) }}</span>
    </div>

    @if($transactions->isEmpty())
        <div class="empty-state">История транзакций пуста.</div>
    @else
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Дата</th>
                    <th>Изменение</th>
                    <th>Баланс после</th>
                    <th>Причина</th>
                    <th>Администратор</th>
                </tr>
            </thead>
            <tbody>
                @foreach($transactions as $tx)
                <tr>
                    <td style="white-space:nowrap;color:var(--muted);font-size:13px;">{{ $tx->created_at->format('d.m.Y H:i') }}</td>
                    <td>
                        @if($tx->amount > 0)
                            <span style="color:#166534;font-weight:950;font-size:16px;">+{{ $tx->amount }}</span>
                        @elseif($tx->amount < 0)
                            <span style="color:#991b1b;font-weight:950;font-size:16px;">{{ $tx->amount }}</span>
                        @else
                            <span style="color:var(--muted);font-weight:850;">= {{ $tx->balance_after }}</span>
                        @endif
                    </td>
                    <td style="font-weight:850;">{{ $tx->balance_after }}</td>
                    <td style="color:var(--muted);font-size:13px;">{{ $tx->reason ?: '—' }}</td>
                    <td style="font-size:13px;">
                        @if($tx->admin)
                            <div style="font-weight:700;">{{ $tx->admin->name }}</div>
                            <div class="muted" style="font-size:12px;">{{ $tx->admin->email }}</div>
                        @else
                            <span class="muted">—</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @if($transactions->hasPages())
    <div class="pagination-wrap">{{ $transactions->links() }}</div>
    @endif
    @endif
</div>
@endsection
