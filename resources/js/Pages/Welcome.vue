<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import ApplicationLogoIcon from '@/Components/ApplicationLogoIcon.vue';

defineProps({
    canLogin:       { type: Boolean },
    canRegister:    { type: Boolean },
    laravelVersion: { type: String, required: true },
    phpVersion:     { type: String, required: true },
});
</script>

<template>
    <Head title="Futbolix — Tu plataforma de fútbol" />

    <div class="min-h-screen bg-futbolix-navy text-white">

        <!-- NAVBAR -->
        <nav class="border-b border-slate-700 bg-futbolix-navy/95 backdrop-blur-sm sticky top-0 z-50">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex h-16 items-center justify-between">
                    <!-- Logo -->
                    <div class="flex items-center gap-3">
                        <ApplicationLogoIcon 
                        class="h-10"/>
                        <span class="text-xl font-bold tracking-tight text-white">
                            Futbo<span class="text-futbolix-green">lix</span>
                        </span>
                    </div>

                    <!-- Nav links -->
                    <div v-if="canLogin" class="flex items-center gap-2">
                        <Link
                            v-if="$page.props.auth.user"
                            :href="route('dashboard')"
                            class="rounded-md px-4 py-2 text-sm font-medium text-slate-300 transition hover:text-white"
                        >
                            Dashboard
                        </Link>

                        <template v-else>
                            <Link
                                :href="route('login')"
                                class="rounded-md px-4 py-2 text-sm font-medium text-slate-300 transition hover:text-white"
                            >
                                Iniciar sesión
                            </Link>
                            <Link
                                v-if="canRegister"
                                :href="route('register')"
                                class="rounded-md bg-futbolix-green px-4 py-2 text-sm font-semibold text-white transition hover:bg-futbolix-green-dark"
                            >
                                Registrarse
                            </Link>
                        </template>
                    </div>
                </div>
            </div>
        </nav>

        <!-- HERO -->
        <section class="relative overflow-hidden">
            <!-- Gradiente de fondo -->
            <div class="absolute inset-0 bg-gradient-to-br from-futbolix-navy via-slate-800 to-futbolix-navy"></div>
            <!-- Decoración campo -->
            <div class="absolute inset-0 opacity-5">
                <div class="absolute left-1/2 top-1/2 h-64 w-64 -translate-x-1/2 -translate-y-1/2 rounded-full border-4 border-white"></div>
                <div class="absolute left-1/2 top-1/2 h-4 w-4 -translate-x-1/2 -translate-y-1/2 rounded-full bg-white"></div>
                <div class="absolute bottom-0 left-0 right-0 h-32 border-t-4 border-white"></div>
                <div class="absolute top-0 left-0 right-0 h-32 border-b-4 border-white"></div>
            </div>

            <div class="relative mx-auto max-w-5xl px-4 py-24 text-center sm:px-6 lg:py-32">
                <span class="badge-gold mb-6 inline-block">
                    Plataforma de fútbol y competiciones
                </span>

                <h1 class="mb-6 text-5xl font-extrabold tracking-tight sm:text-6xl lg:text-7xl">
                    Tu fútbol,
                    <span class="text-futbolix-green"> tu liga</span>
                </h1>

                <p class="mx-auto mb-10 max-w-2xl text-lg text-slate-300 sm:text-xl">
                    Consulta clasificaciones de las 5 grandes ligas europeas y crea tus propios torneos
                    personalizados de forma sencilla, rápida e intuitiva.
                </p>

                <div class="flex flex-col items-center gap-4 sm:flex-row sm:justify-center">
                    <Link
                        v-if="canRegister"
                        :href="route('register')"
                        class="w-full rounded-lg bg-futbolix-green px-8 py-3 text-base font-semibold text-white shadow-lg transition hover:bg-futbolix-green-dark sm:w-auto"
                    >
                        Empezar gratis
                    </Link>
                    <Link
                        v-if="canLogin"
                        :href="route('login')"
                        class="w-full rounded-lg border border-slate-500 px-8 py-3 text-base font-semibold text-slate-300 transition hover:border-slate-300 hover:text-white sm:w-auto"
                    >
                        Iniciar sesión
                    </Link>
                </div>
            </div>
        </section>

        <!-- FEATURES -->
        <section class="bg-futbolix-dark py-20">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="mb-14 text-center">
                    <h2 class="text-3xl font-bold text-white sm:text-4xl">
                        Todo lo que necesitas para el fútbol
                    </h2>
                    <p class="mt-3 text-slate-400">
                        Información real + competiciones personalizadas en una sola plataforma.
                    </p>
                </div>

                <div class="grid gap-8 sm:grid-cols-2 lg:grid-cols-3">
                    <!-- Card 1 -->
                    <div class="card-sport p-6">
                        <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-futbolix-green/20">
                            <svg class="h-6 w-6 text-futbolix-green" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                            </svg>
                        </div>
                        <h3 class="mb-2 text-lg font-semibold text-white">5 Grandes Ligas</h3>
                        <p class="text-sm text-slate-400">
                            Consulta clasificaciones, resultados y estadísticas de LaLiga, Premier League,
                            Bundesliga, Serie A y Ligue 1 en tiempo real.
                        </p>
                    </div>

                    <!-- Card 2 -->
                    <div class="card-sport p-6">
                        <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-futbolix-gold/20">
                            <svg class="h-6 w-6 text-futbolix-gold" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/>
                            </svg>
                        </div>
                        <h3 class="mb-2 text-lg font-semibold text-white">Crea tus Torneos</h3>
                        <p class="text-sm text-slate-400">
                            Liga, eliminatoria o formato combinado. Define los equipos, introduce resultados
                            y el sistema genera las clasificaciones automáticamente.
                        </p>
                    </div>

                    <!-- Card 3 -->
                    <div class="card-sport p-6">
                        <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-blue-500/20">
                            <svg class="h-6 w-6 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                        </div>
                        <h3 class="mb-2 text-lg font-semibold text-white">Gestión de Equipos</h3>
                        <p class="text-sm text-slate-400">
                            Administra equipos, jugadores y delega roles. El propietario del torneo controla
                            quién puede gestionar la competición.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- LIGAS STRIP -->
        <section class="bg-futbolix-navy py-12">
            <div class="mx-auto max-w-5xl px-4 text-center">
                <p class="mb-6 text-sm font-semibold uppercase tracking-widest text-slate-500">
                    Ligas disponibles
                </p>
                <div class="flex flex-wrap items-center justify-center gap-6 text-slate-400">
                    <span class="rounded-full border border-slate-700 px-4 py-1.5 text-sm font-medium hover:border-futbolix-green hover:text-futbolix-green transition">🇪🇸 LaLiga</span>
                    <span class="rounded-full border border-slate-700 px-4 py-1.5 text-sm font-medium hover:border-futbolix-green hover:text-futbolix-green transition">🏴󠁧󠁢󠁥󠁮󠁧󠁿 Premier League</span>
                    <span class="rounded-full border border-slate-700 px-4 py-1.5 text-sm font-medium hover:border-futbolix-green hover:text-futbolix-green transition">🇩🇪 Bundesliga</span>
                    <span class="rounded-full border border-slate-700 px-4 py-1.5 text-sm font-medium hover:border-futbolix-green hover:text-futbolix-green transition">🇮🇹 Serie A</span>
                    <span class="rounded-full border border-slate-700 px-4 py-1.5 text-sm font-medium hover:border-futbolix-green hover:text-futbolix-green transition">🇫🇷 Ligue 1</span>
                </div>
            </div>
        </section>

        <!-- FOOTER -->
        <footer class="border-t border-slate-700 bg-futbolix-navy py-8">
            <div class="mx-auto max-w-7xl px-4 text-center text-sm text-slate-500">
                <p class="font-semibold text-slate-400 mb-1">
                    Futbo<span class="text-futbolix-green">lix</span> — Proyecto Fin de Grado
                </p>
                <p>Domingo Rodríguez · Carmelo Villarreal · Fco. Javier Moreno</p>
                <p class="mt-2">2º DAW · Laravel v{{ laravelVersion }} · PHP v{{ phpVersion }}</p>
            </div>
        </footer>

    </div>
</template>
