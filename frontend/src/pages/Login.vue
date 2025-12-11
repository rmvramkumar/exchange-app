<template>
    <div class="min-h-screen bg-linear-to-br from-gray-900 via-gray-800 to-gray-900 flex items-center justify-center p-4 rounded-lg">
        <div class="w-full max-w-md">
            <!-- Header -->
            <div class="text-center mb-8">
                <h1 class="text-4xl font-bold text-white mb-2">Exchange</h1>
                <p class="text-gray-400">Secure Trading Platform</p>
            </div>

            <!-- Card -->
            <div class="bg-gray-800 border border-gray-700 rounded-lg p-8">
                <h2 class="text-2xl font-bold text-white mb-6 text-center">Login</h2>

                <!-- Error Message -->
                <div v-if="error" class="bg-red-900 border border-red-700 text-red-100 p-4 rounded mb-6 text-sm">
                    {{ error }}
                </div>

                <!-- Form -->
                <form @submit.prevent="handleLogin" class="space-y-4">
                    <!-- Email -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-300 mb-2">Email</label>
                        <input
                            v-model="email"
                            type="email"
                            placeholder="you@example.com"
                            class="w-full p-3 bg-gray-700 border border-gray-600 rounded text-white placeholder-gray-500 focus:outline-none focus:border-blue-500"
                            required
                        />
                    </div>

                    <!-- Password -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-300 mb-2">Password</label>
                        <input
                            v-model="password"
                            type="password"
                            placeholder="••••••••"
                            class="w-full p-3 bg-gray-700 border border-gray-600 rounded text-white placeholder-gray-500 focus:outline-none focus:border-blue-500"
                            required
                        />
                    </div>

                    <!-- Login Button -->
                    <button
                        type="submit"
                        :disabled="loading"
                        class="w-full py-3 bg-blue-600 hover:bg-blue-700 disabled:bg-gray-600 disabled:cursor-not-allowed text-white font-bold rounded transition mt-6"
                    >
                        <span v-if="loading">Logging in...</span>
                        <span v-else>Login</span>
                    </button>
                </form>

                <!-- Register Link -->
                <div class="mt-6 text-center">
                    <p class="text-gray-400 text-sm">
                        Don't have an account?
                        <button
                            @click="showRegister = true"
                            class="text-blue-400 hover:text-blue-300 font-semibold"
                        >
                            Register
                        </button>
                    </p>
                </div>

                <!-- Demo Credentials -->
                <div class="mt-6 pt-6 border-t border-gray-700">
                    <p class="text-gray-400 text-xs mb-3">Demo Credentials:</p>
                    <div class="space-y-2 text-xs text-gray-400">
                        <div><strong>Email:</strong> user@example.com</div>
                        <div><strong>Password:</strong> password</div>
                    </div>
                </div>
            </div>

            <!-- Register Modal -->
            <div v-if="showRegister" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 px-4">
                <div class="bg-gray-800 border border-gray-700 rounded-lg p-8 w-full max-w-md">
                    <h2 class="text-2xl font-bold text-white mb-6">Create Account</h2>

                    <div v-if="registerError" class="bg-red-900 border border-red-700 text-red-100 p-4 rounded mb-6 text-sm">
                        {{ registerError }}
                    </div>

                    <form @submit.prevent="handleRegister" class="space-y-4">
                        <!-- Name -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-300 mb-2">Name</label>
                            <input
                                v-model="registerForm.name"
                                type="text"
                                placeholder="John Doe"
                                class="w-full p-3 bg-gray-700 border border-gray-600 rounded text-white placeholder-gray-500 focus:outline-none focus:border-blue-500"
                                required
                            />
                        </div>

                        <!-- Email -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-300 mb-2">Email</label>
                            <input
                                v-model="registerForm.email"
                                type="email"
                                placeholder="you@example.com"
                                class="w-full p-3 bg-gray-700 border border-gray-600 rounded text-white placeholder-gray-500 focus:outline-none focus:border-blue-500"
                                required
                            />
                        </div>

                        <!-- Password -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-300 mb-2">Password</label>
                            <input
                                v-model="registerForm.password"
                                type="password"
                                placeholder="••••••••"
                                class="w-full p-3 bg-gray-700 border border-gray-600 rounded text-white placeholder-gray-500 focus:outline-none focus:border-blue-500"
                                required
                            />
                        </div>

                        <!-- Password Confirm -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-300 mb-2">Confirm Password</label>
                            <input
                                v-model="registerForm.passwordConfirm"
                                type="password"
                                placeholder="••••••••"
                                class="w-full p-3 bg-gray-700 border border-gray-600 rounded text-white placeholder-gray-500 focus:outline-none focus:border-blue-500"
                                required
                            />
                        </div>

                        <!-- Initial Balance (Optional) -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-300 mb-2">Initial USD Balance</label>
                            <input
                                v-model.number="registerForm.balance"
                                type="number"
                                placeholder="1000"
                                step="0.01"
                                class="w-full p-3 bg-gray-700 border border-gray-600 rounded text-white placeholder-gray-500 focus:outline-none focus:border-blue-500"
                            />
                        </div>

                        <!-- Buttons -->
                        <div class="flex gap-3 mt-6">
                            <button
                                type="button"
                                @click="showRegister = false"
                                class="flex-1 py-3 bg-gray-700 hover:bg-gray-600 text-white font-bold rounded transition"
                            >
                                Cancel
                            </button>
                            <button
                                type="submit"
                                :disabled="registerLoading"
                                class="flex-1 py-3 bg-blue-600 hover:bg-blue-700 disabled:bg-gray-600 disabled:cursor-not-allowed text-white font-bold rounded transition"
                            >
                                <span v-if="registerLoading">Creating...</span>
                                <span v-else>Register</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import axios from 'axios';

const emit = defineEmits(['login']);

const email = ref('user@example.com');
const password = ref('password');
const loading = ref(false);
const error = ref('');

const showRegister = ref(false);
const registerForm = ref({
    name: '',
    email: '',
    password: '',
    passwordConfirm: '',
    balance: 1000
});
const registerLoading = ref(false);
const registerError = ref('');

async function handleLogin() {
    loading.value = true;
    error.value = '';

    try {
        // Laravel Sanctum login endpoint (adjust if different)
        const response = await axios.post('/auth/login', {
            email: email.value,
            password: password.value
        });

        // Store token
        const token = response.data.token;
        localStorage.setItem('auth_token', token);
        axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;

        // Ensure Echo uses the same auth header for broadcasting auth
        try {
            if (window.Echo && window.Echo.options) {
                window.Echo.options.auth = window.Echo.options.auth || { headers: {} };
                window.Echo.options.auth.headers = window.Echo.options.auth.headers || {};
                window.Echo.options.auth.headers.Authorization = `Bearer ${token}`;
            }
        } catch (e) {
            // non-fatal
            console.warn('Echo not available to set auth header yet');
        }

        emit('login');
    } catch (err) {
        error.value = err.response?.data?.message || 'Login failed';
        console.error('Login error:', err);
    } finally {
        loading.value = false;
    }
}

async function handleRegister() {
    registerError.value = '';

    if (registerForm.value.password !== registerForm.value.passwordConfirm) {
        registerError.value = 'Passwords do not match';
        return;
    }

    registerLoading.value = true;

    try {
        const response = await axios.post('/auth/register', {
            name: registerForm.value.name,
            email: registerForm.value.email,
            password: registerForm.value.password,
            password_confirmation: registerForm.value.passwordConfirm,
            balance: registerForm.value.balance
        });

        // Auto-login after registration
        email.value = registerForm.value.email;
        password.value = registerForm.value.password;
        showRegister.value = false;

        // Now login
        await handleLogin();
    } catch (err) {
        registerError.value = err.response?.data?.message || 'Registration failed';
        console.error('Register error:', err);
    } finally {
        registerLoading.value = false;
    }
}
</script>

<style scoped>
</style>
