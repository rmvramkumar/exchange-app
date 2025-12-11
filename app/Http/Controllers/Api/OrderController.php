<?php

namespace App\Http\Controllers\Api;

use App\Models\Order;
use Illuminate\Http\Request;
use App\Services\OrderService;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{
    protected $svc;
    public function __construct(OrderService $svc)
    {
        $this->svc = $svc;
    }

    public function profile(Request $request)
    {
        $user = $request->user()->load('assets');
        return response()->json([
            'balance' => $user->balance,
            'assets'  => $user->assets->map(function ($a) {
                return [
                    'symbol' => $a->symbol,
                    'amount' => $a->amount,
                    'locked' => $a->locked_amount
                ];
            })
        ]);
    }

    public function orders(Request $request)
    {
        $symbol = $request->get('symbol');
        $status = $request->get('status'); // optional: filter by status

        // Get both open orders (for orderbook) and user's all orders
        $allOrdersQuery = Order::query();

        // Optionally filter by symbol
        if ($symbol) {
            $allOrdersQuery->where('symbol', strtoupper($symbol));
        }

        // Optionally filter by status (open=1, filled=2, cancelled=3)
        if ($status) {
            $statusMap = ['open' => 1, 'filled' => 2, 'cancelled' => 3];
            if (isset($statusMap[$status])) {
                $allOrdersQuery->where('status', $statusMap[$status]);
            }
        }

        $orders = $allOrdersQuery->orderBy('created_at', 'desc')->get();

        return response()->json($orders);
    }

    public function create(Request $request)
    {
        $request->validate([
            'symbol' => 'required|string',
            'side' => 'required|in:buy,sell',
            'price' => 'required|numeric|min:0.0001',
            'amount' => 'required|numeric|min:0.0001'
        ]);

        try {
            $user = $request->user();
            $order = $this->svc->createOrder($user, $request->only(['symbol', 'side', 'price', 'amount']));
            return response()->json($order, 201);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function cancel(Request $request, $id)
    {
        try {
            $user = $request->user();
            $order = Order::findOrFail($id);
            $cancelled = $this->svc->cancelOrder($user, $order);
            return response()->json(['message' => 'cancelled', 'order' => $cancelled], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }
}
