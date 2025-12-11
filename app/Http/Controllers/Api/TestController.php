<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class TestController extends Controller
{
    // Temporary stub: TestController disabled in cleanup. Keep route removed.
    public function broadcast(Request $request)
    {
        return response()->json(['message' => 'Test endpoint disabled'], 404);
    }
}

