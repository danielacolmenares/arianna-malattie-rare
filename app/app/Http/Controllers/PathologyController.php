<?php

namespace App\Http\Controllers;

use App\Models\Pathology;
use App\Models\SearchEvent;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\SvgWriter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PathologyController extends Controller
{
    public function index(Request $request): View
    {
        $query = trim($request->string('q')->toString());
        $pathologies = $query === ''
            ? Pathology::query()->where('publication_status', 'published')->orderBy('official_name')->get()
            : Pathology::search($query)->take(24)->get()->filter->isPublished()->values();

        if ($query !== '') {
            $recordedQuery = Str::limit($query, 200, '');
            SearchEvent::query()->create([
                'query' => $recordedQuery,
                'normalized_query' => Str::lower(preg_replace('/\s+/u', ' ', $recordedQuery) ?? $recordedQuery),
                'result_count' => $pathologies->count(),
            ]);
        }

        return view('pathologies.index', compact('pathologies', 'query'));
    }

    public function suggestions(Request $request): JsonResponse
    {
        $query = trim($request->string('q')->limit(100)->toString());

        if (mb_strlen($query) < 2) {
            return response()->json(['suggestions' => []]);
        }

        $suggestions = Pathology::search($query)->take(12)->get()
            ->filter->isPublished()
            ->take(6)
            ->map(fn (Pathology $pathology): array => [
                'name' => $pathology->official_name,
                'code' => $pathology->source_id,
                'classification' => data_get($pathology->classifications, '0.valore'),
                'url' => route('pathologies.show', $pathology),
            ])->values();

        return response()->json(['suggestions' => $suggestions]);
    }

    public function show(Pathology $pathology): View
    {
        abort_unless($pathology->isPublished(), 404);

        return view('pathologies.show', ['pathology' => $pathology]);
    }

    public function qr(Request $request, Pathology $pathology): Response
    {
        abort_unless($pathology->isPublished(), 404);

        $url = route('pathologies.show', $pathology);
        $result = (new SvgWriter)->write(new QrCode(
            data: $url,
            errorCorrectionLevel: ErrorCorrectionLevel::Medium,
            size: 600,
            margin: 32,
        ));

        $disposition = $request->boolean('download') ? 'attachment' : 'inline';
        $filename = 'arianna-'.$pathology->slug.'.svg';

        return response($result->getString(), 200, [
            'Content-Type' => 'image/svg+xml; charset=UTF-8',
            'Content-Disposition' => $disposition.'; filename="'.$filename.'"',
            'Cache-Control' => 'public, max-age=3600',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
