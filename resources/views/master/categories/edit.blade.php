@extends('layouts.app')
@section('content')
<div class="page-heading"><div><h1>Edit Kategori</h1><p>Perbarui struktur dan format nomor surat.</p></div></div>
<div class="card form-card"><div class="card-header"><h2><ion-icon name="create-outline"></ion-icon> Informasi kategori</h2></div><div class="card-body"><form method="post" action="{{ route('master.categories.update',$category) }}">@csrf @method('PUT') @include('master.categories.form',['category'=>$category])<div class="form-actions"><a class="btn secondary" href="{{ route('master.categories.index') }}">Batal</a><button class="btn">Simpan perubahan</button></div></form></div></div>
@endsection
