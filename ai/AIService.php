<?php
/**
 * AIService.php — Unified AI Client
 * Supports OpenAI GPT and Google Gemini via HTTP REST.
 * API keys are loaded server-side only — never exposed to frontend.
 */

namespace Nucleus\AI;

class AIService
{
    private string $provider;
    private string $apiKey;
    private string $model;
    private float  $temperature;
    private int    $maxTokens;

    public function __construct()
    {
        $this->provider    = getenv('AI_PROVIDER') ?: 'gemini';
        $this->apiKey      = $this->provider === 'openai'
            ? (getenv('OPENAI_API_KEY') ?: '')
            : (getenv('GEMINI_API_KEY') ?: '');
        $this->model       = getenv('AI_MODEL') ?: ($this->provider === 'openai' ? 'gpt-4o-mini' : 'gemini-1.5-flash');
        $this->temperature = (float)(getenv('AI_TEMPERATURE') ?: 0.7);
        $this->maxTokens   = (int)(getenv('AI_MAX_TOKENS') ?: 1024);
    }

    /**
     * Send a chat completion request.
     * @param array  $messages  [{role:'system|user|assistant', content:'...'}]
     * @param bool   $stream    Whether to stream SSE output
     * @return string|null      Full response text (if not streaming)
     */
    public function chat(array $messages, bool $stream = false): ?string
    {
        if (empty($this->apiKey)) {
            return null; // Offline fallback — caller handles
        }

        return match ($this->provider) {
            'openai' => $this->chatOpenAI($messages, $stream),
            'gemini' => $this->chatGemini($messages, $stream),
            default  => null,
        };
    }

    // ── OpenAI ────────────────────────────────────────────────────────────────
    private function chatOpenAI(array $messages, bool $stream): ?string
    {
        $url     = 'https://api.openai.com/v1/chat/completions';
        $payload = [
            'model'       => $this->model,
            'messages'    => $messages,
            'temperature' => $this->temperature,
            'max_tokens'  => $this->maxTokens,
            'stream'      => $stream,
        ];

        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->apiKey,
        ];

        if ($stream) {
            $this->streamRequest($url, $headers, $payload, 'openai');
            return null;
        }

        $response = $this->httpPost($url, $headers, $payload);
        if (!$response) return null;

        $data = json_decode($response, true);
        return $data['choices'][0]['message']['content'] ?? null;
    }

    // ── Gemini ────────────────────────────────────────────────────────────────
    private function chatGemini(array $messages, bool $stream): ?string
    {
        // Convert OpenAI-format messages to Gemini format
        $contents = [];
        $systemPrompt = '';

        foreach ($messages as $msg) {
            if ($msg['role'] === 'system') {
                $systemPrompt = $msg['content'];
                continue;
            }
            $role = $msg['role'] === 'assistant' ? 'model' : 'user';
            $contents[] = ['role' => $role, 'parts' => [['text' => $msg['content']]]];
        }

        $streamParam = $stream ? ':streamGenerateContent?alt=sse' : ':generateContent';
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$this->model}{$streamParam}?key={$this->apiKey}";

        $payload = [
            'contents'          => $contents,
            'generationConfig'  => [
                'temperature'   => $this->temperature,
                'maxOutputTokens' => $this->maxTokens,
            ],
        ];

        if ($systemPrompt) {
            $payload['systemInstruction'] = ['parts' => [['text' => $systemPrompt]]];
        }

        $headers = ['Content-Type: application/json'];

        if ($stream) {
            $this->streamRequest($url, $headers, $payload, 'gemini');
            return null;
        }

        $response = $this->httpPost($url, $headers, $payload);
        if (!$response) return null;

        $data = json_decode($response, true);
        return $data['candidates'][0]['content']['parts'][0]['text'] ?? null;
    }

    // ── Streaming SSE ──────────────────────────────────────────────────────────
    private function streamRequest(string $url, array $headers, array $payload, string $provider): void
    {
        // Set SSE headers
        if (!headers_sent()) {
            header('Content-Type: text/event-stream');
            header('Cache-Control: no-cache');
            header('X-Accel-Buffering: no');
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_RETURNTRANSFER => false,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT        => 60,
            CURLOPT_WRITEFUNCTION  => function ($ch, $chunk) use ($provider) {
                // Parse and forward each SSE chunk
                $lines = explode("\n", $chunk);
                foreach ($lines as $line) {
                    $line = trim($line);
                    if (!str_starts_with($line, 'data:')) continue;
                    $raw = trim(substr($line, 5));
                    if ($raw === '[DONE]') {
                        echo "event: done\ndata: [DONE]\n\n";
                        ob_flush(); flush();
                        continue;
                    }
                    $parsed = json_decode($raw, true);
                    $text   = match ($provider) {
                        'openai' => $parsed['choices'][0]['delta']['content'] ?? null,
                        'gemini' => $parsed['candidates'][0]['content']['parts'][0]['text'] ?? null,
                        default  => null,
                    };
                    if ($text !== null) {
                        echo 'data: ' . json_encode(['text' => $text]) . "\n\n";
                        ob_flush(); flush();
                    }
                }
                return strlen($chunk);
            },
        ]);
        curl_exec($ch);
        curl_close($ch);
    }

    // ── HTTP POST helper ──────────────────────────────────────────────────────
    private function httpPost(string $url, array $headers, array $payload): ?string
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_FOLLOWLOCATION => true,
        ]);
        $response = curl_exec($ch);
        $errNo    = curl_errno($ch);
        curl_close($ch);

        if ($errNo || !$response) {
            error_log("[AIService] cURL error #{$errNo}");
            return null;
        }
        return $response;
    }

    public function getProvider(): string { return $this->provider; }
    public function hasKey(): bool        { return !empty($this->apiKey); }
}
