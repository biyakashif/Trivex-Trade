<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const passwordInput = ref(null);
const currentPasswordInput = ref(null);

const form = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const updatePassword = () => {
    form.put(route('password.update'), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
        onError: () => {
            if (form.errors.password) {
                form.reset('password', 'password_confirmation');
                passwordInput.value.focus();
            }
            if (form.errors.current_password) {
                form.reset('current_password');
                currentPasswordInput.value.focus();
            }
        },
    });
};
</script>

<template>
    <section class="bg-black rounded-lg p-6 shadow-md">
        <header>
            <h2 class="text-lg font-medium text-white">
                Update Password
            </h2>

            <p class="mt-1 text-sm text-gray-400">
                Ensure your account is using a long, random password to stay
                secure.
            </p>
        </header>

        <form @submit.prevent="updatePassword" class="mt-6 space-y-6">
            <div>
                <InputLabel for="current_password" value="Current Password" class="text-white" />

                <TextInput
                    id="current_password"
                    ref="currentPasswordInput"
                    v-model="form.current_password"
                    type="password"
                    class="mt-1 block w-full bg-black text-white border border-gray-700 focus:border-blue-500 focus:ring-blue-500 placeholder-gray-500"
                    autocomplete="current-password"
                />

                <InputError
                    :message="form.errors.current_password"
                    class="mt-2 text-red-400"
                />
            </div>

            <div>
                <InputLabel for="password" value="New Password" class="text-white" />

                <TextInput
                    id="password"
                    ref="passwordInput"
                    v-model="form.password"
                    type="password"
                    class="mt-1 block w-full bg-black text-white border border-gray-700 focus:border-blue-500 focus:ring-blue-500 placeholder-gray-500"
                    autocomplete="new-password"
                />

                <InputError :message="form.errors.password" class="mt-2 text-red-400" />
            </div>

            <div>
                <InputLabel
                    for="password_confirmation"
                    value="Confirm Password"
                    class="text-white"
                />

                <TextInput
                    id="password_confirmation"
                    v-model="form.password_confirmation"
                    type="password"
                    class="mt-1 block w-full bg-black text-white border border-gray-700 focus:border-blue-500 focus:ring-blue-500 placeholder-gray-500"
                    autocomplete="new-password"
                />

                <InputError
                    :message="form.errors.password_confirmation"
                    class="mt-2 text-red-400"
                />
            </div>

            <div class="flex items-center gap-4">
                <button :disabled="form.processing" class="action-btn focus:ring-blue-500 disabled:opacity-60 disabled:cursor-not-allowed">Save</button>

                <Transition
                    enter-active-class="transition ease-in-out"
                    enter-from-class="opacity-0"
                    leave-active-class="transition ease-in-out"
                    leave-to-class="opacity-0"
                >
                    <p
                        v-if="form.recentlySuccessful"
                        class="text-sm text-green-400"
                    >
                        Saved.
                    </p>
                </Transition>
            </div>
        </form>
    </section>
</template>

<style scoped>
.action-btn {
    background: #23262F !important;
    color: #fff !important;
    padding-left: 1rem;
    padding-right: 1rem;
}
.action-btn:hover {
    background: #f3f4f6 !important;
    color: #181A20 !important;
}
.action-btn:hover * {
    color: #181A20 !important;
}
</style>
