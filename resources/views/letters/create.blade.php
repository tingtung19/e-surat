@extends('layouts.app')
@section('content')
<div class="page-heading"><div><h1>Buat Surat</h1><p>Lengkapi informasi surat dan lampiran sebelum dikirim.</p><div class="breadcrumb"><a href="{{ route('dashboard') }}">Beranda</a> &nbsp;/&nbsp; <a href="{{ route('letters.index') }}">Surat</a> &nbsp;/&nbsp; Buat Surat</div></div></div>
<div class="card form-card">
    <div class="card-header"><h2><ion-icon name="create-outline"></ion-icon> Informasi Surat</h2><span class="muted">Field bertanda * wajib diisi</span></div>
    <div class="card-body">
        <form method="post" action="{{ route('letters.store') }}" enctype="multipart/form-data">@csrf
            <div class="form-grid">
                <div class="form-group">
                    <label for="type">Jenis surat <span style="color:var(--red)">*</span></label>
                    <select id="type" name="type" required>
                        <option value="official" @selected(old('type', 'official') === 'official')>Resmi ke Direktur</option>
                        <option value="internal" @selected(old('type') === 'internal')>Internal antar divisi</option>
                        @if(auth()->user()->isAdmin())<option value="external" @selected(old('type') === 'external')>Surat masuk eksternal</option>@endif
                    </select>
                    <small class="form-help">Pilih alur pemrosesan surat yang sesuai.</small>
                </div>
                <div class="form-group">
                    <label for="target_division_id">Divisi tujuan</label>
                    <select id="target_division_id" name="target_division_id" data-default-division="{{ $defaultDivision?->id }}">
                        <option value="">Pilih divisi</option>
                        @foreach($divisions as $division)<option value="{{ $division->id }}" @selected(old('target_division_id') == $division->id)>{{ $division->division_name ?? $division->name }}</option>@endforeach
                    </select>
                    <small id="target-help" class="form-help">Untuk surat resmi, tujuan otomatis Divisi Umum dan tidak dapat diubah.</small>
                </div>
                <div class="form-group full">
                    <label for="title">Judul surat <span style="color:var(--red)">*</span></label>
                    <input id="title" name="title" required value="{{ old('title') }}" placeholder="Contoh: Permohonan Persetujuan Kegiatan">
                </div>
                <div class="form-group full">
                    <label for="description">Deskripsi / isi surat <span style="color:var(--red)">*</span></label>
                    <textarea id="description" name="description" required placeholder="Tuliskan isi surat secara lengkap...">{{ old('description') }}</textarea>
                </div>
                @if(auth()->user()->isAdmin())
                <div class="form-group full">
                    <label for="external_sender">Pengirim eksternal</label>
                    <input id="external_sender" name="external_sender" value="{{ old('external_sender') }}" placeholder="Isi untuk surat yang berasal dari institusi luar">
                </div>
                @endif
                <div class="form-group full">
                    <label for="attachments">Lampiran</label>
                    <div class="upload-box"><ion-icon name="cloud-upload-outline"></ion-icon><div style="flex:1"><input id="attachments" type="file" name="attachments[]" multiple accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png"><small class="form-help">PDF, Word, Excel, JPG, JPEG, atau PNG. Maksimal 10 MB per file.</small></div></div>
                </div>
            </div>
            <div class="form-actions"><a class="btn secondary" href="{{ route('letters.index') }}"><ion-icon name="close-outline"></ion-icon> Batal</a><button class="btn secondary" name="save_draft" value="1"><ion-icon name="save-outline"></ion-icon> Simpan draft</button><button class="btn" type="submit"><ion-icon name="send-outline"></ion-icon> Submit surat</button></div>
        </form>
    </div>
</div>
<script>
    (() => {
        const type = document.getElementById('type');
        const target = document.getElementById('target_division_id');
        const defaultDivision = target.dataset.defaultDivision;
        const targetHelp = document.getElementById('target-help');

        function syncTargetField() {
            const isOfficial = type.value === 'official';
            const isInternal = type.value === 'internal';
            let hiddenTarget = document.getElementById('official-target');

            if (isOfficial) {
                target.value = defaultDivision || '';
                target.disabled = true;
                if (!hiddenTarget) {
                    hiddenTarget = document.createElement('input');
                    hiddenTarget.type = 'hidden';
                    hiddenTarget.name = 'target_division_id';
                    hiddenTarget.id = 'official-target';
                    target.form.appendChild(hiddenTarget);
                }
                hiddenTarget.value = defaultDivision || '';
                targetHelp.textContent = 'Otomatis diarahkan ke Divisi Umum dan tidak dapat diubah.';
            } else {
                target.disabled = !isInternal;
                if (hiddenTarget) {
                    hiddenTarget.remove();
                }
                if (isInternal) {
                    targetHelp.textContent = 'Pilih salah satu divisi tujuan. Direktur tidak tersedia dalam daftar.';
                } else {
                    target.value = '';
                    targetHelp.textContent = 'Tidak diperlukan untuk surat eksternal.';
                }
            }
        }

        type.addEventListener('change', syncTargetField);
        syncTargetField();
    })();
</script>
@endsection
