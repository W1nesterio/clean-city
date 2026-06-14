@extends('admin.layout')
@section('title', 'Новость — создать')

@push('head')
<style>
    .news-preview-card { border:1px solid var(--line); border-radius:var(--r-lg); overflow:hidden; background:var(--surface); }
    .news-preview-slider { position:relative; background:var(--surface-strong); height:180px; overflow:hidden; }
    .news-preview-slider img { width:100%; height:180px; object-fit:cover; display:block; }
    .news-preview-slider-placeholder { height:180px; display:grid; place-items:center; color:var(--muted); font-size:13px; background:var(--surface-strong); }
    .news-preview-body { padding:14px 16px; }
    .news-preview-title { font-weight:700; font-size:16px; line-height:1.35; color:var(--text); }
    .news-preview-date { font-size:12px; color:var(--muted); margin-top:4px; }
    .news-preview-text { font-size:13px; color:var(--text-soft); line-height:1.6; margin-top:8px; max-height:80px; overflow:hidden; }
    .photo-thumb-list { display:flex; flex-wrap:wrap; gap:8px; margin-top:10px; }
    .photo-thumb { position:relative; }
    .photo-thumb img { width:80px; height:66px; object-fit:cover; border-radius:8px; border:2px solid var(--line); display:block; }
    .photo-thumb-del { position:absolute; top:-6px; right:-6px; width:18px; height:18px; border-radius:50%; background:var(--c-red); color:#fff; border:0; display:grid; place-items:center; font-size:11px; cursor:pointer; }
</style>
@endpush

@section('content')
<div class="page-head">
    <div class="page-head-left">
        <div class="crumb">Главная › <a href="{{ route('admin.news.index') }}">Новости</a> › Создать</div>
        <h1 class="h-page">Новая новость</h1>
        @if($admin->role === 'org_admin')
            <p class="page-sub">Новость будет привязана к вашей организации</p>
        @else
            <p class="page-sub">Главный админ создает только общую новость платформы, видимую всем пользователям.</p>
        @endif
    </div>
    <div class="page-actions">
        <a href="{{ route('admin.news.index') }}" class="btn ghost">← Назад</a>
    </div>
</div>

<form method="POST" action="{{ route('admin.news.store') }}" enctype="multipart/form-data" id="newsForm">
@csrf
<div style="display:grid;grid-template-columns:minmax(0,1fr) 320px;gap:18px;align-items:start;">

    {{-- Main column --}}
    <div style="display:flex;flex-direction:column;gap:18px;">
        <div class="card">
            <div class="card-head"><h2 class="card-title">Содержимое</h2></div>
            <div class="card-body" style="display:flex;flex-direction:column;gap:14px;">
                <div class="field">
                    <div class="field-label">Заголовок *</div>
                    <input id="title" name="title" type="text" value="{{ old('title') }}" placeholder="Например: Городской субботник в эти выходные" required oninput="updatePreview()">
                </div>
                <div class="field">
                    <div class="field-label">Текст новости</div>
                    <textarea id="body" name="body" rows="7" placeholder="Подробное описание события…" style="min-height:160px;" oninput="updatePreview()">{{ old('body') }}</textarea>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-head">
                <div>
                    <h2 class="card-title">Фотографии для слайдера</h2>
                    <div class="card-sub">До 10 фото · max 5 МБ каждый · отображаются в приложении горизонтальным слайдером</div>
                </div>
            </div>
            <div class="card-body" style="display:flex;flex-direction:column;gap:12px;">
                <label style="display:flex;align-items:center;gap:10px;padding:12px 14px;border:2px dashed var(--line);border-radius:var(--r);cursor:pointer;transition:border-color .12s;" onmouseover="this.style.borderColor='var(--primary)'" onmouseout="this.style.borderColor='var(--line)'">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" style="color:var(--muted);flex-shrink:0;"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                    <span style="font-size:13px;color:var(--muted);">Нажмите для выбора фото</span>
                    <input id="photos" name="photos[]" type="file" accept="image/*" multiple style="display:none;" onchange="handlePhotos(this)">
                </label>
                <div class="photo-thumb-list" id="thumbList"></div>
            </div>
        </div>
    </div>

    {{-- Side column --}}
    <div style="display:flex;flex-direction:column;gap:18px;">

        {{-- Live preview --}}
        <div class="card">
            <div class="card-head"><h2 class="card-title">Предпросмотр</h2><span class="pill no-dot pill-green" style="font-size:10px;">мобильный</span></div>
            <div class="card-body" style="padding:12px;">
                <div class="news-preview-card">
                    <div class="news-preview-slider" id="previewSlider">
                        <div class="news-preview-slider-placeholder" id="previewPlaceholder">Фото слайдера</div>
                    </div>
                    <div class="news-preview-body">
                        <div class="news-preview-date" id="previewDate">{{ now()->format('d.m.Y') }}</div>
                        <div class="news-preview-title" id="previewTitle">Заголовок новости</div>
                        <div class="news-preview-text" id="previewText">Текст новости будет отображаться здесь…</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-head"><h2 class="card-title">Параметры</h2></div>
            <div class="card-body" style="display:flex;flex-direction:column;gap:14px;">
                <div class="field">
                    <div class="field-label">Дата публикации *</div>
                    <input id="published_date" name="published_date" type="date" value="{{ old('published_date', date('Y-m-d')) }}" required oninput="document.getElementById('previewDate').textContent=this.value.split('-').reverse().join('.')">
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
                    <input id="active" name="active" type="checkbox" value="1" {{ old('active', '1') ? 'checked' : '' }} style="width:16px;height:16px;flex-shrink:0;accent-color:var(--primary);">
                    <span style="font-size:13px;font-weight:600;">Опубликована (видна в приложении)</span>
                </label>
            </div>
        </div>

        <div style="display:flex;gap:8px;">
            <button type="submit" class="btn" style="flex:1;">Создать новость</button>
            <a href="{{ route('admin.news.index') }}" class="btn ghost">Отмена</a>
        </div>
    </div>
</div>
</form>

@push('scripts')
<script>
let selectedFiles = [];

function handlePhotos(input) {
    Array.from(input.files).forEach(f => selectedFiles.push(f));
    renderThumbs();
    renderPreviewSlider();
}

function renderThumbs() {
    const list = document.getElementById('thumbList');
    list.innerHTML = '';
    selectedFiles.forEach((f, i) => {
        const wrap = document.createElement('div');
        wrap.className = 'photo-thumb';
        const reader = new FileReader();
        reader.onload = e => {
            wrap.innerHTML = `<img src="${e.target.result}"><button type="button" class="photo-thumb-del" onclick="removePhoto(${i})">×</button>`;
        };
        reader.readAsDataURL(f);
        list.appendChild(wrap);
    });
    syncFileInput();
}

function renderPreviewSlider() {
    const slider = document.getElementById('previewSlider');
    const placeholder = document.getElementById('previewPlaceholder');
    if (selectedFiles.length === 0) {
        slider.innerHTML = '<div class="news-preview-slider-placeholder" id="previewPlaceholder">Фото слайдера</div>';
        return;
    }
    const reader = new FileReader();
    reader.onload = e => {
        slider.innerHTML = `<img src="${e.target.result}" style="width:100%;height:180px;object-fit:cover;display:block;">`;
    };
    reader.readAsDataURL(selectedFiles[0]);
}

function removePhoto(idx) {
    selectedFiles.splice(idx, 1);
    renderThumbs();
    renderPreviewSlider();
}

function syncFileInput() {
    const dt = new DataTransfer();
    selectedFiles.forEach(f => dt.items.add(f));
    document.getElementById('photos').files = dt.files;
}

function updatePreview() {
    const t = document.getElementById('title').value || 'Заголовок новости';
    const b = document.getElementById('body').value || 'Текст новости будет отображаться здесь…';
    document.getElementById('previewTitle').textContent = t;
    document.getElementById('previewText').textContent = b;
}
</script>
@endpush
@endsection
