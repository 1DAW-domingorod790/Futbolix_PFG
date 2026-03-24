<script setup lang="ts">
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { route } from 'ziggy-js';
import { User } from '@/types';

defineProps<{
    users: User[];
}>();

const editingUser = ref<User | null>(null);
const creatingUser = ref(false);
const editForm = useForm({ name: '', email: '', role_name: 'user'});
const createForm = useForm({ name: '', email: '', password: '', role_name: 'user'});

function openEdit(user: any) {
    editingUser.value = user;
    editForm.name = user.name;
    editForm.email = user.email;
    editForm.role_name = user.role.name;
}

function closeEdit() {
    editingUser.value = null;
    editForm.reset();
}

function saveUser() {
    if (!editingUser.value) return;
    
    editForm.patch(route('admin.users.update', editingUser.value.id), {
        onSuccess: () => closeEdit(),
    });
}

function deleteUser(user: any) {
    if (confirm(`¿Seguro que quieres eliminar a "${user.name}"? Esta acción no se puede deshacer.`)) {
        router.delete(route('admin.users.destroy', user.id));
    }
}

function openCreate() {
    creatingUser.value = true;
    createForm.reset();
    createForm.role_name = 'user';
}

function closeCreate() {
    creatingUser.value = false;
    createForm.reset();
}

function createUser() {
    createForm.post(route('admin.users.store'), {
        onSuccess: () => closeCreate(),
    });
}
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Gestión de Usuarios" />

        <div class="py-10 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto">
            <h1 class="text-2xl font-bold text-slate-800 dark:text-white mb-6">Gestión de Usuarios</h1>

            <div class="overflow-x-auto rounded-lg border border-slate-200 dark:border-slate-700">
                <table class="w-full text-sm text-left text-slate-600 dark:text-slate-300">
                    <thead class="bg-slate-200 dark:bg-slate-800 text-slate-500 dark:text-slate-400 uppercase text-xs">
                        <tr>
                            <th class="px-6 py-3 text-center">#</th>
                            <th class="px-6 py-3 text-center">Nombre</th>
                            <th class="px-6 py-3 text-center">Email</th>
                            <th class="px-6 py-3 text-center">Rol</th>
                            <th class="px-6 py-3 text-center">Registrado</th>
                            <th class="px-6 py-3 text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-transparent">
                        <tr
                            v-for="user in users"
                            :key="user.id"
                            class="border-t border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-800 transition"
                        >
                            <td class="px-6 py-4 text-slate-400 dark:text-slate-500 text-center">{{ user.id }}</td>
                            <td class="px-6 py-4 font-medium text-slate-800 dark:text-white text-center">{{ user.name }}</td>
                            <td class="px-6 py-4 text-center">{{ user.email }}</td>
                            <td class="px-6 py-4 text-center">
                                <span
                                    :class="user.role.name === 'admin'
                                        ? 'bg-futbolix-green/20 text-futbolix-green'
                                        : 'bg-slate-700 text-slate-300'"
                                    class="rounded-full px-3 py-1 text-xs font-semibold"
                                >
                                    {{ user.role.name }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-slate-400 dark:text-slate-500 text-center">
                                {{ new Date(user.created_at).toLocaleDateString('es-ES') }}
                            </td>
                            <td class="px-6 py-4 flex gap-2 justify-center">
                                <button
                                    @click="openEdit(user)"
                                    class="rounded-md bg-futbolix-green/20 px-3 py-1 text-xs font-semibold text-futbolix-green hover:bg-futbolix-green hover:text-white transition"
                                >
                                    Editar
                                </button>
                                <button
                                    @click="deleteUser(user)"
                                    class="rounded-md bg-red-500/20 px-3 py-1 text-xs font-semibold text-red-400 hover:bg-red-500 hover:text-white transition"
                                >
                                    Eliminar
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="flex items-center gap-2 mt-4">
                <PrimaryButton @click="openCreate" >
                    Crear nuevo usuario
                </PrimaryButton>
            </div>
        </div>

        <!-- Modal de edición -->
        <div
            v-if="editingUser"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm"
            @click.self="closeEdit"
        >
            <div class="w-full max-w-md rounded-xl border border-slate-700 bg-futbolix-dark p-6 shadow-xl">
                <h2 class="mb-6 text-lg font-bold text-white">Editar usuario</h2>

                <div class="space-y-4">
                    <div>
                        <label class="mb-1 block text-sm text-slate-400">Nombre</label>
                        <input
                            v-model="editForm.name"
                            type="text"
                            class="w-full rounded-lg border border-slate-600 bg-slate-800 px-3 py-2 text-white focus:border-futbolix-green focus:outline-none"
                        />
                        <p v-if="editForm.errors.name" class="mt-1 text-xs text-red-400">{{ editForm.errors.name }}</p>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm text-slate-400">Email</label>
                        <input
                            v-model="editForm.email"
                            type="email"
                            class="w-full rounded-lg border border-slate-600 bg-slate-800 px-3 py-2 text-white focus:border-futbolix-green focus:outline-none"
                        />
                        <p v-if="editForm.errors.email" class="mt-1 text-xs text-red-400">{{ editForm.errors.email }}</p>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm text-slate-400">Rol</label>
                        <select
                            v-model="editForm.role_name"
                            class="w-full rounded-lg border border-slate-600 bg-slate-800 px-3 py-2 text-white focus:border-futbolix-green focus:outline-none"
                        >
                            <option value="user">Usuario</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <button
                        @click="closeEdit"
                        class="rounded-lg border border-slate-600 px-4 py-2 text-sm text-slate-300 hover:text-white transition"
                    >
                        Cancelar
                    </button>
                    <button
                        @click="saveUser"
                        :disabled="editForm.processing"
                        class="rounded-lg bg-futbolix-green px-4 py-2 text-sm font-semibold text-white hover:bg-futbolix-green-dark transition disabled:opacity-50"
                    >
                        Guardar
                    </button>
                </div>
            </div>
        </div>

        <!-- Modal crear usuario -->
        <div
            v-if="creatingUser"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm"
            @click.self="closeCreate"
        >
            <div class="w-full max-w-md rounded-xl border border-slate-700 bg-futbolix-dark p-6 shadow-xl">
                <h2 class="mb-6 text-lg font-bold text-white">Crear usuario</h2>

                <div class="space-y-4">
                    <div>
                        <label class="mb-1 block text-sm text-slate-400">Nombre</label>
                        <input
                            v-model="createForm.name"
                            type="text"
                            class="w-full rounded-lg border border-slate-600 bg-slate-800 px-3 py-2 text-white focus:border-futbolix-green focus:outline-none"
                        />
                    </div>

                    <div>
                        <label class="mb-1 block text-sm text-slate-400">Email</label>
                        <input
                            v-model="createForm.email"
                            type="email"
                            class="w-full rounded-lg border border-slate-600 bg-slate-800 px-3 py-2 text-white focus:border-futbolix-green focus:outline-none"
                        />
                    </div>

                    <div>
                        <label class="mb-1 block text-sm text-slate-400">Contraseña</label>
                        <input
                            v-model="createForm.password"
                            type="password"
                            class="w-full rounded-lg border border-slate-600 bg-slate-800 px-3 py-2 text-white focus:border-futbolix-green focus:outline-none"
                        />
                    </div>

                    <div>
                        <label class="mb-1 block text-sm text-slate-400">Rol</label>
                        <select
                            v-model="createForm.role_name"
                            class="w-full rounded-lg border border-slate-600 bg-slate-800 px-3 py-2 text-white focus:border-futbolix-green focus:outline-none"
                        >
                            <option value="user">Usuario</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <button
                        @click="closeCreate"
                        class="rounded-lg border border-slate-600 px-4 py-2 text-sm text-slate-300 hover:text-white transition"
                    >
                        Cancelar
                    </button>
                    <button
                        @click="createUser"
                        :disabled="createForm.processing"
                        class="rounded-lg bg-futbolix-green px-4 py-2 text-sm font-semibold text-white hover:bg-futbolix-green-dark transition disabled:opacity-50"
                    >
                        Crear
                    </button>
                </div>
            </div>
        </div>

    </AuthenticatedLayout>
</template>
