@extends('layouts.app')
@section('content')
<div class="dashboard-hero"><div><div class="breadcrumb"><a href="{{ route('dashboard') }}">Beranda</a> &nbsp;/&nbsp; Dashboard</div><h1>Selamat datang kembali, {{ auth()->user()->name }}</h1><p>Pantau surat, disposisi, dan tindak lanjut terbaru dari satu tempat.</p></div><a class="btn" href="{{ route('letters.create') }}"><ion-icon name="add-outline"></ion-icon> Buat surat baru</a></div>
<div class="grid dashboard-metrics">
    <div class="card metric-card"><span class="metric-icon blue"><ion-icon name="documents-outline"></ion-icon></span><div><div class="metric-label">Total surat</div><div class="metric">{{ $counts['total'] }}</div><small class="metric-note">Seluruh akses surat</small></div></div>
    <div class="card metric-card"><span class="metric-icon orange"><ion-icon name="time-outline"></ion-icon></span><div><div class="metric-label">Menunggu</div><div class="metric">{{ $counts['waiting'] }}</div><small class="metric-note">Perlu dicheck</small></div></div>
    <div class="card metric-card"><span class="metric-icon red"><ion-icon name="eye-off-outline"></ion-icon></span><div><div class="metric-label">Belum dibaca</div><div class="metric">{{ $counts['unread'] }}</div><small class="metric-note">Perlu perhatian</small></div></div>
    <div class="card metric-card"><span class="metric-icon green"><ion-icon name="checkmark-done-outline"></ion-icon></span><div><div class="metric-label">Disposisi saya</div><div class="metric">{{ $pendingDispositions }}</div><small class="metric-note">Belum dibalas</small></div></div>
</div>
<div class="card content-card dashboard-table-card">
    <div class="card-header"><div><h2>Surat terbaru yang belum dibaca</h2><p class="card-subtitle">Menampilkan maksimal 10 surat yang membutuhkan perhatian Anda.</p></div><a href="{{ route('letters.index') }}">Lihat semua <ion-icon name="arrow-forward-outline"></ion-icon></a></div>
    <div class="table-wrap"><table><thead><tr><th>Judul surat</th><th>Status</th><th>Pengirim</th><th>Dibuat</th></tr></thead><tbody>
    @forelse($letters as $letter)<tr><td><a class="unread" href="{{ route('letters.show',$letter) }}">{{ $letter->title }}</a><br><small>{{ $letter->number ?? 'Tanpa nomor' }}</small></td><td><span class="badge {{ in_array($letter->status,['completed','closed']) ? 'badge-green' : ($letter->status === 'waiting_reply' ? 'badge-orange' : 'badge-blue') }}">{{ str_replace('_',' ',ucfirst($letter->status)) }}</span></td><td>{{ $letter->senderDivision?->division_name ?? $letter->senderDivision?->name ?? '-' }}</td><td>{{ $letter->created_at->format('d M Y H:i') }}</td></tr>@empty<tr><td colspan="4"><div class="empty-state"><ion-icon name="checkmark-circle-outline"></ion-icon><span>Semua surat sudah dibaca.</span></div></td></tr>@endforelse
    </tbody></table></div>
</div>
@endsection
