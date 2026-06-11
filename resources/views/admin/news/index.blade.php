@extends('admin.layout')
@section('title', 'Новости')

@section('content')
<div class="page-head">
    <div class="page-head-left">
        <div class="crumb">Главная › Новости</div>
        <h1 class="h-page">Новости</h1>
        <p class="page-sub">Статьи, публикуемые в мобильном приложении</p>
    </div>
    <div class="page-actions">
        <a href="{{ route('admin.news.create') }}" class="btn">+ Добавить новость</a>
    </div>
</div>

<div class="table-card">
    <div class="card-head">
        <div>
            <h2 class="card-title">Все новости</h2>
            <div class="card-sub">{{ $news->total() }} {{ trans_choice('запись|записи|записей', $news->total()) }}</div>
        </div>
    </div>
    @if($news->isEmpty())
        <div class="empty">
            <div class="empty-title">Новостей пока нет</div>
            <div class="empty-sub">Создайте первую новость для мобильного приложения</div>
            <a href="{{ route('admin.news.create') }}" class="btn">Создать новость</a>
        </div>
    @else
    <div class="table-wrap">
        <table class="tbl">
            <thead>
                <tr>
                    <th>Заголовок</th>
                    <th>Дата публикации</th>
                    <th>Фото</th>
                    <th>Статус</th>
                    <th>Автор</th>
                    <th class="row-actions">Действия</th>
                </tr>
            </thead>
            <tbody>
                @foreach($news as $item)
                <tr>
                    <td>
                        <div style="font-weight:600;max-width:340px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $item->title }}</div>
                        @if($item->body)
                        <div class="secondary">{{ Str::limit(strip_tags($item->body), 80) }}</div>
                        @endif
                    </td>
                    <td class="num" style="white-space:nowrap;">{{ $item->published_date->format('d.m.Y') }}</td>
                    <td>
                        @if($item->photos->count())
                            <span class="pill pill-green no-dot">{{ $item->photos->count() }} фото</span>
                        @else
                            <span class="txt-muted">—</span>
                        @endif
                    </td>
                    <td>
                        @if($item->active)
                            <span class="pill pill-green">Опубликована</span>
                        @else
                            <span class="pill pill-gray">Скрыта</span>
                        @endif
                    </td>
                    <td class="txt-muted">{{ $item->author?->name ?? '—' }}</td>
                    <td class="row-actions">
                        <a href="{{ route('admin.news.edit', $item) }}" class="btn ghost sm">Редактировать</a>
                        <form method="POST" action="{{ route('admin.news.destroy', $item) }}" style="display:inline-flex;margin-left:4px;" onsubmit="return confirm('Удалить новость «{{ addslashes($item->title) }}»?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn danger-ghost sm">Удалить</button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @if($news->hasPages())
    <div class="card-foot">{{ $news->links() }}</div>
    @endif
    @endif
</div>
@endsection
