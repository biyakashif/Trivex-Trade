<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';

const messages = ref([]);
const newMessages = ref(['', '', '']);
const error = ref(null);

async function fetchMessages() {
  try {
    const response = await fetch('/admin/messages', {
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
      },
    });
    const data = await response.json();
    messages.value = data.messages || ['', '', '']; // Ensure 3 messages
    newMessages.value = [...messages.value]; // Sync with editable input
  } catch (err) {
    error.value = 'Failed to fetch messages';
    console.error(err);
  }
}

async function saveMessages() {
  try {
    const response = await fetch('/admin/messages', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
      },
      body: JSON.stringify({ messages: newMessages.value.filter(msg => msg.trim()).slice(0, 3) }),
    });
    const data = await response.json();
    if (data.message) {
      await fetchMessages(); // Refresh after save
    }
  } catch (err) {
    error.value = 'Failed to save messages';
    console.error(err);
  }
}

onMounted(fetchMessages);
</script>

<template>
  <Head title="Manage Messages" />
  <AdminLayout>
    <div class="py-6">
      <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
        <h1 class="text-2xl font-bold mb-4">Manage Announcements</h1>
        <p class="text-gray-700 mb-4">Manage up to 3 announcements for all users.</p>

        <!-- Message Form with 3 fixed inputs -->
        <div class="mb-6 space-y-4">
          <div v-for="(message, index) in newMessages" :key="index" class="flex items-center space-x-2">
            <input
              v-model="newMessages[index]"
              type="text"
              class="w-full p-2 border rounded-md"
              :placeholder="'Announcement ' + (index + 1)"
              maxlength="255"
            />
          </div>
          <button
            @click="saveMessages"
            class="px-4 py-2 rounded-md action-btn"
          >
            Save Announcements
          </button>
          <p v-if="error" class="text-red-500 mt-2">{{ error }}</p>
        </div>

        <!-- Preview of Current Messages -->
        <div class="space-y-2">
          <p class="text-gray-600">Current Announcements (Read-only):</p>
          <div v-for="(message, index) in messages" :key="index" class="p-2 bg-gray-100 rounded">
            {{ message || `Announcement ${index + 1} (empty)` }}
          </div>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>