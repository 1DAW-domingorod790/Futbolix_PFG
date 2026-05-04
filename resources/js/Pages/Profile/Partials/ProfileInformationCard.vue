<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const user = computed(() => page.props.auth.user as any);
</script>

<template>
    <div class="flex flex-col items-center">
        <!-- Avatar container -->
        <div class="relative">
            <div class="h-50 w-50 overflow-hidden rounded-2xl ring-4 ring-green-100 shadow-lg">
                <img
                    v-if="user.avatar_url"
                    :src="user.avatar_url"
                    :alt="user.name"
                    class="h-full w-full object-cover"
                />
                <div v-else class="flex h-full w-full items-center justify-center bg-futbolix-green text-4xl font-bold text-white">
                    {{ user.name?.charAt(0).toUpperCase() }}
                </div>
            </div>
            <!-- Badge de estado online -->
            <div class="absolute -bottom-1.5 -right-1.5 flex h-6 w-6 items-center justify-center rounded-full bg-green-500 ring-2 ring-white">
                <div class="h-2.5 w-2.5 rounded-full bg-white"></div>
            </div>
        </div>

        <!-- Nombre y rol -->
        <div class="mt-4 text-center">
            <h3 class="text-lg font-bold text-black dark:text-white">{{ user.name }}</h3>
            <p class="text-sm text-gray-600 dark:text-gray-400">{{ user.email }}</p>
        </div>

    </div>
</template>