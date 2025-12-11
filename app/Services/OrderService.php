<?php

namespace App\Services;

use Exception;
use App\Models\User;
use App\Models\Asset;
use App\Models\Order;
use App\Models\Trade;
use App\Events\OrderMatched;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderService
{
    const COMMISSION_RATE = 0.015; // 1.5%
    const SCALE = 8;

    /**
     * Create and immediately try to match (full-match-only).
     * @param User $user
     * @param array $data ['symbol','side','price','amount']
     */
    public function createOrder($user, array $data)
    {
        // sanitize symbol/inputs outside caller
        return DB::transaction(function () use ($user, $data) {
            $symbol = strtoupper($data['symbol']);
            $side = strtolower($data['side']);
            $price = $data['price'];
            $amount = $data['amount'];

            if ($side === 'buy') {
                $usdRequired = bcmul($price, $amount, self::SCALE);
                // re-load and lock user row to ensure DB-level lock
                $user = User::whereKey($user->id)->lockForUpdate()->first();
                if (bccomp($user->balance, $usdRequired, self::SCALE) < 0) {
                    throw new Exception('Insufficient USD balance');
                }
                // deduct immediately and persist
                $user->balance = bcsub($user->balance, $usdRequired, self::SCALE);
                $user->save();
            } else { // sell
                // lock asset row
                $asset = Asset::where('user_id', $user->id)->where('symbol', $symbol)->lockForUpdate()->first();
                if (!$asset || bccomp($asset->amount, $amount, self::SCALE) < 0) {
                    throw new Exception('Insufficient asset balance');
                }
                $asset->amount = bcsub($asset->amount, $amount, self::SCALE);
                $asset->locked_amount = bcadd($asset->locked_amount, $amount, self::SCALE);
                $asset->save();
            }

            $order = Order::create([
                'user_id' => $user->id,
                'symbol'  => $symbol,
                'side'    => $side,
                'price'   => $price,
                'amount'  => $amount,
                'status'  => Order::STATUS_OPEN
            ]);

            // attempt matching (full-match-only)
            $this->attemptMatch($order);

            return $order;
        }, 5);
    }

    /**
     * Attempt to match the given order with first valid counter order (full match only).
     */
    public function attemptMatch(Order $order)
    {
        // wrap matches in a transaction
        return DB::transaction(function () use ($order) {
            $symbol = $order->symbol;
            // ensure fresh state
            $order = Order::where('id', $order->id)->lockForUpdate()->first();

            if ($order->status != Order::STATUS_OPEN) {
                \Log::info("Order {$order->id} not open, status: {$order->status}");
                return null;
            }

            if ($order->side === 'buy') {
                // find first sell where sell.price <= buy.price
                \Log::info("BUY order {$order->id}: searching for SELL with price <= {$order->price}");
                $counter = Order::where('symbol', $symbol)
                    ->where('side', 'sell')
                    ->where('status', Order::STATUS_OPEN)
                    ->where('price', '<=', $order->price)
                    ->orderBy('created_at', 'asc')
                    ->lockForUpdate()
                    ->first();
                if ($counter) {
                    \Log::info("BUY order {$order->id}: Found SELL order {$counter->id} at price {$counter->price}");
                } else {
                    \Log::info("BUY order {$order->id}: No SELL orders found");
                }
            } else {
                // new sell -> find first buy where buy.price >= sell.price
                \Log::info("SELL order {$order->id}: searching for BUY with price >= {$order->price}");
                $counter = Order::where('symbol', $symbol)
                    ->where('side', 'buy')
                    ->where('status', Order::STATUS_OPEN)
                    ->where('price', '>=', $order->price)
                    ->orderBy('created_at', 'asc')
                    ->lockForUpdate()
                    ->first();
                if ($counter) {
                    \Log::info("SELL order {$order->id}: Found BUY order {$counter->id} at price {$counter->price}");
                } else {
                    \Log::info("SELL order {$order->id}: No BUY orders found");
                }
            }

            if (!$counter) return null;


            // full-match-only: amounts must be equal
            if (bccomp($order->amount, $counter->amount, self::SCALE) !== 0) {
                \Log::info("Order {$order->id} and {$counter->id}: Amount mismatch ({$order->amount} vs {$counter->amount})");
                return null; // no partial matches
            }

            \Log::info("Orders {$order->id} and {$counter->id} MATCHED! Processing trade...");

            // Determine buyer/seller
            $buyOrder = $order->side === 'buy' ? $order : $counter;
            $sellOrder = $order->side === 'sell' ? $order : $counter;

            $tradeVolume = bcmul($buyOrder->price, $buyOrder->amount, self::SCALE); // USD volume
            $commission = bcmul($tradeVolume, self::COMMISSION_RATE, self::SCALE);

            // Lock both user rows
            $buyer = User::whereKey($buyOrder->user_id)->lockForUpdate()->first();
            $seller = User::whereKey($sellOrder->user_id)->lockForUpdate()->first();

            // Lock buyer asset (create if missing) and seller asset
            $buyerAsset = Asset::where('user_id', $buyOrder->user_id)->where('symbol', $symbol)->lockForUpdate()->first();
            if (!$buyerAsset) {
                $buyerAsset = Asset::create(['user_id' => $buyOrder->user_id, 'symbol' => $symbol, 'amount' => 0, 'locked_amount' => 0]);
                // no need to re-lock in most DB drivers for this tiny created row
            }

            $sellerAsset = Asset::where('user_id', $sellOrder->user_id)->where('symbol', $symbol)->lockForUpdate()->first();
            if (!$sellerAsset || bccomp($sellerAsset->locked_amount, $sellOrder->amount, self::SCALE) < 0) {
                throw new Exception('Seller locked amount insufficient');
            }

            // Transfer asset: add to buyer, remove from seller locked_amount
            $buyerAsset->amount = bcadd($buyerAsset->amount, $buyOrder->amount, self::SCALE);
            $buyerAsset->save();

            $sellerAsset->locked_amount = bcsub($sellerAsset->locked_amount, $sellOrder->amount, self::SCALE);
            $sellerAsset->save();

            // Financial transfers: buyer already paid tradeVolume when placing order, now deduct commission
            $buyer->balance = bcsub($buyer->balance, $commission, self::SCALE);
            $buyer->save();

            // Seller receives tradeVolume - commission
            $seller->balance = bcadd($seller->balance, bcsub($tradeVolume, $commission, self::SCALE), self::SCALE);
            $seller->save();

            // Mark both orders as filled
            $buyOrder->status = Order::STATUS_FILLED;
            $sellOrder->status = Order::STATUS_FILLED;
            $buyOrder->save();
            $sellOrder->save();

            // Optional: store trade record
            $trade = Trade::create([
                'buy_order_id' => $buyOrder->id,
                'sell_order_id' => $sellOrder->id,
                'symbol' => $symbol,
                'price' => $buyOrder->price,
                'amount' => $buyOrder->amount,
                'commission' => $commission
            ]);

            \Log::info("Trade {$trade->id} created. Broadcasting OrderMatched to users {$buyOrder->user_id} and {$sellOrder->user_id}");

            // Broadcast to both users
            event(new OrderMatched($trade, $buyOrder->user_id, $sellOrder->user_id));

            return $trade;
        }, 5);
    }

    /**
     * Cancel order and release locked assets / USD
     */
    public function cancelOrder($user, Order $order)
    {
        return DB::transaction(function () use ($user, $order) {
            $order = Order::where('id', $order->id)->lockForUpdate()->first();
            if (!$order || $order->status != Order::STATUS_OPEN) {
                throw new \Exception('Order not open');
            }
            if ($order->user_id !== $user->id) {
                throw new \Exception('Not order owner');
            }

            if ($order->side === 'buy') {
                $usdLocked = bcmul($order->price, $order->amount, self::SCALE);
                $user = User::whereKey($user->id)->lockForUpdate()->first();
                $user->balance = bcadd($user->balance, $usdLocked, self::SCALE);
                $user->save();
            } else {
                $asset = Asset::where('user_id', $user->id)->where('symbol', $order->symbol)->lockForUpdate()->first();
                if ($asset) {
                    $asset->locked_amount = bcsub($asset->locked_amount, $order->amount, self::SCALE);
                    $asset->amount = bcadd($asset->amount, $order->amount, self::SCALE);
                    $asset->save();
                }
            }

            $order->status = Order::STATUS_CANCELLED;
            $order->save();
            return $order;
        }, 5);
    }
}
