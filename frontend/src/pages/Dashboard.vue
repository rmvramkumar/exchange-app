<template>
    <div class="min-h-screen bg-gray-900 text-white">
        <!-- Header -->
        <header class="bg-gray-800 border-b border-gray-700 sticky top-0 z-50">
            <div class="max-w-7xl mx-auto px-4 py-4 flex justify-between items-center">
                <h1 class="text-2xl font-bold">Exchange Dashboard</h1>
                <div class="flex items-center gap-4">
                    <span v-if="profile" class="text-sm">{{ profile.email }}</span>
                    <button
                        @click="logout"
                        class="px-4 py-2 bg-red-600 hover:bg-red-700 rounded transition"
                    >
                        Logout
                    </button>
                </div>
            </div>
        </header>

        <!-- Messages -->
        <div v-if="error" class="mx-auto max-w-7xl mt-4 px-4">
            <div class="bg-red-900 border border-red-700 text-red-100 px-4 py-3 rounded">
                {{ error }}
                <button @click="clearMessages" class="ml-4 underline">Dismiss</button>
            </div>
        </div>
        <div v-if="successMessage" class="mx-auto max-w-7xl mt-4 px-4">
            <div class="bg-green-900 border border-green-700 text-green-100 px-4 py-3 rounded">
                {{ successMessage }}
                <button @click="clearMessages" class="ml-4 underline">Dismiss</button>
            </div>
        </div>

        <main class="max-w-7xl mx-auto px-4 py-8">
            <!-- Wallet Overview -->
            <section class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <!-- USD Balance -->
                <div class="bg-gray-800 border border-gray-700 rounded-lg p-6">
                    <h2 class="text-sm font-semibold text-gray-400 mb-2">USD Balance</h2>
                    <p class="text-3xl font-bold">
                        ${{ profile ? parseFloat(profile.balance).toFixed(2) : '0.00' }}
                    </p>
                </div>

                <!-- Asset Balances -->
                <div class="md:col-span-2 bg-gray-800 border border-gray-700 rounded-lg p-6">
                    <h2 class="text-sm font-semibold text-gray-400 mb-4">Asset Balances</h2>
                    <div v-if="profile?.assets?.length" class="grid grid-cols-2 gap-4">
                        <div v-for="asset in profile.assets" :key="asset.symbol" class="bg-gray-700 p-4 rounded">
                            <div class="text-sm text-gray-300">{{ asset.symbol }}</div>
                            <div class="text-lg font-bold mt-1">{{ parseFloat(asset.amount).toFixed(8) }}</div>
                            <div class="text-xs text-gray-400 mt-2">
                                Locked: {{ parseFloat(asset.locked).toFixed(8) }}
                            </div>
                        </div>
                    </div>
                    <div v-else class="text-gray-400">No assets yet</div>
                </div>
            </section>

            <!-- Main Grid: OrderForm + Orders + Orderbook -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Order Form (Left) -->
                <div class="lg:col-span-1">
                    <OrderForm
                        @order-placed="handleOrderPlaced"
                        :loading="loading"
                    />
                </div>

                <!-- Orders & Orderbook (Right) -->
                <div class="lg:col-span-2 space-y-8">
                    <!-- Orders List -->
                    <section class="bg-gray-800 border border-gray-700 rounded-lg p-6">
                        <h2 class="text-xl font-bold mb-4">Orders</h2>

                        <!-- Tabs/Filter -->
                        <div class="flex gap-2 mb-4 border-b border-gray-700 pb-2">
                            <button
                                v-for="s in ['all', 'open', 'filled', 'cancelled']"
                                :key="s"
                                @click="selectedStatus = s"
                                :class="[
                                    'px-3 py-1 text-sm font-semibold transition',
                                    selectedStatus === s
                                        ? 'text-blue-400 border-b-2 bg-gray-500'
                                        : 'text-gray-400 hover:text-gray-300'
                                ]"
                            >
                                {{ s }}
                            </button>
                        </div>

                        <!-- Orders Table -->
                        <div v-if="filteredOrders.length" class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead class="text-gray-400 text-xs">
                                    <tr class="border-b border-gray-700">
                                        <th class="text-left py-2 px-3">Symbol</th>
                                        <th class="text-left py-2 px-3">Side</th>
                                        <th class="text-left py-2 px-3">Price</th>
                                        <th class="text-left py-2 px-3">Amount</th>
                                        <th class="text-left py-2 px-3">Status</th>
                                        <th class="text-left py-2 px-3">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr
                                        v-for="order in filteredOrders"
                                        :key="order.id"
                                        class="border-b border-gray-700 hover:bg-gray-700 transition"
                                    >
                                        <td class="py-3 px-3 font-semibold">{{ order.symbol }}</td>
                                        <td class="py-3 px-3">
                                            <span :class="order.side === 'buy' ? 'text-green-400' : 'text-red-400'">
                                                {{ order.side.toUpperCase() }}
                                            </span>
                                        </td>
                                        <td class="py-3 px-3">${{ parseFloat(order.price).toFixed(4) }}</td>
                                        <td class="py-3 px-3">{{ parseFloat(order.amount).toFixed(8) }}</td>
                                        <td class="py-3 px-3">
                                            <span :class="getStatusColor(order.status)">
                                                {{ getStatusLabel(order.status) }}
                                            </span>
                                        </td>
                                        <td class="py-3 px-3">
                                            <button
                                                v-if="order.status === 1"
                                                @click="handleCancelOrder(order.id)"
                                                :disabled="loading"
                                                class="text-red-400 hover:text-red-300 disabled:opacity-50 text-xs font-semibold"
                                            >
                                                Cancel
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div v-else class="text-gray-400 text-center py-8">
                            No orders found
                        </div>
                    </section>

                    <!-- Orderbook -->
                    <section class="bg-gray-800 border border-gray-700 rounded-lg p-6">
                        <h2 class="text-xl font-bold mb-4">Orderbook</h2>

                        <!-- Symbol Selector -->
                        <div class="mb-4 flex gap-2">
                            <button
                                v-for="sym in ['BTC', 'ETH']"
                                :key="sym"
                                @click="selectedSymbol = sym"
                                :class="[
                                    'px-4 py-2 rounded font-semibold transition',
                                    selectedSymbol === sym
                                        ? 'bg-blue-600 text-white'
                                        : 'bg-gray-700 text-gray-300 hover:bg-gray-600'
                                ]"
                            >
                                {{ sym }}
                            </button>
                        </div>

                        <!-- Buy Orders (Green) -->
                        <div class="mb-6">
                            <h3 class="text-sm font-semibold text-green-400 mb-2">BUY ORDERS</h3>
                            <div v-if="orderbook[selectedSymbol]?.buy?.length" class="space-y-1">
                                <div
                                    v-for="order in orderbook[selectedSymbol].buy.slice(0, 5)"
                                    :key="'buy-' + order.id"
                                    class="flex justify-between text-sm bg-gray-700 p-2 rounded hover:bg-gray-600 transition"
                                >
                                    <span class="text-green-400">${{ parseFloat(order.price).toFixed(4) }}</span>
                                    <span>{{ parseFloat(order.amount).toFixed(8) }}</span>
                                </div>
                            </div>
                            <div v-else class="text-gray-500 text-sm p-2">No buy orders</div>
                        </div>

                        <!-- Spread / Current Price -->
                        <div class="border-t border-b border-gray-700 py-3 mb-6 text-center">
                            <div class="text-xs text-gray-400">Spread</div>
                            <div v-if="spread" class="text-lg font-bold text-yellow-400">
                                ${{ spread }}
                            </div>
                            <div v-else class="text-gray-500">-</div>
                        </div>

                        <!-- Sell Orders (Red) -->
                        <div>
                            <h3 class="text-sm font-semibold text-red-400 mb-2">SELL ORDERS</h3>
                            <div v-if="orderbook[selectedSymbol]?.sell?.length" class="space-y-1">
                                <div
                                    v-for="order in orderbook[selectedSymbol].sell.slice(0, 5)"
                                    :key="'sell-' + order.id"
                                    class="flex justify-between text-sm bg-gray-700 p-2 rounded hover:bg-gray-600 transition"
                                >
                                    <span class="text-red-400">${{ parseFloat(order.price).toFixed(4) }}</span>
                                    <span>{{ parseFloat(order.amount).toFixed(8) }}</span>
                                </div>
                            </div>
                            <div v-else class="text-gray-500 text-sm p-2">No sell orders</div>
                        </div>
                    </section>
                </div>
            </div>
        </main>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { useExchange } from '../composables/useExchange.js';
import OrderForm from './OrderForm.vue';

const {
    profile,
    orders,
    orderbook,
    loading,
    error,
    successMessage,
    fetchUser,
    fetchProfile,
    fetchOrders,
    cancelOrder,
    subscribeToUpdates,
    clearMessages
} = useExchange();

const selectedStatus = ref('all');
const selectedSymbol = ref('BTC');
const userId = ref(null);

const statusMap = { 'open': 1, 'filled': 2, 'cancelled': 3 };

const filteredOrders = computed(() => {
    if (selectedStatus.value === 'all') {
        return orders.value;
    }
    const statusCode = statusMap[selectedStatus.value];
    return orders.value.filter(o => o.status === statusCode);
});

const spread = computed(() => {
    const buy = orderbook[selectedSymbol.value]?.buy?.[0];
    const sell = orderbook[selectedSymbol.value]?.sell?.[0];

    if (!buy || !sell) return null;

    const diff = parseFloat(sell.price) - parseFloat(buy.price);
    return diff.toFixed(4);
});

function getStatusLabel(status) {
    return { 1: 'OPEN', 2: 'FILLED', 3: 'CANCELLED' }[status] || 'UNKNOWN';
}

function getStatusColor(status) {
    return {
        1: 'text-yellow-400 font-semibold',
        2: 'text-green-400 font-semibold',
        3: 'text-gray-400'
    }[status] || '';
}

async function handleCancelOrder(orderId) {
    if (!confirm('Cancel this order?')) return;

    try {
        await cancelOrder(orderId);
    } catch (err) {
        console.error('Error cancelling order:', err);
    }
}

function handleOrderPlaced() {
    // Refresh data after order placed
    fetchProfile();
    fetchOrders();
}

async function logout() {
    try {
        // Delete token from localStorage or sessionStorage
        localStorage.removeItem('auth_token');
        // Redirect to login
        window.location.href = '/login';
    } catch (err) {
        console.error('Logout error:', err);
    }
}

onMounted(() => {
    // Fetch user and profile once on mount
    fetchUser().then(userData => {
        if (userData?.id) {
            userId.value = userData.id;
            subscribeToUpdates(userData.id);
        }
    }).catch(err => {
        console.error('Failed to fetch user:', err);
    });

    // Fetch initial orders and profile once
    fetchProfile();
    fetchOrders();

    // Real-time updates via Pusher — no polling needed
});
</script>

<style scoped>
/* Add any custom styles here */
</style>
