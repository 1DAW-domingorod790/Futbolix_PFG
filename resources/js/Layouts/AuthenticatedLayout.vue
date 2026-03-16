<script setup>
import { ref } from 'vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import NavLink from '@/Components/NavLink.vue';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';
import { Link } from '@inertiajs/vue3';
import ApplicationLogoIconWhite from '@/Components/ApplicationLogoIconWhite.vue';
import { useTheme } from '@/Composables/useTheme';

const showingNavigationDropdown = ref(false);
const { isDark, toggle } = useTheme();
</script>

<template>
    <div>
        <div class="min-h-screen bg-slate-100 dark:bg-futbolix-navy">

            <!-- NAVBAR -->
            <nav
            class="border-b border-slate-700 bg-futbolix-dark sticky top-0 z-40"
            style="background-color: #00285E;">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="flex h-16 justify-between">

                        <!-- Logo + Nav links -->
                        <div class="flex items-center">
                            <div class="flex shrink-0 items-center">
                                <Link :href="route('dashboard')" class="flex items-center gap-2">
                                    <ApplicationLogoIconWhite />
                                    <h1 class="text-xl font-bold text-white" style="font-size: x-large;">Futbolix</h1>
                                </Link>
                            </div>

                            <div class="hidden space-x-1 sm:ms-8 sm:flex">
                                <NavLink
                                    :href="route('dashboard')"
                                    :active="route().current('dashboard')"
                                >
                                    Dashboard
                                </NavLink>
                                <NavLink
                                    :href="route('matches.index')"
                                    :active="route().current('matches.index')"
                                >
                                    Partidos
                                </NavLink>
                                <NavLink
                                    :href="route('tournaments.index')"
                                    :active="route().current('tournaments.index')"
                                >
                                    Torneos
                                </NavLink>
                                <NavLink
                                    v-if="$page.props.auth.is_admin"
                                    :href="route('admin.users')"
                                    :active="route().current('admin.users')"
                                >
                                    Gestión de usuarios
                                </NavLink>
                            </div>
                        </div>

                        <!-- Theme toggle + User dropdown -->
                        <div class="hidden sm:ms-6 sm:flex sm:items-center gap-2">
                            <!-- Botón tema -->
                            <button
                                @click="toggle"
                                type="button"
                                class="flex h-8 w-8 items-center justify-center rounded-md border border-slate-600 text-slate-300 transition hover:border-futbolix-green hover:text-white"
                                :title="isDark ? 'Cambiar a tema claro' : 'Cambiar a tema oscuro'"
                            >
                                <!-- Sol (modo claro) -->
                                <svg v-if="isDark" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364-6.364l-.707.707M6.343 17.657l-.707.707M17.657 17.657l-.707-.707M6.343 6.343l-.707-.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
                                </svg>
                                <!-- Luna (modo oscuro) -->
                                <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 12.79A9 9 0 1111.21 3 7 7 0 0021 12.79z"/>
                                </svg>
                            </button>

                            <div class="relative ms-3">
                                <Dropdown align="right" width="48">
                                    <template #trigger>
                                        <span class="inline-flex rounded-md">
                                            <button
                                                type="button"
                                                class="inline-flex items-center gap-2 rounded-md border border-slate-600 bg-futbolix-navy px-3 py-2 text-sm font-medium text-slate-300 transition hover:border-futbolix-green hover:text-white focus:outline-none"
                                            >
                                                <img :src="$page.props.auth.user.avatar_url" alt="" class="w-7 h-7 rounded-full">


                                                <svg class="h-4 w-4 text-slate-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                                </svg>
                                            </button>
                                        </span>
                                    </template>

                                    <template #content>
                                        <DropdownLink :href="route('profile.edit')">
                                            Perfil
                                        </DropdownLink>
                                        <DropdownLink :href="route('logout')" method="post" as="button">
                                            Cerrar sesión
                                        </DropdownLink>
                                    </template>
                                </Dropdown>
                            </div>
                        </div>

                        <!-- Hamburger (móvil) -->
                        <div class="-me-2 flex items-center sm:hidden">
                            <button
                                @click="showingNavigationDropdown = !showingNavigationDropdown"
                                class="inline-flex items-center justify-center rounded-md p-2 text-slate-400 transition hover:bg-slate-700 hover:text-white focus:outline-none"
                            >
                                <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                                    <path
                                        :class="{ hidden: showingNavigationDropdown, 'inline-flex': !showingNavigationDropdown }"
                                        stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"
                                    />
                                    <path
                                        :class="{ hidden: !showingNavigationDropdown, 'inline-flex': showingNavigationDropdown }"
                                        stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"
                                    />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Menú responsivo -->
                <div :class="{ block: showingNavigationDropdown, hidden: !showingNavigationDropdown }" class="sm:hidden">
                    <div class="space-y-1 pb-3 pt-2">
                        <ResponsiveNavLink :href="route('dashboard')" :active="route().current('dashboard')">
                            Dashboard
                        </ResponsiveNavLink>
                    </div>

                    <div class="border-t border-slate-700 pb-1 pt-4">
                        <div class="px-4">
                            <div class="flex items-center gap-2">
                                <img :src="$page.props.auth.user.avatar_url" class="w-10 h-10 rounded-full">
                                <div>
                                    <div class="text-sm font-medium text-white">{{ $page.props.auth.user.name }}</div>
                                    <div class="text-xs text-slate-400">{{ $page.props.auth.user.email }}</div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-3 space-y-1">
                            <ResponsiveNavLink :href="route('profile.edit')">Perfil</ResponsiveNavLink>
                            <ResponsiveNavLink :href="route('logout')" method="post" as="button">Cerrar sesión</ResponsiveNavLink>
                        </div>
                    </div>
                </div>
            </nav>

            <!-- Page Heading -->
            <header class="border-b border-slate-700 bg-futbolix-dark" v-if="$slots.header">
                <div class="mx-auto max-w-7xl px-4 py-5 sm:px-6 lg:px-8">
                    <slot name="header" />
                </div>
            </header>

            <!-- Page Content -->
            <main>
                <slot />
            </main>
        </div>
    </div>
</template>
