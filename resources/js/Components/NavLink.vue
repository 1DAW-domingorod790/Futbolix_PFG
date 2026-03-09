<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    href:   { type: String, required: true },
    active: { type: Boolean },
});

const classes = computed(() =>
    props.active
        ? 'inline-flex items-center px-3 py-2 rounded-md text-sm font-medium text-white bg-futbolix-green/20 border-b-2 border-futbolix-green transition duration-150 ease-in-out'
        : 'inline-flex items-center px-3 py-2 rounded-md text-sm font-medium text-slate-400 border-b-2 border-transparent hover:text-white hover:border-slate-500 transition duration-150 ease-in-out',
);
</script>

<template>
    <Link :href="href" :class="classes">
        <slot />
    </Link>
</template>
