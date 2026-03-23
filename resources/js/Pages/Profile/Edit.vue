<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import ProfileInformationCard from './Partials/ProfileInformationCard.vue';
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const activeTab = ref('profile');
</script>

<template>
    <Head title="Futbolix - Perfil" />

    <AuthenticatedLayout>
        <!-- Hero banner -->
        <div class="relative overflow-hidden bg-gradient-to-r from-green-700 via-emerald-600 to-teal-600">
            <div class="absolute inset-0 opacity-10">
                <svg viewBox="0 0 1000 200" preserveAspectRatio="none" class="h-full w-full">
                    <path d="M0,100 C150,0 350,200 500,100 C650,0 850,200 1000,100 L1000,200 L0,200 Z" fill="white"/>
                </svg>
            </div>
            <!-- Campo de fútbol decorativo -->
            <div class="absolute right-60 mx-auto top-1/2 -translate-y-1/2 opacity-10 hidden lg:block">
                <svg width="200" height="120" viewBox="0 0 200 120" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <rect x="1" y="1" width="198" height="118" rx="4" stroke="white" stroke-width="2" fill="none"/>
                    <circle cx="100" cy="60" r="20" stroke="white" stroke-width="2" fill="none"/>
                    <line x1="100" y1="1" x2="100" y2="119" stroke="white" stroke-width="2"/>
                    <rect x="1" y="35" width="25" height="50" stroke="white" stroke-width="2" fill="none"/>
                    <rect x="174" y="35" width="25" height="50" stroke="white" stroke-width="2" fill="none"/>
                    <circle cx="100" cy="60" r="2" fill="white"/>
                </svg>
            </div>
            <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                <h2 class="text-xl text-white font-bold">Mi Perfil</h2>
                <p class="text-green-100 text-sm font-medium">Gestiona tu información personal y seguridad</p>
            </div>
        </div>

        <div class="py-8">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">

                <!-- Single card -->
                <div class="overflow-hidden rounded-2xl bg-white dark:bg-futbolix-dark shadow-sm ring-1 ring-gray-200 dark:ring-slate-700">

                    <!-- Tab navigation -->
                    <div class="flex border-b border-gray-200 dark:border-slate-700">
                        <!-- Información de Perfil -->
                        <button
                            @click="activeTab = 'profile'"
                            class="relative flex-1 px-4 py-4 text-sm font-medium transition-colors duration-200 focus:outline-none"
                            :class="activeTab === 'profile' ? 'text-green-600 dark:text-green-400' : 'text-black/50 dark:text-slate-400 hover:text-black dark:hover:text-slate-200'"
                        >
                            <div class="flex items-center justify-center gap-2">
                                <div class="h-2 w-2 rounded-full bg-green-500"></div>
                                Información de Perfil
                            </div>
                            <span v-if="activeTab === 'profile'" class="absolute bottom-0 left-0 h-0.5 w-full bg-green-500" />
                        </button>

                        <!-- Cambiar Contraseña -->
                        <button
                            @click="activeTab = 'password'"
                            class="relative flex-1 px-4 py-4 text-sm font-medium transition-colors duration-200 focus:outline-none border-l border-gray-200 dark:border-slate-700"
                            :class="activeTab === 'password' ? 'text-blue-600 dark:text-blue-400' : 'text-black/50 dark:text-slate-400 hover:text-black dark:hover:text-slate-200'"
                        >
                            <div class="flex items-center justify-center gap-2">
                                <div class="h-2 w-2 rounded-full bg-blue-500"></div>
                                Cambiar Contraseña
                            </div>
                            <span v-if="activeTab === 'password'" class="absolute bottom-0 left-0 h-0.5 w-full bg-blue-500" />
                        </button>

                        <!-- Eliminar Perfil -->
                        <button
                            @click="activeTab = 'delete'"
                            class="relative flex-1 px-4 py-4 text-sm font-medium transition-colors duration-200 focus:outline-none border-l border-gray-200 dark:border-slate-700"
                            :class="activeTab === 'delete' ? 'text-red-600 dark:text-red-400' : 'text-black/50 dark:text-slate-400 hover:text-black dark:hover:text-slate-200'"
                        >
                            <div class="flex items-center justify-center gap-2">
                                <div class="h-2 w-2 rounded-full bg-red-500"></div>
                                Eliminar Perfil
                            </div>
                            <span v-if="activeTab === 'delete'" class="absolute bottom-0 left-0 h-0.5 w-full bg-red-500" />
                        </button>
                    </div>

                    <!-- Tab content -->
                    <div class="p-6">

                        <!-- Información de Perfil -->
                        <div v-if="activeTab === 'profile'" class="flex flex-col gap-8 md:flex-row md:items-start">
                            <ProfileInformationCard />
                            <div class="flex-1">
                                <UpdateProfileInformationForm
                                    :must-verify-email="mustVerifyEmail"
                                    :status="status"
                                />
                            </div>
                        </div>

                        <!-- Cambiar Contraseña -->
                        <div v-else-if="activeTab === 'password'">
                            <UpdatePasswordForm />
                        </div>

                        <!-- Eliminar Perfil -->
                        <div v-else-if="activeTab === 'delete'">
                            <DeleteUserForm />
                        </div>

                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
