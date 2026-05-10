<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { Check, Crown, Sparkles, Zap } from 'lucide-vue-next';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

defineProps<{
    currentPlan: {
        plan_name: string;
        credits_balance: number;
        monthly_credit_limit: number;
    };
}>();

function upgradeToPro() {
    router.post('/futbolix-ai/upgrade');
}
</script>

<template>
    <Head title="Planes Futbolix AI" />

    <AuthenticatedLayout>

        <!-- HERO -->
        <div class="relative overflow-hidden bg-gradient-to-r from-green-700 via-emerald-600 to-teal-600">
            <div class="absolute inset-0 opacity-10">
                <svg viewBox="0 0 1000 200" preserveAspectRatio="none" class="h-full w-full">
                    <path
                        d="M0,100 C150,0 350,200 500,100 C650,0 850,200 1000,100 L1000,200 L0,200 Z"
                        fill="white"
                    />
                </svg>
            </div>

            <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
                <div class="flex items-center gap-4">
                    <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-white/15 backdrop-blur">
                        <Sparkles class="h-8 w-8 text-white" />
                    </div>

                    <div>
                        <h1 class="text-3xl font-bold text-white">
                            Planes Futbolix AI
                        </h1>

                        <p class="mt-1 text-sm text-green-100">
                            Desbloquea más créditos, respuestas rápidas y funciones premium.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- CONTENT -->
        <div class="bg-slate-100 py-10 dark:bg-[#0f172a]">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">

                <!-- PLAN ACTUAL -->
                <div
                    class="mb-8 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-futbolix-dark"
                >
                    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">

                        <div>
                            <p class="text-sm font-medium text-slate-500 dark:text-slate-400">
                                Tu plan actual
                            </p>

                            <div class="mt-2 flex items-center gap-3">
                                <div
                                    class="flex h-12 w-12 items-center justify-center rounded-xl bg-futbolix-green/10 text-futbolix-green"
                                >
                                    <Crown class="h-6 w-6" />
                                </div>

                                <div>
                                    <h2 class="text-2xl font-bold text-slate-900 dark:text-white">
                                        {{ currentPlan.plan_name === 'pro' ? 'Plan Pro' : 'Plan Free' }}
                                    </h2>

                                    <p class="text-sm text-slate-500 dark:text-slate-400">
                                        {{ currentPlan.credits_balance }}
                                        / {{ currentPlan.monthly_credit_limit }}
                                        créditos disponibles
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="w-full max-w-md">
                            <div class="mb-2 flex items-center justify-between text-sm">
                                <span class="text-slate-500 dark:text-slate-400">
                                    Créditos restantes
                                </span>

                                <span class="font-semibold text-slate-900 dark:text-white">
                                    {{ currentPlan.credits_balance }}
                                </span>
                            </div>

                            <div class="h-3 overflow-hidden rounded-full bg-slate-200 dark:bg-slate-700">
                                <div
                                    class="h-full rounded-full bg-futbolix-green"
                                    :style="{
                                        width: `${(currentPlan.credits_balance / currentPlan.monthly_credit_limit) * 100}%`
                                    }"
                                />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- CARDS -->
                <div class="grid gap-8 lg:grid-cols-2">

                    <!-- FREE -->
                    <div
                        class="rounded-2xl border border-slate-200 bg-white p-8 shadow-sm transition hover:-translate-y-1 hover:shadow-lg dark:border-slate-700 dark:bg-futbolix-dark"
                    >
                        <div class="flex items-center gap-3">
                            <div
                                class="flex h-12 w-12 items-center justify-center rounded-xl bg-slate-100 dark:bg-slate-800"
                            >
                                <Sparkles class="h-6 w-6 text-slate-700 dark:text-slate-200" />
                            </div>

                            <div>
                                <h2 class="text-2xl font-bold text-slate-900 dark:text-white">
                                    Free
                                </h2>

                                <p class="text-sm text-slate-500 dark:text-slate-400">
                                    Ideal para empezar
                                </p>
                            </div>
                        </div>

                        <div class="mt-8">
                            <span class="text-5xl font-bold text-slate-900 dark:text-white">
                                0€
                            </span>

                            <span class="ml-2 text-slate-500 dark:text-slate-400">
                                /mes
                            </span>
                        </div>

                        <ul class="mt-8 space-y-4">
                            <li class="flex items-center gap-3">
                                <Check class="h-5 w-5 text-futbolix-green" />
                                <span>50 créditos mensuales</span>
                            </li>

                            <li class="flex items-center gap-3">
                                <Check class="h-5 w-5 text-futbolix-green" />
                                <span>Acceso básico a Futbolix AI</span>
                            </li>

                            <li class="flex items-center gap-3">
                                <Check class="h-5 w-5 text-futbolix-green" />
                                <span>Historial de conversaciones</span>
                            </li>

                            <li class="flex items-center gap-3">
                                <Check class="h-5 w-5 text-futbolix-green" />
                                <span>Consultas limitadas</span>
                            </li>
                        </ul>

                        <div
                            class="mt-10 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-center text-sm font-medium text-slate-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300"
                        >
                            Plan actual gratuito
                        </div>
                    </div>

                    <!-- PRO -->
                    <div
                        class="relative overflow-hidden rounded-2xl border-2 border-futbolix-green bg-white p-8 shadow-xl dark:bg-futbolix-dark"
                    >

                        <!-- glow -->
                        <div
                            class="absolute -right-10 -top-10 h-40 w-40 rounded-full bg-futbolix-green/20 blur-3xl"
                        />

                        <!-- badge -->
                        <div
                            class="absolute right-6 top-6 rounded-full bg-futbolix-green px-4 py-1 text-xs font-bold uppercase tracking-wide text-white shadow-lg"
                        >
                            Recomendado
                        </div>

                        <div class="relative">
                            <div class="flex items-center gap-3">
                                <div
                                    class="flex h-12 w-12 items-center justify-center rounded-xl bg-futbolix-green/10 text-futbolix-green"
                                >
                                    <Zap class="h-6 w-6" />
                                </div>

                                <div>
                                    <h2 class="text-2xl font-bold text-slate-900 dark:text-white">
                                        Pro
                                    </h2>

                                    <p class="text-sm text-slate-500 dark:text-slate-400">
                                        Para organizadores avanzados
                                    </p>
                                </div>
                            </div>

                            <div class="mt-8">
                                <span class="text-5xl font-bold text-slate-900 dark:text-white">
                                    4,99€
                                </span>

                                <span class="ml-2 text-slate-500 dark:text-slate-400">
                                    /mes
                                </span>
                            </div>

                            <ul class="mt-8 space-y-4">
                                <li class="flex items-center gap-3">
                                    <Check class="h-5 w-5 text-futbolix-green" />
                                    <span>500 créditos mensuales</span>
                                </li>

                                <li class="flex items-center gap-3">
                                    <Check class="h-5 w-5 text-futbolix-green" />
                                    <span>Respuestas más rápidas</span>
                                </li>

                                <li class="flex items-center gap-3">
                                    <Check class="h-5 w-5 text-futbolix-green" />
                                    <span>Mayor contexto IA</span>
                                </li>

                                <li class="flex items-center gap-3">
                                    <Check class="h-5 w-5 text-futbolix-green" />
                                    <span>Prioridad en nuevas funciones</span>
                                </li>

                                <li class="flex items-center gap-3">
                                    <Check class="h-5 w-5 text-futbolix-green" />
                                    <span>Experiencia premium</span>
                                </li>
                            </ul>

                            <button
                                class="mt-10 w-full rounded-xl bg-futbolix-green py-4 text-sm font-semibold text-white transition hover:bg-futbolix-green-dark"
                                @click="upgradeToPro"
                            >
                                Mejorar a Pro
                            </button>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>