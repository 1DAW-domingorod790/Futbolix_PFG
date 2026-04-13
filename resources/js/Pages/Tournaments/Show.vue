<script setup lang="ts">
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import { route } from 'ziggy-js';
import FileInput from '@/Components/FileInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TournamentMatchCard from '@/Components/Tournaments/TournamentMatchCard.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

type TabKey = 'matches' | 'standings' | 'scorers';
type StandingRow = { id: number; position: number | null; name: string; badge: string | null; played: number; won: number; drawn: number; lost: number; goals_for: number; goals_against: number; goal_difference: number; points: number };
type PlayerItem = { id: number; name: string; number: number; goals: number };
type TeamItem = { id: number; name: string; badge: string | null; position: number | null; players?: PlayerItem[] };
type MatchScorer = { player_id: number; goals: number };
type MatchTeam = { id: number | null; name: string | null; badge: string | null; players: PlayerItem[] };
type MatchItem = { id: number; matchday: number | null; scheduled_at: string | null; venue: string | null; status: string | null; home_score: number | null; away_score: number | null; home_scorers: MatchScorer[]; away_scorers: MatchScorer[]; home_team: MatchTeam; away_team: MatchTeam };
type TopScorer = { id: number; name: string; number: number; age: number | null; goals: number; photo_url: string; team_name: string };
type TournamentDetail = {
    id: number;
    code: number;
    name: string;
    logo_url: string;
    description: string | null;
    is_public: boolean;
    can_manage: boolean;
    created_at: string | null;
    admin: { id: number | null; name: string | null };
    summary: { teams_count: number; players_count: number; matches_count: number };
    teams: TeamItem[];
    standings: StandingRow[];
    matches: MatchItem[];
    top_scorers: TopScorer[];
};
type MatchdayGroup = { key: string; label: string; matches: MatchItem[] };

const props = defineProps<{ tournament: TournamentDetail }>();
const page = usePage<{ flash: { success?: string | null } }>();

const tabs: { key: TabKey; label: string }[] = [
    { key: 'standings', label: 'Clasificacion' },
    { key: 'matches', label: 'Partidos programados' },
    { key: 'scorers', label: 'Maximos goleadores' },
];

const activeTab = ref<TabKey>('standings');
const selectedMatchdayKey = ref('');
const showEditPanel = ref(false);
const showTeamForm = ref(false);
const showMatchForm = ref(false);

const settingsForm = useForm({
    name: props.tournament.name,
    description: props.tournament.description ?? '',
    is_public: props.tournament.is_public,
    logo_path: null as File | null,
});

const teamForm = useForm({ name: '' });
const matchForm = useForm({
    matchday: '1',
    scheduled_at: '',
    venue: '',
    home_team_id: '',
    away_team_id: '',
});

const matchdayGroups = computed<MatchdayGroup[]>(() => {
    const groups = new Map<string, MatchdayGroup>();
    props.tournament.matches.forEach((match) => {
        const key = `matchday-${match.matchday ?? 'sin-jornada'}`;
        const label = match.matchday ? `Jornada ${match.matchday}` : 'Sin jornada';
        if (!groups.has(key)) groups.set(key, { key, label, matches: [] });
        groups.get(key)?.matches.push(match);
    });
    return Array.from(groups.values());
});

const activeMatchdayGroup = computed(
    () => matchdayGroups.value.find((group) => group.key === selectedMatchdayKey.value) ?? null,
);

function formatDate(date: string | null) {
    if (!date) return 'Fecha pendiente';
    return new Date(date).toLocaleDateString('es-ES', { day: '2-digit', month: 'long', year: 'numeric' });
}

function teamInitials(name: string | null) {
    if (!name) return 'EQ';
    return name.split(' ').filter(Boolean).slice(0, 2).map((word) => word[0]).join('').toUpperCase();
}

function goalDifferenceLabel(value: number) {
    return value > 0 ? `+${value}` : String(value);
}

function saveSettings() {
    settingsForm.transform((data) => ({ ...data, is_public: data.is_public ? 1 : 0 })).patch(
        route('tournaments.update', props.tournament.id),
        { forceFormData: true, preserveScroll: true },
    );
}

function submitTeam() {
    teamForm.post(route('tournaments.teams.store', props.tournament.id), {
        preserveScroll: true,
        onSuccess: () => {
            closeTeamForm();
        },
    });
}

function submitMatch() {
    matchForm.post(route('tournaments.matches.store', props.tournament.id), {
        preserveScroll: true,
        onSuccess: () => {
            closeMatchForm();
        },
    });
}

function openActionPanel() {
    if (activeTab.value === 'standings') {
        showTeamForm.value = true;
        showMatchForm.value = false;
        teamForm.clearErrors();
        return;
    }

    if (activeTab.value === 'matches') {
        showMatchForm.value = true;
        showTeamForm.value = false;
        matchForm.clearErrors();
    }
}

function closeTeamForm() {
    showTeamForm.value = false;
    teamForm.reset();
    teamForm.clearErrors();
}

function closeMatchForm() {
    showMatchForm.value = false;
    matchForm.reset();
    matchForm.matchday = '1';
    matchForm.clearErrors();
}

if (matchdayGroups.value.length && !selectedMatchdayKey.value) {
    selectedMatchdayKey.value = matchdayGroups.value[0].key;
}

watch(activeTab, () => {
    closeTeamForm();
    closeMatchForm();
});
</script>

<template>
    <Head :title="tournament.name" />

    <AuthenticatedLayout>
        <div class="bg-slate-100 px-4 py-8 dark:bg-[#0f172a] lg:px-8">
            <div class="mx-auto max-w-7xl space-y-6">
                <div v-if="page.props.flash?.success" class="rounded-2xl border border-futbolix-green/30 bg-futbolix-green/10 px-4 py-3 text-sm text-futbolix-green">{{ page.props.flash.success }}</div>

                <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-futbolix-dark">
                    <div class="flex flex-col gap-6 lg:flex-row lg:items-start lg:justify-between">
                        <div class="flex gap-5">
                            <div class="flex shrink-0 flex-col items-center gap-3">
                                <img :src="tournament.logo_url" :alt="tournament.name" class="h-24 w-24 rounded-2xl object-cover shadow-sm">
                                <div v-if="tournament.can_manage" class="w-full">
                                    <FileInput
                                        v-model="settingsForm.logo_path"
                                        input-id="tournament-logo-update"
                                        label="Cambiar logo"
                                    />
                                    <p v-if="settingsForm.errors.logo_path" class="mt-2 text-sm text-red-500">
                                        {{ settingsForm.errors.logo_path }}
                                    </p>
                                </div>
                            </div>

                            <div class="min-w-0">
                                <p class="text-sm font-semibold uppercase tracking-[0.2em] text-futbolix-gold">Detalle del torneo</p>
                                <h1 class="mt-2 text-3xl font-bold text-slate-900 dark:text-white">{{ tournament.name }}</h1>
                                <p class="mt-3 text-sm text-slate-600 dark:text-slate-300">{{ tournament.description || 'Este torneo todavia no tiene descripcion.' }}</p>
                                <div class="mt-4 flex flex-wrap gap-2 text-xs">
                                    <span class="rounded-full bg-futbolix-gold/15 px-3 py-1 font-medium text-futbolix-gold">Codigo {{ tournament.code }}</span>
                                    <span class="rounded-full bg-slate-100 px-3 py-1 font-medium text-slate-600 dark:bg-slate-800 dark:text-slate-300">{{ tournament.is_public ? 'Visible' : 'Oculto' }}</span>
                                    <span class="rounded-full bg-slate-100 px-3 py-1 font-medium text-slate-600 dark:bg-slate-800 dark:text-slate-300">Creado el {{ formatDate(tournament.created_at) }}</span>
                                </div>
                                <p class="mt-3 text-sm text-slate-500 dark:text-slate-400">Administrador: {{ tournament.admin.name || 'No disponible' }}</p>
                            </div>
                        </div>

                        <button
                            v-if="tournament.can_manage"
                            type="button"
                            class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100 dark:hover:bg-slate-700"
                            @click="showEditPanel = !showEditPanel"
                        >
                            {{ showEditPanel ? 'Cerrar edicion' : 'Editar' }}
                        </button>
                    </div>
                </section>

                <section
                    v-if="tournament.can_manage && showEditPanel"
                    class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-futbolix-dark"
                >
                    <form class="space-y-5" @submit.prevent="saveSettings">
                        <div class="flex flex-col gap-2 lg:flex-row lg:items-center lg:justify-between">
                            <div>
                                <h2 class="text-xl font-semibold text-slate-900 dark:text-white">Editar torneo</h2>
                                <p class="text-sm text-slate-500 dark:text-slate-400">
                                    Actualiza el nombre, la descripcion y la visibilidad del torneo.
                                </p>
                            </div>
                        </div>

                        <div class="grid gap-5 lg:grid-cols-2">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-200">
                                    Nombre del torneo
                                </label>
                                <input
                                    v-model="settingsForm.name"
                                    type="text"
                                    class="mt-2 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 focus:border-futbolix-green focus:outline-none focus:ring-1 focus:ring-futbolix-green dark:border-slate-600 dark:bg-slate-900 dark:text-white"
                                >
                                <p v-if="settingsForm.errors.name" class="mt-2 text-sm text-red-500">{{ settingsForm.errors.name }}</p>
                            </div>

                            <label class="flex items-start gap-3 rounded-lg border border-slate-200 bg-slate-50 p-4 text-sm text-slate-600 dark:border-slate-700 dark:bg-slate-900/40 dark:text-slate-300">
                                <input
                                    v-model="settingsForm.is_public"
                                    type="checkbox"
                                    class="mt-1 rounded border-slate-300 text-futbolix-green focus:ring-futbolix-green"
                                >
                                <span>Hacer visible el torneo para el resto de usuarios</span>
                            </label>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-200">
                                Descripcion
                            </label>
                            <textarea
                                v-model="settingsForm.description"
                                rows="4"
                                class="mt-2 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 focus:border-futbolix-green focus:outline-none focus:ring-1 focus:ring-futbolix-green dark:border-slate-600 dark:bg-slate-900 dark:text-white"
                            />
                            <p v-if="settingsForm.errors.description" class="mt-2 text-sm text-red-500">{{ settingsForm.errors.description }}</p>
                        </div>

                        <div class="flex justify-end">
                            <button
                                type="submit"
                                :disabled="settingsForm.processing"
                                class="inline-flex items-center rounded-lg bg-futbolix-green px-4 py-2 text-sm font-semibold text-white transition hover:bg-futbolix-green-dark disabled:opacity-60"
                            >
                                Guardar cambios
                            </button>
                        </div>
                    </form>
                </section>

                <section class="rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-futbolix-dark">
                    <div class="border-b border-slate-200 px-6 py-4 dark:border-slate-700">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                            <ul class="-mb-px flex flex-wrap text-sm font-medium text-center text-slate-500 dark:text-slate-400">
                                <li v-for="tab in tabs" :key="tab.key" class="me-2">
                                    <button
                                        type="button"
                                        class="inline-block rounded-t-lg border-b-2 px-4 py-3"
                                        :class="activeTab === tab.key ? 'border-futbolix-green text-futbolix-green' : 'border-transparent hover:border-slate-300 hover:text-slate-600 dark:hover:border-slate-500 dark:hover:text-slate-300'"
                                        @click="activeTab = tab.key"
                                    >
                                        {{ tab.label }}
                                    </button>
                                </li>
                            </ul>

                            <PrimaryButton
                                v-if="tournament.can_manage && (activeTab === 'standings' || activeTab === 'matches')"
                                @click="openActionPanel"
                            >
                                {{
                                    activeTab === 'standings'
                                        ? 'Anadir equipo'
                                        : 'Anadir partido'
                                }}
                            </PrimaryButton>
                        </div>
                    </div>

                    <div v-if="activeTab === 'matches'" class="space-y-5 px-6 py-6">
                        <div v-if="matchdayGroups.length" class="space-y-4">
                            <div class="flex flex-wrap gap-2"><button v-for="group in matchdayGroups" :key="group.key" type="button" class="rounded-lg border px-4 py-2 text-sm font-medium" :class="selectedMatchdayKey === group.key ? 'border-futbolix-green bg-futbolix-green text-white' : 'border-slate-200 bg-white text-slate-700 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100'" @click="selectedMatchdayKey = group.key">{{ group.label }}</button></div>
                            <div v-if="activeMatchdayGroup" class="grid gap-4 lg:grid-cols-2">
                                <TournamentMatchCard v-for="match in activeMatchdayGroup.matches" :key="match.id" :tournament-id="tournament.id" :match="match" :can-manage="tournament.can_manage" />
                            </div>
                        </div>
                        <div v-else class="rounded-lg border border-dashed border-slate-300 px-6 py-12 text-center text-sm text-slate-500 dark:border-slate-600 dark:text-slate-400">Este torneo todavia no tiene partidos programados.</div>
                    </div>

                    <div v-else-if="activeTab === 'standings'" class="space-y-6 px-6 py-6">
                        <div v-if="tournament.standings.length" class="overflow-x-auto rounded-lg border border-slate-200 dark:border-slate-700">
                            <table class="w-full text-left text-sm text-slate-600 dark:text-slate-300">
                                <thead class="bg-slate-50 text-xs uppercase text-slate-700 dark:bg-slate-800 dark:text-slate-300"><tr><th class="px-4 py-3">Pos</th><th class="px-4 py-3">Equipo</th><th class="px-4 py-3 text-center">PJ</th><th class="px-4 py-3 text-center">G</th><th class="px-4 py-3 text-center">E</th><th class="px-4 py-3 text-center">P</th><th class="px-4 py-3 text-center">GF</th><th class="px-4 py-3 text-center">GC</th><th class="px-4 py-3 text-center">DG</th><th class="px-4 py-3 text-center">Pts</th></tr></thead>
                                <tbody>
                                    <tr v-for="team in tournament.standings" :key="team.id" class="border-t border-slate-200 bg-white dark:border-slate-700 dark:bg-slate-900/40">
                                        <td class="px-4 py-4 font-semibold text-slate-900 dark:text-white">{{ team.position ?? '-' }}</td>
                                        <td class="px-4 py-4">
                                            <Link :href="route('tournaments.teams.show', [tournament.id, team.id])" class="flex items-center gap-3 transition hover:opacity-80">
                                                <div class="flex h-10 w-10 items-center justify-center overflow-hidden rounded-full bg-slate-100 text-xs font-semibold text-slate-700 dark:bg-slate-800 dark:text-slate-100"><img v-if="team.badge" :src="team.badge" :alt="team.name" class="h-full w-full object-cover"><span v-else>{{ teamInitials(team.name) }}</span></div>
                                                <span class="font-semibold text-slate-900 dark:text-white">{{ team.name }}</span>
                                            </Link>
                                        </td>
                                        <td class="px-4 py-4 text-center">{{ team.played }}</td><td class="px-4 py-4 text-center">{{ team.won }}</td><td class="px-4 py-4 text-center">{{ team.drawn }}</td><td class="px-4 py-4 text-center">{{ team.lost }}</td><td class="px-4 py-4 text-center">{{ team.goals_for }}</td><td class="px-4 py-4 text-center">{{ team.goals_against }}</td><td class="px-4 py-4 text-center">{{ goalDifferenceLabel(team.goal_difference) }}</td><td class="px-4 py-4 text-center font-bold text-slate-900 dark:text-white">{{ team.points }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div v-else class="rounded-lg border border-dashed border-slate-300 px-6 py-12 text-center text-sm text-slate-500 dark:border-slate-600 dark:text-slate-400">Este torneo todavia no tiene equipos en la clasificacion.</div>
                    </div>

                    <div v-else class="px-6 py-6">
                        <div v-if="tournament.top_scorers.length" class="grid gap-4 lg:grid-cols-2">
                            <article v-for="(scorer, index) in tournament.top_scorers" :key="scorer.id" class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900/40">
                                <div class="flex items-start justify-between gap-4">
                                    <div class="flex min-w-0 items-center gap-4">
                                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-futbolix-green/10 text-base font-bold text-futbolix-green">{{ index + 1 }}</div>
                                        <img :src="scorer.photo_url" :alt="scorer.name" class="h-14 w-14 rounded-full object-cover">
                                        <div class="min-w-0"><p class="truncate text-base font-semibold text-slate-900 dark:text-white">{{ scorer.name }}</p><p class="text-sm text-slate-500 dark:text-slate-400">{{ scorer.team_name }} - Dorsal {{ scorer.number }}</p><p class="text-sm text-slate-500 dark:text-slate-400">{{ scorer.age ? `${scorer.age} años` : 'Edad no registrada' }}</p></div>
                                    </div>
                                    <span class="inline-flex items-center rounded-full bg-futbolix-gold/15 px-3 py-1 text-sm font-semibold text-futbolix-gold">{{ scorer.goals }} goles</span>
                                </div>
                            </article>
                        </div>
                        <div v-else class="rounded-lg border border-dashed border-slate-300 px-6 py-12 text-center text-sm text-slate-500 dark:border-slate-600 dark:text-slate-400">Aun no hay datos de goleadores para este torneo.</div>
                    </div>
                </section>
            </div>
        </div>

        <div
            v-if="tournament.can_manage && showTeamForm"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 px-4 backdrop-blur-sm"
            @click.self="closeTeamForm"
        >
            <div class="w-full max-w-md rounded-2xl border border-slate-200 bg-white p-6 shadow-xl dark:border-slate-700 dark:bg-futbolix-dark">
                <div class="mb-6">
                    <p class="text-sm font-semibold uppercase tracking-[0.2em] text-futbolix-gold">Plantilla del torneo</p>
                    <h2 class="mt-2 text-xl font-bold text-slate-900 dark:text-white">Anadir equipo</h2>
                    <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">
                        Registra un nuevo equipo para que aparezca en la clasificacion y puedas asignarlo a partidos.
                    </p>
                </div>

                <form class="space-y-4" @submit.prevent="submitTeam">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-200">
                            Nombre del equipo
                        </label>
                        <input
                            v-model="teamForm.name"
                            type="text"
                            placeholder="Ej. Los Invencibles"
                            class="mt-2 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 focus:border-futbolix-green focus:outline-none focus:ring-1 focus:ring-futbolix-green dark:border-slate-600 dark:bg-slate-900 dark:text-white"
                        >
                        <p v-if="teamForm.errors.name" class="mt-2 text-sm text-red-500">{{ teamForm.errors.name }}</p>
                    </div>

                    <div class="mt-6 flex justify-end gap-3">
                        <button
                            type="button"
                            class="rounded-lg border border-slate-300 px-4 py-2 text-sm text-slate-600 transition hover:bg-slate-50 dark:border-slate-600 dark:text-slate-300 dark:hover:bg-slate-800"
                            @click="closeTeamForm"
                        >
                            Cancelar
                        </button>
                        <PrimaryButton>
                            Guardar equipo
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </div>

        <div
            v-if="tournament.can_manage && showMatchForm"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 px-4 backdrop-blur-sm"
            @click.self="closeMatchForm"
        >
            <div class="w-full max-w-2xl rounded-2xl border border-slate-200 bg-white p-6 shadow-xl dark:border-slate-700 dark:bg-futbolix-dark">
                <div class="mb-6">
                    <p class="text-sm font-semibold uppercase tracking-[0.2em] text-futbolix-gold">Calendario del torneo</p>
                    <h2 class="mt-2 text-xl font-bold text-slate-900 dark:text-white">Anadir partido</h2>
                    <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">
                        Configura la jornada, los equipos y el campo para dejar el partido listo en el calendario.
                    </p>
                </div>

                <form class="grid gap-4 lg:grid-cols-2" @submit.prevent="submitMatch">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-200">
                            Jornada
                        </label>
                        <input
                            v-model="matchForm.matchday"
                            type="number"
                            min="1"
                            class="mt-2 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 focus:border-futbolix-green focus:outline-none focus:ring-1 focus:ring-futbolix-green dark:border-slate-600 dark:bg-slate-900 dark:text-white"
                        >
                        <p v-if="matchForm.errors.matchday" class="mt-2 text-sm text-red-500">{{ matchForm.errors.matchday }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-200">
                            Fecha y hora
                        </label>
                        <input
                            v-model="matchForm.scheduled_at"
                            type="datetime-local"
                            class="mt-2 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 focus:border-futbolix-green focus:outline-none focus:ring-1 focus:ring-futbolix-green dark:border-slate-600 dark:bg-slate-900 dark:text-white"
                        >
                        <p v-if="matchForm.errors.scheduled_at" class="mt-2 text-sm text-red-500">{{ matchForm.errors.scheduled_at }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-200">
                            Equipo local
                        </label>
                        <select
                            v-model="matchForm.home_team_id"
                            class="mt-2 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 focus:border-futbolix-green focus:outline-none focus:ring-1 focus:ring-futbolix-green dark:border-slate-600 dark:bg-slate-900 dark:text-white"
                        >
                            <option disabled value="">Selecciona un equipo</option>
                            <option v-for="team in tournament.teams" :key="`home-${team.id}`" :value="String(team.id)">
                                {{ team.name }}
                            </option>
                        </select>
                        <p v-if="matchForm.errors.home_team_id" class="mt-2 text-sm text-red-500">{{ matchForm.errors.home_team_id }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-200">
                            Equipo visitante
                        </label>
                        <select
                            v-model="matchForm.away_team_id"
                            class="mt-2 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 focus:border-futbolix-green focus:outline-none focus:ring-1 focus:ring-futbolix-green dark:border-slate-600 dark:bg-slate-900 dark:text-white"
                        >
                            <option disabled value="">Selecciona un equipo</option>
                            <option v-for="team in tournament.teams" :key="`away-${team.id}`" :value="String(team.id)">
                                {{ team.name }}
                            </option>
                        </select>
                        <p v-if="matchForm.errors.away_team_id" class="mt-2 text-sm text-red-500">{{ matchForm.errors.away_team_id }}</p>
                    </div>

                    <div class="lg:col-span-2">
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-200">
                            Campo
                        </label>
                        <input
                            v-model="matchForm.venue"
                            type="text"
                            placeholder="Ej. Campo municipal"
                            class="mt-2 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 focus:border-futbolix-green focus:outline-none focus:ring-1 focus:ring-futbolix-green dark:border-slate-600 dark:bg-slate-900 dark:text-white"
                        >
                        <p v-if="matchForm.errors.venue" class="mt-2 text-sm text-red-500">{{ matchForm.errors.venue }}</p>
                    </div>

                    <div class="mt-2 flex justify-end gap-3 lg:col-span-2">
                        <button
                            type="button"
                            class="rounded-lg border border-slate-300 px-4 py-2 text-sm text-slate-600 transition hover:bg-slate-50 dark:border-slate-600 dark:text-slate-300 dark:hover:bg-slate-800"
                            @click="closeMatchForm"
                        >
                            Cancelar
                        </button>
                        <PrimaryButton>
                            Guardar partido
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
