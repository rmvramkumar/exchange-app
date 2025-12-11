import { createApp } from 'vue';
import App from './App.vue';
import axios from 'axios';
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

import './style.css'; // tailwind

const app = createApp(App);

axios.defaults.baseURL = 'http://localhost:8000/api';
axios.defaults.withCredentials = true;

// Setup auth token from localStorage if exists
const token = localStorage.getItem('auth_token') || sessionStorage.getItem('auth_token');
if (token) {
    axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;
}

// Add response interceptor to handle auth errors
axios.interceptors.response.use(
    response => response,
    error => {
        if (error.response?.status === 401) {
            // Clear token and redirect to login
            localStorage.removeItem('auth_token');
            sessionStorage.removeItem('auth_token');
            window.location.href = '/';
        }
        return Promise.reject(error);
    }
);

window.Pusher = Pusher;

// Initialize Echo with lazy token loading
window.Echo = new Echo({
    broadcaster: 'pusher',
    key: import.meta.env.VITE_PUSHER_APP_KEY,
    cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER,
    wsHost: import.meta.env.VITE_PUSHER_HOST || `ws-ap2.pusher.com`,
    wsPort: import.meta.env.VITE_PUSHER_PORT || 443,
    wssPort: import.meta.env.VITE_PUSHER_PORT || 443,
    forceTLS: true,
    // Broadcasting auth is under /api/broadcasting/auth with auth:sanctum middleware
    authEndpoint: 'http://localhost:8000/api/broadcasting/auth',
    auth: {
        headers: {
            // Dynamically get token when auth request is made
            get Authorization() {
                const token = localStorage.getItem('auth_token') || sessionStorage.getItem('auth_token');
                return token ? `Bearer ${token}` : '';
            }
        }
    },
    // Enable console logging for debugging
    enableLogging: true
});

// Log Pusher connection state
window.Echo.connector.pusher.connection.bind('connected', () => {
    console.log('[Pusher] Connected to Pusher');
});

window.Echo.connector.pusher.connection.bind('disconnected', () => {
    console.log('[Pusher] Disconnected from Pusher');
});

window.Echo.connector.pusher.connection.bind('error', (error) => {
    console.error('[Pusher] Connection error:', error);
});

app.config.globalProperties.$axios = axios;
app.mount('#app');
