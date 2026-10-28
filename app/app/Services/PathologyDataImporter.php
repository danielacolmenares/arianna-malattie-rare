<?php

namespace App\Services;

use App\Models\Pathology;
use Illuminate\Support\Facades\Validator;

class PathologyDataImporter
{
    public function importFile(string $path): Pathology
    {
        $contents = file_get_contents($path);

        if ($contents === false) {
            throw new \RuntimeException("Impossibile leggere la scheda: {$path}");
        }

        $data = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);

        Validator::make($data, [
            'schema_version' => ['required', 'string', 'in:0.1.0'],
            'id' => ['required', 'string', 'regex:/^PAT-[A-Z0-9-]+$/'],
            'slug' => ['required', 'string', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            'nome_ufficiale' => ['required', 'string', 'min:2'],
            'descrizione_breve' => ['nullable', 'string'],
            'sinonimi' => ['required', 'array'],
            'termini_colloquiali' => ['sometimes', 'array'],
            'parole_chiave' => ['sometimes', 'array'],
            'codici' => ['required', 'array'],
            'classificazioni' => ['required', 'array'],
            'unita_operative' => ['required', 'array', 'min:1'],
            'percorso_accesso' => ['present', 'nullable', 'array'],
            'fonti' => ['required', 'array', 'min:1'],
            'workflow' => ['required', 'array'],
            'qualita' => ['required', 'array'],
        ])->validate();

        return Pathology::query()->updateOrCreate(
            ['source_id' => $data['id']],
            [
                'slug' => $data['slug'],
                'schema_version' => $data['schema_version'],
                'official_name' => $data['nome_ufficiale'],
                'short_description' => $data['descrizione_breve'] ?? null,
                'synonyms' => $data['sinonimi'],
                'colloquial_terms' => $data['termini_colloquiali'] ?? [],
                'keywords' => $data['parole_chiave'] ?? [],
                'codes' => $data['codici'],
                'classifications' => $data['classificazioni'],
                'operational_units' => $data['unita_operative'],
                'access_path' => $data['percorso_accesso'],
                'sources' => $data['fonti'],
                'workflow' => $data['workflow'],
                'quality' => $data['qualita'],
                'source_payload' => $data,
            ],
        );
    }
}
