@extends('layouts.app')

@section('content')
<section class="hero">
    <div class="container">
        <span class="eyebrow">Orientamento sanitario</span>
        <h1>Trova la patologia e il percorso giusto.</h1>
        <p class="lead">Cerca anche per sinonimo, acronimo, codice o nome incompleto. Il motore riconosce gli errori di digitazione più comuni.</p>
        <div class="search-wrap">
            <form id="pathology-search-form" class="search" action="{{ route('pathologies.index') }}" method="get" role="search" autocomplete="off">
                <input id="pathology-search" type="search" name="q" value="{{ $query }}" placeholder="Cerca per patologia, sinonimo o codice…" aria-label="Cerca una patologia" aria-autocomplete="list" aria-controls="search-suggestions" aria-expanded="false" autofocus>
                <button id="voice-search" class="voice-button" type="button" aria-label="Cerca usando la voce" aria-pressed="false" title="Cerca usando la voce">
                    <svg aria-hidden="true" viewBox="0 0 24 24"><path d="M12 15a3.5 3.5 0 0 0 3.5-3.5v-5a3.5 3.5 0 1 0-7 0v5A3.5 3.5 0 0 0 12 15Zm-6-3.5a1 1 0 0 1 2 0 4 4 0 0 0 8 0 1 1 0 1 1 2 0 6 6 0 0 1-5 5.91V20h2a1 1 0 1 1 0 2H9a1 1 0 1 1 0-2h2v-2.59a6 6 0 0 1-5-5.91Z"/></svg>
                    <span class="voice-label">Voce</span>
                </button>
                <button class="button" type="submit">Cerca</button>
            </form>
            <ul id="search-suggestions" class="suggestions" role="listbox" hidden></ul>
            <span id="search-status" class="search-status" role="status" aria-live="polite"></span>
            <div id="voice-status" class="voice-feedback" aria-live="polite"></div>
        </div>
    </div>
</section>
<section class="content">
    <div class="container">
        <div class="section-head">
            <div>
                <span class="eyebrow">Schede disponibili</span>
                <h2>{{ $query !== '' ? 'Risultati della ricerca' : 'Patologie campione' }}</h2>
            </div>
            <span class="count">{{ $pathologies->count() }} {{ $pathologies->count() === 1 ? 'scheda' : 'schede' }}</span>
        </div>

        @if ($pathologies->isEmpty())
            <div class="empty">
                <h3>Nessun risultato per “{{ $query }}”</h3>
                <p class="muted">Prova con una denominazione, un sinonimo o un codice diverso.</p>
                <a class="button" href="{{ route('pathologies.index') }}">Mostra tutte</a>
            </div>
        @else
            <div class="cards">
                @foreach ($pathologies as $pathology)
                    <article class="card">
                        <span class="card-code">{{ $pathology->source_id }}</span>
                        <h3>{{ $pathology->official_name }}</h3>
                        <p>{{ data_get($pathology->classifications, '0.valore', 'Classificazione da definire') }}</p>
                        <div class="badges">
                            @foreach (array_slice($pathology->codes, 0, 3) as $code)
                                <span class="badge">{{ $code['sistema'] }} {{ $code['valore'] }}</span>
                            @endforeach
                        </div>
                        <a class="card-link" href="{{ route('pathologies.show', $pathology) }}">Apri la scheda →</a>
                    </article>
                @endforeach
            </div>
        @endif

        <div class="notice"><strong>Nota sul prototipo:</strong> queste schede servono a collaudare struttura e ricerca. I contenuti non sono ancora autorizzati alla pubblicazione.</div>
    </div>
</section>

<script>
(() => {
    const input = document.getElementById('pathology-search');
    const form = document.getElementById('pathology-search-form');
    const voiceButton = document.getElementById('voice-search');
    const voiceStatus = document.getElementById('voice-status');
    const list = document.getElementById('search-suggestions');
    const status = document.getElementById('search-status');
    const endpoint = @json(route('pathologies.suggestions'));
    const voiceEndpoint = @json(route('pathologies.voice'));
    let timer;
    let controller;
    let active = -1;

    const close = () => {
        list.hidden = true;
        list.replaceChildren();
        input.setAttribute('aria-expanded', 'false');
        input.removeAttribute('aria-activedescendant');
        active = -1;
    };

    const select = (index) => {
        const options = [...list.querySelectorAll('[role="option"]')];
        if (!options.length) return;
        active = (index + options.length) % options.length;
        options.forEach((option, i) => option.setAttribute('aria-selected', i === active ? 'true' : 'false'));
        input.setAttribute('aria-activedescendant', options[active].id);
        options[active].scrollIntoView({ block: 'nearest' });
    };

    const render = (items) => {
        list.replaceChildren();
        active = -1;
        items.forEach((item, index) => {
            const row = document.createElement('li');
            const link = document.createElement('a');
            const text = document.createElement('span');
            const name = document.createElement('span');
            const meta = document.createElement('span');
            const code = document.createElement('span');
            link.id = `suggestion-${index}`;
            link.className = 'suggestion';
            link.href = item.url;
            link.role = 'option';
            link.setAttribute('aria-selected', 'false');
            name.className = 'suggestion-name';
            name.textContent = item.name;
            meta.className = 'suggestion-meta';
            meta.textContent = item.classification || 'Patologia rara';
            code.className = 'suggestion-code';
            code.textContent = item.code;
            text.append(name, meta);
            link.append(text, code);
            row.append(link);
            list.append(row);
        });
        list.hidden = items.length === 0;
        input.setAttribute('aria-expanded', items.length ? 'true' : 'false');
        status.textContent = items.length ? `${items.length} suggerimenti disponibili` : 'Nessun suggerimento';
    };


    const setVoiceState = (recording, message) => {
        voiceButton.classList.toggle('is-recording', recording);
        voiceButton.setAttribute('aria-pressed', recording ? 'true' : 'false');
        voiceButton.querySelector('.voice-label').textContent = recording ? 'Stop' : 'Voce';
        status.textContent = message;
        voiceStatus.textContent = message;
    };

    if (!navigator.mediaDevices?.getUserMedia || !window.MediaRecorder) {
        voiceButton.hidden = true;
    } else {
        voiceButton.addEventListener('click', async () => {
            if (voiceButton.recorder?.state === 'recording') {
                voiceButton.recorder.stop();
                return;
            }
            try {
                const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
                const chunks = [];
                const type = ['audio/webm;codecs=opus', 'audio/webm', 'audio/mp4'].find(value => MediaRecorder.isTypeSupported(value));
                const recorder = type ? new MediaRecorder(stream, { mimeType: type }) : new MediaRecorder(stream);
                voiceButton.recorder = recorder;
                recorder.addEventListener('dataavailable', event => { if (event.data.size) chunks.push(event.data); });
                recorder.addEventListener('stop', async () => {
                    clearTimeout(voiceButton.stopTimer);
                    stream.getTracks().forEach(track => track.stop());
                    setVoiceState(false, 'Trascrizione in corso…');
                    const audio = new Blob(chunks, { type: recorder.mimeType || 'audio/webm' });
                    const data = new FormData();
                    data.append('audio', audio, recorder.mimeType.includes('mp4') ? 'ricerca-vocale.mp4' : 'ricerca-vocale.webm');
                    try {
                        const response = await fetch(voiceEndpoint, {
                            method: 'POST',
                            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                            body: data,
                        });
                        const result = await response.json();
                        if (!response.ok) throw new Error(result.message || 'Trascrizione non riuscita');
                        input.value = (result.query || result.transcript).trim();
                        status.textContent = `Ho riconosciuto: ${input.value}. Avvio la ricerca.`;
                        voiceStatus.textContent = status.textContent;
                        form.requestSubmit();
                    } catch (error) {
                        status.textContent = error.message;
                        voiceStatus.textContent = error.message;
                    } finally {
                        voiceButton.recorder = null;
                    }
                });
                recorder.start();
                setVoiceState(true, 'Sto ascoltando. Pronuncia il nome della patologia, poi premi Stop.');
                voiceButton.stopTimer = setTimeout(() => recorder.state === 'recording' && recorder.stop(), 12000);
            } catch (error) {
                setVoiceState(false, error.name === 'NotAllowedError'
                    ? 'Permesso del microfono negato. Consentilo nelle impostazioni del browser.'
                    : 'Non riesco ad accedere al microfono.');
            }
        });
    }

    input.addEventListener('input', () => {
        clearTimeout(timer);
        controller?.abort();
        const query = input.value.trim();
        if (query.length < 2) return close();
        timer = setTimeout(async () => {
            controller = new AbortController();
            try {
                const response = await fetch(`${endpoint}?q=${encodeURIComponent(query)}`, { signal: controller.signal });
                if (!response.ok) throw new Error('Errore nella ricerca');
                render((await response.json()).suggestions || []);
            } catch (error) {
                if (error.name !== 'AbortError') close();
            }
        }, 180);
    });

    input.addEventListener('keydown', (event) => {
        if (list.hidden) return;
        if (event.key === 'ArrowDown') { event.preventDefault(); select(active + 1); }
        if (event.key === 'ArrowUp') { event.preventDefault(); select(active - 1); }
        if (event.key === 'Enter' && active >= 0) { event.preventDefault(); list.querySelectorAll('[role="option"]')[active].click(); }
        if (event.key === 'Escape') close();
    });

    document.addEventListener('click', (event) => {
        if (!event.target.closest('.search-wrap')) close();
    });
})();
</script>
@endsection
