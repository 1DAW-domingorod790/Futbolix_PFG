<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import Modal from '@/Components/Modal.vue';
import TextInput from '@/Components/TextInput.vue';
import { useForm } from '@inertiajs/vue3';
import { nextTick, ref } from 'vue';

const confirmingUserDeletion = ref(false);
const passwordInput = ref(null);

const form = useForm({
    password: '',
});

const confirmUserDeletion = () => {
    confirmingUserDeletion.value = true;
    // nextTick(() => passwordInput.value.focus());
};

const deleteUser = () => {
    form.delete(route('profile.destroy'), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
        onError: () => passwordInput.value.focus(),
        onFinish: () => form.reset(),
    });
};

const closeModal = () => {
    confirmingUserDeletion.value = false;
    form.clearErrors();
    form.reset();
};
</script>

<template>
    <section class="space-y-4">
        <header>
            <h2 class="text-xl font-bold text-gray-900">Eliminar Cuenta</h2>
            <p class="mt-1 text-sm text-gray-500">
                Una vez eliminada tu cuenta, todos tus datos serán borrados permanentemente.
                Descarga cualquier información que desees conservar antes de continuar.
            </p>
        </header>

        <!-- Aviso de peligro -->
        <div class="flex items-start gap-3 rounded-xl bg-red-50 p-4 ring-1 ring-red-200">
            <svg xmlns="http://www.w3.org/2000/svg" class="mt-0.5 h-5 w-5 shrink-0 text-red-500" viewBox="0 0 24 24" fill="currentColor">
                <path d="M1 21h22L12 2 1 21zm12-3h-2v-2h2v2zm0-4h-2v-4h2v4z"/>
            </svg>
            <p class="text-sm text-red-700">
                <strong class="font-semibold">Esta acción es irreversible.</strong>
                No podrás recuperar tu cuenta ni tus datos una vez eliminada.
            </p>
        </div>

        <!-- Botón de eliminar -->
        <button
            @click="confirmUserDeletion"
            class="inline-flex items-center gap-2 rounded-xl border border-red-200 bg-red-50 px-5 py-2.5 text-sm font-semibold text-red-700 transition-all hover:bg-red-600 hover:text-white hover:shadow-md"
        >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor">
                <path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/>
            </svg>
            Eliminar Mi Cuenta
        </button>

        <!-- Modal de confirmación -->
        <Modal :show="confirmingUserDeletion" @close="closeModal">
            <div class="p-7">
                <!-- Icono de advertencia -->
                <div class="mb-5 flex h-14 w-14 items-center justify-center rounded-2xl bg-red-100">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 text-red-600" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M1 21h22L12 2 1 21zm12-3h-2v-2h2v2zm0-4h-2v-4h2v4z"/>
                    </svg>
                </div>

                <h2 class="text-lg font-bold text-gray-900">
                    ¿Eliminar cuenta definitivamente?
                </h2>
                <p class="mt-2 text-sm text-gray-500">
                    Esta acción no se puede deshacer. Por favor, introduce tu contraseña
                    para confirmar que deseas eliminar tu cuenta permanentemente.
                </p>

                <div class="mt-5">
                    <InputLabel for="password" value="Tu Contraseña" class="mb-1.5 text-sm font-semibold text-gray-700" />
                    <div class="relative">
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2z"/>
                            </svg>
                        </div>
                        <TextInput
                            id="password"
                            ref="passwordInput"
                            v-model="form.password"
                            type="password"
                            class="mt-0 block w-full rounded-xl border-gray-200 bg-gray-50 pl-10 transition focus:ring-1 focus:ring-gray-300 focus:border-gray-300"
                            placeholder="Introduce tu contraseña"
                            @keyup.enter="deleteUser"
                        />
                    </div>
                    <InputError :message="form.errors.password" class="mt-1.5" />
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <button
                        @click="closeModal"
                        class="rounded-xl border border-gray-200 bg-white px-5 py-2.5 text-sm font-semibold text-gray-700 transition hover:bg-gray-50"
                    >
                        Cancelar
                    </button>
                    <button
                        :class="{ 'opacity-60': form.processing }"
                        :disabled="form.processing"
                        @click="deleteUser"
                        class="inline-flex items-center gap-2 rounded-xl bg-red-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition-all hover:bg-red-700 hover:shadow-md"
                    >
                        <svg v-if="form.processing" class="h-4 w-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.4 0 0 5.4 0 12h4z"></path>
                        </svg>
                        <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/>
                        </svg>
                        Eliminar Definitivamente
                    </button>
                </div>
            </div>
        </Modal>
    </section>
</template>
