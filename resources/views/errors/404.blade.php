@extends('admin.layout')
@section('title', '404 — Страница не найдена')

@section('content')
<div style="display:flex;flex-direction:column;align-items:center;justify-content:center;min-height:60vh;text-align:center;padding:48px 24px;">
    <div style="font-size:72px;font-weight:900;color:var(--primary-soft);letter-spacing:-.04em;line-height:1;">404</div>
    <h1 style="margin:16px 0 8px;font-size:28px;font-weight:700;letter-spacing:-.02em;color:var(--text);">Страница не найдена</h1>
    <p style="color:var(--muted);max-width:400px;line-height:1.6;margin:0 0 28px;">
        Запрашиваемая страница не существует или была удалена.
    </p>
    <div style="display:flex;gap:10px;flex-wrap:wrap;justify-content:center;">
        <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('admin.dashboard') }}" class="btn ghost">← Назад</a>
        <a href="{{ route('admin.dashboard') }}" class="btn">На главную</a>
    </div>
</div>
@endsection
