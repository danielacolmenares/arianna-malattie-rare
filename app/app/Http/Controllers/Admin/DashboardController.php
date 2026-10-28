<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Pathology;
use App\Models\SearchEvent;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $topQueries = SearchEvent::query()
            ->selectRaw('normalized_query, max(query) as query, count(*) as searches, max(result_count) as result_count')
            ->groupBy('normalized_query')
            ->orderByDesc('searches')
            ->limit(8)
            ->get();

        $missingQueries = SearchEvent::query()
            ->where('result_count', 0)
            ->selectRaw('normalized_query, max(query) as query, count(*) as searches')
            ->groupBy('normalized_query')
            ->orderByDesc('searches')
            ->limit(8)
            ->get();

        $editorialPlan = $missingQueries->map(fn ($item): array => [
            'priority' => min(100, 50 + ($item->searches * 10)),
            'topic' => $item->query,
            'reason' => $item->searches.' ricerche senza risultato',
            'action' => 'Verificare il fabbisogno e predisporre una nuova scheda',
        ])->concat($this->incompletePathologies())->sortByDesc('priority')->take(8)->values();

        return view('admin.dashboard', [
            'pathologyCount' => Pathology::query()->count(),
            'searchCount' => SearchEvent::query()->count(),
            'missingSearchCount' => SearchEvent::query()->where('result_count', 0)->count(),
            'topQueries' => $topQueries,
            'missingQueries' => $missingQueries,
            'editorialPlan' => $editorialPlan,
        ]);
    }

    private function incompletePathologies(): Collection
    {
        return Pathology::query()->get()->filter(
            fn (Pathology $pathology): bool => data_get($pathology->quality, 'stato_completezza') !== 'completa'
        )->map(fn (Pathology $pathology): array => [
            'priority' => 40,
            'topic' => $pathology->official_name,
            'reason' => 'Scheda con informazioni da completare',
            'action' => 'Verificare i dati mancanti con la struttura competente',
        ]);
    }
}
