<?php

namespace App\Services\Ai;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class GroqService
{
    /**
     * @param  array<int, array{role:string, content:string}>  $messages
     * @return array{content:string, model:string, prompt_tokens:int, completion_tokens:int, total_tokens:int}
     */
    public function chat(array $messages): array
    {
        $apiKey = (string) config('services.groq.api_key');
        $model = (string) config('futbolix_ai.model');

        if ($apiKey === '') {
            throw new RuntimeException('GROQ_API_KEY no esta configurada.');
        }

        try {
            $payload = $this->payloadFor($model, $messages);

            Log::debug('Futbolix AI Groq request prepared', [
                'model' => $model,
                'messages' => count($payload['messages']),
                'payload_bytes' => strlen(json_encode($payload, JSON_UNESCAPED_UNICODE) ?: ''),
            ]);

            $response = Http::withToken($apiKey)
                ->acceptJson()
                ->timeout((int) config('futbolix_ai.timeout_seconds', 30))
                ->post(rtrim((string) config('services.groq.base_url'), '/').'/chat/completions', $payload)
                ->throw();
        } catch (RequestException $exception) {
            report($exception);

            $message = data_get($exception->response?->json(), 'error.message')
                ?: 'No se pudo obtener respuesta de Groq.';

            throw new RuntimeException((string) $message, previous: $exception);
        }

        $payload = $response->json();
        $content = data_get($payload, 'choices.0.message.content');

        if (!is_string($content) || trim($content) === '') {
            throw new RuntimeException('Groq devolvio una respuesta vacia.');
        }

        return [
            'content' => trim($content),
            'model' => (string) data_get($payload, 'model', config('futbolix_ai.model')),
            'prompt_tokens' => (int) data_get($payload, 'usage.prompt_tokens', 0),
            'completion_tokens' => (int) data_get($payload, 'usage.completion_tokens', 0),
            'total_tokens' => (int) data_get($payload, 'usage.total_tokens', 0),
        ];
    }

    private function supportsReasoningEffort(string $model): bool
    {
        return str_starts_with($model, 'openai/gpt-oss');
    }

    private function supportsBrowserSearchTool(string $model): bool
    {
        return str_starts_with($model, 'openai/gpt-oss');
    }

    /**
     * @param  array<int, array{role:string, content:string}>  $messages
     * @return array<string, mixed>
     */
    private function payloadFor(string $model, array $messages): array
    {
        if ($this->isCompoundSystem($model)) {
            return [
                'model' => $model,
                'messages' => $this->messagesForCompound($messages),
            ];
        }

        $payload = [
            'model' => $model,
            'temperature' => (float) config('futbolix_ai.temperature', 0.4),
            'max_completion_tokens' => (int) config('futbolix_ai.max_completion_tokens', 900),
            'messages' => $messages,
        ];

        if ($this->supportsReasoningEffort($model) && filled(config('futbolix_ai.reasoning_effort'))) {
            $payload['reasoning_effort'] = (string) config('futbolix_ai.reasoning_effort', 'low');
        }

        if ((bool) config('futbolix_ai.web_search_enabled', false) && $this->supportsBrowserSearchTool($model)) {
            $payload['tools'] = [
                [
                    'type' => (string) config('futbolix_ai.web_search_tool', 'browser_search'),
                ],
            ];
            $payload['tool_choice'] = (string) config('futbolix_ai.web_search_tool_choice', 'auto');
        }

        return $payload;
    }

    private function isCompoundSystem(string $model): bool
    {
        return str_starts_with($model, 'groq/compound');
    }

    /**
     * Compound systems work best with the minimal payload shown in Groq docs.
     *
     * @param  array<int, array{role:string, content:string}>  $messages
     * @return array<int, array{role:string, content:string}>
     */
    private function messagesForCompound(array $messages): array
    {
        $lastUserMessage = collect($messages)
            ->reverse()
            ->firstWhere('role', 'user');

        $question = is_array($lastUserMessage)
            ? (string) ($lastUserMessage['content'] ?? '')
            : '';

        return [
            [
                'role' => 'user',
                'content' => trim(
                    "Responde en espanol, de forma breve y directa. "
                    ."Si la pregunta requiere informacion actual, usa tu busqueda web integrada.\n\n"
                    ."Pregunta: {$question}"
                ),
            ],
        ];
    }
}
