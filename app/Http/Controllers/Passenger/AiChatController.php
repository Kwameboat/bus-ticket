<?php

namespace App\Http\Controllers\Passenger;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AiChatController extends Controller
{
    public function index()
    {
        return view('passenger.ai-chat');
    }

    public function send(Request $request)
    {
        $request->validate(['message' => ['required', 'string', 'max:1000']]);
        return response()->json(['reply' => 'AI assistant is not configured yet.']);
    }
}
