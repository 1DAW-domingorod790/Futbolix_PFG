<script setup lang="ts">
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { route } from 'ziggy-js';
import FileInput from '@/Components/FileInput.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

type TeamPlayer = {
    id: number;
    name: string;
    dni: string;
    number: number;
    age: number | null;
    goals: number;
    photo_url: string;
};

type TeamMatch = {
    id: number;
    scheduled_at: string | null;
    status: string | null;
    home_score: number | null;
    away_score: number | null;
    home_team: { id: number | null; name: string | null; badge: string | null };
    away_team: { id: number | null; name: string | null; badge: string | null };
};

const props = defineProps<{
    tournament: {
        id: number;
        name: string;
        code: number;
        can_manage: boolean;
    };
    team: {
        id: number;
        code: number;
        name: string;
        badge: string;
        position: number | null;
        created_at: string | null;
        stats: {
            played: number;
            won: number;
            drawn: number;
            lost: number;
            goals_for: number;
            goals_against: number;
            goal_difference: number;
            points: number;
        };
        players: TeamPlayer[];
        recent_matches: TeamMatch[];
    };
}>();

const page = usePage<{ flash: { success?: string | null } }>();
const showPlayerForm = ref(false);

const badgeForm = useForm({
    badge: null as File | null,
});

const playerForm = useForm({
    dni: '',
    name: '',
    number: '',
    age: '',
    photo_path: null as File | null,
    stay_on_team: true,
});

const totalGoals = computed(() => props.team.players.reduce((sum, player) => sum + player.goals, 0));

function formatDate(date: string | null) {
    if (!date) return 'Fecha pendiente';
    return new Date(date).toLocaleDateString('es-ES', {
        day: '2-digit',
        month: 'long',
        year: 'numeric',
    });
}

function formatTime(date: string | null) {
    if (!date) return '--:--';
    return new Date(date).toLocaleTimeString('es-ES', {
        hour: '2-digit',
        minute: '2-digit',
    });
}

function teamInitials(name: string | null) {
    if (!name) return 'EQ';
    return name
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((word) => word[0])
        .join('')
        .toUpperCase();
}

function goalDifferenceLabel(value: number) {
    return value > 0 ? `+${value}` : String(value);
}

function statusLabel(status: string | null) {
    if (status === 'FINISHED') return 'Finalizado';
    if (status === 'IN_PLAY') return 'En juego';
    return 'Programado';
}

function statusClasses(status: string | null) {
    if (status === 'FINISHED') {
        return 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300';
    }

    if (status === 'IN_PLAY') {
        return 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300';
    }

    return 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300';
}

function saveBadge() {
    badgeForm.patch(route('tournaments.teams.update', [props.tournament.id, props.team.id]), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => badgeForm.reset(),
    });
}

function openPlayerForm() {
    showPlayerForm.value = true;
    playerForm.clearErrors();
}

function closePlayerForm() {
    showPlayerForm.value = false;
    playerForm.reset();
    playerForm.photo_path = null;
    playerForm.stay_on_team = true;
    playerForm.clearErrors();
}

function submitPlayer() {
    playerForm.post(route('tournaments.teams.players.store', [props.tournament.id, props.team.id]), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => closePlayerForm(),
    });
}
</script>

<template>
    <Head :title="team.name" />

    <AuthenticatedLayout>
        <div class="bg-slate-100 px-4 py-8 dark:bg-[#0f172a] lg:px-8">
            <div class="mx-auto max-w-7xl space-y-6">
                <div v-if="page.props.flash?.success" class="rounded-2xl border border-futbolix-green/30 bg-futbolix-green/10 px-4 py-3 text-sm text-futbolix-green">
                    {{ page.props.flash.success }}
                </div>

                <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-futbolix-dark">
                    <div class="flex flex-col gap-6 lg:flex-row lg:items-start lg:justify-between">
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-center">
                            <div class="flex h-28 w-28 items-center justify-center overflow-hidden rounded-3xl bg-slate-100 shadow-sm dark:bg-slate-800">
                                <img :src="team.badge" :alt="team.name" class="h-full w-full object-cover">
                            </div>

                            <div class="min-w-0">
                                <p class="text-sm font-semibold uppercase tracking-[0.2em] text-futbolix-gold">Ficha del equipo</p>
                                <h1 class="mt-2 text-3xl font-bold text-slate-900 dark:text-white">{{ team.name }}</h1>
                                <p class="mt-3 text-sm text-slate-500 dark:text-slate-400">
                                    Torneo
                                    <Link :href="route('tournaments.show', tournament.id)" class="font-semibold text-futbolix-green hover:underline">
                                        {{ tournament.name }}
                                    </Link>
                                    · Codigo {{ team.code }}
                                </p>
                            </div>
                        </div>

                        <Link
                            :href="route('tournaments.show', tournament.id)"
                            class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100 dark:hover:bg-slate-700"
                        >
                            Volver al torneo
                        </Link>
                    </div>
                </section>

                <div class="grid gap-6 xl:grid-cols-[1.1fr_0.9fr]">
                    <div class="space-y-6">
                        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-futbolix-dark">
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <h2 class="text-xl font-semibold text-slate-900 dark:text-white">Plantilla</h2>
                                    <p class="text-sm text-slate-500 dark:text-slate-400">
                                        Solo los jugadores de esta lista se pueden seleccionar como goleadores en los partidos del torneo.
                                    </p>
                                </div>

                                <button
                                    v-if="tournament.can_manage"
                                    type="button"
                                    class="inline-flex items-center justify-center rounded-lg bg-futbolix-green px-4 py-2 text-sm font-semibold text-white transition hover:bg-futbolix-green-dark"
                                    @click="openPlayerForm"
                                >
                                    Anadir jugador
                                </button>
                            </div>

                            <div v-if="team.players.length" class="mt-6 grid gap-4 md:grid-cols-2">
                                <article
                                    v-for="player in team.players"
                                    :key="player.id"
                                    class="rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-900/40"
                                >
                                    <div class="flex items-start gap-4">
                                        <img :src="player.photo_url" :alt="player.name" class="h-16 w-16 rounded-2xl object-cover">
                                        <div class="min-w-0 flex-1">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <p class="truncate text-base font-semibold text-slate-900 dark:text-white">{{ player.name }}</p>
                                                <span class="rounded-full bg-futbolix-green/10 px-2.5 py-1 text-xs font-semibold text-futbolix-green">
                                                    #{{ player.number }}
                                                </span>
                                            </div>
                                            <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">
                                                DNI {{ player.dni }} · {{ player.age ? `${player.age} años` : 'Edad no registrada' }}
                                            </p>
                                        </div>
                                    </div>
                                </article>
                            </div>

                            <div v-else class="mt-6 rounded-lg border border-dashed border-slate-300 px-6 py-12 text-center text-sm text-slate-500 dark:border-slate-600 dark:text-slate-400">
                                Este equipo todavia no tiene jugadores registrados.
                            </div>
                        </section>

                        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-futbolix-dark">
                            <div>
                                <h2 class="text-xl font-semibold text-slate-900 dark:text-white">Ultimos partidos</h2>
                                <p class="text-sm text-slate-500 dark:text-slate-400">
                                    Resumen rapido de los encuentros mas recientes de {{ team.name }} dentro del torneo.
                                </p>
                            </div>

                            <div v-if="team.recent_matches.length" class="mt-6 space-y-4">
                                <article
                                    v-for="match in team.recent_matches"
                                    :key="match.id"
                                    class="rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-900/40"
                                >
                                    <div class="flex flex-wrap items-center justify-between gap-3">
                                        <p class="text-sm text-slate-500 dark:text-slate-400">
                                            {{ formatDate(match.scheduled_at) }} - {{ formatTime(match.scheduled_at) }}
                                        </p>
                                        <span class="rounded-full px-3 py-1 text-xs font-semibold" :class="statusClasses(match.status)">
                                            {{ statusLabel(match.status) }}
                                        </span>
                                    </div>

                                    <div class="mt-4 grid grid-cols-[minmax(0,1fr)_auto_minmax(0,1fr)] items-center gap-3">
                                        <div class="flex min-w-0 items-center gap-3">
                                            <div class="flex h-10 w-10 items-center justify-center overflow-hidden rounded-full bg-slate-100 text-xs font-semibold text-slate-700 dark:bg-slate-800 dark:text-slate-100">
                                                <img v-if="match.home_team.badge" :src="match.home_team.badge" :alt="match.home_team.name || 'Local'" class="h-full w-full object-cover">
                                                <span v-else>{{ teamInitials(match.home_team.name) }}</span>
                                            </div>
                                            <p class="truncate text-sm font-semibold text-slate-900 dark:text-white">{{ match.home_team.name || 'Equipo local' }}</p>
                                        </div>

                                        <div class="rounded-lg bg-slate-100 px-4 py-2 text-sm font-bold text-slate-900 dark:bg-slate-800 dark:text-white">
                                            {{ match.home_score ?? '-' }} - {{ match.away_score ?? '-' }}
                                        </div>

                                        <div class="flex min-w-0 items-center justify-end gap-3 text-right">
                                            <p class="truncate text-sm font-semibold text-slate-900 dark:text-white">{{ match.away_team.name || 'Equipo visitante' }}</p>
                                            <div class="flex h-10 w-10 items-center justify-center overflow-hidden rounded-full bg-slate-100 text-xs font-semibold text-slate-700 dark:bg-slate-800 dark:text-slate-100">
                                                <img v-if="match.away_team.badge" :src="match.away_team.badge" :alt="match.away_team.name || 'Visitante'" class="h-full w-full object-cover">
                                                <span v-else>{{ teamInitials(match.away_team.name) }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </article>
                            </div>

                            <div v-else class="mt-6 rounded-lg border border-dashed border-slate-300 px-6 py-12 text-center text-sm text-slate-500 dark:border-slate-600 dark:text-slate-400">
                                Aun no hay partidos registrados para este equipo.
                            </div>
                        </section>
                    </div>
                </div>
            </div>
        </div>

        <div
            v-if="tournament.can_manage && showPlayerForm"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 px-4 backdrop-blur-sm"
            @click.self="closePlayerForm"
        >
            <div class="w-full max-w-2xl rounded-2xl border border-slate-200 bg-white p-6 shadow-xl dark:border-slate-700 dark:bg-futbolix-dark">
                <div class="mb-6">
                    <p class="text-sm font-semibold uppercase tracking-[0.2em] text-futbolix-gold">Plantilla del equipo</p>
                    <h2 class="mt-2 text-xl font-bold text-slate-900 dark:text-white">Anadir jugador</h2>
                    <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">
                        Este jugador quedara disponible para seleccionarlo como goleador en los resultados de los partidos.
                    </p>
                </div>

                <form class="grid gap-4 lg:grid-cols-2" @submit.prevent="submitPlayer">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-200">Nombre</label>
                        <input
                            v-model="playerForm.name"
                            type="text"
                            class="mt-2 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 focus:border-futbolix-green focus:outline-none focus:ring-1 focus:ring-futbolix-green dark:border-slate-600 dark:bg-slate-900 dark:text-white"
                        >
                        <p v-if="playerForm.errors.name" class="mt-2 text-sm text-red-500">{{ playerForm.errors.name }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-200">DNI</label>
                        <input
                            v-model="playerForm.dni"
                            type="text"
                            class="mt-2 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 focus:border-futbolix-green focus:outline-none focus:ring-1 focus:ring-futbolix-green dark:border-slate-600 dark:bg-slate-900 dark:text-white"
                        >
                        <p v-if="playerForm.errors.dni" class="mt-2 text-sm text-red-500">{{ playerForm.errors.dni }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-200">Dorsal</label>
                        <input
                            v-model="playerForm.number"
                            type="number"
                            min="1"
                            max="99"
                            class="mt-2 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 focus:border-futbolix-green focus:outline-none focus:ring-1 focus:ring-futbolix-green dark:border-slate-600 dark:bg-slate-900 dark:text-white"
                        >
                        <p v-if="playerForm.errors.number" class="mt-2 text-sm text-red-500">{{ playerForm.errors.number }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-200">Edad</label>
                        <input
                            v-model="playerForm.age"
                            type="number"
                            min="1"
                            max="99"
                            class="mt-2 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 focus:border-futbolix-green focus:outline-none focus:ring-1 focus:ring-futbolix-green dark:border-slate-600 dark:bg-slate-900 dark:text-white"
                        >
                        <p v-if="playerForm.errors.age" class="mt-2 text-sm text-red-500">{{ playerForm.errors.age }}</p>
                    </div>

                    <div class="lg:col-span-2">
                        <FileInput
                            v-model="playerForm.photo_path"
                            input-id="team-player-photo"
                            label="Subir foto del jugador"
                        />
                        <p v-if="playerForm.errors.photo_path" class="mt-2 text-sm text-red-500">{{ playerForm.errors.photo_path }}</p>
                    </div>

                    <div class="mt-2 flex justify-end gap-3 lg:col-span-2">
                        <button
                            type="button"
                            class="rounded-lg border border-slate-300 px-4 py-2 text-sm text-slate-600 transition hover:bg-slate-50 dark:border-slate-600 dark:text-slate-300 dark:hover:bg-slate-800"
                            @click="closePlayerForm"
                        >
                            Cancelar
                        </button>
                        <button
                            type="submit"
                            :disabled="playerForm.processing"
                            class="inline-flex items-center rounded-lg bg-futbolix-green px-4 py-2 text-sm font-semibold text-white transition hover:bg-futbolix-green-dark disabled:opacity-60"
                        >
                            Guardar jugador
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
