@extends('layouts.app')
@section('content')
<div style="display:flex;justify-content:space-between;align-items:center"><h1>Daftar Surat</h1><a class="btn" href="{{ route('letters.create') }}">Buat surat</a></div>
<div class="card">
    <table id="letters-table" class="display" style="width:100%">
        <thead><tr><th>Surat</th><th>Jenis</th><th>Status</th><th>Pengirim</th><th>Waktu</th></tr></thead>
    </table>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        if (window.DataTable) {
            new DataTable('#letters-table', {
                processing: true,
                serverSide: true,
                ajax: @json(route('letters.index')),
                order: [[4, 'desc']],
                columns: [
                    { data: 'title', render: function (data, type, row) {
                        const className = row.unread ? 'unread-letter' : '';
                        return '<a class="' + className + '" href="' + row.url + '">' + escapeHtml(data) + '</a><br><small class="muted">' + escapeHtml(row.number) + '</small>';
                    }},
                    { data: 'type' },
                    { data: 'status', render: function (data) { return '<span class="badge">' + escapeHtml(data) + '</span>'; }},
                    { data: 'sender' },
                    { data: 'created_at' }
                ]
            });
        }
    });
    function escapeHtml(value) {
        return String(value).replace(/[&<>"']/g, function (character) {
            return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[character];
        });
    }
</script>
@endsection
