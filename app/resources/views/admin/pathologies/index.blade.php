@extends('layouts.app', ['title' => 'Schede amministrative · Arianna'])

@section('content')
<section class="admin-page">
    <div class="container">
        <a class="back" href="{{ route('admin.dashboard') }}">← Torna all’area riservata</a>
        <span class="eyebrow">Back office dimostrativo</span>
        <h1 class="admin-title">Schede delle patologie</h1>
        <p class="lead">Elenco condiviso delle schede disponibili nel back office.</p>
        <div class="admin-list-heading"><p><strong>{{ $pathologies->count() }}</strong> schede disponibili</p><span class="status">{{ auth()->user()->role->label() }}</span></div>
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead><tr><th>Patologia</th><th>Identificativo</th><th>Stato</th><th>Completezza</th><th>Ultima modifica</th><th><span class="visually-hidden">Azioni</span></th></tr></thead>
                <tbody>
                    @foreach ($pathologies as $pathology)
                        @php
                            $workflowStatus = $pathology->draft?->status->label() ?? str_replace('_', ' ', data_get($pathology->workflow, 'stato', 'non definito'));
                            $qualityStatus = data_get($pathology->quality, 'stato_completezza', 'non definita');
                            $lastUpdate = $pathology->draft?->updated_at ?? $pathology->updated_at;
                        @endphp
                        <tr>
                            <td data-label="Patologia"><strong>{{ $pathology->official_name }}</strong></td>
                            <td data-label="Identificativo"><code>{{ $pathology->source_id }}</code></td>
                            <td data-label="Stato"><span class="status">{{ $workflowStatus }}</span></td>
                            <td data-label="Completezza">{{ str_replace('_', ' ', $qualityStatus) }}</td>
                            <td data-label="Ultima modifica"><time datetime="{{ $lastUpdate->toIso8601String() }}">{{ $lastUpdate->format('d/m/Y H:i') }}</time></td>
                            <td class="admin-table-action">
                                <div class="table-actions">
                                    @if (auth()->user()->canPerform('edit-drafts') && (! $pathology->draft || $pathology->draft->status === \App\Enums\DraftStatus::Draft))
                                        <a class="button" href="{{ route('admin.pathologies.edit', $pathology) }}">Modifica bozza</a>
                                    @endif
                                    @if ($pathology->draft)
                                        <a class="button button-secondary" href="{{ route('admin.pathologies.workflow', $pathology) }}">Gestisci flusso</a>
                                    @endif
                                    <a class="button button-secondary" href="{{ route('pathologies.show', $pathology) }}">Apri scheda</a>
                                </div>
                                @if ($pathology->draft?->editor)<small class="muted">Bozza salvata da {{ $pathology->draft->editor->name }}</small>@endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <p class="auth-note">La modifica delle bozze è disponibile soltanto per redattore e amministratrice. La versione pubblica resta separata.</p>
    </div>
</section>
@endsection
