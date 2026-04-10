<script setup lang="ts">
import { ref, watch } from 'vue';

const props = withDefaults(defineProps<{
    modelValue: File | null;
    inputId?: string;
    label?: string;
    accept?: string;
}>(), {
    inputId: 'file-input',
    label: 'Subir imagen',
    accept: 'image/*',
});

const file = ref<File | null>(props.modelValue);

const emit = defineEmits<{
    (e: 'update:modelValue', file: File | null): void;
}>();

watch(
    () => props.modelValue,
    (value) => {
        file.value = value;
    },
);

watch(file, () => emit('update:modelValue', file.value));

function handleFileSelect(e: Event) {
    const input = e.target as HTMLInputElement;
    file.value = input?.files?.[0] || null;
    input.value = '';
}

function removeFile() {
    file.value = null;
}
</script>

<template>
    <div class="space-y-2">
        <div class="inline-flex items-center rounded-lg bg-[#0059DE] px-4 py-2 text-sm font-semibold text-white">
            <label :for="inputId" class="cursor-pointer">{{ label }}</label>
            <input
                :id="inputId"
                type="file"
                :accept="accept"
                hidden
                @change="handleFileSelect"
            />
        </div>

        <div v-if="file" class="flex items-center gap-3 text-sm text-slate-600 dark:text-slate-300">
            <span>{{ file.name }}</span>
            <button type="button" class="text-red-500 hover:text-red-600" @click="removeFile">Quitar</button>
        </div>
    </div>
</template>
