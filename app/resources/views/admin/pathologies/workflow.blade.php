@extends('layouts.app', ['title' => 'Flusso della scheda · '.$pathology->official_name.' · Arianna'])

@section('content')
<section class="admin-page">
    <div class="container form-container">
        <a class="back" href="{{ route('admin.pathologies.index') }}">← Torna all’elenco delle schede</a>
        <span class="eyebrow">Flusso editoriale dimostrativo</span>
        <h1 class="admin-title">{{ $pathology->official_name }}</h1>

        @if (session('status'))<div class="success-message" role="status">{{ session('status') }}</div>@endif
        @if ($errors->any())
            <div class="error-summary" role="alert"><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
        @endif

        @if (! $draft)
            <div class="admin-card">
                <h2>Nessuna bozza salvata</h2>
                <p class="muted">Un redattore o l’amministratrice deve prima aprire e salvare la bozza.</p>
            </div>
        @else
            <div class="workflow-overview">
                <div><span class="muted">Stato attuale</span><p><span class="status">{{ $draft->status->label() }}</span></p></div>
                <div><span class="muted">Ultimo salvataggio</span><p><strong>{{ $draft->editor?->name ?? 'Non disponibile' }}</strong><br>{{ $draft->updated_at->format('d/m/Y H:i') }}</p></div>
                @if ($draft->statusEditor)
                    <div><span class="muted">Ultimo passaggio</span><p><strong>{{ $draft->statusEditor->name }}</strong><br>{{ $draft->status_changed_at?->format('d/m/Y H:i') }}</p></div>
                @endif
            </div>

            @if ($draft->last_note)
                <div class="workflow-note"><strong>Ultima nota</strong><p>{{ $draft->last_note }}</p></div>
            @endif

            <div class="workflow-steps" aria-label="Stati del flusso">
                @foreach ([
                    \App\Enums\DraftStatus::Draft,
                    \App\Enums\DraftStatus::InValidation,
                    \App\Enums\DraftStatus::AwaitingAuthorization,
                    \App\Enums\DraftStatus::Authorized,
                    \App\Enums\DraftStatus::Published,
                    \App\Enums\DraftStatus::Withdrawn,
                ] as $step)
                    <div class="workflow-step {{ $draft->status === $step ? 'workflow-step-current' : '' }}">{{ $step->label() }}</div>
                @endforeach
            </div>

            @php($canSubmit = $draft->status === \App\Enums\DraftStatus::Draft && auth()->user()->canPerform('edit-drafts'))
            @php($canValidate = $draft->status === \App\Enums\DraftStatus::InValidation && auth()->user()->canPerform('validate-content'))
            @php($canAuthorize = $draft->status === \App\Enums\DraftStatus::AwaitingAuthorization && auth()->user()->canPerform('authorize-content'))
            @php($canPublish = $draft->status === \App\Enums\DraftStatus::Authorized && auth()->user()->canPerform('authorize-content'))
            @php($canWithdraw = $draft->status === \App\Enums\DraftStatus::Published && auth()->user()->canPerform('authorize-content'))

            @if ($canSubmit || $canValidate || $canAuthorize || $canPublish || $canWithdraw)
                <form class="editor-form workflow-form" method="post" action="{{ route('admin.pathologies.transition', $pathology) }}">
                    @csrf
                    @if ($canValidate || $canAuthorize || $canPublish || $canWithdraw)
                        <div class="form-field">
                            <label for="note">Nota</label>
                            <textarea id="note" name="note" rows="4" maxlength="2000" placeholder="Obbligatoria in caso di rinvio o ritiro">{{ old('note') }}</textarea>
                            <p class="form-help">La nota è obbligatoria per rinvio e ritiro; negli altri passaggi è facoltativa.</p>
                        </div>
                    @endif
                    <div class="form-actions">
                        @if ($canSubmit)<button class="button" name="action" value="submit" type="submit">Invia in validazione</button>@endif
                        @if ($canValidate)
                            <button class="button" name="action" value="validate" type="submit">Valida e invia al Direttore</button>
                            <button class="button button-danger" name="action" value="return_validation" type="submit">Rinvia al redattore</button>
                        @endif
                        @if ($canAuthorize)
                            <button class="button" name="action" value="authorize" type="submit">Autorizza scheda</button>
                            <button class="button button-danger" name="action" value="return_authorization" type="submit">Rinvia al redattore</button>
                        @endif
                        @if ($canPublish)<button class="button" name="action" value="publish" type="submit">Pubblica scheda</button>@endif
                        @if ($canWithdraw)<button class="button button-danger" name="action" value="withdraw" type="submit">Ritira scheda</button>@endif
                    </div>
                </form>
            @else
                <p class="auth-note">In questo stato il tuo profilo non deve eseguire alcuna azione.</p>
            @endif
        @endif

        <section class="workflow-history" aria-labelledby="workflow-history-title">
            <h2 id="workflow-history-title">Storico delle operazioni</h2>
            @if ($events->isEmpty())
                <p class="muted">Non sono ancora presenti operazioni registrate per questa scheda.</p>
            @else
                <ol class="workflow-history-list">
                    @foreach ($events as $event)
                        <li class="admin-card">
                            <div class="admin-list-heading">
                                <strong>{{ $event->actionLabel() }}</strong>
                                <time datetime="{{ $event->created_at->toIso8601String() }}">{{ $event->created_at->format('d/m/Y H:i') }}</time>
                            </div>
                            <p>{{ $event->user?->name ?? 'Utente non più disponibile' }} · {{ $event->user_role->label() }}</p>
                            @if ($event->previous_status || $event->next_status)
                                <p class="muted">{{ $event->previous_status?->label() ?? '—' }} → {{ $event->next_status?->label() ?? '—' }}</p>
                            @endif
                            @if ($event->note)<div class="workflow-note"><strong>Nota</strong><p>{{ $event->note }}</p></div>@endif
                        </li>
                    @endforeach
                </ol>
            @endif
        </section>
    </div>
</section>
@endsection
