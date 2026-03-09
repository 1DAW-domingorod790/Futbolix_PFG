<script setup>
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div class="flex min-h-screen flex-col items-center justify-center bg-futbolix-navy px-4">

        <!-- Logo -->
        <div class="mb-8 text-center">
            <Link href="/" class="inline-flex flex-col items-center gap-2">
                <div class="flex h-14 w-14 items-center justify-center rounded-full bg-futbolix-green shadow-lg shadow-futbolix-green/30">
                    <svg class="h-8 w-8 text-white" fill="currentColor" viewBox="0 0 24 24">
                        <circle cx="12" cy="12" r="10" fill="none" stroke="currentColor" stroke-width="1.5"/>
                        <path d="M12 2a10 10 0 1 0 0 20A10 10 0 0 0 12 2zm0 1.5a8.5 8.5 0 0 1 5.3 1.85L14.5 8H9.5L6.7 5.35A8.5 8.5 0 0 1 12 3.5zM5.6 6.4 8 8.8v4.4l-3.8 2.8A8.47 8.47 0 0 1 3.5 12c0-2.1.76-4.02 2.1-5.6zm.9 10.3 3.5-2.6h4l3.5 2.6A8.49 8.49 0 0 1 12 20.5a8.49 8.49 0 0 1-5.5-3.8zm11.9-.5-3.8-2.8V8.8l2.4-2.4A8.48 8.48 0 0 1 20.5 12c0 1.56-.42 3.01-1.1 4.2z"/>
                    </svg>
                </div>
                <span class="text-2xl font-bold text-white">
                    Futbo<span class="text-futbolix-green">lix</span>
                </span>
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
