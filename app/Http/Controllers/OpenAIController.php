<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Models\Building;
use App\Models\Floor;
use App\Models\Room;
use Symfony\Component\HttpFoundation\Response;

class OpenAIController extends Controller
{
    private string $apiBase = 'https://generativelanguage.googleapis.com/v1beta';

    private array $modelCandidates = ['gemini-2.5-flash-lite', 'gemini-2.0-flash', 'gemini-1.5-flash'];

    private function apiKey(): string
    {
        $key = config('services.gemini.key') ?: env('GEMINI_API_KEY');
        if (!$key) {
            abort(Response::HTTP_INTERNAL_SERVER_ERROR, 'Missing Gemini API key');
        }

        return $key;
    }

    private function geminiJson(array $payload, int $timeout = 60, ?string $model = null): array
    {
        $modelName = $model ?: config('services.gemini.model', $this->modelCandidates[0]);
        $resp = Http::timeout($timeout)
            ->acceptJson()
            ->asJson()
            ->post($this->apiBase . '/models/' . $modelName . ':generateContent?key=' . urlencode($this->apiKey()), $payload);

        if ($resp->failed()) {
            Log::warning('Gemini API error', ['model' => $modelName, 'status' => $resp->status(), 'body' => $resp->body()]);
            abort($resp->status(), $resp->body());
        }

        return $resp->json();
    }

    private function geminiJsonWithFallbacks(array $payload, int $timeout = 60): array
    {
        $models = array_values(array_unique(array_merge(
            [config('services.gemini.model')],
            $this->modelCandidates
        )));
        $lastError = null;

        foreach ($models as $model) {
            if (!$model) {
                continue;
            }

            try {
                return $this->geminiJson($payload, $timeout, $model);
            } catch (\Throwable $e) {
                $lastError = $e;
                $message = $e->getMessage();
                if (!str_contains($message, '404') && !str_contains($message, 'not found')) {
                    throw $e;
                }

                Log::warning('Gemini model fallback triggered', [
                    'model' => $model,
                    'error' => $message,
                ]);
            }
        }

        if ($lastError) {
            throw $lastError;
        }

        throw new \RuntimeException('No Gemini model candidates were available');
    }

    private function geminiText(array $data): string
    {
        foreach (($data['candidates'] ?? []) as $candidate) {
            $parts = data_get($candidate, 'content.parts', []);
            $text = collect($parts)->pluck('text')->filter()->implode('');
            if (trim($text) !== '') {
                return trim($text);
            }
        }

        return '';
    }

    private function geminiJsonFromText(string $text): array
    {
        $clean = trim($text);
        $clean = preg_replace('/^```(?:json)?\s*|\s*```$/i', '', $clean) ?? $clean;
        $parsed = json_decode($clean, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException('Gemini returned invalid JSON: ' . json_last_error_msg());
        }

        return is_array($parsed) ? $parsed : [];
    }

    private function uploadAudioForTranscription(string $path, ?string $mimeType = null): array
    {
        $fileContents = Storage::get($path);
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $detectedMime = $mimeType ?: Storage::mimeType($path);
        $mimeType = $detectedMime ?: 'audio/webm';

        // Normalize common audio containers to audio/* to avoid Gemini treating them as video.
        if (str_starts_with($mimeType, 'video/') && in_array($ext, ['webm', 'ogg'], true)) {
            $mimeType = $ext === 'ogg' ? 'audio/ogg' : 'audio/webm';
        }

        Log::info('Gemini transcription input', [
            'path' => $path,
            'ext' => $ext,
            'detected_mime' => $detectedMime,
            'used_mime' => $mimeType,
        ]);

        return $this->geminiJsonWithFallbacks([
            'contents' => [
                [
                    'role' => 'user',
                    'parts' => [
                        ['text' => 'Transcribe the audio verbatim. Return only the transcript text. Do not summarize, translate, or add bullet points.'],
                        [
                            'inlineData' => [
                                'mimeType' => $mimeType,
                                'data' => base64_encode($fileContents),
                            ],
                        ],
                    ],
                ],
            ],
            'generationConfig' => [
                'temperature' => 0,
                'maxOutputTokens' => 2048,
            ],
        ], 120);
    }

    // POST /openai/transcribe (multipart: file|audio) or JSON { audio_path }
    public function transcribe(Request $request)
    {
        $apiKey = config('services.gemini.key') ?: env('GEMINI_API_KEY');
        if (!$apiKey) {
            Log::error('Gemini API key not configured');
            return response()->json([
                'error' => 'api_key_missing',
                'message' => 'Gemini API key is not configured. Please add GEMINI_API_KEY to your .env file.'
            ], 500);
        }

        $validator = Validator::make($request->all(), [
            'audio_path' => 'required_without:file|required_without:audio|string',
            'file' => 'required_without:audio_path|file|mimes:mp3,wav,webm,ogg,m4a|max:20480',
            'audio' => 'required_without:audio_path|file|mimes:mp3,wav,webm,ogg,m4a|max:20480',
        ]);
        if ($validator->fails()) {
            Log::warning('Transcription validation failed', ['errors' => $validator->errors()->toArray()]);
            return response()->json(['error' => 'validation_failed', 'messages' => $validator->errors()], 422);
        }

        $path = null;
        if ($request->hasFile('file')) {
            $path = $request->file('file')->store('temp-audio');
            Log::info('Audio file stored', ['path' => $path, 'original' => $request->file('file')->getClientOriginalName()]);
        } elseif ($request->hasFile('audio')) {
            $path = $request->file('audio')->store('temp-audio');
            Log::info('Audio file stored', ['path' => $path, 'original' => $request->file('audio')->getClientOriginalName()]);
        } else {
            $path = $request->input('audio_path');
        }

        if (!$path || !Storage::exists($path)) {
            Log::error('Audio file not found', ['path' => $path]);
            return response()->json(['error' => 'audio_not_found', 'detail' => $path], 404);
        }

        $activeModel = config('services.gemini.model', $this->modelCandidates[0]);

        try {
            Log::info('Attempting transcription with Gemini');
            $mimeType = $request->hasFile('file')
                ? $request->file('file')->getMimeType()
                : ($request->hasFile('audio') ? $request->file('audio')->getMimeType() : null);
            $json = $this->uploadAudioForTranscription($path, $mimeType);
            $text = $this->geminiText($json);
            if ($text === '') {
                $text = trim((string) ($json['text'] ?? $json['transcript'] ?? $json['transcription'] ?? $json['result'] ?? ''));
            }

            if ($text !== '') {
                if ($request->hasFile('file') || $request->hasFile('audio')) {
                    Storage::delete($path);
                }

                Log::info('Transcription successful with Gemini', ['text_length' => strlen($text)]);
                return response()->json(['model' => $activeModel, 'transcript' => $text, 'raw' => $json]);
            }

            $errors = [['model' => $activeModel, 'error' => 'No transcript returned by Gemini']];
        } catch (\Throwable $e) {
            $errorMsg = $e->getMessage();
            Log::warning('Transcription attempt failed', ['model' => $activeModel, 'error' => $errorMsg]);
            $errors = [['model' => $activeModel, 'error' => $errorMsg]];
        }

        if ($request->hasFile('file') || $request->hasFile('audio')) {
            Storage::delete($path);
        }

        Log::error('Transcription failed', ['errors' => $errors]);
        return response()->json([
            'error' => 'transcription_failed',
            'message' => 'Transcription failed. Check if your Gemini API key is valid.',
            'attempts' => $errors
        ], 500);
    }

    // POST /openai/translate { text }
    public function translate(Request $request)
    {
        $request->validate(['text' => 'required|string']);
        $data = $this->geminiJsonWithFallbacks([
            'systemInstruction' => [
                'parts' => [[
                    'text' => 'You are a precise translation engine. Detect the language of the user text and output ONLY an accurate English translation. If the text is already English, output it unchanged. No commentary.',
                ]],
            ],
            'contents' => [[
                'role' => 'user',
                'parts' => [[ 'text' => $request->input('text') ]],
            ]],
            'generationConfig' => [
                'temperature' => 0,
                'maxOutputTokens' => 512,
            ],
        ]);

        $translated = $this->geminiText($data) ?: $request->input('text');
        return response()->json(['translated' => trim($translated), 'raw' => $data]);
    }

    // Build catalog snippet for room list
    private function buildRoomCatalogSnippet(int $limit = 800): string
    {
        $lines = [];
        $buildings = Building::with(['floors.rooms'])->get();
        foreach ($buildings as $b) {
            foreach ($b->floors as $f) {
                foreach ($f->rooms as $r) {
                    $lines[] = implode('|', [
                        $r->id,
                        str_replace(['\n', '|'], ' ', $b->name ?? ''),
                        str_replace(['\n', '|'], ' ', $f->floor_name ?? $f->name ?? ''),
                        str_replace(['\n', '|'], ' ', $r->room_name ?? $r->name ?? ''),
                        $b->id,
                        $f->id,
                    ]);
                    if (count($lines) >= $limit) {
                        return implode("\n", $lines);
                    }
                }
            }
        }

        return implode("\n", $lines);
    }

    // Utilities for string similarity / inference
    private function tokenize(string $s): array
    {
        return array_values(array_filter(preg_split('/\s+/', strtolower(preg_replace('/[^a-z0-9\s]/', ' ', $s))) ?? []));
    }

    private function jaccard(array $a, array $b): float
    {
        $sa = array_unique($a);
        $sb = array_unique($b);
        if (!$sa || !$sb) {
            return 0;
        }
        $i = count(array_intersect($sa, $sb));
        $u = count($sa) + count($sb) - $i;
        return $u ? $i / $u : 0;
    }

    private function levenshteinScore(string $a, string $b): float
    {
        $a = trim(strtolower($a));
        $b = trim(strtolower($b));
        if ($a === $b) {
            return 1;
        }
        $lev = levenshtein($a, $b);
        $max = max(strlen($a), strlen($b)) ?: 1;
        return 1 - ($lev / $max);
    }

    private function stringSimilarity(string $a, string $b): float
    {
        if (!$a || !$b) {
            return 0;
        }
        $lev = $this->levenshteinScore($a, $b);
        $tok = $this->jaccard($this->tokenize($a), $this->tokenize($b));
        return ($lev * 0.6) + ($tok * 0.4);
    }

    private function inferLocation(string $transcript): ?array
    {
        $lower = strtolower($transcript);
        $buildings = Building::with(['floors.rooms'])->get();
        $flat = [];
        foreach ($buildings as $b) {
            foreach ($b->floors as $f) {
                foreach ($f->rooms as $r) {
                    $flat[] = [
                        'room_id' => $r->id,
                        'room_name' => $r->room_name ?? $r->name ?? '',
                        'floor_id' => $f->id,
                        'floor_name' => $f->floor_name ?? $f->name ?? '',
                        'building_id' => $b->id,
                        'building_name' => $b->name,
                    ];
                }
            }
        }
        $best = null;
        foreach ($flat as $row) {
            $roomNameLower = strtolower($row['room_name']);
            $direct = str_contains($lower, $roomNameLower);
            $fuzzy = $this->stringSimilarity($transcript, $row['room_name']);
            $score = $direct ? max($fuzzy, 0.9) : $fuzzy * 0.75;
            if (!$best || $score > $best['score']) {
                $best = ['score' => $score, 'data' => $row, 'reason' => $direct ? 'direct' : 'fuzzy'];
            }
        }
        if (!$best || $best['score'] < 0.55) {
            return null;
        }

        return [
            'building_id' => $best['data']['building_id'],
            'floor_id' => $best['data']['floor_id'],
            'room_id' => $best['data']['room_id'],
            'building_name_match' => $best['data']['building_name'],
            'floor_name_match' => $best['data']['floor_name'],
            'room_name_match' => $best['data']['room_name'],
            'confidence' => round($best['score'], 3),
            'details' => $best['reason'] . ':' . number_format($best['score'], 2),
        ];
    }
    public function extract(Request $request)
    {
        $request->validate(['transcript' => 'required|string']);
        $transcript = $request->input('transcript');

        $translatedResp = $this->translate(new Request(['text' => $transcript]));
        $translatedJson = $translatedResp->getData(true);
        $english = $translatedJson['translated'] ?? $transcript;

        $catalog = $this->buildRoomCatalogSnippet();

        $systemPrompt = "You are an assistant extracting structured emergency request fields AND inferring the most likely location (room).\nReturn ONLY strict JSON with these keys: description, mobility_status, injuries, other_injury, urgency_level, additional_info, room_id, floor_id, building_id, room_name, floor_name, building_name, room_selection_confidence.\nRules:\n1) mobility_status must be one of: normal, limited, immobile, unknown, ''.\n2) urgency_level must be one of: low, medium, high, critical, ''.\n3) injuries must be an ARRAY using only: none, bleeding, fracture, burn, head, breathing, unconscious, chest_pain, seizure, allergic, other. Use [] when unknown.\n4) other_injury must be non-empty only when injuries contains 'other'.\n5) Do not guess unsupported values; use empty values when uncertain.\n6) Description should keep incident facts concise and faithful to transcript.\n7) Location IDs must come from catalog rows only.\nLOCATION SELECTION TASK:\nCatalog lines: roomId|BuildingName|FloorName|RoomName|BuildingId|FloorId\nCatalog (may be truncated):\n$catalog\nConfidence rules: 0.9+ explicit; 0.6-0.89 partial; <0.55 insufficient (then blank IDs).";

        $userPrompt = 'English Transcript: "' . $english . '"';

        $data = $this->geminiJsonWithFallbacks([
            'systemInstruction' => [
                'parts' => [[ 'text' => $systemPrompt ]],
            ],
            'contents' => [[
                'role' => 'user',
                'parts' => [[ 'text' => $userPrompt ]],
            ]],
            'generationConfig' => [
                'temperature' => 0.1,
                'maxOutputTokens' => 2048,
                'responseMimeType' => 'application/json',
            ],
        ], 120);

        $content = $this->geminiText($data) ?: '{}';
        $parsed = $this->normalizeExtractedFields($this->geminiJsonFromText($content));
        if (!empty($parsed['room_selection_confidence']) && $parsed['room_selection_confidence'] < 0.55) {
            $parsed['room_id'] = $parsed['floor_id'] = $parsed['building_id'] = '';
            $parsed['room_name'] = $parsed['floor_name'] = $parsed['building_name'] = '';
            $parsed['room_selection_confidence'] = 0;
        }

        return response()->json([
            'fields' => $parsed,
            'translated' => $english,
            'raw_api' => $data,
        ]);
    }

    // POST /openai/extract-full { transcript }
    public function extractFull(Request $request)
    {
        $request->validate(['transcript' => 'required|string']);
        $transcript = $request->input('transcript');
        $extract = $this->extract(new Request(['transcript' => $transcript]));
        $extractData = $extract->getData(true);

        $lower = strtolower($transcript);
        $raw_building_hint = $this->regexFirst($lower, '/\b([a-z0-9\- ]+?) building\b/');
        $raw_room_hint = $this->regexFirst($lower, '/room\s+([a-z0-9\-]+)/');
        $raw_floor_hint = $this->regexFirst($lower, '/(\b\d+(st|nd|rd|th) floor\b|\b(first|second|third|fourth|fifth|sixth|seventh|eighth|ninth|tenth) floor\b)/');

        $fields = $extractData['fields'] ?? [];
        $loc = null;
        if (!empty($fields['room_id']) && ($fields['room_selection_confidence'] ?? 0) >= 0.55) {
            $loc = [
                'building_id' => $fields['building_id'] ?? null,
                'floor_id' => $fields['floor_id'] ?? null,
                'room_id' => $fields['room_id'] ?? null,
                'building_name_match' => $fields['building_name'] ?? null,
                'floor_name_match' => $fields['floor_name'] ?? null,
                'room_name_match' => $fields['room_name'] ?? null,
                'confidence' => $fields['room_selection_confidence'] ?? 0,
                'details' => 'ai-selection'
            ];
        } else {
            $loc = $this->inferLocation($transcript);
        }

        return response()->json([
            'transcript' => $transcript,
            'fields' => $fields,
            'raw_building_hint' => $raw_building_hint,
            'raw_floor_hint' => $raw_floor_hint,
            'raw_room_hint' => $raw_room_hint,
            'location_inference' => $loc,
        ]);
    }

    private function normalizeExtractedFields(array $parsed): array
    {
        $normalized = [
            'description' => trim((string) ($parsed['description'] ?? '')),
            'mobility_status' => '',
            'injuries' => [],
            'other_injury' => trim((string) ($parsed['other_injury'] ?? '')),
            'urgency_level' => '',
            'additional_info' => trim((string) ($parsed['additional_info'] ?? '')),
            'room_id' => (string) ($parsed['room_id'] ?? ''),
            'floor_id' => (string) ($parsed['floor_id'] ?? ''),
            'building_id' => (string) ($parsed['building_id'] ?? ''),
            'room_name' => trim((string) ($parsed['room_name'] ?? '')),
            'floor_name' => trim((string) ($parsed['floor_name'] ?? '')),
            'building_name' => trim((string) ($parsed['building_name'] ?? '')),
            'room_selection_confidence' => (float) ($parsed['room_selection_confidence'] ?? 0),
        ];

        $mobilityMap = [
            'mobile' => 'normal',
            'normal' => 'normal',
            'can_walk' => 'normal',
            'limited' => 'limited',
            'limited_mobility' => 'limited',
            'immobile' => 'immobile',
            'cannot_move' => 'immobile',
            'unknown' => 'unknown',
            '' => '',
        ];
        $mobilityRaw = strtolower(trim((string) ($parsed['mobility_status'] ?? '')));
        $normalized['mobility_status'] = $mobilityMap[$mobilityRaw] ?? '';

        $urgencyMap = [
            'low' => 'low',
            'medium' => 'medium',
            'high' => 'high',
            'urgent' => 'high',
            'critical' => 'critical',
            'severe' => 'critical',
            '' => '',
        ];
        $urgencyRaw = strtolower(trim((string) ($parsed['urgency_level'] ?? '')));
        $normalized['urgency_level'] = $urgencyMap[$urgencyRaw] ?? '';

        $allowedInjuries = ['none', 'bleeding', 'fracture', 'burn', 'head', 'breathing', 'unconscious', 'chest_pain', 'seizure', 'allergic', 'other'];
        $injuryAliases = [
            'head_injury' => 'head',
            'breathing_difficulty' => 'breathing',
            'chest pain' => 'chest_pain',
            'chest-pain' => 'chest_pain',
            'allergy' => 'allergic',
        ];

        $injuriesRaw = $parsed['injuries'] ?? [];
        if (is_string($injuriesRaw)) {
            $injuriesRaw = preg_split('/[,|]/', $injuriesRaw) ?: [];
        }
        if (!is_array($injuriesRaw)) {
            $injuriesRaw = [];
        }

        $cleanInjuries = [];
        foreach ($injuriesRaw as $injury) {
            $value = strtolower(trim((string) $injury));
            if ($value === '') {
                continue;
            }
            $value = $injuryAliases[$value] ?? $value;
            if (in_array($value, $allowedInjuries, true)) {
                $cleanInjuries[] = $value;
            }
        }
        $normalized['injuries'] = array_values(array_unique($cleanInjuries));

        if (!in_array('other', $normalized['injuries'], true)) {
            $normalized['other_injury'] = '';
        }

        foreach (['room_id', 'floor_id', 'building_id'] as $idField) {
            if ($normalized[$idField] !== '' && !ctype_digit($normalized[$idField])) {
                $normalized[$idField] = '';
            }
        }

        $normalized['room_selection_confidence'] = max(0, min(1, $normalized['room_selection_confidence']));

        return $normalized;
    }

    private function regexFirst(string $text, string $pattern): ?string
    {
        if (preg_match($pattern, $text, $m)) {
            return $m[1] ?? $m[0];
        }
        return null;
    }
}
