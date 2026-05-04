<?php

namespace App\Http\Controllers\Ai;

use App\Http\Controllers\Controller;
use App\Http\Requests\Ai\SendAiMessageRequest;
use App\Models\Ai\Conversation;
use App\Services\Ai\AiChatService;
use App\Services\Ai\AiCreditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class AiConversationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return response()->json([
            'conversations' => $request->user()
                ->aiConversations()
                ->withCount('messages')
                ->latest('updated_at')
                ->get()
                ->map(fn (Conversation $conversation) => $this->serializeConversation($conversation)),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $conversation = Conversation::create([
            'user_id' => $request->user()->id,
            'title' => 'Nueva conversacion',
        ]);

        return response()->json([
            'conversation' => $this->serializeConversation($conversation),
        ], 201);
    }

    public function show(Request $request, Conversation $conversation): JsonResponse
    {
        abort_unless($conversation->user_id === $request->user()->id, 403);

        return response()->json([
            'conversation' => $this->serializeConversation($conversation),
            'messages' => $conversation->messages()
                ->get()
                ->map(fn ($message) => $this->serializeMessage($message)),
        ]);
    }

    public function send(
        SendAiMessageRequest $request,
        Conversation $conversation,
        AiChatService $chat,
    ): JsonResponse {
        abort_unless($conversation->user_id === $request->user()->id, 403);

        try {
            $result = $chat->send(
                $request->user(),
                $conversation,
                (string) $request->validated('message'),
            );
        } catch (RuntimeException $exception) {
            report($exception);

            return response()->json([
                'message' => config('app.debug')
                    ? 'Groq: '.$exception->getMessage()
                    : 'Futbolix AI no pudo responder ahora mismo. Revisa la configuracion de Groq o intentalo mas tarde.',
            ], 502);
        }

        return response()->json([
            'conversation' => $this->serializeConversation($result['conversation']),
            'user_message' => $this->serializeMessage($result['user_message']),
            'assistant_message' => $this->serializeMessage($result['assistant_message']),
            'plan' => $this->serializePlan($result['plan']),
            'usage' => $result['usage'],
        ]);
    }

    public function destroy(Request $request, Conversation $conversation): JsonResponse
    {
        abort_unless($conversation->user_id === $request->user()->id, 403);

        $conversation->delete();

        return response()->json(status: 204);
    }

    public function credits(Request $request, AiCreditService $credits): JsonResponse
    {
        return response()->json([
            'plan' => $this->serializePlan($credits->planFor($request->user())),
        ]);
    }

    private function serializeConversation(Conversation $conversation): array
    {
        return [
            'id' => $conversation->id,
            'title' => $conversation->title,
            'messages_count' => $conversation->messages_count ?? null,
            'updated_at' => $conversation->updated_at?->toIso8601String(),
        ];
    }

    private function serializeMessage($message): array
    {
        return [
            'id' => $message->id,
            'role' => $message->role,
            'content' => $message->content,
            'prompt_tokens' => $message->prompt_tokens,
            'completion_tokens' => $message->completion_tokens,
            'total_tokens' => $message->total_tokens,
            'credits_spent' => $message->credits_spent,
            'created_at' => $message->created_at?->toIso8601String(),
        ];
    }

    private function serializePlan($plan): array
    {
        return [
            'plan_name' => $plan->plan_name,
            'credits_balance' => $plan->credits_balance,
            'monthly_credit_limit' => $plan->monthly_credit_limit,
            'renewal_date' => $plan->renewal_date?->toDateString(),
        ];
    }
}
