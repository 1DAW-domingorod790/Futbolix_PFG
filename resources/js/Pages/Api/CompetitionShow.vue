<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { route } from 'ziggy-js';

type Team = {
    id: number | string;
    name?: string | null;
    shortname?: string | null;
    tla?: string | null;
    crest?: string | null;
    founded?: number | null;
    venue?: string | null;
    external_id?: number | string | null;
};

type Game = {
    id: number | string;
    matchday?: number | null;
    home_score?: number | null;
    away_score?: number | null;
    utc_date?: string | null;
    status?: string | null;
    home_team?: Team | null;
    away_team?: Team | null;
    homeTeam?: Team | null;
    awayTeam?: Team | null;
};

type Competition = {
    id: number | string;
    name?: string | null;
    code?: string | null;
    type?: string | null;
    emblem?: string | null;
    startDate?: string | null;
    endDate?: string | null;
    lastUpdated?: string | null;
    updated_at?: string | null;
    currentMatchDay?: number | null;
    teams?: Team[];
    games?: Game[];
    teams_count?: number;
    games_count?: number;
};

const props = defineProps<{
    competition: Competition;
}>();

const activeTab = ref<'teams' | 'matchdays'>('teams');
const selectedMatchdayKey = ref<string>('');

const sortedTeams = computed(() =>
    [...(props.competition.teams ?? [])].sort((a, b) =>
        (a.name ?? '').localeCompare(b.name ?? '', 'es'),
    ),
);

const sortedGames = computed(() =>
    [...(props.competition.games ?? [])].sort((a, b) => {
        const matchdayA = a.matchday ?? Number.MAX_SAFE_INTEGER;
        const matchdayB = b.matchday ?? Number.MAX_SAFE_INTEGER;

        if (matchdayA !== matchdayB) {
            return matchdayA - matchdayB;
        }

        return new Date(a.utc_date ?? '').getTime() - new Date(b.utc_date ?? '').getTime();
    }),
);

const matchdayGroups = computed(() => {
    const groups = new Map<
        string,
        {
            key: string;
            matchday: number | null;
            label: string;
            games: Game[];
        }
    >();

    sortedGames.value.forEach((game) => {
        const key = game.matchday ? `matchday-${game.matchday}` : 'matchday-unassigned';

        if (!groups.has(key)) {
            groups.set(key, {
                key,
                matchday: game.matchday ?? null,
                label: game.matchday ? `Jornada ${game.matchday}` : 'Partidos sin jornada',
                games: [],
            });
        }

        groups.get(key)?.games.push(game);
    });

    return Array.from(groups.values());
});

const matchdayOptions = computed(() =>
    matchdayGroups.value.map((group) => ({
        key: group.key,
        label: group.label,
        matchday: group.matchday,
        gameCount: group.games.length,
    })),
);

const activeMatchdayGroup = computed(
    () =>
        matchdayGroups.value.find((group) => group.key === selectedMatchdayKey.value) ??
        matchdayGroups.value[0] ??
        null,
);

const activeMatchdayIndex = computed(() =>
    matchdayGroups.value.findIndex((group) => group.key === activeMatchdayGroup.value?.key),
);

function resolveInitialMatchdayKey() {
    if (!matchdayGroups.value.length) {
        return '';
    }

    const currentMatchdayKey = props.competition.currentMatchDay
        ? `matchday-${props.competition.currentMatchDay}`
        : '';

    const currentMatchdayExists = currentMatchdayKey
        && matchdayGroups.value.some((group) => group.key === currentMatchdayKey);

    if (currentMatchdayExists) {
        return currentMatchdayKey;
    }

    return matchdayGroups.value[0]?.key ?? '';
}

function showPreviousMatchday() {
    if (activeMatchdayIndex.value <= 0) {
        return;
    }

    selectedMatchdayKey.value = matchdayGroups.value[activeMatchdayIndex.value - 1]?.key ?? selectedMatchdayKey.value;
}

function showNextMatchday() {
    if (activeMatchdayIndex.value < 0 || activeMatchdayIndex.value >= matchdayGroups.value.length - 1) {
        return;
    }

    selectedMatchdayKey.value = matchdayGroups.value[activeMatchdayIndex.value + 1]?.key ?? selectedMatchdayKey.value;
}

watch(
    matchdayGroups,
    (groups) => {
        if (!groups.length) {
            selectedMatchdayKey.value = '';
            return;
        }

        const selectedExists = groups.some((group) => group.key === selectedMatchdayKey.value);

        if (!selectedExists) {
            selectedMatchdayKey.value = resolveInitialMatchdayKey();
        }
    },
    { immediate: true },
);

function formatDate(date?: string | null) {
    if (!date) {
        return 'Pendiente';
    }

    return new Date(date).toLocaleDateString('es-ES', {
        day: '2-digit',
        month: 'long',
        year: 'numeric',
    });
}

function formatDateTime(date?: string | null) {
    if (!date) {
        return 'Sin actualizar';
    }

    return new Date(date).toLocaleDateString('es-ES', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

function formatShortDate(date?: string | null) {
    if (!date) {
        return 'Fecha pendiente';
    }

    return new Date(date).toLocaleDateString('es-ES', {
        weekday: 'short',
        day: '2-digit',
        month: 'short',
    });
}

function formatTime(date?: string | null) {
    if (!date) {
        return '--:--';
    }

    return new Date(date).toLocaleTimeString('es-ES', {
        hour: '2-digit',
        minute: '2-digit',
    });
}

function competitionInitials(name?: string | null) {
    if (!name) {
        return 'CP';
    }

    return name
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((word) => word[0])
        .join('')
        .toUpperCase();
}

function teamInitials(team?: Team | null) {
    const baseName = team?.tla || team?.shortname || team?.name;

    if (!baseName) {
        return 'EQ';
    }

    return baseName
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((word) => word[0])
        .join('')
        .toUpperCase()
        .slice(0, 3);
}

function competitionTypeLabel(type?: string | null) {
    if (!type) {
        return 'Formato sin definir';
    }

    if (type === 'LEAGUE') {
        return 'Liga';
    }

    if (type === 'CUP') {
        return 'Copa';
    }

    return type;
}

function teamName(team?: Team | null, fallback = 'Equipo') {
    return team?.shortname || team?.name || fallback;
}

function gameStatus(game: Game) {
    if (game.status === 'FINISHED') {
        return `${game.home_score ?? 0} - ${game.away_score ?? 0}`;
    }

    if (game.status === 'IN_PLAY') {
        return 'En juego';
    }

    return 'VS';
}

function gameStatusLabel(status?: string | null) {
    if (!status) {
        return 'Pendiente';
    }

    if (status === 'FINISHED') {
        return 'Finalizado';
    }

    if (status === 'IN_PLAY') {
        return 'En juego';
    }

    if (status === 'TIMED') {
        return 'Programado';
    }

    return status;
}
</script>

<template>
    <Head :title="competition.name || 'Competición'" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-2 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <h2 class="text-xl font-semibold leading-tight text-white">
                        {{ competition.name || 'Competición' }}
                    </h2>
                    <p class="text-sm font-semibold text-white/75">
                        Resumen general de la competición y consulta por equipos o jornadas.
                    </p>
                </div>

                <Link
                    :href="route('competitions.index')"
                    class="inline-flex items-center justify-center rounded-full border border-white/25 bg-white/10 px-4 py-2 text-sm font-bold text-white transition hover:bg-white/20"
                >
                    Volver a competiciones
                </Link>
            </div>
        </template>

        <div class="min-h-screen  px-4 py-6 lg:px-6 lg:py-8">
            <div class="mx-auto flex w-full max-w-[1500px] flex-col gap-5">
                <section class="overflow-hidden rounded-[2rem] border-4 border-[#083b8d] bg-[#00357b] shadow-[0_18px_45px_rgba(8,59,141,0.18)]">
                    <div class="grid gap-5 p-5 lg:grid-cols-[minmax(0,1.5fr)_minmax(340px,1fr)] lg:p-6">
                        <div class="rounded-[1.75rem] border border-[#0c4ea9] bg-[#00479d] p-5 text-white">
                            <div class="flex flex-col gap-4 sm:flex-row sm:items-start">
                                <div class="flex h-20 w-20 shrink-0 items-center justify-center overflow-hidden rounded-[1.5rem] bg-white p-3 shadow-[0_10px_24px_rgba(255,255,255,0.2)]">
                                    <img
                                        v-if="competition.emblem"
                                        :src="competition.emblem"
                                        :alt="competition.name || 'Competición'"
                                        class="h-full w-full object-contain"
                                    >
                                    <span v-else class="text-xl font-black text-[#00357b]">
                                        {{ competitionInitials(competition.name) }}
                                    </span>
                                </div>

                                <div class="min-w-0 flex-1">
                                    <p class="text-xs font-black uppercase tracking-[0.22em] text-[#d8e7ff]">
                                        Competición
                                    </p>
                                    <h1 class="mt-2 text-3xl font-black leading-tight lg:text-4xl">
                                        {{ competition.name || 'Sin nombre' }}
                                    </h1>
                                    <p class="mt-3 max-w-2xl text-sm font-medium leading-6 text-[#d8e7ff]">
                                        Sigue de un vistazo el formato, el calendario de temporada y el desarrollo de cada jornada.
                                    </p>

                                    <div class="mt-4 flex flex-wrap gap-2">
                                        <span class="rounded-full border border-[#5ca1ff] bg-[#0051b2] px-3 py-1 text-xs font-black uppercase tracking-wide text-white">
                                            {{ competition.code || 'Sin código' }}
                                        </span>
                                        <span class="rounded-full border border-[#5ca1ff] bg-[#0051b2] px-3 py-1 text-xs font-black uppercase tracking-wide text-white">
                                            {{ competitionTypeLabel(competition.type) }}
                                        </span>
                                        <span class="rounded-full border border-[#5ca1ff] bg-[#ffd400] px-3 py-1 text-xs font-black uppercase tracking-wide text-[#1d1d1d]">
                                            Jornada {{ competition.currentMatchDay || 'N/D' }}
                                        </span>
                                    </div>

                                </div>
                            </div>
                        </div>

                        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-1">
                            <article class="rounded-[1.5rem] border border-[#0c4ea9] bg-[#eef1f5] p-4 text-[#00357b]">
                                <p class="text-[11px] font-black uppercase tracking-[0.18em] text-[#4a6ea9]">
                                    Temporada
                                </p>
                                <dl class="mt-3 space-y-3 text-sm font-semibold">
                                    <div class="flex items-center justify-between gap-4 border-t border-[#c8d5e6] pt-3 first:border-t-0 first:pt-0">
                                        <dt>Inicio</dt>
                                        <dd class="text-right">{{ formatDate(competition.startDate) }}</dd>
                                    </div>
                                    <div class="flex items-center justify-between gap-4 border-t border-[#c8d5e6] pt-3">
                                        <dt>Fin</dt>
                                        <dd class="text-right">{{ formatDate(competition.endDate) }}</dd>
                                    </div>
                                    <div class="flex items-center justify-between gap-4 border-t border-[#c8d5e6] pt-3 text-gray-500">
                                        <dt>Última actualización</dt>
                                        <dd class="text-right">{{ formatDateTime(competition.updated_at) }}</dd>
                                    </div>
                                </dl>
                            </article>
                        </div>
                    </div>
                </section>

                <section class="rounded-[2rem] border-4 border-[#083b8d] bg-[#eef1f5] p-4 shadow-[0_18px_45px_rgba(8,59,141,0.18)] lg:p-5">
                    <div class="flex flex-wrap gap-2">
                        <button
                            type="button"
                            class="rounded-full border px-4 py-2 text-sm font-black uppercase tracking-wide transition"
                            :class="activeTab === 'teams'
                                ? 'border-[#ffd400] bg-[#ffd400] text-[#1d1d1d]'
                                : 'border-[#5ca1ff] bg-[#0051b2] text-white hover:bg-[#0b5fc7]'"
                            @click="activeTab = 'teams'"
                        >
                            Equipos participantes
                        </button>
                        <button
                            type="button"
                            class="rounded-full border px-4 py-2 text-sm font-black uppercase tracking-wide transition"
                            :class="activeTab === 'matchdays'
                                ? 'border-[#ffd400] bg-[#ffd400] text-[#1d1d1d]'
                                : 'border-[#5ca1ff] bg-[#0051b2] text-white hover:bg-[#0b5fc7]'"
                            @click="activeTab = 'matchdays'"
                        >
                            Partidos por jornadas
                        </button>
                    </div>
                </section>

                <section
                    v-if="activeTab === 'teams'"
                    class="rounded-[2rem] border-4 border-[#083b8d] bg-[#eef1f5] p-4 shadow-[0_18px_45px_rgba(8,59,141,0.18)] lg:p-5"
                >
                    <div class="mb-4 flex flex-col gap-2 lg:flex-row lg:items-end lg:justify-between">
                        <div>
                            <h3 class="text-xl font-black uppercase tracking-wide text-[#00357b]">
                                Equipos participantes
                            </h3>
                            <p class="text-sm font-semibold text-[#4a6ea9]">
                                {{ sortedTeams.length }} clubes registrados en esta competición.
                            </p>
                        </div>
                    </div>

                    <div v-if="sortedTeams.length" class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-4">
                        <article
                            v-for="team in sortedTeams"
                            :key="team.id"
                            class="rounded-2xl border-2 border-[#042b67] bg-[#00357b] p-3 text-white shadow-[0_8px_18px_rgba(3,34,82,0.2)]"
                        >
                            <div class="flex items-start gap-3">
                                <div class="flex h-14 w-14 shrink-0 items-center justify-center overflow-hidden rounded-2xl bg-white p-2">
                                    <img
                                        v-if="team.crest"
                                        :src="team.crest"
                                        :alt="team.name || 'Equipo'"
                                        class="h-full w-full object-contain"
                                    >
                                    <span v-else class="text-sm font-black text-[#00357b]">
                                        {{ teamInitials(team) }}
                                    </span>
                                </div>

                                <div class="min-w-0 flex-1">
                                    <h4 class="line-clamp-2 text-base font-black leading-tight">
                                        {{ team.name || 'Equipo sin nombre' }}
                                    </h4>
                                    <p class="mt-1 text-xs font-semibold uppercase tracking-wide text-[#d8e7ff]">
                                        {{ team.shortname || 'Sin nombre corto' }}
                                    </p>
                                </div>

                                <span class="rounded-full border border-[#5ca1ff] bg-[#0051b2] px-2.5 py-1 text-[11px] font-black uppercase tracking-wide text-white">
                                    {{ team.tla || 'N/D' }}
                                </span>
                            </div>

                            <dl class="mt-4 space-y-3 text-sm">
                                <div class="flex items-center justify-between gap-4 border-t border-[#0c4ea9] pt-3">
                                    <dt class="text-[#d8e7ff]">Fundación</dt>
                                    <dd class="text-right font-bold">{{ team.founded || 'N/D' }}</dd>
                                </div>
                                <div class="flex items-center justify-between gap-4 border-t border-[#0c4ea9] pt-3">
                                    <dt class="text-[#d8e7ff]">Estadio</dt>
                                    <dd class="text-right font-bold">{{ team.venue || 'Pendiente' }}</dd>
                                </div>
                            </dl>
                        </article>
                    </div>

                    <div
                        v-else
                        class="rounded-2xl border-2 border-dashed border-[#5ca1ff] bg-white px-6 py-12 text-center text-sm font-semibold text-[#4a6ea9]"
                    >
                        No hay equipos asociados a esta competición todavía.
                    </div>
                </section>

                <section
                    v-else
                    class="rounded-[2rem] border-4 border-[#083b8d] bg-[#eef1f5] p-4 shadow-[0_18px_45px_rgba(8,59,141,0.18)] lg:p-5"
                >
                    <div class="mb-4 flex flex-col gap-2 lg:flex-row lg:items-end lg:justify-between">
                        <div>
                            <h3 class="text-xl font-black uppercase tracking-wide text-[#00357b]">
                                Partidos por jornadas
                            </h3>
                            <p class="text-sm font-semibold text-[#4a6ea9]">
                                Navega por {{ matchdayGroups.length }} bloques de jornada disponibles para esta competición.
                            </p>
                        </div>

                        <p class="text-sm font-semibold text-[#4a6ea9]">
                            {{ sortedGames.length }} partidos registrados
                        </p>
                    </div>

                    <div v-if="matchdayGroups.length && activeMatchdayGroup" class="space-y-4">
                        <div class="rounded-2xl border-2 border-[#0c4ea9] bg-white p-4 shadow-[0_10px_24px_rgba(8,59,141,0.08)]">
                            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                                <div>
                                    <p class="text-xs font-black uppercase tracking-[0.18em] text-[#4a6ea9]">
                                        Selector de jornada
                                    </p>
                                    <div class="mt-2 flex items-center gap-3">
                                        <button
                                            type="button"
                                            class="flex h-11 w-11 items-center justify-center rounded-full border-2 border-[#083b8d] bg-[#eef1f5] text-[#00357b] transition hover:bg-[#dfe8f5] disabled:cursor-not-allowed disabled:opacity-40"
                                            :disabled="activeMatchdayIndex <= 0"
                                            @click="showPreviousMatchday"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="m15 18-6-6 6-6" />
                                            </svg>
                                        </button>

                                        <div class="min-w-0">
                                            <p class="text-lg font-black text-[#00357b]">
                                                {{ activeMatchdayGroup.label }}
                                            </p>
                                            <p class="text-sm font-semibold text-[#4a6ea9]">
                                                {{ activeMatchdayGroup.games.length }} partidos en esta jornada
                                            </p>
                                        </div>

                                        <button
                                            type="button"
                                            class="flex h-11 w-11 items-center justify-center rounded-full border-2 border-[#083b8d] bg-[#eef1f5] text-[#00357b] transition hover:bg-[#dfe8f5] disabled:cursor-not-allowed disabled:opacity-40"
                                            :disabled="activeMatchdayIndex < 0 || activeMatchdayIndex >= matchdayGroups.length - 1"
                                            @click="showNextMatchday"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="m9 18 6-6-6-6" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>

                                <p class="text-sm font-semibold text-[#4a6ea9]">
                                    {{ matchdayOptions.length }} jornadas disponibles
                                </p>
                            </div>

                            <div class="mt-4 flex gap-2 overflow-x-auto pb-1">
                                <button
                                    v-for="option in matchdayOptions"
                                    :key="option.key"
                                    type="button"
                                    class="shrink-0 rounded-2xl border-2 px-4 py-3 text-left transition"
                                    :class="selectedMatchdayKey === option.key
                                        ? 'border-[#083b8d] bg-[#00357b] text-white shadow-[0_10px_22px_rgba(8,59,141,0.18)]'
                                        : 'border-[#c8d5e6] bg-[#eef1f5] text-[#00357b] hover:border-[#5ca1ff] hover:bg-white'"
                                    @click="selectedMatchdayKey = option.key"
                                >
                                    <p class="text-sm font-black uppercase tracking-wide">
                                        {{ option.label }}
                                    </p>
                                    <p
                                        class="mt-1 text-xs font-semibold"
                                        :class="selectedMatchdayKey === option.key ? 'text-[#d8e7ff]' : 'text-[#4a6ea9]'"
                                    >
                                        {{ option.gameCount }} partidos
                                    </p>
                                </button>
                            </div>
                        </div>

                        <article class="overflow-hidden rounded-2xl border-2 border-[#042b67] bg-[#00357b] shadow-[0_8px_18px_rgba(3,34,82,0.2)]">
                            <div class="flex items-center justify-between gap-3 border-b border-[#0c4ea9] px-4 py-3 text-white">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-10 w-10 items-center justify-center rounded-full bg-[#ffd400] text-sm font-black text-[#1d1d1d]">
                                        {{ activeMatchdayGroup.matchday ?? '-' }}
                                    </div>
                                    <div>
                                        <h4 class="text-lg font-black">
                                            {{ activeMatchdayGroup.label }}
                                        </h4>
                                        <p class="text-xs font-semibold uppercase tracking-wide text-[#d8e7ff]">
                                            {{ activeMatchdayGroup.games.length }} partidos
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div class="grid gap-3 p-3 md:grid-cols-2 xl:grid-cols-3">
                                <article
                                    v-for="game in activeMatchdayGroup.games"
                                    :key="game.id"
                                    class="rounded-xl border border-[#5ca1ff] bg-[#0051b2] px-3 py-3 text-white"
                                >
                                    <div class="mb-4 flex items-center justify-between gap-3 text-[11px] font-bold uppercase tracking-wide text-[#d8e7ff]">
                                        <span>{{ formatShortDate(game.utc_date) }}</span>
                                        <span>{{ formatTime(game.utc_date) }}</span>
                                    </div>

                                    <div class="grid grid-cols-[minmax(0,1fr)_auto_minmax(0,1fr)] items-center gap-3">
                                        <div class="flex min-w-0 items-center gap-3">
                                            <div class="flex h-11 w-11 shrink-0 items-center justify-center overflow-hidden rounded-xl bg-white p-2">
                                                <img
                                                    v-if="game.homeTeam?.crest || game.home_team?.crest"
                                                    :src="(game.homeTeam?.crest ?? game.home_team?.crest) ?? undefined"
                                                    :alt="teamName(game.homeTeam || game.home_team, 'Local')"
                                                    class="h-full w-full object-contain"
                                                >
                                                <span v-else class="text-xs font-black text-[#00357b]">
                                                    {{ teamInitials(game.homeTeam || game.home_team) }}
                                                </span>
                                            </div>
                                            <p class="line-clamp-2 text-sm font-black leading-tight">
                                                {{ teamName(game.homeTeam || game.home_team, 'Local') }}
                                            </p>
                                        </div>

                                        <div class="rounded-full border-2 border-[#ffd400] bg-[#00357b] px-3 py-2 text-center text-sm font-black text-[#ffd400]">
                                            {{ gameStatus(game) }}
                                        </div>

                                        <div class="flex min-w-0 items-center justify-end gap-3 text-right">
                                            <p class="line-clamp-2 text-sm font-black leading-tight">
                                                {{ teamName(game.awayTeam || game.away_team, 'Visitante') }}
                                            </p>
                                            <div class="flex h-11 w-11 shrink-0 items-center justify-center overflow-hidden rounded-xl bg-white p-2">
                                                <img
                                                    v-if="game.awayTeam?.crest || game.away_team?.crest"
                                                    :src="game.awayTeam?.crest ?? game.away_team?.crest ?? undefined"
                                                    :alt="teamName(game.awayTeam || game.away_team, 'Visitante')"
                                                    class="h-full w-full object-contain"
                                                >
                                                <span v-else class="text-xs font-black text-[#00357b]">
                                                    {{ teamInitials(game.awayTeam || game.away_team) }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </article>
                            </div>
                        </article>
                    </div>

                    <div
                        v-else
                        class="rounded-2xl border-2 border-dashed border-[#5ca1ff] bg-white px-6 py-12 text-center text-sm font-semibold text-[#4a6ea9]"
                    >
                        No hay partidos registrados para esta competición todavía.
                    </div>
                </section>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
