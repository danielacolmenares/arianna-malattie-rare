<?php

namespace App\Http\Controllers\Admin;

use App\Enums\DraftStatus;
use App\Http\Controllers\Controller;
use App\Models\Pathology;
use App\Models\PathologyDraft;
use App\Models\WorkflowEvent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PathologyController extends Controller
{
    public function index(): View
    {
        return view('admin.pathologies.index', [
            'pathologies' => Pathology::query()->with(['draft.editor'])->orderBy('official_name')->get(),
        ]);
    }

    public function edit(Pathology $pathology): View
    {
        abort_if($pathology->draft && $pathology->draft->status !== DraftStatus::Draft, 409, 'La bozza non è modificabile nello stato attuale.');

        $draft = $pathology->draft ?? new PathologyDraft([
            'official_name' => $pathology->official_name,
            'short_description' => $pathology->short_description,
            'synonyms' => collect($pathology->synonyms)->pluck('valore')->filter()->values()->all(),
            'colloquial_terms' => $pathology->colloquial_terms,
            'keywords' => $pathology->keywords,
        ]);

        return view('admin.pathologies.edit', compact('pathology', 'draft'));
    }

    public function update(Request $request, Pathology $pathology): RedirectResponse
    {
        abort_if($pathology->draft && $pathology->draft->status !== DraftStatus::Draft, 409, 'La bozza non è modificabile nello stato attuale.');

        $validated = $request->validate([
            'official_name' => ['required', 'string', 'max:255'],
            'short_description' => ['nullable', 'string', 'max:2000'],
            'synonyms' => ['nullable', 'string', 'max:4000'],
            'colloquial_terms' => ['nullable', 'string', 'max:4000'],
            'keywords' => ['nullable', 'string', 'max:4000'],
        ], [
            'official_name.required' => 'Inserisci il nome ufficiale della patologia.',
            'official_name.max' => 'Il nome ufficiale non può superare 255 caratteri.',
            'short_description.max' => 'La descrizione non può superare 2.000 caratteri.',
        ]);

        $draft = $pathology->draft()->updateOrCreate([], [
            'official_name' => $validated['official_name'],
            'short_description' => $validated['short_description'] ?? null,
            'synonyms' => $this->lines($validated['synonyms'] ?? ''),
            'colloquial_terms' => $this->lines($validated['colloquial_terms'] ?? ''),
            'keywords' => $this->lines($validated['keywords'] ?? ''),
            'status' => DraftStatus::Draft,
            'updated_by' => $request->user()->id,
        ]);

        WorkflowEvent::query()->create([
            'pathology_id' => $pathology->id,
            'user_id' => $request->user()->id,
            'user_role' => $request->user()->role,
            'action' => 'draft_saved',
            'previous_status' => $draft->status,
            'next_status' => $draft->status,
        ]);

        return redirect()
            ->route('admin.pathologies.edit', $pathology)
            ->with('status', 'Bozza salvata. La scheda pubblica non è stata modificata.');
    }

    /** @return list<string> */
    private function lines(string $value): array
    {
        return collect(preg_split('/\R/u', $value) ?: [])
            ->map(fn (string $line): string => trim($line))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }
}
