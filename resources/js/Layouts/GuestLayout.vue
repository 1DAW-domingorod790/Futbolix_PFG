<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import ApplicationLogoIcon from '@/Components/ApplicationLogoIcon.vue';

</script>

<template>
    <div 
    class="flex min-h-screen flex-col items-center justify-center bg-futbolix-navy px-4"
    style="background: linear-gradient(180deg, #003B8C 0%, #00142F 50%);">

        <!-- Logo -->
        <div class="mb-8 text-center">
            <Link href="/" class="inline-flex flex-col items-center gap-2">
                <ApplicationLogoIcon />
                <h1 class="text-xl font-bold text-white" style="font-size: xx-large;">Futbolix</h1>
            </Link>

        </div>

        <!-- Card -->
        <div class="w-full max-w-md rounded-2xl border border-slate-700 bg-futbolix-dark px-8 py-8 shadow-2xl">
            <slot />
        </div>

        <!-- Footer mínimo -->
        <p class="mt-6 text-xs text-slate-600">
            &copy; {{ new Date().getFullYear() }} Futbolix · 2º DAW
        </p>
    </div>
</template>
