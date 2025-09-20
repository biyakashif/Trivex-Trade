<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});
</script>

<template>
    <Head title="Profile" />

    <AuthenticatedLayout>
        <div class="pt-2 py-12">
            <div class="mx-auto space-y-6 px-4 sm:max-w-2xl md:max-w-3xl lg:max-w-4xl">
                <div class="bg-black p-6 rounded-xl shadow-lg">
                    <UpdateProfileInformationForm
                        :must-verify-email="mustVerifyEmail"
                        :status="status"
                        class="w-full"
                    />
                </div>

                <div class="bg-black p-6 rounded-xl shadow-lg">
                    <UpdatePasswordForm class="w-full" />
                </div>
<!-- 
                <div class="bg-black p-6 rounded-xl shadow-lg">
                    <DeleteUserForm class="w-full" />
                </div> -->
            </div>
        </div>
    </AuthenticatedLayout>
</template>