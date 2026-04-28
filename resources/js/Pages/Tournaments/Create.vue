<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import TournamentForm from '@/Components/Tournaments/TournamentForm.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

type TournamentFormatOption = {
    value: string;
    label: string;
    has_playoffs: boolean;
    has_groups: boolean;
    has_regular_phase: boolean;
};

const props = defineProps<{
    formatOptions: TournamentFormatOption[];
}>();

const form = useForm({
    name: '',
    description: '',
    format: 'league',
    playoff_teams_count: '',
    groups_count: '',
    regular_phase_matchdays_count: '',
    logo_path: null as File | null,
});

function updateFormat(value: string) {
    const option = props.formatOptions.find((formatOption) => formatOption.value === value);

    form.format = value;

    if (!option?.has_playoffs) {
        form.playoff_teams_count = '';
    }

    if (!option?.has_groups) {
        form.groups_count = '';
    }

    if (!option?.has_regular_phase) {
        form.regular_phase_matchdays_count = '';
    }
}

function submit() {
    form.post(route('tournaments.store'), {
        forceFormData: true,
    });
}
</script>

<template>
    <Head title="Crear torneo" />

    <AuthenticatedLayout>
        <div class="bg-slate-100 px-4 py-8 dark:bg-[#0f172a] lg:px-8">
            <div class="mx-auto max-w-4xl space-y-6">
                <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-futbolix-dark">
                    <p class="text-sm font-semibold uppercase tracking-[0.2em] text-futbolix-gold">Nuevo torneo</p>
                    <h1 class="mt-2 text-3xl font-bold text-slate-900 dark:text-white">Crear torneo</h1>
                    <p class="mt-3 text-sm leading-6 text-slate-500 dark:text-slate-400">
                        Completa los datos basicos para registrar el torneo. Se creara oculto por defecto y podras
                        elegir su formato competitivo desde ahora.
                    </p>
                </section>

                <TournamentForm
                    :name="form.name"
                    :description="form.description"
                    :format="form.format"
                    :playoff-teams-count="form.playoff_teams_count"
                    :groups-count="form.groups_count"
                    :regular-phase-matchdays-count="form.regular_phase_matchdays_count"
                    :logo-path="form.logo_path"
                    :format-options="formatOptions"
                    :errors="form.errors"
                    :processing="form.processing"
                    :cancel-href="route('tournaments.index')"
                    submit-label="Crear torneo"
                    @submit="submit"
                    @update:name="form.name = $event"
                    @update:description="form.description = $event"
                    @update:format="updateFormat"
                    @update:playoff-teams-count="form.playoff_teams_count = $event"
                    @update:groups-count="form.groups_count = $event"
                    @update:regular-phase-matchdays-count="form.regular_phase_matchdays_count = $event"
                    @update:logo-path="form.logo_path = $event"
                />
            </div>
        </div>
    </AuthenticatedLayout>
</template>
