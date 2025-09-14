<!-- <script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm } from '@inertiajs/vue3';

const form = useForm({
    password: '',
});

const submit = () => {
    form.post(route('password.confirm'), {
        onFinish: () => form.reset(),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Confirm Password" />

        <div class="mb-4 text-sm text-gray-600">
            This is a secure area of the application. Please confirm your
            password before continuing.
        </div>

        <form @submit.prevent="submit">
            <div>
                <InputLabel for="password" value="Password" />
                <TextInput
                    id="password"
                    type="password"
                    class="mt-1 block w-full"
                    v-model="form.password"
                    required
                    autocomplete="current-password"
                    autofocus
                />
                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <div class="mt-4 flex justify-end">
                <PrimaryButton
                    class="ms-4"
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    Confirm
                </PrimaryButton>
            </div>
        </form>
    </GuestLayout>
</template> -->
<template>
    <div class="min-h-screen flex items-center justify-center">
        <div class="confirm-card bg-gradient-to-b from-[#0f0c29] via-[#302b63] to-[#24243e] p-8 rounded-lg shadow-lg w-full max-w-sm text-white">
            <!-- Logo -->
            <div class="flex justify-center mb-5">
                <img src="/path/to/your/logo.png" alt="Logo" class="w-20 h-20" />
            </div>

            <!-- Instruction Text -->
            <div class="mb-4 text-sm text-gray-400 text-center">
                This is a secure area of the application. Please confirm your password before continuing.
            </div>

            <!-- Validation Errors -->
            <div v-if="Object.keys(form.errors).length" class="mb-4 text-red-400 text-sm">
                <div v-for="error in form.errors" :key="error">{{ error }}</div>
            </div>

            <!-- Password Form -->
            <form @submit.prevent="submit">
                <!-- Password -->
                <div class="mb-4">
                    <label for="password" class="block text-gray-300 text-sm font-medium">Password</label>
                    <input
                        id="password"
                        v-model="form.password"
                        type="password"
                        class="mt-1 w-full p-3 border border-gray-400 bg-transparent text-white rounded focus:ring focus:ring-blue-300 focus:border-white"
                        :class="{ 'border-red-500': form.errors.password }"
                        required
                        autocomplete="current-password"
                    />
                </div>

                <!-- Submit Button -->
                <div class="flex justify-center">
                    <button
                        type="submit"
                        class="w-full bg-green-500 text-white p-3 rounded hover:bg-green-600 disabled:opacity-50"
                        :disabled="form.processing"
                    >
                        Confirm
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>

<script>
import { useForm } from '@inertiajs/vue3';

export default {
    name: 'ConfirmPassword',
    setup() {
        const form = useForm({
            password: '',
        });

        function submit() {
            form.post('/password/confirm', {
                onSuccess: () => form.reset(),
            });
        }

        return { form, submit };
    },
};
</script>

<style scoped>
.confirm-card {
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

input::placeholder {
    color: rgba(255, 255, 255, 0.6);
}
</style>