<template>
<div class="min-h-screen flex flex-col items-center justify-center bg-[#181A20] px-4">
    <div class="mb-8 flex justify-center w-full">
        <Link :href="route('welcome')">
            <ApplicationLogo class="h-6 w-auto" />
        </Link>
    </div>
    <form @submit.prevent="submit" class="w-full max-w-sm">
        <h2 class="text-white text-3xl font-bold mb-6">Confirm Password</h2>
        <div class="mb-4 text-sm text-gray-300 text-center">
            This is a secure area of the application. Please confirm your password before continuing.
        </div>
        <div v-if="Object.keys(form.errors).length" class="mb-4 text-red-400 text-sm">
            <div v-for="error in form.errors" :key="error">{{ error }}</div>
        </div>
        <div class="mb-8">
            <label for="password" class="block text-sm text-white mb-2">Password</label>
            <input
                id="password"
                v-model="form.password"
                type="password"
                class="w-full px-4 py-2 rounded-md bg-[#23262F] text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 border-none"
                :class="{ 'ring-2 ring-red-500': form.errors.password }"
                placeholder="Password"
                required
                autocomplete="current-password"
            />
        </div>
        <button
            type="submit"
            :disabled="form.processing"
        >
            Confirm
        </button>
    </form>
</div>
</template>

<script>
import { useForm, Link } from '@inertiajs/vue3';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';

export default {
    name: 'ConfirmPassword',
    components: {
        ApplicationLogo,
    },
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
body {
    background: #181A20;
}

input:-webkit-autofill,
input:-webkit-autofill:focus,
input:-webkit-autofill:hover,
input:-webkit-autofill:active {
    -webkit-box-shadow: 0 0 0 1000px #23262F inset !important;
    box-shadow: 0 0 0 1000px #23262F inset !important;
    -webkit-text-fill-color: #fff !important;
    caret-color: #fff !important;
    color: #fff !important;
    transition: background-color 5000s ease-in-out 0s;
}

button[type="submit"] {
    background: #23262F;
    color: #fff;
    border-radius: 9999px;
    width: 100%;
    padding-top: 0.5rem;
    padding-bottom: 0.5rem;
    font-size: 1rem;
    font-weight: 400;
    box-shadow: none;
    transition: background 0.2s, color 0.2s;
    min-height: 2.2rem;
}
button[type="submit"]:hover:not(:disabled) {
    background: #f3f4f6;
    color: #181A20;
}
button[type="submit"]:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}
</style>