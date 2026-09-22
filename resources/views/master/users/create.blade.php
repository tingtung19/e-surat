@extends('layouts.app')
@section('content')
<div class="page-heading"><div><h1>Tambah User</h1><p>Buat akun baru untuk divisi atau pengelola sistem.</p></div></div>
<div class="card form-card"><div class="card-header"><h2><ion-icon name="person-add-outline"></ion-icon> Informasi akun</h2></div><div class="card-body"><form method="post" action="{{ route('master.users.store') }}">@csrf @include('master.users.form')<div class="form-actions"><a class="btn secondary" href="{{ route('master.users.index') }}">Batal</a><button class="btn">Simpan user</button></div></form></div></div>
@endsection
