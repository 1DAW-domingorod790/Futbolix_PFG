<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
</script>

<template>
    <Head title="Torneos" />

    <AuthenticatedLayout>
        <div class="min-h-screen bg-[#0f172a] px-4 py-8 lg:px-8">
            <div class="mx-auto max-w-4xl">
                <div class="rounded-2xl border border-slate-700 bg-futbolix-dark p-10 text-center">
                    <div class="mb-4 flex justify-center">
                        <div class="flex h-16 w-16 items-center justify-center rounded-full bg-futbolix-gold/20">
                            <svg class="h-8 w-8 text-futbolix-gold" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/>
                            </svg>
                        </div>
                    </div>
                    <h2 class="mb-2 text-2xl font-bold text-white">Mis Torneos</h2>
                    <p class="text-slate-400">Próximamente podrás crear y gestionar tus propias competiciones personalizadas.</p>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
