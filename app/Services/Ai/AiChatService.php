<?php

namespace App\Services\Ai;

use App\Models\Ai\Conversation;
use App\Models\Ai\Message;
use App\Models\Ai\UsageLog;
use App\Models\Ai\UserPlan;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class AiChatService
{
    public function __construct(
        private readonly GroqService $groq,
        private readonly AiCreditService $credits,
        private readonly AiBusinessContextService $businessContext,
    ) {
    }

    /**
     * @return array{conversation:Conversation,user_message:Message,assistant_message:Message,plan:UserPlan,usage:array<string,int|string>}
     */
    public function send(User $user, Conversation $conversation, string $content): array
    {
        $plan = $this->credits->planFor($user);
        $this->credits->assertHasCredits($plan, $this->credits->estimateCreditsForMessage($content));

        $userMessage = Message::create([
            'conversation_id' => $conversation->id,
            'role' => Message::ROLE_USER,
            'content' => $content,
        ]);

        if ($conversation->messages()->where('role', Message::ROLE_USER)->count() === 1) {
            $conversation->update(['title' => $this->titleFromPrompt($content)]);
        }

        $groqResponse = $this->groq->chat($this->messagesForGroq($user, $conversation));
        $creditsSpent = $this->credits->creditsForTokens($groqResponse['total_tokens']);

        return DB::transaction(function () use ($user, $conversation, $userMessage, $groqResponse, $creditsSpent) {
            $lockedPlan = UserPlan::query()
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->firstOrFail();

            $this->credits->assertHasCredits($lockedPlan, $creditsSpent);

            $assistantMessage = Message::create([
                'conversation_id' => $conversation->id,
                'role' => Message::ROLE_ASSISTANT,
                'content' => $groqResponse['content'],
                'prompt_tokens' => $groqResponse['prompt_tokens'],
                'completion_tokens' => $groqResponse['completion_tokens'],
                'total_tokens' => $groqResponse['total_tokens'],
                'credits_spent' => $creditsSpent,
            ]);

            $lockedPlan = $this->credits->deduct($lockedPlan, $creditsSpent);

            UsageLog::create([
                'user_id' => $user->id,
                'conversation_id' => $conversation->id,
                'model_used' => $groqResponse['model'],
                'prompt_tokens' => $groqResponse['prompt_tokens'],
                'completion_tokens' => $groqResponse['completion_tokens'],
                'total_tokens' => $groqResponse['total_tokens'],
                'credits_spent' => $creditsSpent,
                'request_type' => 'chat',
            ]);

            $conversation->touch();

            return [
                'conversation' => $conversation->fresh(),
                'user_message' => $userMessage->fresh(),
                'assistant_message' => $assistantMessage,
                'plan' => $lockedPlan,
                'usage' => [
                    'model' => $groqResponse['model'],
                    'prompt_tokens' => $groqResponse['prompt_tokens'],
                    'completion_tokens' => $groqResponse['completion_tokens'],
                    'total_tokens' => $groqResponse['total_tokens'],
                    'credits_spent' => $creditsSpent,
                ],
            ];
        });
    }

    /**
     * @return array<int, array{role:string, content:string}>
     */
    private function messagesForGroq(User $user, Conversation $conversation): array
    {
        $latestUserMessage = $conversation->messages()
            ->where('role', Message::ROLE_USER)
            ->latest()
            ->first();

        $messages = [
            [
                'role' => Message::ROLE_SYSTEM,
                'content' => $this->truncateSystemPrompt($this->systemPrompt($user, $latestUserMessage?->content)),
            ],
        ];

        if (!$latestUserMessage) {
            return $messages;
        }

        if (! (bool) config('futbolix_ai.include_conversation_history', false)) {
            $messages[] = [
                'role' => Message::ROLE_USER,
                'content' => $this->truncateForContext($latestUserMessage->content),
            ];

            return $messages;
        }

        $history = $conversation->messages()
            ->latest()
            ->limit((int) config('futbolix_ai.max_context_messages', 16))
            ->get()
            ->reverse()
            ->values();

        foreach ($history as $message) {
            $messages[] = [
                'role' => $message->role,
                'content' => $this->truncateForContext($message->content),
            ];
        }

        return $messages;
    }

    private function systemPrompt(User $user, ?string $question = null): string
    {
        return <<<PROMPT
Eres Futbolix AI, un asistente especializado en futbol, estadisticas y gestion de torneos dentro de la aplicacion Futbolix.

Reglas principales:
- Responde en espanol por defecto.
- Se claro, util y estructurado.
- Ayuda a interpretar datos futbolisticos, comparar equipos, redactar previas, cronicas, resumenes, reglas y recomendaciones de formato de torneo.
- No inventes datos si no estan disponibles.
- Si faltan datos, indicalo explicitamente y explica que informacion haria falta.
- Prioriza la informacion real almacenada en la base de datos de Futbolix cuando exista.
- La aplicacion tiene datos locales de las 5 grandes ligas procedentes de la API de futbol.
- No uses Internet. Responde usando el contexto de Futbolix que se incluye abajo.
- Para preguntas simples de actualidad, responde de forma breve y directa, evitando explicaciones largas.
- Cuando el usuario pregunte por estadisticas, usa un tono analitico.
- Cuando el usuario pida textos generados, usa un tono natural y profesional.
- Si el usuario pide una accion que requiere modificar datos, explica los pasos; no afirmes que has modificado la aplicacion.

Contexto de negocio disponible:
{$this->businessContext->buildFor($user, $question)}

Si te preguntan por algo que no aparece en el contexto, di claramente que Futbolix no tiene ese dato actualizado en la base de datos local.
PROMPT;
    }

    private function truncateForContext(string $content): string
    {
        $limit = max(300, (int) config('futbolix_ai.max_message_context_chars', 800));

        if (mb_strlen($content) <= $limit) {
            return $content;
        }

        return mb_substr($content, 0, $limit)."\n\n[Contenido recortado para no superar el limite de contexto.]";
    }

    private function truncateSystemPrompt(string $content): string
    {
        $limit = max(700, (int) config('futbolix_ai.max_system_context_chars', 1200));

        if (mb_strlen($content) <= $limit) {
            return $content;
        }

        return mb_substr($content, 0, $limit)."\n\n[Contexto de sistema recortado.]";
    }

    private function titleFromPrompt(string $prompt): string
    {
        $title = trim(preg_replace('/\s+/', ' ', $prompt) ?? $prompt);

        return mb_strlen($title) > 54 ? mb_substr($title, 0, 51).'...' : $title;
    }
}
