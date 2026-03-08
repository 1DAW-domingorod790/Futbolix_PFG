<script setup lang="ts">
import { ref } from 'vue';

const file = ref<File | null>(null);

const emit = defineEmits<{
  (e: "changed", file: File | null): void;
}>();

function handleFileSelect(e: Event) {
  const input = e.target as HTMLInputElement;
  const selected = input?.files?.[0] || null;

  file.value = selected; // reemplaza cualquier archivo anterior
  emit("changed", file.value);

  input.value = ""; // reset para poder subir el mismo archivo otra vez
}

function removeFile() {
  file.value = null;
  emit("changed", file.value);
}
</script>

<template>
  <button class="button text-white p-3 rounded" style="background-color: #0059DE;">
    <label for="file-input" class="btn">Subir foto</label>
    <input
      id="file-input"
      type="file"
      accept="image/*"
    @change="handleFileSelect"
    hidden
    />
  </button>
  
<div v-if="file">
    {{ file.name }}
    <button @click="removeFile" class="ml-2 text-red-500">Eliminar</button>
  </div>
</template>