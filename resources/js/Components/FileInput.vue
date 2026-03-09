<script setup lang="ts">
import { ref, watch } from 'vue';

const file = ref<File | null>(null);

const props = defineProps<{ modelValue: File | null }>();
const emit = defineEmits<{
  (e: "update:modelValue", file: File | null): void;
}>();

watch(file, () => emit('update:modelValue', file.value));

function handleFileSelect(e: Event) {
  const input = e.target as HTMLInputElement;
  file.value = input?.files?.[0] || null;
  input.value = ""; 
}

function removeFile() {
  file.value = null;
}
</script>

<template>
  <div class=" text-white p-3 rounded w-25" style="background-color: #0059DE;">
    <label for="avatar_path" class="btn">Subir foto</label>
    <input
      id="avatar_path"
      type="file"
      accept="image/*"
      hidden
      @change="handleFileSelect"
    />
  </div>
  
<div v-if="file">
    {{ file.name }}
    <button @click="removeFile" class="ml-2 text-red-500">Quitar</button>
  </div>
</template>