<?php

namespace App\Http\Controllers;

use App\Models\Pathology;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Throwable;

class VoiceSearchController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $request->validate(['audio' => ['required', 'file', 'max:6144']], [
            'audio.required' => 'Non è stata ricevuta alcuna registrazione.',
            'audio.max' => 'La registrazione è troppo lunga.',
        ]);
        $audio = $request->file('audio');
        $stream = fopen($audio->getRealPath(), 'r');
        try {
            $prompt = Pathology::query()
                ->get(['official_name', 'synonyms'])
                ->flatMap(fn (Pathology $pathology): array => [
                    $pathology->official_name,
                    ...collect($pathology->synonyms)->pluck('valore')->all(),
                ])
                ->filter()
                ->unique()
                ->implode('. ');
            $prompt = mb_substr($prompt.'. Patologie rare, termini medici italiani.', 0, 1400);

            $response = Http::attach('file', $stream, $audio->getClientOriginalName() ?: 'ricerca-vocale.webm', [
                'Content-Type' => $audio->getMimeType() ?: 'audio/webm',
            ])->connectTimeout(5)->timeout(90)->post(rtrim(config('services.whisper.url'), '/').'/inference', [
                'language' => 'it', 'response_format' => 'json', 'temperature' => '0.0', 'prompt' => $prompt,
            ]);
            if ($response->failed()) {
                return response()->json(['message' => 'Non sono riuscito a trascrivere la registrazione. Riprova parlando vicino al microfono.'], 503);
            }
            $transcript = trim((string) $response->json('text'));
            if ($transcript === '') {
                return response()->json(['message' => 'Non ho riconosciuto parole nella registrazione. Riprova in un ambiente più silenzioso.'], 422);
            }

            $query = $this->normalizeQuery($transcript);

            return response()->json([
                'transcript' => $transcript,
                'query' => $query,
            ]);
        } catch (ConnectionException) {
            return response()->json(['message' => 'Il servizio vocale locale non è disponibile in questo momento.'], 503);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json(['message' => 'Si è verificato un problema durante la trascrizione.'], 500);
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }
    }

    private function normalizeQuery(string $transcript): string
    {
        $query = preg_replace(
            '/^\s*(?:per favore\s+)?(?:sto cercando|cerco|cerca|cercami|trova|trovami|mostra|mostrami|dimmi|vorrei cercare|vorrei trovare|vorrei informazioni su|voglio cercare|voglio trovare|ho bisogno di informazioni su|mi servono informazioni su)\s+/iu',
            '',
            $transcript,
        ) ?? $transcript;

        $query = preg_replace(
            '/^\s*(?:(?:la|il|lo|una|un)\s+)?(?:patologia|malattia)\s+(?:(?:chiamata|denominata)\s+)?/iu',
            '',
            $query,
        ) ?? $query;

        return trim($query, " \t\n\r\0\x0B.,!?;:");
    }
}
