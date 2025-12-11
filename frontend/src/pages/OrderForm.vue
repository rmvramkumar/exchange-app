<template>
    <div class="bg-gray-800 border border-gray-700 rounded-lg p-6">
        <h3 class="text-xl font-bold mb-6">Place Order</h3>

        <form @submit.prevent="handlePlaceOrder" class="space-y-4">
            <!-- Symbol Selector -->
            <div>
                <label class="block text-sm font-semibold text-gray-400 mb-2">Symbol</label>
                <select
                    v-model="formData.symbol"
                    class="w-full p-3 bg-gray-700 border border-gray-600 rounded text-white focus:outline-none focus:border-blue-500"
                >
                    <option>BTC</option>
                    <option>ETH</option>
                </select>
            </div>

            <!-- Side Selector -->
            <div>
                <label class="block text-sm font-semibold text-gray-400 mb-2">Side</label>
                <div class="flex gap-2">
                    <button
                        type="button"
                        @click="formData.side = 'buy'"
                        :class="[
                            'flex-1 py-3 rounded font-semibold transition',
                            formData.side === 'buy'
                                ? 'bg-green-600 text-white'
                                : 'bg-gray-700 text-gray-300 hover:bg-gray-600'
                        ]"
                    >
                        BUY
                    </button>
                    <button
                        type="button"
                        @click="formData.side = 'sell'"
                        :class="[
                            'flex-1 py-3 rounded font-semibold transition',
                            formData.side === 'sell'
                                ? 'bg-red-600 text-white'
                                : 'bg-gray-700 text-gray-300 hover:bg-gray-600'
                        ]"
                    >
                        SELL
                    </button>
                </div>
            </div>

            <!-- Price -->
            <div>
                <label class="block text-sm font-semibold text-gray-400 mb-2">Price (USD)</label>
                <input
                    v-model.number="formData.price"
                    type="number"
                    step="0.0001"
                    placeholder="e.g., 95000"
                    class="w-full p-3 bg-gray-700 border border-gray-600 rounded text-white placeholder-gray-500 focus:outline-none focus:border-blue-500"
                    required
                />
            </div>

            <!-- Amount -->
            <div>
                <label class="block text-sm font-semibold text-gray-400 mb-2">Amount</label>
                <input
                    v-model.number="formData.amount"
                    type="number"
                    step="0.00000001"
                    placeholder="e.g., 0.01"
                    class="w-full p-3 bg-gray-700 border border-gray-600 rounded text-white placeholder-gray-500 focus:outline-none focus:border-blue-500"
                    required
                />
            </div>

            <!-- Estimated USD / Volume -->
            <div class="bg-gray-700 rounded p-4">
                <div class="flex justify-between text-sm mb-2">
                    <span class="text-gray-400">Volume (USD)</span>
                    <span class="text-white font-semibold">{{ estimatedVolume }}</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-400">Commission (1.5%)</span>
                    <span class="text-white font-semibold">{{ estimatedCommission }}</span>
                </div>
            </div>

            <!-- Submit Button -->
            <button
                type="submit"
                :disabled="loading || !isFormValid"
                :class="[
                    'w-full py-3 rounded font-bold transition text-white',
                    isFormValid && !loading
                        ? formData.side === 'buy'
                            ? 'bg-green-600 hover:bg-green-700'
                            : 'bg-red-600 hover:bg-red-700'
                        : 'bg-gray-600 cursor-not-allowed opacity-50'
                ]"
            >
                <span v-if="loading">Placing Order...</span>
                <span v-else>
                    {{ formData.side === 'buy' ? 'BUY' : 'SELL' }} {{ formData.symbol }}
                </span>
            </button>

            <!-- Validation Messages -->
            <div v-if="validationError" class="bg-red-900 border border-red-700 text-red-100 p-3 rounded text-sm">
                {{ validationError }}
            </div>
        </form>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useExchange } from '../composables/useExchange.js';

const emit = defineEmits(['order-placed']);

const { placeOrder, loading, error, successMessage } = useExchange();

const formData = ref({
    symbol: 'BTC',
    side: 'buy',
    price: '',
    amount: ''
});

const validationError = ref('');

const isFormValid = computed(() => {
    return formData.value.price > 0 && formData.value.amount > 0;
});

const estimatedVolume = computed(() => {
    const vol = formData.value.price * formData.value.amount;
    return isNaN(vol) ? '$0.00' : `$${vol.toFixed(2)}`;
});

const estimatedCommission = computed(() => {
    const vol = formData.value.price * formData.value.amount;
    const commission = vol * 0.015;
    return isNaN(commission) ? '$0.00' : `$${commission.toFixed(2)}`;
});

async function handlePlaceOrder() {
    validationError.value = '';

    if (!isFormValid.value) {
        validationError.value = 'Please enter valid price and amount';
        return;
    }

    try {
        await placeOrder({
            symbol: formData.value.symbol,
            side: formData.value.side,
            price: parseFloat(formData.value.price),
            amount: parseFloat(formData.value.amount)
        });

        // Clear form on success
        formData.value.price = '';
        formData.value.amount = '';
        formData.value.side = 'buy';

        emit('order-placed');
    } catch (err) {
        validationError.value = err.response?.data?.message || 'Failed to place order';
    }
}
</script>

<style scoped>
input::-webkit-outer-spin-button,
input::-webkit-inner-spin-button {
    -webkit-appearance: none;
    margin: 0;
}

input[type=number] {
    -moz-appearance: textfield;
}
</style>
