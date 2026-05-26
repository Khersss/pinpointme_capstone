<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class TranslationService
{
    private string $apiBase = 'https://generativelanguage.googleapis.com/v1beta';
    private array $modelCandidates = ['gemini-2.5-flash-lite', 'gemini-2.0-flash', 'gemini-1.5-flash'];

    /**
     * Get Gemini API key
     */
    private function apiKey(): string
    {
        $key = config('services.gemini.key') ?: env('GEMINI_API_KEY');
        if (!$key) {
            throw new \Exception('Missing Gemini API key');
        }
        return $key;
    }

    /**
     * Make HTTP JSON request to Gemini API
     */
    private function geminiJson(array $payload, int $timeout = 60, ?string $model = null): array
    {
        $modelName = $model ?: config('services.gemini.model', $this->modelCandidates[0]);
        $resp = Http::timeout($timeout)
            ->acceptJson()
            ->asJson()
            ->post($this->apiBase . '/models/' . $modelName . ':generateContent?key=' . urlencode($this->apiKey()), $payload);

        if ($resp->failed()) {
            Log::warning('Gemini API error', [
                'model' => $modelName,
                'status' => $resp->status(),
                'body' => $resp->body()
            ]);
            throw new \Exception('Translation API error: ' . $resp->body());
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

    /**
     * Extract text content from a Gemini response.
     */
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

    private function geminiGenerateText(string $text, string $systemPrompt, int $timeout = 60): string
    {
        $data = $this->geminiJsonWithFallbacks([
            'systemInstruction' => [
                'parts' => [
                    ['text' => $systemPrompt],
                ],
            ],
            'contents' => [
                [
                    'role' => 'user',
                    'parts' => [
                        ['text' => $text],
                    ],
                ],
            ],
            'generationConfig' => [
                'temperature' => 0,
                'maxOutputTokens' => 512,
            ],
        ], $timeout);

        return $this->geminiText($data);
    }

    /**
     * Translate text to English using Gemini
     */
    public function translateToEnglish(string $text): string
    {
        if (empty(trim($text))) {
            return $text;
        }

        $cacheKey = 'translation_' . md5($text);

        return Cache::remember($cacheKey, 3600, function () use ($text) {
            try {
                $translated = $this->geminiGenerateText(
                    $text,
                    'You are a precise translation engine for emergency situations. Detect the language of the user text and output ONLY an accurate English translation. If the text is already English, output it unchanged. Preserve all important details about injuries, location, and urgency. No commentary or explanations.'
                );

                return $translated !== '' ? trim($translated) : $text;

            } catch (\Exception $e) {
                Log::error('Translation failed', [
                    'text' => $text,
                    'error' => $e->getMessage()
                ]);

                return $text;
            }
        });
    }

    /**
     * Translate multiple text fields
     */
    public function translateFields(array $fields): array
    {
        $translated = [];
        
        foreach ($fields as $key => $value) {
            if (!empty($value) && is_string($value)) {
                $translated[$key] = $this->translateToEnglish($value);
            } else {
                $translated[$key] = $value;
            }
        }
        
        return $translated;
    }

    /**
     * Check if text appears to be in English (simple heuristic)
     */
    public function isLikelyEnglish(string $text): bool
    {
        // Simple check - if over 80% of words are common English words
        $commonWords = [
            'the', 'is', 'at', 'which', 'on', 'and', 'a', 'to', 'this', 'be', 
            'has', 'have', 'it', 'in', 'of', 'for', 'not', 'with', 'he', 'as', 
            'you', 'do', 'will', 'can', 'if', 'no', 'man', 'up', 'her', 'all', 
            'any', 'may', 'say', 'she', 'or', 'an', 'are', 'his', 'your', 'how',
            'help', 'need', 'emergency', 'hurt', 'pain', 'blood', 'injury', 'fire',
            'stuck', 'trapped', 'fell', 'broken', 'stairs', 'bathroom', 'room',
            'floor', 'building', 'urgent', 'please', 'quickly', 'fast', 'now'
        ];
        
        $words = preg_split('/\s+/', strtolower(trim($text)));
        $totalWords = count($words);
        
        if ($totalWords === 0) {
            return true; // Empty text is "English"
        }
        
        $englishWords = 0;
        foreach ($words as $word) {
            $cleanWord = preg_replace('/[^a-z]/', '', $word);
            if (in_array($cleanWord, $commonWords)) {
                $englishWords++;
            }
        }
        
        return ($englishWords / $totalWords) > 0.6; // 60% threshold
    }
}