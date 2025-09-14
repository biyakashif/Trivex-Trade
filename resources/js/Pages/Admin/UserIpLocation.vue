<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

// Define props with proper type checking and defaults
const props = defineProps({
    savedLocations: {
        type: Object,
        required: true,
        default: () => ({
            data: [],
            current_page: 1,
            per_page: 15,
            last_page: 1,
            from: null,
            to: null,
            total: 0,
            links: [],
            prev_page_url: null,
            next_page_url: null
        })
    }
});

// Compute the page information for AdminLayout
const page = computed(() => ({
    component: 'Admin/UserIpLocation',
    props: {
        savedLocations: props.savedLocations
    }
}));
</script>

<template>
    <Head title="User Location - Admin" />
    <AdminLayout :page="page">
        <div class="py-6">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h1 class="text-2xl font-bold mb-4">User Location</h1>
                <div class="space-y-6">
                    <div>
                        <h2 class="text-lg font-semibold">User Location</h2>
                        <div class="overflow-x-auto mt-2">
                            <!-- Table Header -->
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-200">
                                    <tr>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">User</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">IP</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">City</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Region</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Country</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Created At</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="location in savedLocations.data" :key="location.id">
                                        <td class="px-4 py-2">{{ location.user?.name || 'N/A' }}</td>
                                        <td class="px-4 py-2">{{ location.ip_address || 'N/A' }}</td>
                                        <td class="px-4 py-2">{{ location.city || 'Unknown' }}</td>
                                        <td class="px-4 py-2">{{ location.region || 'Unknown' }}</td>
                                        <td class="px-4 py-2">{{ location.country || 'Unknown' }}</td>
                                        <td class="px-4 py-2">{{ location.created_at ? new Date(location.created_at).toLocaleString() : 'N/A' }}</td>
                                    </tr>
                                    <tr v-if="!savedLocations.data?.length">
                                        <td colspan="6" class="px-4 py-2 text-center text-gray-500">No saved locations.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <div v-if="savedLocations.total > 0" class="mt-4 flex justify-between items-center">
                            <div>
                                <p class="text-sm text-gray-600">
                                    Showing {{ savedLocations.from || 0 }} to {{ savedLocations.to || 0 }} of {{ savedLocations.total || 0 }} entries
                                </p>
                            </div>
                            <div class="flex space-x-2">
                                <Link
                                    v-if="savedLocations.prev_page_url"
                                    :href="savedLocations.prev_page_url"
                                    class="px-3 py-1 border rounded text-sm bg-white border-gray-300 hover:bg-gray-100"
                                >
                                    Previous
                                </Link>
                                
                                <template v-for="(link, i) in savedLocations.links" :key="i">
                                    <Link
                                        v-if="link.url && !['Previous', 'Next'].includes(link.label)"
                                        :href="link.url"
                                        class="px-3 py-1 border rounded text-sm"
                                        :class="link.active ? 'bg-blue-500 text-white border-blue-500' : 'bg-white border-gray-300 hover:bg-gray-100'"
                                    >
                                        {{ link.label }}
                                    </Link>
                                </template>

                                <Link
                                    v-if="savedLocations.next_page_url"
                                    :href="savedLocations.next_page_url"
                                    class="px-3 py-1 border rounded text-sm bg-white border-gray-300 hover:bg-gray-100"
                                >
                                    Next
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>