<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

class DebugController extends Controller
{
    /**
     * Get debug info about Pusher connection and credentials
     */
    public function pusherInfo()
    {
        // Debug endpoint disabled in cleanup; return minimal info without secrets
        return response()->json([
            'pusher' => [
                'app_id' => env('PUSHER_APP_ID'),
                'app_key' => env('PUSHER_APP_KEY'),
                'cluster' => env('PUSHER_APP_CLUSTER'),
                'broadcast_driver' => env('BROADCAST_DRIVER'),
            ]
        ]);
    }
}
