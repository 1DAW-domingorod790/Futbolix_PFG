<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import TournamentForm from '@/Components/Tournaments/TournamentForm.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { route } from 'ziggy-js';

const form = useForm({
    name: '',
    description: '',
});

function submit() {
    form.post(route('tournaments.store'));
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
                        Completa los datos basicos para registrar el torneo. Mas adelante podremos ampliar esta ficha
                        con equipos, formato de competicion, jornadas y clasificacion.
                    </p>
                </section>

                <TournamentForm
                    :name="form.name"
                    :description="form.description"
                    :errors="form.errors"
                    :processing="form.processing"
                    :cancel-href="route('tournaments.index')"
                    submit-label="Crear torneo"
                    @submit="submit"
                    @update:name="form.name = $event"
                    @update:description="form.description = $event"
                />
            </div>
        </div>
    </AuthenticatedLayout>
</template>
