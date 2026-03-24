<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';

const user = usePage().props.auth.user;
</script>

<template>
    <div class="flex flex-col items-center">
        <!-- Avatar container -->
        <div class="relative">
            <div class="h-50 w-50 overflow-hidden rounded-2xl ring-4 ring-green-100 shadow-lg">
                <img
                    :src="user.avatar_url"
                    :alt="user.name"
                    class="h-full w-full object-cover"
                />
            </div>
            <!-- Badge de estado online -->
            <div class="absolute -bottom-1.5 -right-1.5 flex h-6 w-6 items-center justify-center rounded-full bg-green-500 ring-2 ring-white">
                <div class="h-2.5 w-2.5 rounded-full bg-white"></div>
            </div>
        </div>

        <!-- Nombre y rol -->
        <div class="mt-4 text-center">
            <h3 class="text-lg font-bold text-gray-900">{{ user.name }}</h3>
            <p class="text-sm text-gray-500">{{ user.email }}</p>
        </div>

    </div>
</template>