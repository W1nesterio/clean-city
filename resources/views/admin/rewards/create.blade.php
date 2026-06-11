@extends('admin.layout')
@section('title', 'Купон — создать')

@section('content')
<div class="page-head">
    <div class="page-head-left">
        <div class="crumb">Главная › <a href="{{ route('admin.rewards.index') }}">Вознаграждения</a> › Создать</div>
        <h1 class="h-page">Новое вознаграждение</h1>
    </div>
    <div class="page-actions">
        <a href="{{ route('admin.rewards.index') }}" class="btn ghost">← Назад</a>
    </div>
</div>

<form method="POST" action="{{ route('admin.rewards.store') }}" enctype="multipart/form-data">
@csrf
<div class="grid-main-side" style="grid-template-columns:minmax(0,1fr) 360px;">

    {{-- Main column --}}
    <div>
        <div class="card">
            <div class="card-head"><h2 class="card-title">Основное</h2></div>
            <div class="card-body" style="display:flex;flex-direction:column;gap:16px;">
                <div class="field">
                    <label for="title">Название купона *</label>
                    <input id="title" name="title" type="text" value="{{ old('title') }}" placeholder="Например: Скидка 10% в кофейне" required>
                </div>
                <div class="field">
                    <label for="description">Описание</label>
                    <textarea id="description" name="description" rows="4" placeholder="Условия получения и использования…">{{ old('description') }}</textarea>
                </div>
            </div>
        </div>

        <div class="card" style="margin-top:18px;">
            <div class="card-head">
                <h2 class="card-title">Фотография</h2>
                <div class="card-sub">Формат JPG / PNG · до 5 МБ</div>
            </div>
            <div class="card-body">
                <label id="photoDropzone" style="display:flex;align-items:center;gap:12px;padding:16px 18px;border:2px dashed var(--line);border-radius:var(--r);cursor:pointer;transition:border-color .12s;" onmouseover="this.style.borderColor='var(--primary)'" onmouseout="if(!window._rewardHasPhoto)this.style.borderColor='var(--line)'">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" style="color:var(--muted);flex-shrink:0;"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                    <div>
                        <div style="font-size:13px;font-weight:600;color:var(--text);" id="photoLabel">Нажмите для выбора фотографии</div>
                        <div style="font-size:12px;color:var(--muted);margin-top:2px;">или перетащите файл сюда</div>
                    </div>
                    <input id="photo" name="photo" type="file" accept="image/*" style="display:none;" onchange="handleRewardPhoto(this)">
                </label>
                <div id="photoPreviewWrap" style="display:none;margin-top:12px;position:relative;width:fit-content;">
                    <img id="photoPreview" style="max-width:260px;max-height:180px;border-radius:10px;border:1px solid var(--line);display:block;">
                    <button type="button" onclick="clearRewardPhoto()" style="position:absolute;top:-8px;right:-8px;width:22px;height:22px;border-radius:50%;background:var(--c-red);color:#fff;border:0;cursor:pointer;font-size:14px;display:grid;place-items:center;line-height:1;">×</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Side column --}}
    <div>
        <div class="card">
            <div class="card-head"><h2 class="card-title">Параметры</h2></div>
            <div class="card-body" style="display:flex;flex-direction:column;gap:16px;">

                <div class="field">
                    <label for="points_required">Стоимость в баллах *</label>
                    <input id="points_required" name="points_required" type="number" min="0" value="{{ old('points_required', 50) }}" required>
                </div>

                <div class="field">
                    <label for="code">Промокод <span class="muted">(необязательно)</span></label>
                    <input id="code" name="code" type="text" value="{{ old('code') }}" placeholder="CITY-2026" style="font-family:monospace;letter-spacing:.06em;">
                </div>

                <div class="field">
                    <label for="valid_from">Действует с</label>
                    <input id="valid_from" name="valid_from" type="date" value="{{ old('valid_from') }}">
                </div>

                <div class="field">
                    <label for="valid_to">Действует до</label>
                    <input id="valid_to" name="valid_to" type="date" value="{{ old('valid_to') }}">
                </div>

                @if(in_array($admin->role, ['admin','super_admin']))
                <div class="field">
                    <label for="organization_id">Организация (пусто — платформенная)</label>
                    <select name="organization_id" id="organization_id">
                        <option value="">🌐 Для всех</option>
                        @foreach($organizations as $org)
                            <option value="{{ $org->id }}" @selected(old('organization_id') == $org->id)>{{ $org->name }}</option>
                        @endforeach
                    </select>
                </div>
                @else
                <div class="field">
                    <label>Организация</label>
                    <div class="key-chip">{{ $admin->organization->name ?? '—' }}</div>
                </div>
                @endif

                <div style="display:flex;align-items:center;gap:12px;">
                    <input id="active" name="active" type="checkbox" value="1" {{ old('active', '1') ? 'checked' : '' }} style="width:18px;height:18px;flex-shrink:0;cursor:pointer;">
                    <label for="active" style="margin:0;font-size:14px;font-weight:700;color:var(--text);cursor:pointer;">Активно (доступно пользователям)</label>
                </div>

            </div>
        </div>

        <div class="card" style="margin-top:18px;border-left:3px solid var(--primary);">
            <div class="card-body" style="padding:16px;">
                <div class="info-label">Как это работает</div>
                <div style="font-size:13px;line-height:1.6;color:var(--muted);margin-top:6px;">
                    Пользователь накапливает баллы за активность. Когда у него достаточно баллов, он нажимает «Получить» и баллы списываются. Если указан код — он отображается после получения.
                </div>
            </div>
        </div>

        <div class="form-actions" style="margin-top:16px;">
            <button type="submit" class="btn btn-primary" style="flex:1;justify-content:center;">Создать вознаграждение</button>
            <a href="{{ route('admin.rewards.index') }}" class="btn btn-light">Отмена</a>
        </div>
    </div>
</div>
</form>

@push('scripts')
<script>
window._rewardHasPhoto = false;

function handleRewardPhoto(input) {
    const file = input.files[0];
    if (!file) return;
    window._rewardHasPhoto = true;
    document.getElementById('photoLabel').textContent = file.name;
    document.getElementById('photoDropzone').style.borderColor = 'var(--primary)';
    const reader = new FileReader();
    reader.onload = e => {
        document.getElementById('photoPreview').src = e.target.result;
        document.getElementById('photoPreviewWrap').style.display = 'block';
    };
    reader.readAsDataURL(file);
}

function clearRewardPhoto() {
    window._rewardHasPhoto = false;
    document.getElementById('photo').value = '';
    document.getElementById('photoLabel').textContent = 'Нажмите для выбора фотографии';
    document.getElementById('photoDropzone').style.borderColor = 'var(--line)';
    document.getElementById('photoPreviewWrap').style.display = 'none';
    document.getElementById('photoPreview').src = '';
}

// Drag-and-drop support
const dropzone = document.getElementById('photoDropzone');
dropzone.addEventListener('dragover', e => { e.preventDefault(); dropzone.style.borderColor = 'var(--primary)'; });
dropzone.addEventListener('dragleave', () => { if (!window._rewardHasPhoto) dropzone.style.borderColor = 'var(--line)'; });
dropzone.addEventListener('drop', e => {
    e.preventDefault();
    const file = e.dataTransfer.files[0];
    if (file && file.type.startsWith('image/')) {
        const dt = new DataTransfer();
        dt.items.add(file);
        document.getElementById('photo').files = dt.files;
        handleRewardPhoto(document.getElementById('photo'));
    }
});
</script>
@endpush
@endsection
