@extends('layouts.app')
@section('content')
<div class="page-heading"><div><h1>Dashboard Persuratan</h1><p>Ringkasan aktivitas surat dan tindak lanjut institusi.</p><div class="breadcrumb"><a href="{{ route('dashboard') }}">Beranda</a> &nbsp;/&nbsp; Dashboard</div></div><a class="btn" href="{{ route('letters.create') }}">＋&nbsp; Buat surat baru</a></div>
<div class="grid">
    <div class="card metric-card"><span class="metric-icon blue"><ion-icon name="documents-outline"></ion-icon></span><div><div class="metric-label">Total surat</div><div class="metric">{{ $counts['total'] }}</div></div></div>
    <div class="card metric-card"><span class="metric-icon orange"><ion-icon name="time-outline"></ion-icon></span><div><div class="metric-label">Menunggu tindakan</div><div class="metric">{{ $counts['waiting'] }}</div></div></div>
    <div class="card metric-card"><span class="metric-icon red"><ion-icon name="eye-off-outline"></ion-icon></span><div><div class="metric-label">Belum dibuka</div><div class="metric">{{ $counts['unread'] }}</div></div></div>
    <div class="card metric-card"><span class="metric-icon green"><ion-icon name="checkmark-done-outline"></ion-icon></span><div><div class="metric-label">Disposisi belum dibalas</div><div class="metric">{{ $pendingDispositions }}</div></div></div>
</div>
<div class="card content-card">
    <div class="card-header"><h2>Surat terbaru</h2><a href="{{ route('letters.index') }}">Lihat semua →</a></div>
    <div class="table-wrap"><table><thead><tr><th>Judul surat</th><th>Jenis</th><th>Status</th><th>Dibuat</th></tr></thead><tbody>
    @forelse($letters as $letter)<tr><td><a class="{{ $letter->opened_at ? '' : 'unread' }}" href="{{ route('letters.show',$letter) }}">{{ $letter->title }}</a><br><small>{{ $letter->number ?? 'Tanpa nomor' }}</small></td><td>{{ ucfirst($letter->type) }}</td><td><span class="badge {{ in_array($letter->status,['completed','closed']) ? 'badge-green' : ($letter->status === 'waiting_reply' ? 'badge-orange' : 'badge-blue') }}">{{ str_replace('_',' ',ucfirst($letter->status)) }}</span></td><td>{{ $letter->created_at->format('d M Y H:i') }}</td></tr>@empty<tr><td colspan="4">Belum ada surat.</td></tr>@endforelse
    </tbody></table></div>
</div>
@endsection
