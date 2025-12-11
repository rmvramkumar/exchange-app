import { ref, reactive, computed } from 'vue';
import axios from 'axios';

const profile = ref(null);
const orders = ref([]);
const user = ref(null);
const orderbook = reactive({
    BTC: { buy: [], sell: [] },
    ETH: { buy: [], sell: [] }
});
const loading = ref(false);
const error = ref(null);
const successMessage = ref(null);

export function useExchange() {
    /**
     * Fetch current authenticated user
     */
    async function fetchUser() {
        try {
            const response = await axios.get('/auth/user');
            user.value = response.data;
            return response.data;
        } catch (err) {
            console.error('Error fetching user:', err);
            throw err;
        }
    }

    /**
     * Fetch user profile (balance + assets)
     */
    async function fetchProfile() {
        try {
            loading.value = true;
            const response = await axios.get('/profile');
            profile.value = response.data;
            error.value = null;
        } catch (err) {
            error.value = err.response?.data?.message || 'Failed to fetch profile';
            console.error('Error fetching profile:', err);
        } finally {
            loading.value = false;
        }
    }

    /**
     * Fetch all orders (open, filled, cancelled)
     */
    async function fetchOrders(symbol = null) {
        try {
            loading.value = true;
            const params = symbol ? { symbol } : {};
            const response = await axios.get('/orders', { params });
            orders.value = response.data;
            error.value = null;

            // Separate into orderbook (open orders only)
            updateOrderbook(response.data);
        } catch (err) {
            error.value = err.response?.data?.message || 'Failed to fetch orders';
            console.error('Error fetching orders:', err);
        } finally {
            loading.value = false;
        }
    }

    /**
     * Separate open orders by symbol and side for orderbook
     */
    function updateOrderbook(allOrders) {
        // Reset orderbook
        Object.keys(orderbook).forEach(symbol => {
            orderbook[symbol].buy = [];
            orderbook[symbol].sell = [];
        });

        // Filter and sort open orders
        const openOrders = allOrders.filter(o => o.status === 1);
        openOrders.forEach(order => {
            if (orderbook[order.symbol]) {
                if (order.side === 'buy') {
                    orderbook[order.symbol].buy.push(order);
                } else {
                    orderbook[order.symbol].sell.push(order);
                }
            }
        });

        // Sort buy orders by price descending, sell orders by price ascending
        Object.keys(orderbook).forEach(symbol => {
            orderbook[symbol].buy.sort((a, b) => parseFloat(b.price) - parseFloat(a.price));
            orderbook[symbol].sell.sort((a, b) => parseFloat(a.price) - parseFloat(b.price));
        });
    }

    /**
     * Place a new order
     */
    async function placeOrder(orderData) {
        try {
            loading.value = true;
            const response = await axios.post('/orders', orderData);
            successMessage.value = `Order placed: ${orderData.side.toUpperCase()} ${orderData.amount} ${orderData.symbol} @ $${orderData.price}`;
            error.value = null;

            // Refresh profile and orders
            await Promise.all([fetchProfile(), fetchOrders()]);

            return response.data;
        } catch (err) {
            error.value = err.response?.data?.message || 'Failed to place order';
            throw err;
        } finally {
            loading.value = false;
        }
    }

    /**
     * Cancel an existing order
     */
    async function cancelOrder(orderId) {
        try {
            loading.value = true;
            const response = await axios.post(`/orders/${orderId}/cancel`);
            successMessage.value = 'Order cancelled successfully';
            error.value = null;

            // Refresh profile and orders
            await Promise.all([fetchProfile(), fetchOrders()]);

            return response.data;
        } catch (err) {
            error.value = err.response?.data?.message || 'Failed to cancel order';
            throw err;
        } finally {
            loading.value = false;
        }
    }

    /**
     * Handle incoming OrderMatched event from Pusher
     */
    function handleOrderMatched(data) {
        const trade = data.trade;
        successMessage.value = `Trade executed: ${trade.amount} ${trade.symbol} @ $${trade.price}`;

        // Refresh data after a brief delay to let DB update
        setTimeout(() => {
            fetchProfile();
            fetchOrders();
        }, 500);
    }

    /**
     * Subscribe to private channel for real-time updates
     */
    function subscribeToUpdates(userId) {
        if (window.Echo) {
            // Echo.private() automatically adds the 'private-' prefix,
            // so we pass the base name without the prefix
            const channelName = `user.${userId}`;
            console.log(`[Echo] Subscribing to private channel: ${channelName}`);
            const channel = window.Echo.private(channelName);

            // Called when subscription is successful
            channel.subscribed(() => {
                console.log(`[Echo] Successfully subscribed to private-user.${userId}`);
            });

            // Listen for OrderMatched event
            channel.listen('OrderMatched', (data) => {
                console.log('[Echo] OrderMatched event received:', data);
                handleOrderMatched(data);
            });

            // Listen for errors
            channel.error((error) => {
                console.error('[Echo] Subscription error:', error);
            });
        } else {
            console.warn('[Echo] window.Echo not available');
        }
    }    /**
     * Clear messages
     */
    function clearMessages() {
        error.value = null;
        successMessage.value = null;
    }

    return {
        profile,
        orders,
        user,
        orderbook,
        loading,
        error,
        successMessage,
        fetchUser,
        fetchProfile,
        fetchOrders,
        placeOrder,
        cancelOrder,
        subscribeToUpdates,
        clearMessages
    };
}

