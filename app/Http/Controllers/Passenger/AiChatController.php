<?php
namespace App\Http\Controllers\Passenger;

use App\Http\Controllers\Controller;
use App\Services\AI\AiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AiChatController extends Controller
{
    public function __construct(private AiService $aiService) {}

    public function index()
    {
        $session  = $this->aiService->getOrCreateSession(Auth::user());
        $messages = $session->messages()->latest()->take(50)->get()->reverse()->values();
        return view('passenger.ai-chat', compact('session','messages'));
    }

    public function send(Request $request)
    {
        $request->validate(['message'=>'required|string|max:1000']);

        if (!$this->aiService->isEnabled('passenger')) {
            return response()->json(['reply'=>'AI assistant is currently unavailable.'],503);
        }

        $session = $this->aiService->getOrCreateSession(Auth::user());
        $reply   = $this->aiService->passengerChat(Auth::user(), $request->message, $session);

        return response()->json(['reply'=>$reply,'timestamp'=>now()->format('H:i')]);
    }
}
