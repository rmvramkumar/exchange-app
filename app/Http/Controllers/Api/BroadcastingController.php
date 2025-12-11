<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class BroadcastingController extends Controller
{
    /**
     * Handle broadcasting auth requests for Pusher
     * Called when a client (Pusher.js) subscribes to a private channel
     */
    public function auth(Request $request)
    {
        // The authenticated user via auth:sanctum
        $user = $request->user();

        // Validate channel and socket_id from Pusher
        $channelName = $request->input('channel_name');
        $socketId = $request->input('socket_id');

        if (!$channelName || !$socketId) {
            return response()->json(['message' => 'Missing channel_name or socket_id'], 400);
        }

        // For user-specific private channels, validate ownership
        if (strpos($channelName, 'private-user.') === 0) {
            $idFromChannel = (int) str_replace('private-user.', '', $channelName);
            if ((int) $user->id !== $idFromChannel) {
                return response()->json(['message' => 'Unauthorized for this channel'], 403);
            }
        }

        // Generate auth signature using Pusher's algorithm
        $authSignature = hash_hmac('sha256', $socketId . ':' . $channelName, env('PUSHER_APP_SECRET'));
        $auth = env('PUSHER_APP_KEY') . ':' . $authSignature;

        return response()->json(['auth' => $auth]);
    }
}
