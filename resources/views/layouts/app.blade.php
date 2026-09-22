<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'E-Surat' }}</title>
    <script type="module" src="https://unpkg.com/ionicons@8.0.13/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@8.0.13/dist/ionicons/ionicons.js"></script>
    <style>
        :root { --blue:#3f6ad8; --navy:#1f2937; --ink:#495057; --muted:#6c757d; --line:#dee2e6; --canvas:#f1f4f6; --green:#3ac47d; --orange:#f7b924; --red:#d92550; --purple:#794c8a }
        * { box-sizing:border-box }
        body { margin:0; color:var(--ink); background:var(--canvas); font:14px -apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Arial,sans-serif }
        a { color:var(--blue); text-decoration:none } button,input,select,textarea { font:inherit }
        .app-shell { min-height:100vh; display:flex }
        .app-sidebar { position:fixed; z-index:20; inset:0 auto 0 0; width:280px; color:#495057; background:#fff; box-shadow:3px 0 12px #00000012 }
        .brand { height:68px; display:flex; align-items:center; gap:11px; padding:0 25px; background:#fff; color:#495057; font-size:19px; font-weight:700; letter-spacing:.3px }
        .brand-mark { display:grid; place-items:center; width:30px; height:30px; border-radius:7px; color:#fff; background:var(--blue); font-size:15px }
        .sidebar-caption { padding:26px 25px 9px; color:#adb5bd; font-size:10px; font-weight:700; letter-spacing:1px; text-transform:uppercase }
        .sidebar-nav { padding:0 10px; margin:0; list-style:none }
        .sidebar-nav a { display:flex; align-items:center; gap:12px; padding:12px 15px; border-radius:4px; color:#6c757d; font-size:13px }
        .sidebar-nav a:hover,.sidebar-nav a.active { color:#3f6ad8; background:#e9ecef }
        .sidebar-icon { display:inline-flex; align-items:center; justify-content:center; width:20px; color:#adb5bd; font-size:17px }
        .sidebar-icon ion-icon { width:18px; height:18px }
        .sidebar-nav a.active .sidebar-icon { color:#3f6ad8 }
        .app-main { width:calc(100% - 280px); min-width:0; margin-left:280px }
        .app-header { height:68px; display:flex; align-items:center; justify-content:space-between; padding:0 28px; background:#fff; border-bottom:1px solid var(--line); box-shadow:0 2px 5px #00000008 }
        .app-header-left { display:flex; align-items:center; gap:25px }
        .search-box { display:flex; align-items:center; width:260px; border:1px solid #e9ecef; border-radius:20px; background:#f8f9fa }
        label.search-box { margin:0; color:inherit; font-weight:400 }
        .search-box input { border:0; outline:0; padding:8px 14px; background:transparent; font-size:13px }
        .search-box span { padding-right:13px; color:#adb5bd }
        .header-toggle { border:0; color:#6c757d; background:transparent; cursor:pointer; font-size:21px }
        .header-actions { display:flex; align-items:center; gap:22px; color:#6c757d }
        .notification { position:relative; display:inline-flex; font-size:19px }
        .notification ion-icon,.header-toggle ion-icon { width:21px; height:21px }
        .notification-badge { position:absolute; top:-7px; right:-9px; min-width:16px; padding:2px 4px; border-radius:10px; color:#fff; background:var(--red); font-size:9px; text-align:center }
        .profile { display:flex; align-items:center; gap:9px; color:var(--ink); font-weight:400 }
        .avatar { display:grid; place-items:center; width:34px; height:34px; border-radius:50%; color:#fff; background:var(--blue); font-size:12px }
        .page-content { max-width:1440px; margin:0 auto; padding:28px }
        .page-heading { display:flex; align-items:flex-start; justify-content:space-between; gap:20px; margin-bottom:24px }
        .page-heading h1 { margin:0 0 5px; color:#495057; font-size:23px; font-weight:400 }
        .page-heading p { margin:0; color:var(--muted) }
        .breadcrumb { margin-top:8px; color:#98a0a8; font-size:12px }
        .breadcrumb a { color:var(--blue) }
        .grid { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:20px }
        .card { background:#fff; border:1px solid var(--line); border-radius:4px; box-shadow:0 2px 6px #00000008 }
        .metric-card { display:flex; align-items:center; gap:17px; min-height:112px; padding:19px 21px }
        .metric-icon { display:grid; place-items:center; width:54px; height:54px; border-radius:50%; color:#fff; font-size:21px }
        .metric-icon ion-icon { width:24px; height:24px }
        .metric-icon.blue { background:var(--blue) } .metric-icon.green { background:var(--green) } .metric-icon.orange { background:var(--orange) } .metric-icon.red { background:var(--red) }
        .metric-label { color:var(--muted); font-size:12px; text-transform:uppercase }
        .muted { color:var(--muted) }
        .metric { margin-top:3px; color:#495057; font-size:27px; font-weight:600 }
        .card-header { display:flex; align-items:center; justify-content:space-between; padding:17px 20px; border-bottom:1px solid var(--line) }
        .card-header h2,.card-header h3 { margin:0; color:#495057; font-size:16px; font-weight:500 }
        .card-body { padding:20px }
        .content-card { margin-top:24px }
        .form-card { max-width:980px; margin:0 auto }
        .form-card .card-header { padding:22px 26px }
        .form-card .card-body { padding:24px 26px 26px }
        .form-card .card-header h2 { display:flex; align-items:center; gap:10px; font-size:18px }
        .form-card .card-header h2 ion-icon { color:var(--blue); font-size:21px }
        .form-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:0 22px }
        .form-group { min-width:0; margin-bottom:18px }
        .form-group.full { grid-column:1 / -1 }
        .form-group label { margin:0 0 7px }
        .form-help { display:block; margin-top:6px; color:#98a0a8; font-size:12px; font-weight:400 }
        .form-actions { display:flex; align-items:center; justify-content:flex-end; gap:10px; padding-top:20px; margin-top:4px; border-top:1px solid var(--line) }
        .upload-box { display:flex; align-items:center; gap:14px; padding:16px; border:1px dashed #b8c2cc; border-radius:4px; background:#f8f9fa }
        .upload-box ion-icon { flex:0 0 auto; color:var(--blue); font-size:27px }
        .upload-box input { padding:0; border:0; background:transparent }
        .table-wrap { overflow-x:auto }
        table { width:100%; border-collapse:collapse }
        th,td { padding:14px 20px; text-align:left; border-bottom:1px solid #edf0f2 }
        th { color:#8a929a; font-size:11px; font-weight:600; text-transform:uppercase; letter-spacing:.4px }
        td { color:#5b6168 } tr:last-child td { border-bottom:0 }
        .unread { color:#343a40; font-weight:700 }
        .badge { display:inline-block; border-radius:3px; padding:5px 9px; color:#fff; background:#6c757d; font-size:11px; font-weight:600; text-transform:capitalize }
        .badge-blue { background:#3f6ad8 } .badge-green { background:#3ac47d } .badge-orange { background:#f7b924; color:#523c00 } .badge-red { background:#d92550 }
        .btn { display:inline-flex; align-items:center; gap:7px; border:0; border-radius:3px; padding:10px 15px; color:#fff; background:var(--blue); cursor:pointer; font-weight:600; text-decoration:none }
        .btn:hover { filter:brightness(.94) }.btn.secondary { color:#495057; background:#e9ecef }
        .alert { margin-bottom:18px; padding:13px 16px; border-left:4px solid var(--green); color:#24613f; background:#dff6e9; border-radius:3px }
        label { display:block; margin:15px 0 7px; color:#495057; font-weight:600 } input,textarea,select { width:100%; border:1px solid #ced4da; border-radius:3px; padding:10px 12px; color:#495057; background:#fff } input:focus,textarea:focus,select:focus { outline:0; border-color:#86b7fe; box-shadow:0 0 0 .2rem #3f6ad840 } textarea { min-height:130px; resize:vertical }
        .two { display:grid; grid-template-columns:1fr 1fr; gap:22px }
        .pagination { display:flex; gap:5px; padding:16px 20px; list-style:none }
        .pagination li a,.pagination li span { display:block; padding:6px 10px; border:1px solid var(--line); color:var(--blue) }
        @media (max-width:900px) { .app-sidebar { transform:translateX(-100%); transition:transform .2s } .app-sidebar.open { transform:translateX(0) } .app-main { width:100%; margin-left:0 } .grid { grid-template-columns:repeat(2,minmax(0,1fr)) } }
        @media (max-width:600px) { .page-content { padding:20px 14px } .page-heading { display:block } .page-heading .btn { margin-top:15px } .grid,.two,.form-grid { grid-template-columns:1fr } .form-group.full { grid-column:auto } .form-card .card-header,.form-card .card-body { padding:18px } .form-actions { justify-content:stretch; flex-direction:column-reverse } .form-actions .btn { width:100%; justify-content:center } .app-header { padding:0 16px } .header-actions { gap:12px } .profile span { display:none } }
    </style>
</head>
<body>
<div class="app-shell">
    <aside class="app-sidebar" id="sidebar">
        <a class="brand" href="{{ route('dashboard') }}"><span class="brand-mark"><ion-icon name="mail-outline"></ion-icon></span><span>E-SURAT</span></a>
        <div class="sidebar-caption">Menu utama</div>
        <ul class="sidebar-nav">
            <li><a class="{{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}"><span class="sidebar-icon"><ion-icon name="grid-outline"></ion-icon></span>Dashboard</a></li>
            <li><a class="{{ request()->routeIs('letters.*') && !request()->routeIs('letters.create') ? 'active' : '' }}" href="{{ route('letters.index') }}"><span class="sidebar-icon"><ion-icon name="documents-outline"></ion-icon></span>Semua Surat</a></li>
            <li><a class="{{ request()->routeIs('letters.create') ? 'active' : '' }}" href="{{ route('letters.create') }}"><span class="sidebar-icon"><ion-icon name="add-circle-outline"></ion-icon></span>Buat Surat</a></li>
        </ul>
        <div class="sidebar-caption">Informasi</div>
        <ul class="sidebar-nav">
            <li><a href="#"><span class="sidebar-icon"><ion-icon name="time-outline"></ion-icon></span>Aktivitas &amp; Histori</a></li>
            @if(auth()->user()->isAdmin())
            <li><a class="{{ request()->routeIs('master.users.*') ? 'active' : '' }}" href="{{ route('master.users.index') }}"><span class="sidebar-icon"><ion-icon name="people-outline"></ion-icon></span>Data Users</a></li>
            <li><a class="{{ request()->routeIs('master.categories.*') ? 'active' : '' }}" href="{{ route('master.categories.index') }}"><span class="sidebar-icon"><ion-icon name="albums-outline"></ion-icon></span>Kategori Surat</a></li>
            @endif
            <li><a href="#"><span class="sidebar-icon"><ion-icon name="settings-outline"></ion-icon></span>Pengaturan</a></li>
        </ul>
    </aside>
    <section class="app-main">
        <header class="app-header">
            <div class="app-header-left"><button class="header-toggle" type="button" onclick="document.getElementById('sidebar').classList.toggle('open')" aria-label="Buka menu"><ion-icon name="menu-outline"></ion-icon></button><label class="search-box"><input type="search" placeholder="Type to search"><span><ion-icon name="search-outline"></ion-icon></span></label></div>
            <div class="header-actions">
                    <span class="notification"><ion-icon name="notifications-outline"></ion-icon><span class="notification-badge">{{ $pendingDispositions ?? 0 }}</span></span>
                <span class="profile"><span class="avatar">{{ strtoupper(substr(auth()->user()->name, 0, 2)) }}</span><span>{{ auth()->user()->name }}</span></span>
                <form method="post" action="{{ route('logout') }}">@csrf<button class="header-toggle" type="submit" title="Keluar"><ion-icon name="log-out-outline"></ion-icon></button></form>
            </div>
        </header>
        <main class="page-content">
            @if(session('success'))<div class="alert">{{ session('success') }}</div>@endif
            @if($errors->any())<div class="alert" style="border-color:var(--red);color:#8b1e35;background:#fde4ea">{{ $errors->first() }}</div>@endif
            @yield('content')
        </main>
    </section>
</div>
</body>
</html>
