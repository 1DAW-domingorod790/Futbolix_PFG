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
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-white">
                Partidos    
            </h2>
        </template>

        <div class="min-h-screen bg-[radial-gradient(circle_at_top,#f7f9fc_0%,#dce4ef_45%,#c7d3e6_100%)] px-4 py-8 lg:px-8 lg:py-12">
            <div class="mx-auto w-full max-w-7xl">
                <section class="rounded-[2rem] border-4 border-[#083b8d] bg-[#eef1f5] p-4 shadow-[0_18px_45px_rgba(8,59,141,0.18)] lg:p-6">
                    <div class="rounded-[1.75rem] p-3 lg:p-6">
                        <div class="mb-6 flex items-center gap-3 rounded-2xl bg-[#ffd400] px-4 py-3 shadow-[0_8px_20px_rgba(160,130,0,0.2)] lg:mx-auto lg:max-w-2xl lg:px-6 lg:py-4 sticky top-20 z-10 border border-2">
                            <button
                                type="button"
                                class="flex h-10 w-10 items-center justify-center rounded-full text-3xl font-black text-black transition hover:bg-black/10 disabled:cursor-not-allowed disabled:opacity-40 lg:h-12 lg:w-12"
                                :disabled="safeSelectedDateIndex === 0"
                                @click="showPreviousDay"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <line x1="19" y1="12" x2="5" y2="12"></line>
                                    <polyline points="12 19 5 12 12 5"></polyline>
                                </svg>
                            </button>
                            <div class="flex-1 text-center text-2xl font-black text-black font-semibold lg:text-4xl">
                                {{ currentDayLabel }}
                            </div>
                            <button
                                type="button"
                                class="flex h-10 w-10 items-center justify-center rounded-full text-3xl font-black text-black transition hover:bg-black/10 disabled:cursor-not-allowed disabled:opacity-40 lg:h-12 lg:w-12"
                                :disabled="safeSelectedDateIndex >= groupedDates.length - 1"
                                @click="showNextDay"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <line x1="5" y1="12" x2="19" y2="12"></line>
                                    <polyline points="12 5 19 12 12 19"></polyline>
                                </svg>
                            </button>
                        </div>

                        <div
                            v-if="competitionSections.length"
                            class="grid grid-cols-1 gap-5 xl:grid-cols-2"
                        >
                            <section
                                v-for="section in competitionSections"
                                :key="section.id"
                                class="overflow-hidden rounded-2xl border-2 border-[#042b67] bg-[#00357b] shadow-[0_8px_18px_rgba(3,34,82,0.3)]"
                            >   
                                <div class="flex items-center justify-between gap-3 border-b border-[#0c4ea9] px-4 py-3 text-white lg:px-5 lg:py-4">
                                    <div class="flex min-w-0 items-center gap-3">
                                        <div class="flex h-8 w-8 items-center justify-center overflow-hidden rounded-full bg-white/95 p-1 lg:h-10 lg:w-10">
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
                                        <h3 class="truncate text-xl font-black lg:text-2xl">
                                            {{ section.name }}
                                        </h3>
                                    </div>
                                    <div class="shrink-0 text-sm font-bold lg:text-base">
                                        Jornada {{ section.matchday || '-' }}
                                    </div>
                                </div>

                                <div class="space-y-3 p-2 lg:p-3">
                                    <article
                                        v-for="game in section.games"
                                        :key="game.id"
                                        class="rounded-xl border border-[#5ca1ff] bg-[#0051b2] px-3 py-2 text-white lg:px-4 lg:py-3"
                                    >
                                        <div class="mb-3 flex items-center justify-between gap-3 text-[10px] font-semibold uppercase tracking-wide text-[#d8e7ff] lg:text-xs">
                                            <span>{{ formatDateTime(game.utc_date) }}</span>
                                            <span class="truncate text-right">{{ venueName(game) }}</span>
                                        </div>

                                        <div class="grid grid-cols-[minmax(0,1fr)_auto_minmax(0,1fr)] items-center gap-3 lg:grid-cols-[minmax(0,1fr)_96px_minmax(0,1fr)] lg:gap-4">
                                            <div class="flex min-w-0 items-center gap-3 text-left">
                                                <img
                                                    v-if="game.home_team?.crest || game.homeTeam?.crest"
                                                    :src="game.home_team?.crest || game.homeTeam?.crest"
                                                    :alt="teamName(game.home_team || game.homeTeam, 'Local')"
                                                    class="h-12 w-12 shrink-0 object-contain lg:h-14 lg:w-14"
                                                >
                                                <p class="line-clamp-2 text-sm font-bold leading-tight lg:text-base">
                                                    {{ teamName(game.home_team || game.homeTeam, 'Local') }}
                                                </p>
                                            </div>

                                            <div
                                                class="mx-auto flex h-11 w-11 items-center justify-center rounded-full border-2 border-[#ffd400] bg-[#00357b] px-2 text-center text-sm font-black text-[#ffd400] lg:h-16 lg:w-16 lg:text-base"
                                            >
                                                {{ gameStatus(game) }}
                                            </div>

                                            <div class="flex min-w-0 items-center justify-end gap-3 text-right">
                                                <p class="order-1 line-clamp-2 text-sm font-bold leading-tight lg:order-1 lg:text-base">
                                                    {{ teamName(game.away_team || game.awayTeam, 'Visitante') }}
                                                </p>
                                                <img
                                                    v-if="game.away_team?.crest || game.awayTeam?.crest"
                                                    :src="game.away_team?.crest || game.awayTeam?.crest"
                                                    :alt="teamName(game.away_team || game.awayTeam, 'Visitante')"
                                                    class="order-2 h-12 w-12 shrink-0 object-contain lg:order-2 lg:h-14 lg:w-14"
                                                >
                                            </div>
                                        </div>
                                    </article>
                                </div>
                            </section>
                        </div>

                        <div
                            v-else
                            class="rounded-2xl border-2 border-dashed border-[#0c4ea9] bg-white/60 px-6 py-12 text-center text-sm font-medium text-[#00357b]"
                        >
                            No hay partidos disponibles para mostrar.
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
