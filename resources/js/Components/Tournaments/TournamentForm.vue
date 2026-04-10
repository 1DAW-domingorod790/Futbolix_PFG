<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import FileInput from '@/Components/FileInput.vue';
import InputError from '@/Components/InputError.vue';

defineProps<{
    name: string;
    description: string;
    logoPath: File | null;
    errors: Partial<Record<'name' | 'description' | 'logo_path', string>>;
    processing?: boolean;
    cancelHref: string;
    submitLabel?: string;
}>();

const emit = defineEmits<{
    (event: 'submit'): void;
    (event: 'update:name', value: string): void;
    (event: 'update:description', value: string): void;
    (event: 'update:logoPath', value: File | null): void;
}>();
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
                :disabled="processing || name.trim().length === 0"
                class="inline-flex items-center rounded-lg bg-futbolix-green px-4 py-2 text-sm font-semibold text-white transition hover:bg-futbolix-green-dark disabled:cursor-not-allowed disabled:opacity-60"
            >
                {{ submitLabel ?? 'Guardar torneo' }}
            </button>
        </div>
    </form>
</template>
