<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import axios from 'axios';
import { Bot, Loader2, MessageSquare, Plus, Send, Sparkles, Trash2, UserRound, Zap } from 'lucide-vue-next';
import { computed, nextTick, onMounted, ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

type Conversation = {
    id: number;
    title: string;
    messages_count: number | null;
    updated_at: string | null;
};

type Message = {
    id: number;
    role: 'user' | 'assistant' | 'system';
    content: string;
    prompt_tokens: number | null;
    completion_tokens: number | null;
    total_tokens: number | null;
    credits_spent: number;
    created_at: string | null;
};

type Plan = {
    plan_name: 'free' | 'pro';
    credits_balance: number;
    monthly_credit_limit: number;
    renewal_date: string | null;
};

const props = defineProps<{
    initialConversations: Conversation[];
    plan: Plan;
    creditRules: {
        tokens_per_credit: number;
        minimum_credits_per_message: number;
        estimated_tokens_per_message: number;
    };
}>();

const conversations = ref<Conversation[]>([...props.initialConversations]);
const currentConversation = ref<Conversation | null>(conversations.value[0] ?? null);
const messages = ref<Message[]>([]);
const plan = ref<Plan>(props.plan);
const prompt = ref('');
const loadingConversation = ref(false);
const sending = ref(false);
const error = ref<string | null>(null);
const messageList = ref<HTMLElement | null>(null);

const suggestedPrompts = [
    'Resume mis torneos activos y dime que deberia revisar primero.',
    'Recomiendame el mejor formato para un torneo de 12 equipos.',
    'Ayudame a redactar reglas claras para una competicion amateur.',
    'Genera una previa profesional para un partido equilibrado.',
];

const planLabel = computed(() => (plan.value.plan_name === 'pro' ? 'Pro' : 'Free'));
const creditPercentage = computed(() => {
    if (plan.value.monthly_credit_limit <= 0) {
        return 0;
    }

    return Math.min(100, Math.round((plan.value.credits_balance / plan.value.monthly_credit_limit) * 100));
});

const estimatedCredits = computed(() => {
    const estimatedTokens = Math.max(
        Math.ceil(prompt.value.length / 4),
        props.creditRules.estimated_tokens_per_message,
    );

    return Math.max(
        props.creditRules.minimum_credits_per_message,
        Math.ceil(estimatedTokens / props.creditRules.tokens_per_credit),
    );
});

async function scrollToBottom() {
    await nextTick();
    messageList.value?.scrollTo({
        top: messageList.value.scrollHeight,
        behavior: 'smooth',
    });
}

async function refreshConversations() {
    const response = await axios.get('/api/futbolix-ai/conversations');
    conversations.value = response.data.conversations;
}

async function loadConversation(conversation: Conversation) {
    currentConversation.value = conversation;
    loadingConversation.value = true;
    error.value = null;

    try {
        const response = await axios.get(`/api/futbolix-ai/conversations/${conversation.id}`);
        currentConversation.value = response.data.conversation;
        messages.value = response.data.messages;
        await scrollToBottom();
    } catch (exception) {
        console.error(exception);
        error.value = 'No se pudo abrir la conversacion.';
    } finally {
        loadingConversation.value = false;
    }
}

async function createConversation() {
    error.value = null;

    try {
        const response = await axios.post('/api/futbolix-ai/conversations');
        conversations.value = [response.data.conversation, ...conversations.value];
        await loadConversation(response.data.conversation);
    } catch (exception) {
        console.error(exception);
        error.value = 'No se pudo crear una nueva conversacion.';
    }
}

async function deleteConversation(conversation: Conversation) {
    error.value = null;

    try {
        await axios.delete(`/api/futbolix-ai/conversations/${conversation.id}`);
        conversations.value = conversations.value.filter((item) => item.id !== conversation.id);

        if (currentConversation.value?.id === conversation.id) {
            currentConversation.value = conversations.value[0] ?? null;
            messages.value = [];

            if (currentConversation.value) {
                await loadConversation(currentConversation.value);
            }
        }
    } catch (exception) {
        console.error(exception);
        error.value = 'No se pudo borrar la conversacion.';
    }
}

function useSuggestion(value: string) {
    prompt.value = value;
}

async function sendMessage() {
    const content = prompt.value.trim();

    if (!content || sending.value) {
        return;
    }

    if (!currentConversation.value) {
        await createConversation();
    }

    if (!currentConversation.value) {
        return;
    }

    prompt.value = '';
    sending.value = true;
    error.value = null;

    const optimisticMessage: Message = {
        id: Date.now(),
        role: 'user',
        content,
        prompt_tokens: null,
        completion_tokens: null,
        total_tokens: null,
        credits_spent: 0,
        created_at: new Date().toISOString(),
    };

    messages.value.push(optimisticMessage);
    await scrollToBottom();

    try {
        const response = await axios.post(
            `/api/futbolix-ai/conversations/${currentConversation.value.id}/messages`,
            { message: content },
        );

        messages.value = messages.value.filter((message) => message.id !== optimisticMessage.id);
        messages.value.push(response.data.user_message, response.data.assistant_message);
        plan.value = response.data.plan;
        currentConversation.value = response.data.conversation;
        await refreshConversations();
        await scrollToBottom();
    } catch (exception: unknown) {
        console.error(exception);
        messages.value = messages.value.filter((message) => message.id !== optimisticMessage.id);
        error.value = errorMessageFromException(exception);
    } finally {
        sending.value = false;
    }
}

function errorMessageFromException(exception: unknown) {
    if (axios.isAxiosError(exception)) {
        const data = exception.response?.data as {
            message?: string;
            errors?: Record<string, string[]>;
        } | undefined;

        return data?.message || data?.errors?.credits?.[0] || 'No se pudo enviar el mensaje.';
    }

    return 'No se pudo enviar el mensaje.';
}

function formatDate(value: string | null) {
    if (!value) {
        return 'Sin actividad';
    }

    return new Intl.DateTimeFormat('es-ES', {
        day: '2-digit',
        month: 'short',
        hour: '2-digit',
        minute: '2-digit',
    }).format(new Date(value));
}

onMounted(async () => {
    if (currentConversation.value) {
        await loadConversation(currentConversation.value);
    }
});
</script>

<template>
    <Head title="Futbolix AI" />

    <AuthenticatedLayout>
        <div class="h-[calc(100vh-4rem)] bg-slate-100 text-slate-950 dark:bg-[#0f172a] dark:text-white">
            <div class="mx-auto flex h-full max-w-7xl flex-col gap-4 px-4 py-4 lg:flex-row lg:px-6">
                <aside class="flex w-full flex-col rounded-lg border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-futbolix-dark lg:w-80">
                    <div class="border-b border-slate-200 p-4 dark:border-slate-700">
                        <button
                            type="button"
                            class="inline-flex w-full items-center justify-center gap-2 rounded-md bg-futbolix-green px-4 py-3 text-sm font-semibold text-white transition hover:bg-futbolix-green-dark focus:outline-none focus:ring-2 focus:ring-futbolix-green focus:ring-offset-2"
                            @click="createConversation"
                        >
                            <Plus class="h-4 w-4" />
                            Nueva conversacion
                        </button>
                    </div>

                    <div class="space-y-3 border-b border-slate-200 p-4 dark:border-slate-700">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-xs font-semibold uppercase text-slate-500 dark:text-slate-400">Plan actual</p>
                                <p class="text-lg font-bold">{{ planLabel }}</p>
                            </div>
                            <div class="flex h-10 w-10 items-center justify-center rounded-md bg-futbolix-gold/15 text-futbolix-gold">
                                <Zap class="h-5 w-5" />
                            </div>
                        </div>

                        <div>
                            <div class="mb-2 flex items-center justify-between text-sm">
                                <span class="text-slate-500 dark:text-slate-400">Creditos</span>
                                <span class="font-semibold">{{ plan.credits_balance }} / {{ plan.monthly_credit_limit }}</span>
                            </div>
                            <div class="h-2 overflow-hidden rounded-full bg-slate-200 dark:bg-slate-700">
                                <div
                                    class="h-full rounded-full bg-futbolix-green transition-all"
                                    :style="{ width: `${creditPercentage}%` }"
                                />
                            </div>
                        </div>
                    </div>

                    <div class="min-h-0 flex-1 overflow-y-auto p-3">
                        <p class="px-2 pb-2 text-xs font-semibold uppercase text-slate-500 dark:text-slate-400">
                            Historial
                        </p>

                        <div
                            v-for="conversation in conversations"
                            :key="conversation.id"
                            class="group mb-1 flex w-full items-start gap-2 rounded-md transition hover:bg-slate-100 dark:hover:bg-slate-800/70"
                            :class="currentConversation?.id === conversation.id ? 'bg-futbolix-green/10 text-futbolix-green dark:bg-futbolix-green/15' : 'text-slate-700 dark:text-slate-200'"
                        >
                            <button
                                type="button"
                                class="flex min-w-0 flex-1 items-start gap-3 px-3 py-3 text-left"
                                @click="loadConversation(conversation)"
                            >
                                <MessageSquare class="mt-0.5 h-4 w-4 shrink-0" />
                                <span class="min-w-0 flex-1">
                                    <span class="block truncate text-sm font-semibold">{{ conversation.title }}</span>
                                    <span class="block text-xs text-slate-500 dark:text-slate-400">
                                        {{ formatDate(conversation.updated_at) }}
                                    </span>
                                </span>
                            </button>
                            <button
                                type="button"
                                class="mr-2 mt-3 rounded p-1 text-slate-400 opacity-0 transition hover:bg-red-500/10 hover:text-red-500 group-hover:opacity-100"
                                title="Borrar conversacion"
                                @click="deleteConversation(conversation)"
                            >
                                <Trash2 class="h-4 w-4" />
                            </button>
                        </div>

                        <div v-if="conversations.length === 0" class="px-3 py-10 text-center text-sm text-slate-500 dark:text-slate-400">
                            Todavia no tienes conversaciones.
                        </div>
                    </div>
                </aside>

                <main class="flex min-h-0 flex-1 flex-col overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-futbolix-dark">
                    <header class="border-b border-slate-200 px-5 py-4 dark:border-slate-700">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div class="flex items-center gap-3">
                                <div class="flex h-11 w-11 items-center justify-center rounded-md bg-futbolix-green text-white shadow-lg shadow-futbolix-green/20">
                                    <Bot class="h-6 w-6" />
                                </div>
                                <div>
                                    <h1 class="text-xl font-bold">Futbolix AI</h1>
                                    <p class="text-sm text-slate-500 dark:text-slate-400">
                                        Asistente de futbol, estadisticas y torneos
                                    </p>
                                </div>
                            </div>

                            <div class="rounded-md border border-slate-200 px-3 py-2 text-xs text-slate-500 dark:border-slate-700 dark:text-slate-400">
                                Coste estimado: <span class="font-semibold text-slate-900 dark:text-white">{{ estimatedCredits }}</span> credito(s)
                            </div>
                        </div>
                    </header>

                    <div ref="messageList" class="min-h-0 flex-1 overflow-y-auto px-4 py-5 sm:px-6">
                        <div v-if="loadingConversation" class="flex h-full items-center justify-center text-slate-500 dark:text-slate-400">
                            <Loader2 class="mr-2 h-5 w-5 animate-spin" />
                            Cargando conversacion...
                        </div>

                        <div v-else-if="messages.length === 0" class="mx-auto flex max-w-3xl flex-col items-center justify-center py-12 text-center overflow-y-auto">
                            <div class="mb-5 flex h-16 w-16 items-center justify-center rounded-lg bg-futbolix-green/10 text-futbolix-green">
                                <Sparkles class="h-8 w-8" />
                            </div>
                            <h2 class="text-2xl font-bold">Pregunta algo sobre futbol o tus torneos</h2>
                            <p class="mt-3 max-w-xl text-sm leading-6 text-slate-500 dark:text-slate-400">
                                Futbolix AI puede ayudarte a resumir jornadas, comparar equipos, generar cronicas, explicar estadisticas y preparar reglas de competicion.
                            </p>

                            <div class="mt-7 grid w-full gap-3 sm:grid-cols-2">
                                <button
                                    v-for="suggestion in suggestedPrompts"
                                    :key="suggestion"
                                    type="button"
                                    class="rounded-lg border border-slate-200 bg-slate-50 p-4 text-left text-sm font-medium text-slate-700 transition hover:border-futbolix-green hover:text-futbolix-green dark:border-slate-700 dark:bg-slate-900/40 dark:text-slate-200"
                                    @click="useSuggestion(suggestion)"
                                >
                                    {{ suggestion }}
                                </button>
                            </div>
                        </div>

                        <div v-else class="mx-auto max-w-4xl space-y-5">
                            <article
                                v-for="message in messages"
                                :key="message.id"
                                class="flex gap-3"
                                :class="message.role === 'user' ? 'justify-end' : 'justify-start'"
                            >
                                <div v-if="message.role !== 'user'" class="mt-1 flex h-9 w-9 shrink-0 items-center justify-center rounded-md bg-futbolix-green text-white">
                                    <Bot class="h-5 w-5" />
                                </div>

                                <div
                                    class="max-w-[86%] rounded-lg px-4 py-3 text-sm leading-6 shadow-sm sm:max-w-[76%]"
                                    :class="message.role === 'user'
                                        ? 'bg-futbolix-green text-white'
                                        : 'border border-slate-200 bg-slate-50 text-slate-800 dark:border-slate-700 dark:bg-slate-900/50 dark:text-slate-100'"
                                >
                                    <p class="whitespace-pre-wrap">{{ message.content }}</p>
                                    <p
                                        v-if="message.role === 'assistant' && message.credits_spent"
                                        class="mt-3 border-t pt-2 text-xs"
                                        :class="message.role === 'user' ? 'border-white/20 text-white/80' : 'border-slate-200 text-slate-500 dark:border-slate-700 dark:text-slate-400'"
                                    >
                                        {{ message.credits_spent }} credito(s) consumido(s)
                                        <span v-if="message.total_tokens"> - {{ message.total_tokens }} tokens</span>
                                    </p>
                                </div>

                                <div v-if="message.role === 'user'" class="mt-1 flex h-9 w-9 shrink-0 items-center justify-center rounded-md bg-slate-900 text-white dark:bg-slate-700">
                                    <UserRound class="h-5 w-5" />
                                </div>
                            </article>

                            <div v-if="sending" class="flex items-center gap-3 text-sm text-slate-500 dark:text-slate-400">
                                <div class="flex h-9 w-9 items-center justify-center rounded-md bg-futbolix-green text-white">
                                    <Bot class="h-5 w-5" />
                                </div>
                                <span class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 dark:border-slate-700 dark:bg-slate-900/50">
                                    <Loader2 class="h-4 w-4 animate-spin" />
                                    Futbolix AI esta pensando...
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="border-t border-slate-200 p-4 dark:border-slate-700">
                        <div v-if="error" class="mb-3 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-500/30 dark:bg-red-500/10 dark:text-red-200">
                            {{ error }}
                        </div>

                        <form class="mx-auto flex max-w-4xl items-end gap-3" @submit.prevent="sendMessage">
                            <label class="sr-only" for="futbolix-ai-prompt">Mensaje para Futbolix AI</label>
                            <textarea
                                id="futbolix-ai-prompt"
                                v-model="prompt"
                                rows="1"
                                class="max-h-36 min-h-12 flex-1 resize-none rounded-lg border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-futbolix-green focus:ring-futbolix-green dark:border-slate-700 dark:bg-slate-900 dark:text-white"
                                placeholder="Pregunta por una jornada, una previa, una comparativa o un torneo..."
                                @keydown.enter.exact.prevent="sendMessage"
                            />
                            <button
                                type="submit"
                                class="flex h-12 w-12 shrink-0 items-center justify-center rounded-md bg-futbolix-green text-white transition hover:bg-futbolix-green-dark disabled:cursor-not-allowed disabled:opacity-60"
                                :disabled="sending || prompt.trim().length < 2"
                                title="Enviar mensaje"
                            >
                                <Loader2 v-if="sending" class="h-5 w-5 animate-spin" />
                                <Send v-else class="h-5 w-5" />
                            </button>
                        </form>
                    </div>
                </main>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
