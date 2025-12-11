<script setup>
import { ref, onMounted } from 'vue';
import Dashboard from './pages/Dashboard.vue';
import Login from './pages/Login.vue';

const isAuthenticated = ref(false);

onMounted(() => {
    // Check if user has valid token
    const token = localStorage.getItem('auth_token') || sessionStorage.getItem('auth_token');
    isAuthenticated.value = !!token;
});
</script>

<template>
    <div>
        <Dashboard v-if="isAuthenticated" />
        <Login v-else @login="isAuthenticated = true" />
    </div>
</template>

<style scoped>
</style>

