@extends('layouts.app')
@section('content')
<div class="page-heading"><div><h1>Tambah Kategori</h1><p>Buat kategori induk atau sub-kategori surat.</p></div></div>
<div class="card form-card"><div class="card-header"><h2><ion-icon name="albums-outline"></ion-icon> Informasi kategori</h2></div><div class="card-body"><form method="post" action="{{ route('master.categories.store') }}">@csrf @include('master.categories.form')<div class="form-actions"><a class="btn secondary" href="{{ route('master.categories.index') }}">Batal</a><button class="btn">Simpan kategori</button></div></form></div></div>
@endsection
