@extends('layouts.app', ['title' => 'Modifica bozza · '.$pathology->official_name.' · Arianna'])

@section('content')
<section class="admin-page">
    <div class="container form-container">
        <a class="back" href="{{ route('admin.pathologies.index') }}">← Torna all’elenco delle schede</a>
        <span class="eyebrow">Redazione della bozza</span>
        <h1 class="admin-title">{{ $pathology->official_name }}</h1>
        <p class="lead">Le modifiche restano nella bozza e non cambiano la scheda pubblica.</p>

        @if (session('status'))
            <div class="success-message" role="status">{{ session('status') }}</div>
        @endif

        @if ($errors->any())
            <div class="error-summary" role="alert">
                <strong>Controlla i campi evidenziati.</strong>
                <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif

        <form class="editor-form" method="post" action="{{ route('admin.pathologies.update', $pathology) }}">
            @csrf
            @method('put')
            <div class="form-field">
                <label for="official_name">Nome ufficiale</label>
                <input id="official_name" name="official_name" type="text" value="{{ old('official_name', $draft->official_name) }}" maxlength="255" required>
                @error('official_name')<p class="field-error">{{ $message }}</p>@enderror
            </div>
            <div class="form-field">
                <label for="short_description">Descrizione breve</label>
                <textarea id="short_description" name="short_description" rows="6" maxlength="2000">{{ old('short_description', $draft->short_description) }}</textarea>
                <p class="form-help">Massimo 2.000 caratteri.</p>
                @error('short_description')<p class="field-error">{{ $message }}</p>@enderror
            </div>
            <div class="form-grid">
                <div class="form-field">
                    <label for="synonyms">Sinonimi</label>
                    <textarea id="synonyms" name="synonyms" rows="7">{{ old('synonyms', implode("\n", $draft->synonyms ?? [])) }}</textarea>
                    <p class="form-help">Un sinonimo per riga.</p>
                </div>
                <div class="form-field">
                    <label for="colloquial_terms">Termini colloquiali</label>
                    <textarea id="colloquial_terms" name="colloquial_terms" rows="7">{{ old('colloquial_terms', implode("\n", $draft->colloquial_terms ?? [])) }}</textarea>
                    <p class="form-help">Un termine per riga.</p>
                </div>
            </div>
            <div class="form-field">
                <label for="keywords">Parole chiave</label>
                <textarea id="keywords" name="keywords" rows="5">{{ old('keywords', implode("\n", $draft->keywords ?? [])) }}</textarea>
                <p class="form-help">Una parola o espressione per riga.</p>
            </div>
            <div class="form-actions">
                <button class="button" type="submit">Salva bozza</button>
                <a class="button button-secondary" href="{{ route('admin.pathologies.workflow', $pathology) }}">Gestisci flusso</a>
                <a class="button button-secondary" href="{{ route('pathologies.show', $pathology) }}">Visualizza versione pubblica</a>
            </div>
        </form>
    </div>
</section>
@endsection

