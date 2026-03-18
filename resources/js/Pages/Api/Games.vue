<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import { route } from 'ziggy-js';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

type Team = {
    id: number | string;
    name?: string | null;
    shortname?: string | null;
    crest?: string | null;
    venue?: string | null;
};

type Competition = {
    id: number | string;
    external_id?: number | string | null;
    name?: string | null;
    code?: string | null;
    type?: string | null;
    emblem?: string | null;
    startDate?: string | null;
    endDate?: string | null;
    currentMatchDay?: number | null;
    lastUpdated?: string | null;
};

type Game = {
    id: number | string;
    utc_date?: string | null;
    status?: string | null;
    home_score?: number | null;
    away_score?: number | null;
    competition?: Competition | null;
    home_team?: Team | null;
    away_team?: Team | null;
    homeTeam?: Team | null;
    awayTeam?: Team | null;
};

const props = defineProps<{
    games: Game[];
    competitions: Competition[];
}>();

const selectedDateIndex = ref(0);

const groupedDates = computed(() => {
    const groups = new Map<
        string,
        {
            key: string;
            date: Date;
            games: Game[];
        }
    >();

    props.games.forEach((game) => {
        if (!game.utc_date) {
            return;
        }

        const date = new Date(game.utc_date);
        const dateKey = `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;

        if (!groups.has(dateKey)) {
            groups.set(dateKey, {
                key: dateKey,
                date,
                games: [],
            });
        }

        groups.get(dateKey)?.games.push(game);
    });

    return Array.from(groups.values())
        .sort((a, b) => a.date.getTime() - b.date.getTime())
        .map((group) => ({
            ...group,
            games: [...group.games].sort(
                (a, b) =>
                    new Date(a.utc_date ?? '').getTime() -
                    new Date(b.utc_date ?? '').getTime(),
            ),
        }));
});

watch(
    groupedDates,
    (dates) => {
        if (!dates.length) {
            selectedDateIndex.value = 0;
            return;
        }

        const today = new Date();
        const todayKey = `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}-${String(today.getDate()).padStart(2, '0')}`;
        const todayIndex = dates.findIndex((group) => group.key === todayKey);

        selectedDateIndex.value = todayIndex >= 0 ? todayIndex : 0;
    },
    { immediate: true },
);

const safeSelectedDateIndex = computed(() => {
    if (!groupedDates.value.length) {
        return 0;
    }

    return Math.min(selectedDateIndex.value, groupedDates.value.length - 1);
});

const activeDateGroup = computed(
    () => groupedDates.value[safeSelectedDateIndex.value] ?? null,
);

const gamesForSelectedDate = computed(() => activeDateGroup.value?.games ?? []);

const normalizedCompetitions = computed(() =>
    [...(props.competitions ?? [])].sort((a, b) => {
        const hasMatchdayA = a.currentMatchDay ? 1 : 0;
        const hasMatchdayB = b.currentMatchDay ? 1 : 0;

        if (hasMatchdayA !== hasMatchdayB) {
            return hasMatchdayB - hasMatchdayA;
        }

        return (a.name ?? '').localeCompare(b.name ?? '', 'es');
    }),
);

const nationalCompetitionIds = [2014, 2021, 2019, 2015, 2002];
const internationalCompetitionIds = [2000, 2001];

const nationalCompetitions = computed(
    () =>
        nationalCompetitionIds
            .map((competitionId) =>
                normalizedCompetitions.value.find(
                    (competition) =>
                        Number(competition.external_id) === competitionId,
                ),
            )
            .filter(Boolean) as Competition[],
);

const internationalCompetitions = computed(
    () =>
        internationalCompetitionIds
            .map((competitionId) =>
                normalizedCompetitions.value.find(
                    (competition) =>
                        Number(competition.external_id) === competitionId,
                ),
            )
            .filter(Boolean) as Competition[],
);

const otherCompetitions = computed(() => {
    const featuredCompetitionIds = new Set([
        ...nationalCompetitionIds,
        ...internationalCompetitionIds,
    ]);

    return normalizedCompetitions.value.filter(
        (competition) =>
            !featuredCompetitionIds.has(Number(competition.external_id)),
    );
});

const currentDayLabel = computed(() => {
    if (!activeDateGroup.value) {
        return 'Sin partidos';
    }

    const today = new Date();
    const selected = activeDateGroup.value.date;

    const todayKey = `${today.getFullYear()}-${today.getMonth()}-${today.getDate()}`;
    const selectedKey = `${selected.getFullYear()}-${selected.getMonth()}-${selected.getDate()}`;

    if (todayKey === selectedKey) {
        return 'Hoy';
    }

    return selected.toLocaleDateString('es-ES', {
        weekday: 'short',
        day: 'numeric',
        month: 'short',
    });
});

function showPreviousDay() {
    if (selectedDateIndex.value > 0) {
        selectedDateIndex.value -= 1;
    }
}

function showNextDay() {
    if (selectedDateIndex.value < groupedDates.value.length - 1) {
        selectedDateIndex.value += 1;
    }
}

function formatTime(date?: string | number | Date | null) {
    if (!date) {
        return '--:--';
    }

    return new Date(date).toLocaleTimeString('es-ES', {
        hour: '2-digit',
        minute: '2-digit',
    });
}

function formatDateTime(date?: string | number | Date | null) {
    if (!date) {
        return 'Sin fecha';
    }

    return `${new Date(date).toLocaleDateString('es-ES', {
        day: '2-digit',
        month: '2-digit',
    })} ${formatTime(date)}`;
}

function formatDate(date?: string | null) {
    if (!date) {
        return 'Pendiente';
    }

    return new Date(date).toLocaleDateString('es-ES', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    });
}

function teamName(team: Team | null | undefined, fallback: string) {
    return team?.shortname || team?.name || fallback;
}

function venueName(game: Game) {
    return game.home_team?.venue || game.homeTeam?.venue || 'Estadio pendiente';
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
</script>

<template>
    <Head title="Partidos y competiciones" />

    <AuthenticatedLayout>
        <div class="min-h-screen bg-slate-100 dark:bg-[#0f172a] px-3 py-4 lg:px-6 lg:py-6">
            <div
                class="mx-auto grid w-full max-w-screen-2xl gap-4 xl:grid-cols-[minmax(0,1.2fr)_620px]"
            >
                <section class="space-y-4">
                    <div
                        class="w-100 mx-auto sticky top-20 z-10 flex items-center gap-2 rounded-xl border-2 bg-[#ffd400] px-3 py-2 shadow-[0_8px_20px_rgba(160,130,0,0.2)] md:max-w-sm"
                    >
                        <button
                            type="button"
                            class="flex h-7 w-7 items-center justify-center rounded-full text-black transition hover:bg-black/10 disabled:cursor-not-allowed disabled:opacity-40"
                            :disabled="safeSelectedDateIndex === 0"
                            @click="showPreviousDay"
                        >
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                width="18"
                                height="18"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="black"
                                stroke-width="2.5"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            >
                                <line x1="19" y1="12" x2="5" y2="12"></line>
                                <polyline points="12 19 5 12 12 5"></polyline>
                            </svg>
                        </button>
                        <div
                            class="flex-1 text-center text-sm font-semibold tracking-wide text-black uppercase"
                        >
                            {{ currentDayLabel }}
                        </div>
                        <button
                            type="button"
                            class="flex h-7 w-7 items-center justify-center rounded-full text-slate-300 transition hover:bg-black/10 disabled:cursor-not-allowed disabled:opacity-40"
                            :disabled="
                                safeSelectedDateIndex >= groupedDates.length - 1
                            "
                            @click="showNextDay"
                        >
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                width="18"
                                height="18"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="black"
                                stroke-width="2.5"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            >
                                <line x1="5" y1="12" x2="19" y2="12"></line>
                                <polyline points="12 5 19 12 12 19"></polyline>
                            </svg>
                        </button>
                    </div>

                    <div
                        v-if="gamesForSelectedDate.length"
                        class="rounded-[2rem] border-4 border-[#083b8d] bg-[#eef1f5] p-3 shadow-[0_18px_45px_rgba(8,59,141,0.18)] lg:p-4"
                    >
                        <div
                            class="mb-3 flex items-center justify-between gap-3 px-1"
                        >
                            <div>
                                <h3
                                    class="text-lg font-black tracking-wide text-[#00357b] uppercase"
                                >
                                    Todos los partidos
                                </h3>
                                <p class="text-xs font-semibold text-[#2b4d7c]">
                                    {{ gamesForSelectedDate.length }} encuentros
                                    para esta fecha
                                </p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-3 2xl:grid-cols-2">
                            <Link
                                v-for="game in gamesForSelectedDate"
                                :key="game.id"
                                :href="route('matches.show', game.id)"
                                class="overflow-hidden rounded-xl border-2 border-[#042b67] bg-[#00357b] shadow-[0_6px_14px_rgba(3,34,82,0.25)]"
                            >
                                <div
                                    class="flex items-center justify-between gap-2 border-b border-[#0c4ea9] px-3 py-2 text-white lg:px-4 lg:py-3"
                                >
                                    <div
                                        class="flex min-w-0 items-center gap-2"
                                    >
                                        <div
                                            class="flex h-7 w-7 items-center justify-center overflow-hidden rounded-full bg-white/95 p-1 lg:h-8 lg:w-8"
                                        >
                                            <img
                                                v-if="game.competition?.emblem"
                                                :src="game.competition.emblem"
                                                :alt="
                                                    game.competition?.name ||
                                                    'Competición'
                                                "
                                                class="h-full w-full object-contain"
                                            />
                                            <span
                                                v-else
                                                class="text-xs font-black text-[#00357b]"
                                            >
                                                {{
                                                    competitionInitials(
                                                        game.competition?.name,
                                                    )
                                                }}
                                            </span>
                                        </div>
                                        <div class="min-w-0">
                                            <h3
                                                class="truncate text-sm font-black lg:text-base"
                                            >
                                                {{
                                                    game.competition?.name ||
                                                    'Sin competición'
                                                }}
                                            </h3>
                                            <p
                                                class="text-[10px] font-semibold tracking-wide text-[#d8e7ff] uppercase"
                                            >
                                                {{
                                                    formatDateTime(
                                                        game.utc_date,
                                                    )
                                                }}
                                            </p>
                                        </div>
                                    </div>
                                    <div
                                        class="shrink-0 rounded-full border border-[#d9b100] bg-[#ffd84d] px-2 py-1 text-right text-[10px] font-bold tracking-wide text-[#4a3900] uppercase"
                                    >
                                        {{ venueName(game) }}
                                    </div>
                                </div>

                                <div class="space-y-3 p-3">
                                    <div
                                        class="grid grid-cols-[minmax(0,1fr)_auto_minmax(0,1fr)] items-center gap-2 lg:grid-cols-[minmax(0,1fr)_88px_minmax(0,1fr)] lg:gap-3"
                                    >
                                        <div
                                            class="flex min-w-0 items-center gap-2 text-left"
                                        >
                                            <img
                                                v-if="
                                                    game.home_team?.crest ||
                                                    game.homeTeam?.crest
                                                "
                                                :src="
                                                    game.home_team?.crest ??
                                                    game.homeTeam?.crest ??
                                                    undefined
                                                "
                                                :alt="
                                                    teamName(
                                                        game.home_team ||
                                                            game.homeTeam,
                                                        'Local',
                                                    )
                                                "
                                                class="h-8 w-8 shrink-0 object-contain lg:h-10 lg:w-10"
                                            />
                                            <p
                                                class="line-clamp-2 text-xs leading-tight font-bold text-white lg:text-sm"
                                            >
                                                {{
                                                    teamName(
                                                        game.home_team ||
                                                            game.homeTeam,
                                                        'Local',
                                                    )
                                                }}
                                            </p>
                                        </div>

                                        <div
                                            class="mx-auto flex h-10 min-w-[56px] items-center justify-center rounded-full border-2 border-[#ffd400] bg-[#00357b] px-2 text-center text-xs font-black text-[#ffd400] lg:h-12 lg:min-w-[72px] lg:text-sm"
                                        >
                                            {{ gameStatus(game) }}
                                        </div>

                                        <div
                                            class="flex min-w-0 items-center justify-end gap-2 text-right"
                                        >
                                            <p
                                                class="order-1 line-clamp-2 text-xs leading-tight font-bold text-white lg:text-sm"
                                            >
                                                {{
                                                    teamName(
                                                        game.away_team ||
                                                            game.awayTeam,
                                                        'Visitante',
                                                    )
                                                }}
                                            </p>
                                            <img
                                                v-if="
                                                    game.away_team?.crest ||
                                                    game.awayTeam?.crest
                                                "
                                                :src="
                                                    game.away_team?.crest ??
                                                    game.awayTeam?.crest ??
                                                    undefined
                                                "
                                                :alt="
                                                    teamName(
                                                        game.away_team ||
                                                            game.awayTeam,
                                                        'Visitante',
                                                    )
                                                "
                                                class="order-2 h-8 w-8 shrink-0 object-contain lg:h-10 lg:w-10"
                                            />
                                        </div>
                                    </div>
                                </div>
                            </Link>
                        </div>
                    </div>

                    <div
                        v-else
                        class="rounded-xl border border-dashed border-slate-700 bg-[#1e293b] px-6 py-12 text-center text-sm font-medium text-slate-400"
                    >
                        No hay partidos disponibles para mostrar.
                    </div>
                </section>

                <aside class="space-y-4">
                    <div
                        class="rounded-[2rem] border-4 border-[#083b8d] bg-[#eef1f5] p-3 shadow-[0_18px_45px_rgba(8,59,141,0.18)] lg:sticky lg:top-20 lg:p-4"
                    >
                        <div class="space-y-5">
                            <section
                                v-if="nationalCompetitions.length"
                                class="space-y-3"
                            >
                                <div class="px-1">
                                    <h3
                                        class="text-lg font-black tracking-wide text-[#00357b] uppercase"
                                    >
                                        Nacionales
                                    </h3>
                                    <p
                                        class="text-xs font-semibold text-[#2b4d7c]"
                                    >
                                        Competiciones principales en seguimiento
                                    </p>
                                </div>

                                <div class="space-y-3">
                                    <Link
                                        v-for="competition in nationalCompetitions"
                                        :key="competition.id"
                                        :href="
                                            route(
                                                'competitions.show',
                                                competition.id,
                                            )
                                        "
                                        class="block overflow-hidden rounded-2xl border-2 border-[#042b67] bg-[#00357b] shadow-[0_8px_18px_rgba(3,34,82,0.3)] transition duration-150 hover:-translate-y-1 hover:shadow-[0_14px_30px_rgba(3,34,82,0.38)]"
                                    >
                                        <div class="px-3 py-3 text-white">
                                            <div
                                                class="flex items-start justify-between gap-3"
                                            >
                                                <div
                                                    class="flex min-w-0 items-start gap-2.5"
                                                >
                                                    <div
                                                        class="flex h-10 w-10 shrink-0 items-center justify-center overflow-hidden rounded-full bg-white/95 p-1"
                                                    >
                                                        <img
                                                            v-if="
                                                                competition.emblem
                                                            "
                                                            :src="
                                                                competition.emblem
                                                            "
                                                            :alt="
                                                                competition.name ||
                                                                'Competición'
                                                            "
                                                            class="h-full w-full object-contain"
                                                        />
                                                        <span
                                                            v-else
                                                            class="text-sm font-black text-[#00357b]"
                                                        >
                                                            {{
                                                                competitionInitials(
                                                                    competition.name,
                                                                )
                                                            }}
                                                        </span>
                                                    </div>

                                                    <div class="min-w-0">
                                                        <div>
                                                            <h3
                                                                class="line-clamp-2 text-sm leading-tight font-black lg:text-base"
                                                            >
                                                                {{
                                                                    competition.name ||
                                                                    'Competición sin nombre'
                                                                }}
                                                            </h3>
                                                            <p
                                                                class="mt-1 text-[11px] font-semibold tracking-wide text-[#d8e7ff] uppercase"
                                                            >
                                                                {{
                                                                    competition.code ||
                                                                    'Sin código'
                                                                }}
                                                                ·
                                                                {{
                                                                    competitionTypeLabel(
                                                                        competition.type,
                                                                    )
                                                                }}
                                                            </p>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div
                                                    class="flex shrink-0 flex-row items-end gap-2 text-right"
                                                >
                                                    <span
                                                        class="rounded-full border border-[#d9b100] bg-[#ffd84d] px-2 py-1 text-[11px] font-bold text-[#4a3900]"
                                                    >
                                                        Jornada
                                                        {{
                                                            competition.currentMatchDay ||
                                                            'N/D'
                                                        }}
                                                    </span>
                                                    <span
                                                        class="rounded-full border border-[#d9b100] bg-[#ffd84d] px-2 py-1 text-[11px] font-bold text-[#4a3900]"
                                                    >
                                                        Finaliza
                                                        {{
                                                            formatDate(
                                                                competition.endDate,
                                                            )
                                                        }}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </Link>
                                </div>
                            </section>

                            <section
                                v-if="internationalCompetitions.length"
                                class="space-y-3"
                            >
                                <div class="px-1">
                                    <h3
                                        class="text-lg font-black tracking-wide text-[#00357b] uppercase"
                                    >
                                        Internacionales
                                    </h3>
                                </div>

                                <div class="space-y-3">
                                    <Link
                                        v-for="competition in internationalCompetitions"
                                        :key="competition.id"
                                        :href="
                                            route(
                                                'competitions.show',
                                                competition.id,
                                            )
                                        "
                                        class="block overflow-hidden rounded-2xl border-2 border-[#042b67] bg-[#00357b] shadow-[0_8px_18px_rgba(3,34,82,0.3)] transition duration-150 hover:-translate-y-1 hover:shadow-[0_14px_30px_rgba(3,34,82,0.38)]"
                                    >
                                        <div class="px-3 py-3 text-white">
                                            <div
                                                class="flex items-start justify-between gap-3"
                                            >
                                                <div
                                                    class="flex min-w-0 items-start gap-2.5"
                                                >
                                                    <div
                                                        class="flex h-10 w-10 shrink-0 items-center justify-center overflow-hidden rounded-full bg-white/95 p-1"
                                                    >
                                                        <img
                                                            v-if="
                                                                competition.emblem
                                                            "
                                                            :src="
                                                                competition.emblem
                                                            "
                                                            :alt="
                                                                competition.name ||
                                                                'Competición'
                                                            "
                                                            class="h-full w-full object-contain"
                                                        />
                                                        <span
                                                            v-else
                                                            class="text-sm font-black text-[#00357b]"
                                                        >
                                                            {{
                                                                competitionInitials(
                                                                    competition.name,
                                                                )
                                                            }}
                                                        </span>
                                                    </div>

                                                    <div class="min-w-0">
                                                        <h3
                                                            class="line-clamp-2 text-sm leading-tight font-black lg:text-base"
                                                        >
                                                            {{
                                                                competition.name ||
                                                                'Competición sin nombre'
                                                            }}
                                                        </h3>
                                                        <p
                                                            class="mt-1 text-[11px] font-semibold tracking-wide text-[#d8e7ff] uppercase"
                                                        >
                                                            {{
                                                                competition.code ||
                                                                'Sin código'
                                                            }}
                                                            ·
                                                            {{
                                                                competitionTypeLabel(
                                                                    competition.type,
                                                                )
                                                            }}
                                                        </p>
                                                    </div>
                                                </div>

                                                <div
                                                    class="flex shrink-0 flex-row items-end gap-2 text-right"
                                                >
                                                    <span
                                                        class="rounded-full border border-[#d9b100] bg-[#ffd84d] px-2 py-1 text-[11px] font-bold text-[#4a3900]"
                                                    >
                                                        Jornada
                                                        {{
                                                            competition.currentMatchDay ||
                                                            'N/D'
                                                        }}
                                                    </span>
                                                    <span
                                                        class="rounded-full border border-[#d9b100] bg-[#ffd84d] px-2 py-1 text-[11px] font-bold text-[#4a3900]"
                                                    >
                                                        Finaliza
                                                        {{
                                                            formatDate(
                                                                competition.endDate,
                                                            )
                                                        }}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </Link>
                                </div>
                            </section>

                            <section
                                v-if="otherCompetitions.length"
                                class="space-y-3"
                            >
                                <div class="px-1">
                                    <h3
                                        class="text-lg font-black tracking-wide text-[#00357b] uppercase"
                                    >
                                        Otras competiciones
                                    </h3>
                                </div>

                                <div class="space-y-2">
                                    <Link
                                        v-for="competition in otherCompetitions"
                                        :key="competition.id"
                                        :href="
                                            route(
                                                'competitions.show',
                                                competition.id,
                                            )
                                        "
                                        class="flex items-center gap-3 rounded-xl border-2 border-[#042b67] bg-[#00357b] px-3 py-2 text-white shadow-[0_6px_14px_rgba(3,34,82,0.25)] transition duration-150 hover:-translate-y-0.5"
                                    >
                                        <div
                                            class="flex h-10 w-10 shrink-0 items-center justify-center overflow-hidden rounded-full bg-white/95 p-1"
                                        >
                                            <img
                                                v-if="competition.emblem"
                                                :src="competition.emblem"
                                                :alt="
                                                    competition.name ||
                                                    'Competición'
                                                "
                                                class="h-full w-full object-contain"
                                            />
                                            <span
                                                v-else
                                                class="text-sm font-black text-[#00357b]"
                                            >
                                                {{
                                                    competitionInitials(
                                                        competition.name,
                                                    )
                                                }}
                                            </span>
                                        </div>

                                        <div class="min-w-0 flex-1">
                                            <p
                                                class="truncate text-sm font-black"
                                            >
                                                {{
                                                    competition.name ||
                                                    'Competición sin nombre'
                                                }}
                                            </p>
                                            <p
                                                class="text-[11px] font-semibold tracking-wide text-[#d8e7ff] uppercase"
                                            >
                                                {{
                                                    competition.currentMatchDay
                                                        ? `Jornada ${competition.currentMatchDay}`
                                                        : competitionTypeLabel(
                                                              competition.type,
                                                          )
                                                }}
                                            </p>
                                        </div>
                                    </Link>
                                </div>
                            </section>

                            <div
                                v-if="
                                    !nationalCompetitions.length &&
                                    !internationalCompetitions.length &&
                                    !otherCompetitions.length
                                "
                                class="rounded-2xl border-2 border-dashed border-[#0c4ea9] bg-white/60 px-6 py-12 text-center text-sm font-medium text-[#00357b]"
                            >
                                No hay competiciones disponibles para mostrar.
                            </div>
                        </div>
                    </div>
                </aside>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
