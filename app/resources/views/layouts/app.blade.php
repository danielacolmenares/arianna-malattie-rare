<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="Arianna, guida ai percorsi di cura per le malattie rare.">
    <title>{{ $title ?? 'Arianna' }}</title>
    <style>
        :root { --ink:#16332f; --muted:#5f746f; --teal:#087f72; --teal-dark:#075f57; --mint:#e6f4f0; --cream:#f7f5ee; --paper:#fff; --line:#dce7e3; --amber:#a65d00; --amber-bg:#fff4d6; }
        * { box-sizing: border-box; }
        html { scroll-behavior: smooth; }
        body { margin:0; color:var(--ink); background:var(--cream); font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; line-height:1.55; }
        a { color:inherit; }
        .topbar { background:var(--ink); color:#fff; }
        .nav { max-width:1120px; margin:auto; padding:18px 24px; display:flex; align-items:center; justify-content:space-between; gap:24px; }
        .brand { display:flex; align-items:center; gap:12px; text-decoration:none; font-weight:800; letter-spacing:-.02em; font-size:1.16rem; }
        .brand-mark { width:36px; height:36px; display:grid; place-items:center; border-radius:11px; color:var(--ink); background:#8be0cc; font-size:1.15rem; }
        .nav-note { color:#cae0da; font-size:.88rem; }
        main { min-height:calc(100vh - 150px); }
        .container { max-width:1120px; margin:auto; padding:0 24px; }
        .hero { padding:72px 0 54px; background:linear-gradient(135deg, #dff3ed 0%, #f7f5ee 62%, #eef1df 100%); border-bottom:1px solid var(--line); }
        .eyebrow { display:inline-flex; align-items:center; gap:8px; color:var(--teal-dark); font-weight:750; text-transform:uppercase; letter-spacing:.09em; font-size:.75rem; }
        .eyebrow::before { content:""; width:24px; height:3px; border-radius:3px; background:var(--teal); }
        h1 { margin:14px 0 14px; max-width:760px; font-size:clamp(2.2rem, 5vw, 4.3rem); line-height:1.03; letter-spacing:-.055em; }
        .lead { max-width:720px; margin:0; color:var(--muted); font-size:clamp(1rem, 2vw, 1.2rem); }
        .search { display:flex; max-width:820px; margin-top:34px; padding:7px; background:#fff; border:1px solid #c9ddd7; border-radius:16px; box-shadow:0 15px 36px rgba(22,51,47,.10); }
        .search input { min-width:0; flex:1; border:0; outline:0; padding:13px 15px; font:inherit; color:var(--ink); background:transparent; }
        .button { border:0; border-radius:11px; padding:13px 22px; cursor:pointer; color:#fff; background:var(--teal); font:inherit; font-weight:750; text-decoration:none; display:inline-flex; align-items:center; justify-content:center; }
        .button:hover { background:var(--teal-dark); }
        .voice-button { flex:0 0 auto; min-width:76px; border:1px solid #bdd8d1; border-radius:11px; padding:10px 12px; cursor:pointer; color:var(--teal-dark); background:var(--mint); font:inherit; font-weight:750; display:inline-flex; align-items:center; justify-content:center; gap:7px; }
        .voice-button:hover { background:#d6eee7; }
        .voice-button svg { width:20px; height:20px; fill:currentColor; }
        .voice-feedback { min-height:1.5em; margin:9px 4px 0; color:var(--teal-dark); font-size:.9rem; font-weight:650; }
        .voice-button.is-recording { color:#fff; background:#b42318; border-color:#b42318; animation:voice-pulse 1.2s infinite; }
        @keyframes voice-pulse { 50% { box-shadow:0 0 0 5px rgba(180,35,24,.16); } }

        .content { padding:48px 0 72px; }
        .section-head { display:flex; align-items:end; justify-content:space-between; gap:20px; margin-bottom:22px; }
        h2 { margin:0; font-size:clamp(1.5rem, 3vw, 2.1rem); letter-spacing:-.035em; }
        .count { color:var(--muted); font-size:.93rem; }
        .cards { display:grid; grid-template-columns:repeat(2, minmax(0, 1fr)); gap:20px; }
        .card { display:flex; flex-direction:column; min-height:270px; padding:26px; background:var(--paper); border:1px solid var(--line); border-radius:18px; box-shadow:0 8px 22px rgba(22,51,47,.045); }
        .card:hover { border-color:#acd4ca; transform:translateY(-2px); transition:.18s ease; }
        .card-code { color:var(--teal-dark); font-size:.78rem; font-weight:800; letter-spacing:.08em; }
        .card h3 { margin:10px 0 8px; font-size:1.45rem; line-height:1.2; letter-spacing:-.025em; }
        .card p { margin:0; color:var(--muted); }
        .badges { display:flex; flex-wrap:wrap; gap:7px; margin:18px 0 22px; }
        .badge { padding:5px 9px; border-radius:999px; color:var(--teal-dark); background:var(--mint); font-size:.76rem; font-weight:700; }
        .card-link { margin-top:auto; color:var(--teal-dark); font-weight:800; text-decoration:none; }
        .notice { margin-top:26px; padding:15px 18px; border-radius:12px; color:#6f4300; background:var(--amber-bg); border:1px solid #edd696; font-size:.9rem; }
        .empty { padding:42px; text-align:center; background:#fff; border:1px dashed #b7cbc6; border-radius:18px; }
        .empty h3 { margin:0 0 6px; }
        .back { display:inline-flex; gap:8px; margin:32px 0 24px; color:var(--teal-dark); text-decoration:none; font-weight:750; }
        .detail-hero { padding:12px 0 34px; }
        .detail-title { margin:7px 0 12px; font-size:clamp(2rem, 5vw, 3.6rem); }
        .detail-grid { display:grid; grid-template-columns:minmax(0, 1.55fr) minmax(260px, .75fr); gap:22px; padding-bottom:72px; }
        .panel { padding:26px; background:#fff; border:1px solid var(--line); border-radius:18px; }
        .panel + .panel { margin-top:18px; }
        .panel h2 { margin-bottom:18px; font-size:1.25rem; }
        .row { padding:15px 0; border-top:1px solid #edf1ef; }
        .row:first-of-type { padding-top:0; border-top:0; }
        .row strong { display:block; margin-bottom:3px; }
        .muted { color:var(--muted); }
        .source { display:block; padding:13px 0; border-top:1px solid #edf1ef; color:var(--teal-dark); font-weight:700; text-decoration:none; }
        .source:first-of-type { border-top:0; padding-top:0; }
        .status { display:inline-flex; padding:6px 10px; border-radius:999px; background:var(--amber-bg); color:var(--amber); font-size:.78rem; font-weight:800; text-transform:uppercase; letter-spacing:.05em; }
        .qr-figure { margin:0 0 18px; text-align:center; }
        .qr-image { display:block; width:min(100%, 250px); height:auto; margin:0 auto 10px; padding:10px; background:#fff; border:1px solid var(--line); border-radius:12px; }
        .qr-figure figcaption, .qr-note, .copy-status { color:var(--muted); font-size:.82rem; }
        .share-label { display:block; margin-bottom:6px; font-size:.82rem; font-weight:750; }
        .share-row { display:flex; gap:8px; }
        .share-url { min-width:0; width:100%; padding:10px; border:1px solid #c9ddd7; border-radius:9px; color:var(--ink); background:#f9fbfa; }
        .button-secondary { flex:0 0 auto; padding:10px 13px; color:var(--teal-dark); background:var(--mint); border:1px solid #bdd8d1; }
        .button-secondary:hover { color:#fff; }
        .copy-status { min-height:1.3em; margin:6px 0 12px; }
        .qr-download { width:100%; }
        .qr-note { margin:8px 0 0; text-align:center; }
        .nav-actions { display:flex; align-items:center; gap:14px; }
        .nav-user { color:#cae0da; font-size:.86rem; }
        .nav-link, .nav-button { color:#fff; background:transparent; border:1px solid #52716a; border-radius:9px; padding:8px 11px; font:inherit; font-size:.84rem; font-weight:750; text-decoration:none; cursor:pointer; }
        .nav-link:hover, .nav-button:hover { background:#274943; }
        .logout-form { margin:0; }
        .auth-page { min-height:calc(100vh - 150px); display:grid; place-items:center; padding:48px 24px; }
        .auth-card { width:min(100%, 470px); padding:34px; background:#fff; border:1px solid var(--line); border-radius:20px; box-shadow:0 16px 40px rgba(22,51,47,.09); }
        .auth-title, .admin-title { margin:10px 0 10px; font-size:clamp(2rem, 5vw, 3.2rem); }
        .auth-form { display:grid; gap:18px; margin-top:26px; }
        .auth-form label { display:block; margin-bottom:6px; font-weight:750; }
        .auth-form input { width:100%; padding:12px 13px; border:1px solid #bdd2cc; border-radius:10px; color:var(--ink); background:#fff; font:inherit; }
        .auth-form input:focus { outline:3px solid rgba(8,127,114,.18); border-color:var(--teal); }
        .field-error { margin:6px 0 0; color:#a32920; font-size:.85rem; font-weight:650; }
        .auth-note { margin:18px 0 0; color:var(--muted); font-size:.84rem; }
        .auth-back { margin-bottom:0; }
        .admin-page { padding:58px 0 80px; }
        .admin-summary { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:20px; margin-top:32px; }
        .admin-card { padding:26px; background:#fff; border:1px solid var(--line); border-radius:18px; }
        .admin-card h2 { margin:13px 0 8px; font-size:1.35rem; }
        .admin-number { display:block; color:var(--teal-dark); font-size:2.7rem; font-weight:850; line-height:1; }
        footer { padding:24px; color:#bdd1cc; background:var(--ink); text-align:center; font-size:.85rem; }
        .search-wrap { position:relative; max-width:820px; margin-top:34px; }
        .search-wrap .search { margin-top:0; }
        .suggestions { position:absolute; z-index:20; top:calc(100% + 8px); left:0; right:0; margin:0; padding:7px; list-style:none; background:#fff; border:1px solid #c9ddd7; border-radius:15px; box-shadow:0 18px 40px rgba(22,51,47,.16); }
        .suggestions[hidden] { display:none; }
        .suggestion { display:flex; justify-content:space-between; align-items:center; gap:18px; padding:12px 13px; border-radius:10px; text-decoration:none; }
        .suggestion:hover, .suggestion[aria-selected="true"] { background:var(--mint); }
        .suggestion-name { display:block; font-weight:750; }
        .suggestion-meta { display:block; color:var(--muted); font-size:.79rem; }
        .suggestion-code { flex:0 0 auto; color:var(--teal-dark); font-size:.75rem; font-weight:800; }
        .workflow-overview { display:grid; grid-template-columns:repeat(3,1fr); gap:18px; margin:26px 0; }
        .workflow-overview > div { padding:18px; border:1px solid var(--line); border-radius:14px; background:#fff; }
        .workflow-overview p { margin:8px 0 0; }
        .workflow-note { margin:20px 0; padding:18px; border-left:4px solid var(--amber); border-radius:10px; background:var(--amber-bg); }
        .workflow-note p { margin-bottom:0; }
        .workflow-steps { display:grid; grid-template-columns:repeat(4,1fr); gap:10px; margin:28px 0; }
        .workflow-step { padding:14px 10px; border:1px solid var(--line); border-radius:12px; text-align:center; color:var(--muted); font-weight:750; background:#fff; }
        .workflow-step-current { border-color:var(--blue); color:var(--blue); background:#eff6ff; }
        .workflow-form { margin-top:20px; }
        .button-danger { background:#fff1f2; color:#9f1239; border:1px solid #fecdd3; }        .table-actions { display:flex; gap:8px; justify-content:flex-end; margin-bottom:6px; }
        .form-container { max-width:980px; }
        .editor-form { margin-top:28px; padding:28px; border:1px solid var(--line); border-radius:20px; background:#fff; box-shadow:var(--shadow); }
        .form-field { margin-bottom:22px; }
        .form-field label { display:block; margin-bottom:8px; font-weight:800; }
        .form-field input, .form-field textarea { width:100%; border:1px solid #cbd5e1; border-radius:12px; padding:13px 14px; font:inherit; color:var(--ink); background:#fff; }
        .form-field textarea { resize:vertical; line-height:1.55; }
        .form-field input:focus, .form-field textarea:focus { outline:3px solid rgba(31,111,235,.16); border-color:var(--blue); }
        .form-grid { display:grid; grid-template-columns:1fr 1fr; gap:20px; }
        .form-help { margin:6px 0 0; color:var(--muted); font-size:.82rem; }
        .form-actions { display:flex; gap:12px; align-items:center; flex-wrap:wrap; }
        .success-message { margin:22px 0; padding:14px 16px; border-radius:12px; background:#e8f7ef; color:#176b3a; font-weight:750; }
        .error-summary { margin:22px 0; padding:14px 18px; border-radius:12px; background:#fff1f2; color:#9f1239; }
        .error-summary ul { margin-bottom:0; }        .admin-list-heading { display:flex; align-items:center; justify-content:space-between; gap:20px; margin:28px 0 14px; }
        .admin-table-wrap { overflow-x:auto; border:1px solid var(--line); border-radius:18px; background:#fff; box-shadow:var(--shadow); }
        .admin-table { width:100%; border-collapse:collapse; min-width:960px; }
        .admin-table th, .admin-table td { padding:16px 18px; text-align:left; border-bottom:1px solid var(--line); vertical-align:middle; }
        .admin-table th { color:var(--muted); font-size:.78rem; text-transform:uppercase; letter-spacing:.05em; background:#f8fafc; }
        .admin-table tbody tr:last-child td { border-bottom:0; }
        .admin-table tbody tr:hover { background:#fbfdff; }
        .admin-table code { white-space:nowrap; color:var(--blue); font-weight:700; }
        .admin-table-action { text-align:right !important; }
        .admin-table .button { white-space:nowrap; padding:10px 14px; }
        .visually-hidden { position:absolute; width:1px; height:1px; padding:0; margin:-1px; overflow:hidden; clip:rect(0,0,0,0); white-space:nowrap; border:0; }        .search-status { position:absolute; width:1px; height:1px; overflow:hidden; clip:rect(0,0,0,0); }        @media (max-width:760px) { .nav-note{display:none}.hero{padding:50px 0 40px}.search{flex-direction:column}.voice-button{width:100%}.cards,.detail-grid,.admin-summary,.form-grid,.workflow-overview,.workflow-steps{grid-template-columns:1fr}.share-row{flex-direction:column}.button-secondary{width:100%}.card{min-height:230px}.section-head{align-items:start;flex-direction:column}.content{padding-top:34px} }
    </style>
</head>
<body>
<header class="topbar">
    <nav class="nav" aria-label="Navigazione principale">
        <a class="brand" href="{{ route('pathologies.index') }}"><span class="brand-mark">A</span><span>Arianna</span></a>
        <div class="nav-actions">
            @auth
                <span class="nav-user">{{ auth()->user()->name }}</span>
                <a class="nav-link" href="{{ route('admin.dashboard') }}">Area riservata</a>
                <form class="logout-form" method="post" action="{{ route('logout') }}">
                    @csrf
                    <button class="nav-button" type="submit">Esci</button>
                </form>
            @else
                <span class="nav-note">Prototipo dimostrativo</span>
                <a class="nav-link" href="{{ route('login') }}">Area riservata</a>
            @endauth
        </div>
    </nav>
</header>
<main>@yield('content')</main>
<footer>Arianna · Prototipo dimostrativo con dati di esempio da fonti pubbliche</footer>
</body>
</html>


