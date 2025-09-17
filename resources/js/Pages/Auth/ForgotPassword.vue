<template>
<div class="min-h-screen flex flex-col items-center justify-center bg-[#181A20] px-4">
    <div class="mb-8 flex justify-center w-full">
        <Link :href="route('welcome')">
            <ApplicationLogo class="h-6 w-auto" />
        </Link>
    </div>
    <form @submit.prevent="submit" class="w-full max-w-sm">
        <h2 class="text-white text-3xl font-bold mb-6">Forgot Password</h2>
        <div class="mb-4 text-sm text-gray-300 text-center">
            Forgot your password? No problem. Just let us know your email address and we will email you a password reset link that will allow you to choose a new one.
        </div>
        <div v-if="$page.props.status" class="mb-4 text-green-400 text-sm text-center">
            {{ $page.props.status }}
        </div>
        <div v-if="Object.keys(form.errors).length" class="mb-4 text-red-400 text-sm">
            <div v-for="error in form.errors" :key="error">{{ error }}</div>
        </div>
        <div class="mb-8">
            <label for="email" class="block text-sm text-white mb-2">Email</label>
            <input
                id="email"
                v-model="form.email"
                type="email"
                class="w-full px-4 py-2 rounded-md bg-[#23262F] text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 border-none"
                :class="{ 'ring-2 ring-red-500': form.errors.email }"
                placeholder="your@email.com"
                required
                autofocus
            />
        </div>
        <button
            type="submit"
            class="w-full py-2 rounded-full bg-[#23262F] text-white text-base font-semibold hover:bg-[#23262F]/90 focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:opacity-50"
            :disabled="form.processing"
        >
            Email Password Reset Link
        </button>
        <div class="mt-8 text-center">
            <span class="text-gray-400">Remember your password?</span>
            <a href="/login" class="text-blue-400 hover:underline ml-1">Log In</a>
        </div>
    </form>
</div>
</template>

<script setup>
import { useForm, Link } from '@inertiajs/vue3';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';

const form = useForm({
    email: '',
});

function submit() {
    form.post(route('password.request'), {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
        },
        onError: (errors) => {
            console.log('Forgot password errors:', errors);
        },
    });
}
</script>

<script>
export default {
    name: 'ForgotPassword',
    props: {
        status: String,
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