@extends('layouts.app')
@section('content')
<div class="detail-heading">
    <div class="detail-title">
        <div class="breadcrumb"><a href="{{ route('dashboard') }}">Beranda</a> &nbsp;/&nbsp; <a href="{{ route('letters.index') }}">Surat</a> &nbsp;/&nbsp; Detail</div>
        <h1>{{ $letter->title }}</h1>
        <div class="detail-subtitle">
            <span>{{ ucfirst($letter->type) }}</span>
            <span>•</span>
            <span>{{ str_replace('_', ' ', ucfirst($letter->status)) }}</span>
            @if($letter->opened_at)<span>•</span><span>Dibuka {{ $letter->opened_at->format('d M Y H:i') }}</span>@endif
        </div>
    </div>
    <div class="detail-actions">
        <span class="badge badge-blue">{{ $letter->number ?? 'Belum bernomor' }}</span>
        @if($letter->created_by === auth()->id() && ! $letter->opened_at && ! in_array($letter->status, ['cancelled', 'completed', 'closed']))
            <form method="post" action="{{ route('letters.cancel', $letter) }}">@csrf<button class="btn secondary" type="submit"><ion-icon name="close-circle-outline"></ion-icon> Batalkan</button></form>
        @endif
    </div>
</div>

<div class="detail-layout">
    <div class="detail-stack">
        <section class="card detail-card">
            <div class="card-header"><h2><ion-icon name="document-text-outline"></ion-icon> Isi surat</h2></div>
            <div class="card-body">
                <p class="letter-body">{{ $letter->description }}</p>
                <div class="meta-list">
                    <div><span class="meta-label">Dibuat oleh</span><span class="meta-value">{{ $letter->creator->name }}</span></div>
                    <div><span class="meta-label">Waktu dibuat</span><span class="meta-value">{{ $letter->created_at->format('d M Y H:i') }}</span></div>
                    <div><span class="meta-label">Asal divisi</span><span class="meta-value">{{ $letter->senderDivision?->division_name ?? $letter->senderDivision?->name ?? '-' }}</span></div>
                    <div><span class="meta-label">Kategori</span><span class="meta-value">{{ $letter->category?->name ?? 'Belum ditentukan' }}</span></div>
                </div>
                @if($letter->attachments->isNotEmpty())
                    <div class="attachment-list">
                        <span class="meta-label">Lampiran ({{ $letter->attachments->count() }})</span>
                        @foreach($letter->attachments as $attachment)
                            <a class="attachment-item" href="{{ asset('storage/'.$attachment->file_path) }}" target="_blank"><ion-icon name="attach-outline"></ion-icon><span>{{ $attachment->file_name }}</span></a>
                        @endforeach
                    </div>
                @endif
            </div>
        </section>

        @if(auth()->user()->isAdmin() && in_array($letter->status, ['waiting_verification', 'draft']))
            <section class="card detail-card verify-card">
                <div class="card-header"><h2><ion-icon name="shield-checkmark-outline"></ion-icon> Verifikasi administrasi</h2></div>
                <div class="card-body">
                    <form method="post" action="{{ route('letters.verify', $letter) }}">@csrf
                        <div class="form-grid">
                            <div class="form-group"><label for="number">Nomor surat</label><input id="number" name="number" required placeholder="Contoh: 001/UM/IX/2026"></div>
                            <div class="form-group"><label for="category_id">Kategori</label><select id="category_id" name="category_id" required>@foreach(\App\Models\LetterCategory::with('children')->whereNull('parent_id')->get() as $category)<option value="{{ $category->id }}">{{ $category->name }}</option>@foreach($category->children as $child)<option value="{{ $child->id }}">— {{ $child->name }}</option>@endforeach @endforeach</select></div>
                        </div>
                        <button class="btn" type="submit"><ion-icon name="checkmark-outline"></ion-icon> Verifikasi &amp; teruskan</button>
                    </form>
                </div>
            </section>
        @endif

        <section class="card detail-card">
            <div class="card-header"><h2><ion-icon name="time-outline"></ion-icon> Timeline audit</h2></div>
            <div class="card-body">
                @if($letter->histories->isNotEmpty())
                    <ol class="timeline">@foreach($letter->histories as $history)<li class="timeline-item"><div class="timeline-action">{{ ucfirst($history->action) }} · {{ $history->user->name }}</div><div class="timeline-description">{{ $history->occurred_at->format('d M Y H:i') }} — {{ $history->description }}</div></li>@endforeach</ol>
                @else <p class="muted">Belum ada histori.</p>@endif
            </div>
        </section>
    </div>

    <div class="detail-stack">
        <section class="card detail-card">
            <div class="card-header"><h2><ion-icon name="chatbubbles-outline"></ion-icon> Komentar</h2><span class="muted">{{ $letter->comments->count() }}</span></div>
            <div class="card-body">
                @forelse($letter->comments as $comment)
                    <div class="comment-item"><div class="item-head"><span class="item-author">{{ $comment->user->name }}</span><span class="item-date">{{ $comment->created_at->format('d M Y H:i') }}</span></div><p class="item-text">{{ $comment->body }}</p></div>
                @empty <p class="muted">Belum ada komentar.</p>@endforelse
                <form class="compact-form" method="post" action="{{ route('letters.comments.store', $letter) }}">@csrf<label for="body">Tulis balasan</label><textarea id="body" name="body" placeholder="Tulis komentar atau tanggapan..." required></textarea><button class="btn" style="margin-top:10px" type="submit"><ion-icon name="send-outline"></ion-icon> Kirim komentar</button></form>
            </div>
        </section>

        <section class="card detail-card">
            <div class="card-header"><h2><ion-icon name="git-network-outline"></ion-icon> Disposisi &amp; tembusan</h2></div>
            <div class="card-body">
                @forelse($letter->dispositions as $disposition)
                    <div class="disposition-item"><div class="item-head"><span class="item-author">{{ $disposition->type === 'cc' ? 'Tembusan: ' : '' }}{{ $disposition->recipient->division_name ?? $disposition->recipient->name }}</span><span class="badge {{ $disposition->type === 'cc' ? 'badge-blue' : ($disposition->is_replied ? 'badge-green' : 'badge-orange') }}">{{ $disposition->type === 'cc' ? 'Informasi' : ($disposition->is_replied ? 'Dibalas' : 'Belum dibalas') }}</span></div><p class="item-text">{{ $disposition->note }}</p>
                    @if($disposition->to_user_id === auth()->id() && $disposition->type === 'disposition' && ! $disposition->is_replied)<form class="compact-form" method="post" enctype="multipart/form-data" action="{{ route('letters.dispositions.reply', [$letter, $disposition]) }}">@csrf<label>Jawaban disposisi</label><textarea name="body" placeholder="Tulis jawaban disposisi..." required></textarea><input type="file" name="attachments[]" multiple style="margin-top:10px"><button class="btn" style="margin-top:10px" type="submit"><ion-icon name="send-outline"></ion-icon> Kirim jawaban</button></form>@endif</div>
                @empty <p class="muted">Belum ada disposisi atau tembusan.</p>@endforelse
                @if(auth()->user()->isDirector())
                    <form class="compact-form" method="post" action="{{ route('letters.dispositions.store', $letter) }}">@csrf<label for="to_user_id">Kirim disposisi</label><select id="to_user_id" name="to_user_id" required><option value="">Pilih divisi tujuan</option>@foreach($divisions as $division)<option value="{{ $division->id }}">{{ $division->division_name ?? $division->name }}</option>@endforeach</select><textarea name="note" placeholder="Instruksi disposisi..." required style="margin-top:10px"></textarea><button class="btn" style="margin-top:10px" type="submit"><ion-icon name="arrow-redo-outline"></ion-icon> Disposisikan</button></form>
                    <form class="compact-form" method="post" action="{{ route('letters.cc.store', $letter) }}">@csrf<label for="cc_user_id">Tambah tembusan</label><select id="cc_user_id" name="to_user_id" required><option value="">Pilih penerima tembusan</option>@foreach($divisions as $division)<option value="{{ $division->id }}">{{ $division->division_name ?? $division->name }}</option>@endforeach</select><input name="note" placeholder="Catatan informasi (opsional)" style="margin-top:10px"><button class="btn secondary" style="margin-top:10px" type="submit"><ion-icon name="copy-outline"></ion-icon> Tambah tembusan</button></form>
                @endif
                @if((auth()->user()->isDirector() || auth()->user()->isAdmin()) && $letter->status !== 'closed')<form class="compact-form" method="post" action="{{ route('letters.close', $letter) }}">@csrf<button class="btn secondary" type="submit"><ion-icon name="archive-outline"></ion-icon> Tutup surat</button></form>@endif
            </div>
        </section>
    </div>
</div>
@endsection
