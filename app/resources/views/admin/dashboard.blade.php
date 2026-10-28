@extends('layouts.app', ['title' => 'Area riservata · Arianna'])

@section('content')
<section class="admin-page">
    <div class="container">
        <span class="eyebrow">Back office dimostrativo</span>
        <h1 class="admin-title">Area riservata</h1>
        <p class="lead">Accesso effettuato come {{ auth()->user()->name }}.</p>
        <p><span class="status">{{ auth()->user()->role->label() }}</span></p>

        <div class="admin-summary">
            <article class="admin-card">
                <span class="admin-number">{{ $pathologyCount }}</span>
                <h2>Schede disponibili</h2>
                <p class="muted">Consulta nome, stato, completezza e ultima modifica.</p>
                <a class="button" href="{{ route('admin.pathologies.index') }}">Vedi elenco schede</a>
            </article>
            <article class="admin-card">
                <span class="status">Fase 3</span>
                <h2>Ruolo e permessi</h2>
                <p class="muted">Il profilo vede e apre soltanto le funzioni consentite dal proprio ruolo.</p>
            </article>
        </div>

        <h2 class="section-title">Osservatorio delle ricerche</h2>
        <p class="lead">Indicatori aggregati per individuare bisogni informativi e pianificare gli aggiornamenti. Non vengono registrati IP, dispositivi o dati identificativi degli utenti.</p>
        <div class="admin-summary">
            <article class="admin-card">
                <span class="admin-number">{{ $searchCount }}</span>
                <h2>Ricerche registrate</h2>
                <p class="muted">Richieste testuali inviate al motore di ricerca.</p>
            </article>
            <article class="admin-card">
                <span class="admin-number">{{ $missingSearchCount }}</span>
                <h2>Senza risultato</h2>
                <p class="muted">Richieste che possono indicare una lacuna informativa.</p>
            </article>
        </div>

        <div class="admin-list-heading"><h2>Patologie e termini più ricercati</h2></div>
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead><tr><th>Ricerca</th><th>Frequenza</th><th>Esito più recente</th></tr></thead>
                <tbody>
                    @forelse ($topQueries as $item)
                        <tr><td><strong>{{ $item->query }}</strong></td><td>{{ $item->searches }}</td><td>{{ $item->result_count > 0 ? 'Risultato disponibile' : 'Nessun risultato' }}</td></tr>
                    @empty
                        <tr><td colspan="3">Nessun dato di utilizzo disponibile.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="admin-list-heading"><h2>Ricerche senza risultato</h2></div>
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead><tr><th>Termine cercato</th><th>Occorrenze</th><th>Indicazione</th></tr></thead>
                <tbody>
                    @forelse ($missingQueries as $item)
                        <tr><td><strong>{{ $item->query }}</strong></td><td>{{ $item->searches }}</td><td>Possibile lacuna informativa</td></tr>
                    @empty
                        <tr><td colspan="3">Nessuna ricerca senza risultato.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="admin-list-heading"><h2>Piano editoriale prioritizzato</h2></div>
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead><tr><th>Priorità</th><th>Argomento</th><th>Motivazione</th><th>Attività proposta</th></tr></thead>
                <tbody>
                    @forelse ($editorialPlan as $item)
                        <tr><td><span class="status">{{ $item['priority'] }}/100</span></td><td><strong>{{ $item['topic'] }}</strong></td><td>{{ $item['reason'] }}</td><td>{{ $item['action'] }}</td></tr>
                    @empty
                        <tr><td colspan="4">Il piano sarà generato con i primi dati di utilizzo.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <h2 class="section-title">Funzioni del profilo</h2>
        <div class="admin-summary">
            @foreach ([
                ['permission' => 'edit-drafts', 'route' => 'admin.editor', 'title' => 'Redazione', 'text' => 'Preparazione e modifica delle bozze.'],
                ['permission' => 'validate-content', 'route' => 'admin.validator', 'title' => 'Validazione', 'text' => 'Controllo e validazione dei contenuti.'],
                ['permission' => 'authorize-content', 'route' => 'admin.director', 'title' => 'Autorizzazione', 'text' => 'Autorizzazione del Direttore UOC.'],
                ['permission' => 'manage-users', 'route' => 'admin.users', 'title' => 'Utenti', 'text' => 'Gestione dimostrativa degli account.'],
            ] as $function)
                <article class="admin-card">
                    <span class="status">{{ auth()->user()->canPerform($function['permission']) ? 'Consentito' : 'Non consentito' }}</span>
                    <h2>{{ $function['title'] }}</h2>
                    <p class="muted">{{ $function['text'] }}</p>
                    @if (auth()->user()->canPerform($function['permission']))
                        <a class="button" href="{{ route($function['route']) }}">Apri</a>
                    @endif
                </article>
            @endforeach
        </div>
    </div>
</section>
@endsection

