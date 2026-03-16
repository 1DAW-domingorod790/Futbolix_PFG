<script setup>
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
        class="w-full bg-gray-400 rounded-md border border-slate-600 px-3 py-2 text-sm text-white placeholder-slate-500 shadow-sm transition focus:border-futbolix-green focus:outline-none focus:ring-1 focus:ring-futbolix-green"
        v-model="model"
        ref="input"
        style="background-color: #0f172a;"
    />
</template>
