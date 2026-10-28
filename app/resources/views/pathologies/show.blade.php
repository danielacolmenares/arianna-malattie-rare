@extends('layouts.app', ['title' => $pathology->official_name.' · Arianna'])

@section('content')
<div class="container">
    <a class="back" href="{{ route('pathologies.index') }}">← Torna alla ricerca</a>
    <section class="detail-hero">
        <span class="eyebrow">{{ $pathology->source_id }}</span>
        <h1 class="detail-title">{{ $pathology->official_name }}</h1>
        <p class="lead">{{ $pathology->short_description ?: 'Descrizione sintetica in attesa di validazione.' }}</p>
    </section>

    <div class="detail-grid">
        <div>
            <section class="panel">
                <h2>Denominazioni e codici</h2>
                <div class="row"><strong>Sinonimi</strong><span class="muted">{{ collect($pathology->synonyms)->pluck('valore')->join(', ') ?: 'Nessun sinonimo registrato' }}</span></div>
                <div class="row"><strong>Codici di riferimento</strong><div class="badges">@foreach ($pathology->codes as $code)<span class="badge">{{ $code['sistema'] }} {{ $code['valore'] }}</span>@endforeach</div></div>
                <div class="row"><strong>Classificazione</strong><span class="muted">{{ collect($pathology->classifications)->pluck('valore')->join(', ') }}</span></div>
            </section>

            <section class="panel">
                <h2>Unità operative</h2>
                @foreach ($pathology->operational_units as $unit)
                    <div class="row">
                        <strong>{{ $unit['nome'] }}</strong>
                        <span class="muted">Relazione: {{ str_replace('_', ' ', $unit['relazione_con_patologia']) }}</span>
                        @if (!empty($unit['medici_certificatori']))
                            <div class="badges">@foreach ($unit['medici_certificatori'] as $doctor)<span class="badge">{{ $doctor['nome'] }}</span>@endforeach</div>
                        @endif
                    </div>
                @endforeach
            </section>

            <section class="panel">
                <h2>Percorso di accesso</h2>
                @if ($pathology->access_path)
                    <p>{{ $pathology->access_path['istruzioni'] }}</p>
                @else
                    <p class="muted">Il percorso di accesso è ancora da verificare con l’ente.</p>
                @endif
            </section>
        </div>

        <aside>
            <section class="panel">
                <h2>Condividi la scheda</h2>
                <figure class="qr-figure">
                    <img class="qr-image" src="{{ route('pathologies.qr', $pathology) }}" alt="Codice QR per aprire la scheda {{ $pathology->official_name }}">
                    <figcaption>Inquadra il QR per aprire questa scheda.</figcaption>
                </figure>
                <label class="share-label" for="share-url">Link stabile della scheda</label>
                <div class="share-row">
                    <input id="share-url" class="share-url" type="text" readonly value="{{ route('pathologies.show', $pathology) }}">
                    <button id="copy-share-url" class="button button-secondary" type="button">Copia link</button>
                </div>
                <p id="copy-status" class="copy-status" role="status" aria-live="polite"></p>
                <a class="button qr-download" href="{{ route('pathologies.qr', ['pathology' => $pathology, 'download' => 1]) }}" download>Scarica QR in SVG</a>
                <p class="muted qr-note">Formato vettoriale adatto anche alla stampa.</p>
            </section>

            <section class="panel">
                <h2>Stato della scheda</h2>
                <span class="status">{{ str_replace('_', ' ', $pathology->workflow['stato']) }}</span>
                <div class="row"><strong>Completezza</strong><span class="muted">{{ str_replace('_', ' ', $pathology->quality['stato_completezza']) }}</span></div>
                <div class="row"><strong>Ultima verifica</strong><span class="muted">{{ \Illuminate\Support\Carbon::parse($pathology->quality['ultima_verifica'])->format('d/m/Y') }}</span></div>
            </section>

            <section class="panel">
                <h2>Fonti</h2>
                @foreach ($pathology->sources as $source)
                    <a class="source" href="{{ $source['url'] }}" target="_blank" rel="noopener noreferrer">{{ $source['titolo'] }} ↗</a>
                @endforeach
            </section>
        </aside>
    </div>
</div>

<script>
(() => {
    const button = document.getElementById('copy-share-url');
    const input = document.getElementById('share-url');
    const status = document.getElementById('copy-status');

    button.addEventListener('click', async () => {
        try {
            await navigator.clipboard.writeText(input.value);
            status.textContent = 'Link copiato.';
        } catch (error) {
            input.select();
            status.textContent = 'Link selezionato. Premi Ctrl+C per copiarlo.';
        }
    });
})();
</script>
@endsection