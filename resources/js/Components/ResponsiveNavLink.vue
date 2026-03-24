<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    href:   { type: String, required: true },
    active: { type: Boolean },
});

const classes = computed(() =>
    props.active
        ? 'block w-full ps-3 pe-4 py-2 border-l-4 border-futbolix-green text-start text-base font-medium text-futbolix-green bg-futbolix-green/10 focus:outline-none transition duration-150 ease-in-out'
        : 'block w-full ps-3 pe-4 py-2 border-l-4 border-transparent text-start text-base font-medium text-slate-400 hover:text-white hover:bg-slate-700/50 hover:border-slate-500 focus:outline-none transition duration-150 ease-in-out',
);
</script>

<template>
    <Link :href="href" :class="classes">
        <slot />
    </Link>
</template>
