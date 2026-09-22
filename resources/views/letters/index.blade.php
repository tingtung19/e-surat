@extends('layouts.app')
@section('content')
<div style="display:flex;justify-content:space-between;align-items:center"><h1>Daftar Surat</h1><a class="btn" href="{{ route('letters.create') }}">Buat surat</a></div>
<div class="card"><table><thead><tr><th>Surat</th><th>Jenis</th><th>Status</th><th>Pengirim</th><th>Waktu</th></tr></thead><tbody>
@forelse($letters as $letter)<tr><td><a href="{{ route('letters.show',$letter) }}" style="{{ $letter->opened_at ? '' : 'font-weight:700' }}">{{ $letter->title }}</a><br><small class="muted">{{ $letter->number ?? 'Tanpa nomor' }}</small></td><td>{{ ucfirst($letter->type) }}</td><td><span class="badge">{{ str_replace('_',' ',ucfirst($letter->status)) }}</span></td><td>{{ $letter->senderDivision?->division_name ?? $letter->senderDivision?->name }}</td><td>{{ $letter->created_at->format('d/m/Y H:i') }}</td></tr>@empty<tr><td colspan="5">Belum ada data.</td></tr>@endforelse
</tbody></table>{{ $letters->links() }}</div>
@endsection
