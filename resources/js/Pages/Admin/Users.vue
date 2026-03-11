<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';

defineProps({
    users: Array,
});

const editingUser = ref(null);
const form = useForm({ name: '', email: '', role_name: 'user'});

function openEdit(user) {
    editingUser.value = user;
    form.name = user.name;
    form.email = user.email;
    form.role_name = user.role.name;
}

function closeEdit() {
    editingUser.value = null;
    form.reset();
}

function saveUser() {
    form.patch(route('admin.users.update', editingUser.value.id), {
        onSuccess: () => closeEdit(),
    });
}

function deleteUser(user) {
    if (confirm(`¿Seguro que quieres eliminar a "${user.name}"? Esta acción no se puede deshacer.`)) {
        router.delete(route('admin.users.destroy', user.id));
    }
}
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Gestión de Usuarios" />

        <div class="py-10 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto">
            <h1 class="text-2xl font-bold text-white mb-6">Gestión de Usuarios</h1>

            <div class="overflow-x-auto rounded-lg border border-slate-700">
                <table class="w-full text-sm text-left text-slate-300">
                    <thead class="bg-slate-800 text-slate-400 uppercase text-xs">
                        <tr>
                            <th class="px-6 py-3 text-center">#</th>
                            <th class="px-6 py-3 text-center">Nombre</th>
                            <th class="px-6 py-3 text-center">Email</th>
                            <th class="px-6 py-3 text-center">Rol</th>
                            <th class="px-6 py-3 text-center">Registrado</th>
                            <th class="px-6 py-3 text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="user in users"
                            :key="user.id"
                            class="border-t border-slate-700 hover:bg-slate-800 transition"
                        >
                            <td class="px-6 py-4 text-slate-500 text-center">{{ user.id }}</td>
                            <td class="px-6 py-4 font-medium text-white text-center">{{ user.name }}</td>
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
                            <td class="px-6 py-4 text-slate-500 text-center">
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
                            v-model="form.name"
                            type="text"
                            class="w-full rounded-lg border border-slate-600 bg-slate-800 px-3 py-2 text-white focus:border-futbolix-green focus:outline-none"
                        />
                        <p v-if="form.errors.name" class="mt-1 text-xs text-red-400">{{ form.errors.name }}</p>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm text-slate-400">Email</label>
                        <input
                            v-model="form.email"
                            type="email"
                            class="w-full rounded-lg border border-slate-600 bg-slate-800 px-3 py-2 text-white focus:border-futbolix-green focus:outline-none"
                        />
                        <p v-if="form.errors.email" class="mt-1 text-xs text-red-400">{{ form.errors.email }}</p>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm text-slate-400">Rol</label>
                        <select
                            v-model="form.role_name"
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
                        :disabled="form.processing"
                        class="rounded-lg bg-futbolix-green px-4 py-2 text-sm font-semibold text-white hover:bg-futbolix-green-dark transition disabled:opacity-50"
                    >
                        Guardar
                    </button>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
