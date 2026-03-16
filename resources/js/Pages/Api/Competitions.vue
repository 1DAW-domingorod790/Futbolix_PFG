<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { route } from 'ziggy-js';

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

const props = defineProps<{
    competitions: Competition[];
}>();

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

const nationalCompetitions = computed(() =>
    nationalCompetitionIds
        .map((competitionId) =>
            normalizedCompetitions.value.find(
                (competition) => Number(competition.external_id) === competitionId,
            ),
        )
        .filter(Boolean) as Competition[],
);

const internationalCompetitions = computed(() =>
    internationalCompetitionIds
        .map((competitionId) =>
            normalizedCompetitions.value.find(
                (competition) => Number(competition.external_id) === competitionId,
            ),
        )
        .filter(Boolean) as Competition[],
);

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
    <Head title="Competiciones" />

    <AuthenticatedLayout >
       <template #header>
            <h2 class="text-xl font-semibold leading-tight text-white">
                Competiciones
            </h2>
            <p class="text-sm font-semibold text-white/75">
                Aquí encontrarás información actualizada sobre ligas nacionales e internacionales, incluyendo detalles como la jornada actual y la fecha de finalización.
            </p>
        </template>

        <div class="px-4 py-5 lg:px-6 lg:py-6">
            <div class="mx-auto w-full max-w-[1500px]">
                <section class="rounded-[2rem] border-4 border-[#083b8d] bg-[#eef1f5] p-3 shadow-[0_18px_45px_rgba(8,59,141,0.18)] lg:p-4 mt-4">
                    <div class="rounded-[1.75rem] p-2 lg:p-3">
                        <div
                            v-if="nationalCompetitions.length || internationalCompetitions.length"
                            class="space-y-5"
                        >
                            <section
                                v-if="nationalCompetitions.length"
                                class="space-y-3"
                            >
                                <h3 class="px-1 text-lg font-black uppercase tracking-wide text-[#00357b]">
                                    Ligas nacionales:
                                </h3>

                                <div class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-5">
                                    <Link
                                        v-for="competition in nationalCompetitions"
                                        :key="competition.id"
                                        :href="route('competitions.show', competition.id)"
                                        class="block overflow-hidden rounded-2xl border-2 border-[#042b67] bg-[#00357b] shadow-[0_8px_18px_rgba(3,34,82,0.3)] transition duration-150 hover:-translate-y-1 hover:shadow-[0_14px_30px_rgba(3,34,82,0.38)]"
                                    >
                                        <div class="border-b border-[#0c4ea9] px-3 py-3 text-white">
                                            <div class="flex items-start gap-2.5">
                                                <div class="flex h-10 w-10 shrink-0 items-center justify-center overflow-hidden rounded-full bg-white/95 p-1 lg:h-11 lg:w-11">
                                                    <img
                                                        v-if="competition.emblem"
                                                        :src="competition.emblem"
                                                        :alt="competition.name || 'Competición'"
                                                        class="h-full w-full object-contain"
                                                    >
                                                    <span
                                                        v-else
                                                        class="text-sm font-black text-[#00357b]"
                                                    >
                                                        {{ competitionInitials(competition.name) }}
                                                    </span>
                                                </div>

                                                <div class="min-w-0">
                                                    <h3 class="line-clamp-2 text-sm font-black leading-tight text-white lg:text-base">
                                                        {{ competition.name || 'Competición sin nombre' }}
                                                    </h3>
                                                    <p class="mt-1 text-[11px] font-semibold uppercase tracking-wide text-[#d8e7ff]">
                                                        {{ competition.code || 'Sin código' }} · {{ competitionTypeLabel(competition.type) }}
                                                    </p>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="grid gap-2 p-2.5">
                                            <article class="rounded-xl border border-[#5ca1ff] bg-[#0051b2] px-3 py-3 text-white">
                                                <p class="text-[10px] font-black uppercase tracking-[0.14em] text-[#d8e7ff]">
                                                    Jornada actual
                                                </p>
                                                <p class="mt-1.5 text-xl font-black">
                                                    {{ competition.currentMatchDay || 'N/D' }}
                                                </p>
                                            </article>

                                            <article class="rounded-xl border border-[#5ca1ff] bg-[#0051b2] px-3 py-3 text-white">
                                                <p class="text-[10px] font-black uppercase tracking-[0.14em] text-[#d8e7ff]">
                                                    Fecha de finalización
                                                </p>
                                                <p class="mt-1.5 text-sm font-bold leading-snug">
                                                    {{ formatDate(competition.endDate) }}
                                                </p>
                                            </article>
                                        </div>
                                    </Link>
                                </div>
                            </section>

                            <section
                                v-if="internationalCompetitions.length"
                                class="space-y-3"
                            >
                                <h3 class="px-1 text-lg font-black uppercase tracking-wide text-[#00357b]">
                                    Competiciones internacionales:
                                </h3>

                                <div class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-5">
                                    <Link
                                        v-for="competition in internationalCompetitions"
                                        :key="competition.id"
                                        :href="route('competitions.show', competition.id)"
                                        class="block overflow-hidden rounded-2xl border-2 border-[#042b67] bg-[#00357b] shadow-[0_8px_18px_rgba(3,34,82,0.3)] transition duration-150 hover:-translate-y-1 hover:shadow-[0_14px_30px_rgba(3,34,82,0.38)]"
                                    >
                                        <div class="border-b border-[#0c4ea9] px-3 py-3 text-white">
                                            <div class="flex items-start gap-2.5">
                                                <div class="flex h-10 w-10 shrink-0 items-center justify-center overflow-hidden rounded-full bg-white/95 p-1 lg:h-11 lg:w-11">
                                                    <img
                                                        v-if="competition.emblem"
                                                        :src="competition.emblem"
                                                        :alt="competition.name || 'Competición'"
                                                        class="h-full w-full object-contain"
                                                    >
                                                    <span
                                                        v-else
                                                        class="text-sm font-black text-[#00357b]"
                                                    >
                                                        {{ competitionInitials(competition.name) }}
                                                    </span>
                                                </div>

                                                <div class="min-w-0">
                                                    <h3 class="line-clamp-2 text-sm font-black leading-tight text-white lg:text-base">
                                                        {{ competition.name || 'Competición sin nombre' }}
                                                    </h3>
                                                    <p class="mt-1 text-[11px] font-semibold uppercase tracking-wide text-[#d8e7ff]">
                                                        {{ competition.code || 'Sin código' }} · {{ competitionTypeLabel(competition.type) }}
                                                    </p>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="grid gap-2 p-2.5">
                                            <article class="rounded-xl border border-[#5ca1ff] bg-[#0051b2] px-3 py-3 text-white">
                                                <p class="text-[10px] font-black uppercase tracking-[0.14em] text-[#d8e7ff]">
                                                    Jornada actual
                                                </p>
                                                <p class="mt-1.5 text-xl font-black">
                                                    {{ competition.currentMatchDay || 'N/D' }}
                                                </p>
                                            </article>

                                            <article class="rounded-xl border border-[#5ca1ff] bg-[#0051b2] px-3 py-3 text-white">
                                                <p class="text-[10px] font-black uppercase tracking-[0.14em] text-[#d8e7ff]">
                                                    Fecha de finalización
                                                </p>
                                                <p class="mt-1.5 text-sm font-bold leading-snug">
                                                    {{ formatDate(competition.endDate) }}
                                                </p>
                                            </article>
                                        </div>
                                    </Link>
                                </div>
                            </section>
                        </div>

                        <div
                            v-else
                            class="rounded-2xl border-2 border-dashed border-[#0c4ea9] bg-white/60 px-6 py-12 text-center text-sm font-medium text-[#00357b]"
                        >
                            No hay competiciones disponibles para mostrar.
                        </div>
                    </div>
                </section>
            </div>
            <br><br>
        </div>
    </AuthenticatedLayout>
</template>
