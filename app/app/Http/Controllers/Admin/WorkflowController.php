<?php

namespace App\Http\Controllers\Admin;

use App\Enums\DraftStatus;
use App\Http\Controllers\Controller;
use App\Models\Pathology;
use App\Models\WorkflowEvent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class WorkflowController extends Controller
{
    public function show(Pathology $pathology): View
    {
        return view('admin.pathologies.workflow', [
            'pathology' => $pathology,
            'draft' => $pathology->draft?->load(['editor', 'statusEditor']),
            'events' => $pathology->workflowEvents()->with('user')->latest('created_at')->latest('id')->get(),
        ]);
    }

    public function transition(Request $request, Pathology $pathology): RedirectResponse
    {
        $draft = $pathology->draft;
        abort_if($draft === null, 404);

        $validated = $request->validate([
            'action' => ['required', 'in:submit,validate,return_validation,authorize,return_authorization,publish,withdraw'],
            'note' => ['nullable', 'string', 'max:2000'],
        ]);

        $action = $validated['action'];
        $note = trim((string) ($validated['note'] ?? ''));
        $this->authorizeAction($request, $action);

        if ((str_starts_with($action, 'return_') || $action === 'withdraw') && $note === '') {
            throw ValidationException::withMessages(['note' => 'Inserisci una nota per spiegare il rinvio o il ritiro.']);
        }

        $nextStatus = $this->nextStatus($draft->status, $action);
        if ($nextStatus === null) {
            throw ValidationException::withMessages(['action' => 'Questo passaggio non è disponibile nello stato attuale.']);
        }

        DB::transaction(function () use ($draft, $nextStatus, $note, $request, $pathology, $action): void {
            $previousStatus = $draft->status;

            if ($action === 'publish') {
                $workflow = $pathology->workflow ?? [];
                data_set($workflow, 'stato', 'pubblicata');
                data_set($workflow, 'pubblicabile', true);

                $pathology->update([
                    'official_name' => $draft->official_name,
                    'short_description' => $draft->short_description,
                    'synonyms' => collect($draft->synonyms)->map(fn (string $value): array => [
                        'valore' => $value,
                        'tipo' => 'sinonimo',
                    ])->values()->all(),
                    'colloquial_terms' => $draft->colloquial_terms,
                    'keywords' => $draft->keywords,
                    'workflow' => $workflow,
                    'publication_status' => 'published',
                    'published_at' => now(),
                    'withdrawn_at' => null,
                ]);
            }

            if ($action === 'withdraw') {
                $workflow = $pathology->workflow ?? [];
                data_set($workflow, 'stato', 'ritirata');
                data_set($workflow, 'pubblicabile', false);

                $pathology->update([
                    'workflow' => $workflow,
                    'publication_status' => 'withdrawn',
                    'withdrawn_at' => now(),
                ]);
            }

            $draft->update([
                'status' => $nextStatus,
                'last_note' => $note !== '' ? $note : null,
                'status_changed_by' => $request->user()->id,
                'status_changed_at' => now(),
            ]);

            WorkflowEvent::query()->create([
                'pathology_id' => $pathology->id,
                'user_id' => $request->user()->id,
                'user_role' => $request->user()->role,
                'action' => $action,
                'previous_status' => $previousStatus,
                'next_status' => $nextStatus,
                'note' => $note !== '' ? $note : null,
            ]);
        });

        $pathology->refresh();
        if ($action === 'publish') {
            $pathology->searchable();
        } elseif ($action === 'withdraw') {
            $pathology->unsearchable();
        }

        return redirect()
            ->route('admin.pathologies.workflow', $pathology)
            ->with('status', 'Stato aggiornato: '.$nextStatus->label().'.');
    }

    private function authorizeAction(Request $request, string $action): void
    {
        $permission = match ($action) {
            'submit' => 'edit-drafts',
            'validate', 'return_validation' => 'validate-content',
            'authorize', 'return_authorization', 'publish', 'withdraw' => 'authorize-content',
        };
        abort_unless($request->user()->canPerform($permission), 403);
    }

    private function nextStatus(DraftStatus $current, string $action): ?DraftStatus
    {
        return match ([$current, $action]) {
            [DraftStatus::Draft, 'submit'] => DraftStatus::InValidation,
            [DraftStatus::InValidation, 'validate'] => DraftStatus::AwaitingAuthorization,
            [DraftStatus::InValidation, 'return_validation'] => DraftStatus::Draft,
            [DraftStatus::AwaitingAuthorization, 'authorize'] => DraftStatus::Authorized,
            [DraftStatus::AwaitingAuthorization, 'return_authorization'] => DraftStatus::Draft,
            [DraftStatus::Authorized, 'publish'] => DraftStatus::Published,
            [DraftStatus::Published, 'withdraw'] => DraftStatus::Withdrawn,
            default => null,
        };
    }
}
