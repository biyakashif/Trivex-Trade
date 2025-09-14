
<template>
    <!-- Navbar/Header -->
    <NavLink />
    <div class="min-h-screen bg-black flex items-center justify-center py-16 relative overflow-hidden">
        <div class="bg-black p-6 rounded-lg shadow-md w-full max-w-md border border-gray-700 text-white z-10">
            <div class="flex justify-center mb-5">
                <img src="/path/to/your/logo.png" alt="Logo" class="w-20 h-20" />
            </div>
            <div class="mb-4 text-sm text-gray-300 text-center">
                This is a secure area of the application. Please confirm your password before continuing.
            </div>
            <div v-if="Object.keys(form.errors).length" class="mb-4 text-red-400 text-sm">
                <div v-for="error in form.errors" :key="error">{{ error }}</div>
            </div>
            <form @submit.prevent="submit">
                <div class="mb-4">
                    <label for="password" class="block text-white text-sm font-medium">Password</label>
                    <input
                        id="password"
                        v-model="form.password"
                        type="password"
                        class="mt-1 w-full p-2 border border-gray-700 rounded-md focus:ring focus:ring-blue-500 bg-black text-white placeholder-gray-500"
                        :class="{ 'border-red-500': form.errors.password }"
                        required
                        autocomplete="current-password"
                    />
                </div>
                <div class="flex justify-center">
                    <button
                        type="submit"
                        class="bg-white text-black w-full py-0.5 rounded-full hover:bg-gray-200 disabled:opacity-50 text-base font-semibold shadow-md"
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