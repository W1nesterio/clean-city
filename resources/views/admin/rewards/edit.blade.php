@extends('admin.layout')
@section('title', 'Купон — редактировать')

@section('content')
<div class="page-head">
    <div class="page-head-left">
        <div class="crumb">Главная › <a href="{{ route('admin.rewards.index') }}">Вознаграждения</a> › Редактировать</div>
        <h1 class="h-page">{{ Str::limit($reward->title, 55) }}</h1>
    </div>
    <div class="page-actions">
        <a href="{{ route('admin.rewards.index') }}" class="btn ghost">← Назад</a>
        <form method="POST" action="{{ route('admin.rewards.destroy', $reward) }}" onsubmit="return confirm('Удалить это вознаграждение?')">
            @csrf @method('DELETE')
            <button type="submit" class="btn danger-ghost sm">Удалить</button>
        </form>
    </div>
</div>

<form method="POST" action="{{ route('admin.rewards.update', $reward) }}" enctype="multipart/form-data">
@csrf @method('PUT')
<div class="grid-main-side" style="grid-template-columns:minmax(0,1fr) 360px;">

    {{-- Main column --}}
    <div>
        <div class="card">
            <div class="card-head"><h2 class="card-title">Основное</h2></div>
            <div class="card-body" style="display:flex;flex-direction:column;gap:16px;">
                <div class="field">
                    <label for="title">Название купона *</label>
                    <input id="title" name="title" type="text" value="{{ old('title', $reward->title) }}" required>
                </div>
                <div class="field">
                    <label for="description">Описание</label>
                    <textarea id="description" name="description" rows="4">{{ old('description', $reward->description) }}</textarea>
                </div>
            </div>
        </div>

        {{-- Photo --}}
        <div class="card" style="margin-top:18px;">
            <div class="card-head"><h2 class="card-title">Фотография</h2></div>
            <div class="card-body" style="display:flex;flex-direction:column;gap:14px;">
                @if($reward->photo_path)
                <div>
                    <div class="info-label" style="margin-bottom:8px;">Текущее фото</div>
                    <img id="currentPhoto" src="{{ url(Storage::url($reward->photo_path)) }}" style="max-width:260px;border-radius:12px;border:1px solid var(--line);display:block;">
                    <label style="display:flex;align-items:center;gap:8px;margin-top:10px;cursor:pointer;font-size:13px;font-weight:700;color:var(--danger);">
                        <input type="checkbox" name="delete_photo" value="1" id="deletePhoto" style="width:15px;height:15px;">
                        Удалить текущее фото
                    </label>
                </div>
                @endif
                <label id="photoDropzone" style="display:flex;align-items:center;gap:12px;padding:16px 18px;border:2px dashed var(--line);border-radius:var(--r);cursor:pointer;transition:border-color .12s;" onmouseover="this.style.borderColor='var(--primary)'" onmouseout="if(!window._rewardHasPhoto)this.style.borderColor='var(--line)'">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" style="color:var(--muted);flex-shrink:0;"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                    <div>
                        <div style="font-size:13px;font-weight:600;color:var(--text);" id="photoLabel">{{ $reward->photo_path ? 'Нажмите для замены фотографии' : 'Нажмите для выбора фотографии' }}</div>
                        <div style="font-size:12px;color:var(--muted);margin-top:2px;">JPG / PNG · до 5 МБ</div>
                    </div>
                    <input id="photo" name="photo" type="file" accept="image/*" style="display:none;" onchange="handleRewardPhoto(this)">
                </label>
                <div id="photoPreviewWrap" style="display:none;margin-top:4px;position:relative;width:fit-content;">
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
                    <input id="points_required" name="points_required" type="number" min="0" value="{{ old('points_required', $reward->points_required) }}" required>
                </div>

                <div class="field">
                    <label for="code">Промокод <span class="muted">(необязательно)</span></label>
                    <input id="code" name="code" type="text" value="{{ old('code', $reward->code) }}" placeholder="CITY-2026" style="font-family:monospace;letter-spacing:.06em;">
                </div>

                <div class="field">
                    <label for="valid_from">Действует с</label>
                    <input id="valid_from" name="valid_from" type="date" value="{{ old('valid_from', $reward->valid_from?->format('Y-m-d')) }}">
                </div>

                <div class="field">
                    <label for="valid_to">Действует до</label>
                    <input id="valid_to" name="valid_to" type="date" value="{{ old('valid_to', $reward->valid_to?->format('Y-m-d')) }}">
                </div>

                @if($admin->role === 'org_admin')
                <div class="field">
                    <label>Организация</label>
                    <div class="key-chip">{{ $admin->organization->name ?? '—' }}</div>
                </div>
                @else
                <div class="field">
                    <label>Область купона</label>
                    <div class="key-chip">Платформа</div>
                </div>
                @endif

                <div style="display:flex;align-items:center;gap:12px;">
                    <input id="active" name="active" type="checkbox" value="1" {{ old('active', $reward->active) ? 'checked' : '' }} style="width:18px;height:18px;flex-shrink:0;cursor:pointer;">
                    <label for="active" style="margin:0;font-size:14px;font-weight:700;color:var(--text);cursor:pointer;">Активно (доступно пользователям)</label>
                </div>

                <div style="padding-top:6px;border-top:1px solid var(--line);">
                    <div class="info-label" style="margin-bottom:4px;">Текущий статус</div>
                    @if($reward->active && $reward->isValid())
                        <span class="status-pill status-completed">Активно и действительно</span>
                    @elseif($reward->active)
                        <span class="status-pill status-duplicate">Активно, но истекло</span>
                    @else
                        <span class="status-pill" style="color:#64748b;background:#f1f5f9;border-color:#e2e8f0;">Отключено</span>
                    @endif
                </div>

                <div class="info-item">
                    <div class="info-label">Создано</div>
                    <div class="info-value">{{ $reward->created_at->format('d.m.Y H:i') }}</div>
                </div>
            </div>
        </div>

        <div class="form-actions" style="margin-top:16px;">
            <button type="submit" class="btn btn-primary" style="flex:1;justify-content:center;">Сохранить</button>
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
    document.getElementById('photoLabel').textContent = '{{ $reward->photo_path ? "Нажмите для замены фотографии" : "Нажмите для выбора фотографии" }}';
    document.getElementById('photoDropzone').style.borderColor = 'var(--line)';
    document.getElementById('photoPreviewWrap').style.display = 'none';
}

const dropzone = document.getElementById('photoDropzone');
dropzone.addEventListener('dragover', e => { e.preventDefault(); dropzone.style.borderColor = 'var(--primary)'; });
dropzone.addEventListener('dragleave', () => { if (!window._rewardHasPhoto) dropzone.style.borderColor = 'var(--line)'; });
dropzone.addEventListener('drop', e => {
    e.preventDefault();
    const file = e.dataTransfer.files[0];
    if (file && file.type.startsWith('image/')) {
        const dt = new DataTransfer(); dt.items.add(file);
        document.getElementById('photo').files = dt.files;
        handleRewardPhoto(document.getElementById('photo'));
    }
});

@if($reward->photo_path)
document.getElementById('deletePhoto')?.addEventListener('change', function () {
    const cur = document.getElementById('currentPhoto');
    if (cur) cur.style.opacity = this.checked ? '0.3' : '1';
});
@endif
</script>
@endpush
@endsection
