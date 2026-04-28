<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import FileInput from '@/Components/FileInput.vue';
import InputError from '@/Components/InputError.vue';

type TournamentFormatOption = {
    value: string;
    label: string;
    has_playoffs: boolean;
    has_groups: boolean;
    has_regular_phase: boolean;
};

type TournamentFormErrors = Partial<
    Record<
        'name' | 'description' | 'format' | 'playoff_teams_count' | 'groups_count' | 'regular_phase_matchdays_count' | 'logo_path',
        string
    >
>;

const props = defineProps<{
    name: string;
    description: string;
    format: string;
    playoffTeamsCount: number | string | null;
    groupsCount: number | string | null;
    regularPhaseMatchdaysCount: number | string | null;
    logoPath: File | null;
    formatOptions: TournamentFormatOption[];
    errors: TournamentFormErrors;
    processing?: boolean;
    cancelHref: string;
    submitLabel?: string;
}>();

const emit = defineEmits<{
    (event: 'submit'): void;
    (event: 'update:name', value: string): void;
    (event: 'update:description', value: string): void;
    (event: 'update:format', value: string): void;
    (event: 'update:playoffTeamsCount', value: string): void;
    (event: 'update:groupsCount', value: string): void;
    (event: 'update:regularPhaseMatchdaysCount', value: string): void;
    (event: 'update:logoPath', value: File | null): void;
}>();

const selectedFormat = computed(() => props.formatOptions.find((option) => option.value === props.format) ?? null);
</script>

<template>
    <form
        class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-futbolix-dark"
        @submit.prevent="emit('submit')"
    >
        <div class="space-y-6">
            <div>
                <label for="name" class="block text-sm font-medium text-slate-700 dark:text-slate-200">
                    Nombre del torneo
                </label>
                <input
                    id="name"
                    type="text"
                    maxlength="120"
                    required
                    autofocus
                    :value="name"
                    class="mt-2 block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm transition focus:border-futbolix-green focus:outline-none focus:ring-1 focus:ring-futbolix-green dark:border-slate-700 dark:bg-slate-900 dark:text-white"
                    placeholder="Ej. Liga de amigos 2026"
                    @input="emit('update:name', ($event.target as HTMLInputElement).value)"
                />
                <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">
                    Usa un nombre claro para reconocer el torneo rapidamente.
                </p>
                <InputError class="mt-2" :message="errors.name" />
            </div>

            <div>
                <label for="description" class="block text-sm font-medium text-slate-700 dark:text-slate-200">
                    Descripcion (opcional)
                </label>
                <textarea
                    id="description"
                    maxlength="1000"
                    :value="description"
                    class="mt-2 block min-h-32 w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm transition focus:border-futbolix-green focus:outline-none focus:ring-1 focus:ring-futbolix-green dark:border-slate-700 dark:bg-slate-900 dark:text-white"
                    placeholder="Describe brevemente el objetivo o formato del torneo."
                    @input="emit('update:description', ($event.target as HTMLTextAreaElement).value)"
                />
                <div class="mt-2 flex items-center justify-between gap-3 text-xs text-slate-500 dark:text-slate-400">
                    <span>La descripcion te ayudara a diferenciar torneos cuando tengas varios.</span>
                    <span>{{ description.length }}/1000</span>
                </div>
                <InputError class="mt-2" :message="errors.description" />
            </div>

            <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-900/40">
                <label for="format" class="block text-sm font-medium text-slate-700 dark:text-slate-200">
                    Formato del torneo
                </label>
                <select
                    id="format"
                    required
                    :value="format"
                    class="mt-2 block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm transition focus:border-futbolix-green focus:outline-none focus:ring-1 focus:ring-futbolix-green dark:border-slate-700 dark:bg-slate-900 dark:text-white"
                    @change="emit('update:format', ($event.target as HTMLSelectElement).value)"
                >
                    <option disabled value="">Selecciona un formato</option>
                    <option v-for="option in formatOptions" :key="option.value" :value="option.value">
                        {{ option.label }}
                    </option>
                </select>
                <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">
                    Elige la estructura principal. La generacion automatica de calendario se podra anadir mas adelante.
                </p>
                <InputError class="mt-2" :message="errors.format" />

                <div
                    v-if="selectedFormat?.has_groups || selectedFormat?.has_playoffs || selectedFormat?.has_regular_phase"
                    class="mt-5 grid gap-4 md:grid-cols-2"
                >
                    <div v-if="selectedFormat?.has_groups">
                        <label for="groups-count" class="block text-sm font-medium text-slate-700 dark:text-slate-200">
                            Numero de grupos
                        </label>
                        <input
                            id="groups-count"
                            type="number"
                            min="1"
                            max="64"
                            step="1"
                            :value="groupsCount ?? ''"
                            class="mt-2 block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm transition focus:border-futbolix-green focus:outline-none focus:ring-1 focus:ring-futbolix-green dark:border-slate-700 dark:bg-slate-900 dark:text-white"
                            placeholder="Ej. 4"
                            @input="emit('update:groupsCount', ($event.target as HTMLInputElement).value)"
                        />
                        <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">
                            Se guardara para preparar la fase de grupos.
                        </p>
                        <InputError class="mt-2" :message="errors.groups_count" />
                    </div>

                    <div v-if="selectedFormat?.has_playoffs">
                        <label for="playoff-teams-count" class="block text-sm font-medium text-slate-700 dark:text-slate-200">
                            Equipos en playoffs
                        </label>
                        <input
                            id="playoff-teams-count"
                            type="number"
                            min="2"
                            max="64"
                            step="1"
                            :value="playoffTeamsCount ?? ''"
                            class="mt-2 block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm transition focus:border-futbolix-green focus:outline-none focus:ring-1 focus:ring-futbolix-green dark:border-slate-700 dark:bg-slate-900 dark:text-white"
                            placeholder="Ej. 8"
                            @input="emit('update:playoffTeamsCount', ($event.target as HTMLInputElement).value)"
                        />
                        <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">
                            Usa 2, 4, 8, 16, 32 o 64 para un cuadro directo.
                        </p>
                        <InputError class="mt-2" :message="errors.playoff_teams_count" />
                    </div>

                    <div v-if="selectedFormat?.has_regular_phase">
                        <label for="regular-phase-matchdays-count" class="block text-sm font-medium text-slate-700 dark:text-slate-200">
                            Jornadas de fase previa
                        </label>
                        <input
                            id="regular-phase-matchdays-count"
                            type="number"
                            min="1"
                            max="100"
                            step="1"
                            :value="regularPhaseMatchdaysCount ?? ''"
                            class="mt-2 block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm transition focus:border-futbolix-green focus:outline-none focus:ring-1 focus:ring-futbolix-green dark:border-slate-700 dark:bg-slate-900 dark:text-white"
                            placeholder="Ej. 10"
                            @input="emit('update:regularPhaseMatchdaysCount', ($event.target as HTMLInputElement).value)"
                        />
                        <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">
                            Al terminar esta jornada se podra generar automaticamente el bracket.
                        </p>
                        <InputError class="mt-2" :message="errors.regular_phase_matchdays_count" />
                    </div>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-200">
                    Logo del torneo (opcional)
                </label>
                <div class="mt-2">
                    <FileInput
                        :model-value="logoPath"
                        input-id="tournament-logo"
                        label="Subir logo"
                        @update:model-value="emit('update:logoPath', $event)"
                    />
                </div>
                <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">
                    Si no subes uno, se usara la imagen por defecto del torneo.
                </p>
                <InputError class="mt-2" :message="errors.logo_path" />
            </div>
        </div>

        <div class="mt-8 flex items-center justify-end gap-3">
            <Link
                :href="cancelHref"
                class="inline-flex items-center rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-800"
            >
                Cancelar
            </Link>

            <button
                type="submit"
                :disabled="processing || name.trim().length === 0 || format.trim().length === 0"
                class="inline-flex items-center rounded-lg bg-futbolix-green px-4 py-2 text-sm font-semibold text-white transition hover:bg-futbolix-green-dark disabled:cursor-not-allowed disabled:opacity-60"
            >
                {{ submitLabel ?? 'Guardar torneo' }}
            </button>
        </div>
    </form>
</template>
