<template>
  <button
    @click="$emit('toggle-sidebar')"
    class="hidden lg:block fixed top-4 left-4 z-[102] p-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none"
  >
    <span class="text-2xl">{{ sidebarOpen ? '✕' : '☰' }}</span>
  </button>
</template>

<script setup>
defineProps({ sidebarOpen: Boolean });
defineEmits(['toggle-sidebar']);
</script>