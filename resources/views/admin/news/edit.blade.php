@extends('admin.layout')
@section('title', 'Новость — редактировать')

@push('head')
<style>
    .news-preview-card { border:1px solid var(--line); border-radius:var(--r-lg); overflow:hidden; background:var(--surface); }
    .news-preview-slider { position:relative; background:var(--surface-strong); overflow:hidden; }
    .news-preview-slider img { width:100%;height:180px;object-fit:cover;display:block; }
    .news-preview-slider-placeholder { height:180px; display:grid; place-items:center; color:var(--muted); font-size:13px; }
    .news-preview-body { padding:14px 16px; }
    .news-preview-title { font-weight:700; font-size:15px; line-height:1.35; color:var(--text); }
    .news-preview-date { font-size:12px; color:var(--muted); margin-top:4px; }
    .news-preview-text { font-size:13px; color:var(--text-soft); line-height:1.6; margin-top:8px; max-height:80px; overflow:hidden; }
    .photo-thumb { position:relative; display:inline-block; }
    .photo-thumb img { width:100px;height:80px;object-fit:cover;border-radius:10px;border:2px solid var(--line);display:block; }
    .photo-thumb .del-btn { position:absolute;top:-6px;right:-6px;width:18px;height:18px;border-radius:50%;background:var(--c-red);color:#fff;border:0;display:grid;place-items:center;font-size:11px;cursor:pointer; }
</style>
@endpush

@section('content')
<div class="page-head">
    <div class="page-head-left">
        <div class="crumb">Главная › <a href="{{ route('admin.news.index') }}">Новости</a> › Редактировать</div>
        <h1 class="h-page">{{ Str::limit($news->title, 55) }}</h1>
        @if($news->organization)
            <p class="page-sub">ЖКХ: {{ $news->organization->name }}</p>
        @else
            <p class="page-sub">Видна всем пользователям</p>
        @endif
    </div>
    <div class="page-actions">
        <a href="{{ route('admin.news.index') }}" class="btn ghost">← Назад</a>
        <form method="POST" action="{{ route('admin.news.destroy', $news) }}" onsubmit="return confirm('Удалить эту новость?')">
            @csrf @method('DELETE')
            <button type="submit" class="btn danger-ghost sm">Удалить</button>
        </form>
    </div>
</div>

<form method="POST" action="{{ route('admin.news.update', $news) }}" enctype="multipart/form-data" id="newsForm">
@csrf @method('PUT')
<div style="display:grid;grid-template-columns:minmax(0,1fr) 320px;gap:18px;align-items:start;">

    {{-- Main column --}}
    <div style="display:flex;flex-direction:column;gap:18px;">
        <div class="card">
            <div class="card-head"><h2 class="card-title">Содержимое</h2></div>
            <div class="card-body" style="display:flex;flex-direction:column;gap:14px;">
                <div class="field">
                    <div class="field-label">Заголовок *</div>
                    <input id="title" name="title" type="text" value="{{ old('title', $news->title) }}" required oninput="updatePreview()">
                </div>
                <div class="field">
                    <div class="field-label">Текст новости</div>
                    <textarea id="body" name="body" rows="7" style="min-height:160px;" oninput="updatePreview()">{{ old('body', $news->body) }}</textarea>
                </div>
            </div>
        </div>

        {{-- Existing slider photos --}}
        <div class="card">
            <div class="card-head">
                <div>
                    <h2 class="card-title">Фотографии слайдера</h2>
                    <div class="card-sub">{{ $news->photos->count() }} фото · порядок по загрузке</div>
                </div>
            </div>
            <div class="card-body" style="display:flex;flex-direction:column;gap:14px;">

                @if($news->photos->isNotEmpty())
                <div style="display:flex;flex-wrap:wrap;gap:12px;">
                    @foreach($news->photos as $photo)
                    <div style="position:relative;">
                        <img src="{{ url(Storage::url($photo->path)) }}" style="width:110px;height:88px;object-fit:cover;border-radius:10px;border:2px solid var(--line);display:block;">
                        <label style="display:flex;align-items:center;gap:5px;margin-top:5px;font-size:12px;color:var(--c-red);cursor:pointer;font-weight:600;">
                            <input type="checkbox" name="delete_photos[]" value="{{ $photo->id }}" style="width:13px;height:13px;accent-color:var(--c-red);">
                            Удалить
                        </label>
                    </div>
                    @endforeach
                </div>
                @endif

                <label style="display:flex;align-items:center;gap:10px;padding:10px 14px;border:2px dashed var(--line);border-radius:var(--r);cursor:pointer;transition:border-color .12s;" onmouseover="this.style.borderColor='var(--primary)'" onmouseout="this.style.borderColor='var(--line)'">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" style="color:var(--muted);flex-shrink:0;"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                    <span style="font-size:13px;color:var(--muted);">Добавить ещё фото</span>
                    <input id="photos" name="photos[]" type="file" accept="image/*" multiple style="display:none;" onchange="handlePhotos(this)">
                </label>
                <div style="display:flex;flex-wrap:wrap;gap:8px;" id="newPhotoThumbs"></div>
            </div>
        </div>
    </div>

    {{-- Side column --}}
    <div style="display:flex;flex-direction:column;gap:18px;">

        {{-- Preview --}}
        <div class="card">
            <div class="card-head"><h2 class="card-title">Предпросмотр</h2><span class="pill no-dot pill-green" style="font-size:10px;">мобильный</span></div>
            <div class="card-body" style="padding:12px;">
                <div class="news-preview-card">
                    <div class="news-preview-slider" id="previewSlider">
                        @if($news->photos->isNotEmpty())
                            <img src="{{ url(Storage::url($news->photos->first()->path)) }}" id="previewImg">
                        @else
                            <div class="news-preview-slider-placeholder" id="previewPlaceholder">Фото слайдера</div>
                        @endif
                    </div>
                    <div class="news-preview-body">
                        <div class="news-preview-date" id="previewDate">{{ $news->published_date->format('d.m.Y') }}</div>
                        <div class="news-preview-title" id="previewTitle">{{ $news->title }}</div>
                        <div class="news-preview-text" id="previewText">{{ $news->body }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-head"><h2 class="card-title">Параметры</h2></div>
            <div class="card-body" style="display:flex;flex-direction:column;gap:14px;">
                <div class="field">
                    <div class="field-label">Дата публикации *</div>
                    <input id="published_date" name="published_date" type="date" value="{{ old('published_date', $news->published_date->format('Y-m-d')) }}" required oninput="document.getElementById('previewDate').textContent=this.value.split('-').reverse().join('.')">
                </div>

                @if($admin->role === 'org_admin')
                <div class="field">
                    <div class="field-label">Организация</div>
                    <div class="key-chip" style="font-family:var(--font);">{{ $admin->organization->name ?? '—' }}</div>
                </div>
                @else
                <div class="field">
                    <div class="field-label">Область публикации</div>
                    <div class="key-chip" style="font-family:var(--font);">Платформа</div>
                </div>
                @endif

                <label style="display:flex;align-items:center;gap:10px;cursor:pointer;">
                    <input id="active" name="active" type="checkbox" value="1" {{ old('active', $news->active) ? 'checked' : '' }} style="width:16px;height:16px;flex-shrink:0;accent-color:var(--primary);">
                    <span style="font-size:13px;font-weight:600;">Опубликована</span>
                </label>

                <div class="info-item">
                    <div class="info-label">Создана</div>
                    <div class="info-value">{{ $news->created_at->format('d.m.Y H:i') }}</div>
                </div>
                @if($news->author)
                <div class="info-item">
                    <div class="info-label">Автор</div>
                    <div class="info-value">{{ $news->author->name }}</div>
                </div>
                @endif
            </div>
        </div>

        <div style="display:flex;gap:8px;">
            <button type="submit" class="btn" style="flex:1;">Сохранить изменения</button>
            <a href="{{ route('admin.news.index') }}" class="btn ghost">Отмена</a>
        </div>
    </div>
</div>
</form>

@push('scripts')
<script>
let newFiles = [];

function handlePhotos(input) {
    Array.from(input.files).forEach(f => newFiles.push(f));
    renderNewThumbs();
    renderPreviewFromNew();
}

function renderNewThumbs() {
    const wrap = document.getElementById('newPhotoThumbs');
    wrap.innerHTML = '';
    newFiles.forEach((f, i) => {
        const thumb = document.createElement('div');
        thumb.className = 'photo-thumb';
        const reader = new FileReader();
        reader.onload = e => {
            thumb.innerHTML = `<img src="${e.target.result}"><button type="button" class="del-btn" onclick="removeNew(${i})">×</button>`;
        };
        reader.readAsDataURL(f);
        wrap.appendChild(thumb);
    });
    syncInput();
}

function renderPreviewFromNew() {
    if (newFiles.length === 0) return;
    const reader = new FileReader();
    reader.onload = e => {
        const slider = document.getElementById('previewSlider');
        slider.innerHTML = `<img src="${e.target.result}" style="width:100%;height:180px;object-fit:cover;display:block;">`;
    };
    reader.readAsDataURL(newFiles[0]);
}

function removeNew(i) {
    newFiles.splice(i, 1);
    renderNewThumbs();
}

function syncInput() {
    const dt = new DataTransfer();
    newFiles.forEach(f => dt.items.add(f));
    document.getElementById('photos').files = dt.files;
}

function updatePreview() {
    document.getElementById('previewTitle').textContent = document.getElementById('title').value || 'Заголовок';
    document.getElementById('previewText').textContent = document.getElementById('body').value || '';
}
</script>
@endpush
@endsection
