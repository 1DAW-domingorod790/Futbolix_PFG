<script setup lang="ts">
import { onMounted, ref } from 'vue';

const model = defineModel({
    type: String,
    required: true,
});

const input = ref(null);

onMounted(() => {
    if (input.value.hasAttribute('autofocus')) {
        input.value.focus();
    }
});

defineExpose({ focus: () => input.value.focus() });
</script>

<template>
    <input
        class="w-full rounded-md border border-gray-300 dark:border-slate-600 bg-white dark:bg-[#0f172a] px-3 py-2 text-sm text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-slate-500 shadow-sm transition focus:border-futbolix-green focus:outline-none focus:ring-1 focus:ring-futbolix-green"
        v-model="model"
        ref="input"
    />
</template>
