<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    games: {
        type: Array,
        default: () => [],
    },
    competitions: {
        type: Array,
        default: () => [],
    },
});

const selectedDateIndex = ref(0);

const groupedDates = computed(() => {
    const groups = new Map();

    props.games.forEach((game: any) => {
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

        groups.get(dateKey).games.push(game);
    });

    return Array.from(groups.values())
        .sort((a, b) => a.date - b.date)
        .map((group) => ({
            ...group,
            games: [...group.games].sort((a, b) => new Date(a.utc_date).getTime() - new Date(b.utc_date).getTime()),
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

const activeDateGroup = computed(() => groupedDates.value[safeSelectedDateIndex.value] ?? null);

const competitionSections = computed(() => {
    if (!activeDateGroup.value) {
        return [];
    }

    const sections = new Map();

    activeDateGroup.value.games.forEach((game: any) => {
        const competition = game.competition ?? null;
        const competitionKey = competition?.id ?? `competition-${game.id}`;

        if (!sections.has(competitionKey)) {
            sections.set(competitionKey, {
                id: competitionKey,
                name: competition?.name || 'Sin competición',
                emblem: competition?.emblem || null,
                matchday: competition?.currentMatchDay || null,
                games: [],
            });
        }

        sections.get(competitionKey).games.push(game);
    });

    return Array.from(sections.values());
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

function formatTime(date: string | number | Date) {
    if (!date) {
        return '--:--';
    }

    return new Date(date).toLocaleTimeString('es-ES', {
        hour: '2-digit',
        minute: '2-digit',
    });
}

function formatDateTime(date: string | number | Date) {
    if (!date) {
        return 'Sin fecha';
    }

    return new Date(date).toLocaleDateString('es-ES', {
        day: '2-digit',
        month: '2-digit',
    }) + ` ${formatTime(date)}`;
}

function teamName(team: any, fallback: string) {
    return team?.shortname || team?.name || fallback;
}

function venueName(game: any) {
    return game.home_team?.venue || game.homeTeam?.venue || 'Estadio pendiente';
}

function gameStatus(game: any) {
    if (game.status === 'FINISHED') {
        return `${game.home_score ?? 0} - ${game.away_score ?? 0}`;
    }

    if (game.status === 'IN_PLAY') {
        return 'En juego';
    }

    return 'VS';
}
</script>

<template>
    <Head title="Partidos" />

    <AuthenticatedLayout>
        <div class="min-h-screen bg-[#0f172a] px-3 py-4 lg:px-6 lg:py-6">
            <div class="mx-auto w-full max-w-screen-2xl">
                <div class="mb-4 flex items-center gap-2 rounded-xl bg-[#ffd400] shadow-[0_8px_20px_rgba(160,130,0,0.2)] border border-2 px-3 py-2 sticky top-18 z-10 md:mx-auto md:max-w-sm">
                    <button
                        type="button"
                        class="flex h-7 w-7 items-center justify-center rounded-full text-slate-300 transition hover:bg-black/10 disabled:cursor-not-allowed disabled:opacity-40"
                        :disabled="safeSelectedDateIndex === 0"
                        @click="showPreviousDay"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="black" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="19" y1="12" x2="5" y2="12"></line>
                            <polyline points="12 19 5 12 12 5"></polyline>
                        </svg>
                    </button>
                    <div class="flex-1 text-center text-sm font-semibold text-black uppercase tracking-wide">
                        {{ currentDayLabel }}
                    </div>
                    <button
                        type="button"
                        class="flex h-7 w-7 items-center justify-center rounded-full text-slate-300 transition hover:bg-black/10 disabled:cursor-not-allowed disabled:opacity-40"
                        :disabled="safeSelectedDateIndex >= groupedDates.length - 1"
                        @click="showNextDay"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="black" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                            <polyline points="12 5 19 12 12 19"></polyline>
                        </svg>
                    </button>
                </div>

                <div
                    v-if="competitionSections.length"
                    :class="competitionSections.length === 1 ? 'flex justify-center' : 'grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-3'"
                    class="rounded-[2rem] border-4 border-[#083b8d] bg-[#eef1f5] p-3 shadow-[0_18px_45px_rgba(8,59,141,0.18)] lg:p-4 mt-4"
                >

                    <section
                        v-for="section in competitionSections"
                        :key="section.id"
                        :class="competitionSections.length === 1 ? 'w-full max-w-md' : ''"
                        class="overflow-hidden rounded-xl border-2 border-[#042b67] bg-[#00357b] shadow-[0_6px_14px_rgba(3,34,82,0.25)]"
                    >
                        <div class="flex items-center justify-between gap-2 border-b border-[#0c4ea9] px-3 py-2 text-white lg:px-4 lg:py-3">
                            <div class="flex min-w-0 items-center gap-2">
                                <div class="flex h-7 w-7 items-center justify-center overflow-hidden rounded-full bg-white/95 p-1 lg:h-8 lg:w-8">
                                    <img
                                        v-if="section.emblem"
                                        :src="section.emblem"
                                        :alt="section.name"
                                        class="h-full w-full object-contain"
                                    >
                                    <span v-else class="text-xs font-black text-[#00357b]">
                                        {{ section.name.slice(0, 2).toUpperCase() }}
                                    </span>
                                </div>
                                <h3 class="truncate text-base font-black lg:text-lg">
                                    {{ section.name }}
                                </h3>
                            </div>
                            <div class="shrink-0 text-xs font-bold lg:text-sm">
                                Jornada {{ section.matchday || '-' }}
                            </div>
                        </div>

                        <div class="space-y-2 p-2">
                            <article
                                v-for="game in section.games"
                                :key="game.id"
                                class="rounded-lg border border-[#5ca1ff] bg-[#0051b2] px-2 py-1.5 text-white lg:px-3 lg:py-2"
                            >
                                <div class="mb-1.5 flex items-center justify-between gap-2 text-[9px] font-semibold uppercase tracking-wide text-[#d8e7ff] lg:text-[10px]">
                                    <span>{{ formatDateTime(game.utc_date) }}</span>
                                    <span class="truncate text-right">{{ venueName(game) }}</span>
                                </div>

                                <div class="grid grid-cols-[minmax(0,1fr)_auto_minmax(0,1fr)] items-center gap-2 lg:grid-cols-[minmax(0,1fr)_72px_minmax(0,1fr)] lg:gap-3">
                                    <div class="flex min-w-0 items-center gap-2 text-left">
                                        <img
                                            v-if="game.home_team?.crest || game.homeTeam?.crest"
                                            :src="game.home_team?.crest || game.homeTeam?.crest"
                                            :alt="teamName(game.home_team || game.homeTeam, 'Local')"
                                            class="h-8 w-8 shrink-0 object-contain lg:h-10 lg:w-10"
                                        >
                                        <p class="line-clamp-2 text-xs font-bold leading-tight lg:text-sm">
                                            {{ teamName(game.home_team || game.homeTeam, 'Local') }}
                                        </p>
                                    </div>

                                    <div
                                        class="mx-auto flex h-9 w-9 items-center justify-center rounded-full border-2 border-[#ffd400] bg-[#00357b] px-1 text-center text-xs font-black text-[#ffd400] lg:h-11 lg:w-11 lg:text-sm"
                                    >
                                        {{ gameStatus(game) }}
                                    </div>

                                    <div class="flex min-w-0 items-center justify-end gap-2 text-right">
                                        <p class="order-1 line-clamp-2 text-xs font-bold leading-tight lg:order-1 lg:text-sm">
                                            {{ teamName(game.away_team || game.awayTeam, 'Visitante') }}
                                        </p>
                                        <img
                                            v-if="game.away_team?.crest || game.awayTeam?.crest"
                                            :src="game.away_team?.crest || game.awayTeam?.crest"
                                            :alt="teamName(game.away_team || game.awayTeam, 'Visitante')"
                                            class="order-2 h-8 w-8 shrink-0 object-contain lg:order-2 lg:h-10 lg:w-10"
                                        >
                                    </div>
                                </div>
                            </article>
                        </div>
                    </section>
                </div>

                <div
                    v-else
                    class="rounded-xl border border-dashed border-slate-700 bg-[#1e293b] px-6 py-12 text-center text-sm font-medium text-slate-400"
                >
                    No hay partidos disponibles para mostrar.
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
