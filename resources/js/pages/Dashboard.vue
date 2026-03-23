<script setup>
import ApplicationLogoIconWhite from '@/Components/ApplicationLogoIconWhite.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <div class="py-10">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">

                <!-- Bienvenida -->
                <div class="mb-8 rounded-2xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-futbolix-dark p-6 shadow-sm">
                    <div class="flex items-center gap-4">
                        <img
                            v-if="$page.props.auth.user.avatar_url"
                            :src="$page.props.auth.user.avatar_url"
                            :alt="$page.props.auth.user.name"
                            class="h-14 w-14 shrink-0 rounded-full object-cover shadow-lg"
                        />
                        <div
                            v-else
                            class="flex h-14 w-14 shrink-0 items-center justify-center rounded-full bg-futbolix-green text-xl font-bold text-white shadow-lg shadow-futbolix-green/30"
                        >
                            {{ $page.props.auth.user.name.charAt(0).toUpperCase() }}
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-slate-800 dark:text-white">
                                ¡Bienvenido, {{ $page.props.auth.user.name }}!
                            </h3>
                            <p class="text-sm text-slate-500 dark:text-slate-400">
                                Ya estás dentro de Futbolix. Pronto podrás consultar ligas y gestionar tus torneos.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Cards de acceso rápido -->
                <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3" :class="$page.props.auth.is_admin ? 'lg:grid-cols-4' : 'lg:grid-cols-3'">
                    <Link :href="route('matches.index')" class="card-sport p-6 block hover:border-futbolix-green transition">
                        <div class="mb-3 flex h-10 w-10 items-center justify-center rounded-full bg-futbolix-green/20">
                            <svg class="h-5 w-5 text-futbolix-green" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                            </svg>
                        </div>
                        <h4 class="mb-1 font-semibold text-slate-800 dark:text-white">Ligas europeas</h4>
                        <p class="text-sm text-slate-500 dark:text-slate-400">Consulta clasificaciones y resultados de las 5 grandes ligas.</p>
                    </Link>

                    <Link :href="route('tournaments.index')" class="card-sport p-6 block hover:border-futbolix-green transition">
                        <div class="mb-3 flex h-10 w-10 items-center justify-center rounded-full bg-futbolix-gold/20">
                            <svg class="h-5 w-5 text-futbolix-gold" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/>
                            </svg>
                        </div>
                        <h4 class="mb-1 font-semibold text-slate-800 dark:text-white">Mis torneos</h4>
                        <p class="text-sm text-slate-500 dark:text-slate-400">Crea y gestiona tus propias competiciones personalizadas.</p>
                    </Link>

                    <Link :href="route('profile.edit')" class="card-sport p-6 block hover:border-futbolix-green transition">
                        <div class="mb-3 flex h-10 w-10 items-center justify-center rounded-full bg-blue-500/20">
                            <svg class="h-5 w-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                        </div>
                        <h4 class="mb-1 font-semibold text-slate-800 dark:text-white">Mi perfil</h4>
                        <p class="text-sm text-slate-500 dark:text-slate-400">Edita tu información personal y ajusta tu cuenta.</p>
                    </Link>

                    <Link
                        v-if="$page.props.auth.is_admin"
                        :href="route('admin.users')"
                        class="card-sport p-6 block hover:border-futbolix-green transition"
                    >
                        <div class="mb-3 flex h-10 w-10 items-center justify-center rounded-full bg-red-500/20">
                            <svg class="h-5 w-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                            </svg>
                        </div>
                        <h4 class="mb-1 font-semibold text-slate-800 dark:text-white">Gestión de usuarios</h4>
                        <p class="text-sm text-slate-500 dark:text-slate-400">Administra todos los usuarios registrados.</p>
                    </Link>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
