<?php
namespace App\Services\AI;

use App\Models\{AiChatSession, AiMessage, AiSettings, User, Trip, Route, Booking};
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class AiService
{
    private ?AiSettings $settings;

    public function __construct()
    {
        $this->settings = AiSettings::first();
    }

    public function isEnabled(string $context = 'passenger'): bool
    {
        if (!$this->settings || !$this->settings->enabled) return false;
        return match($context) {
            'passenger' => $this->settings->passenger_chat_enabled,
            'admin'     => $this->settings->admin_insights_enabled,
            default     => false,
        };
    }

    /**
     * Process a passenger chat message
     */
    public function passengerChat(User $user, string $message, AiChatSession $session): string
    {
        // Rate limiting
        $key = "ai_chat:{$user->id}";
        $limit = $this->settings->rate_limit_per_hour ?? 20;
        if (RateLimiter::tooManyAttempts($key, $limit)) {
            return "You've sent too many messages. Please wait before trying again.";
        }
        RateLimiter::hit($key, 3600);

        // Build system context with live data injection
        $systemPrompt = $this->buildPassengerSystemPrompt($user);

        // Try to resolve intent locally first (fast path)
        $localResponse = $this->tryLocalIntent($message, $user);
        if ($localResponse) {
            $this->logMessage($session, $user, 'user', $message);
            $this->logMessage($session, $user, 'assistant', $localResponse, 'local', 'rule-based', 0);
            $session->increment('total_messages', 2);
            return $localResponse;
        }

        // Build messages for AI provider
        $history  = $session->getHistory();
        $messages = array_merge(
            [['role'=>'system','content'=>$systemPrompt]],
            array_slice($history, -10), // last 10 for context
            [['role'=>'user','content'=>$message]]
        );

        // Call AI provider
        $start    = microtime(true);
        $response = $this->callProvider($messages);
        $latency  = (int)((microtime(true) - $start) * 1000);

        $this->logMessage($session, $user, 'user', $message);
        $this->logMessage($session, $user, 'assistant', $response, $this->settings->provider ?? 'mock', $this->settings->model ?? 'mock', $latency, $response === $this->getMockResponse($message) ? 0 : null);
        $session->increment('total_messages', 2);
        $session->update(['last_active_at' => now()]);

        return $response;
    }

    /**
     * Handle common intents without calling AI (faster + cheaper)
     */
    private function tryLocalIntent(string $message, User $user): ?string
    {
        $msg = strtolower($message);

        // Booking history intent
        if (str_contains($msg,'my booking') || str_contains($msg,'my ticket')) {
            $bookings = Booking::where('user_id',$user->id)->with('trip.route')->latest()->take(3)->get();
            if ($bookings->isEmpty()) return "You don't have any bookings yet. Would you like to search for a bus?";
            $list = $bookings->map(fn($b)=>"• {$b->booking_ref} — {$b->trip->route->name} on ".($b->trip->departs_at?->format('D d M Y H:i')??'N/A')." ({$b->booking_status})")->join("\n");
            return "Here are your recent bookings:\n{$list}\n\nNeed help with any of these?";
        }

        // Wallet balance intent
        if (str_contains($msg,'wallet') || str_contains($msg,'balance')) {
            $balance = $user->getOrCreateWallet()->balance;
            return "Your wallet balance is **GHS ".number_format($balance,2)."**. You can top up or use it for your next booking.";
        }

        // Refund policy intent
        if (str_contains($msg,'refund') && str_contains($msg,'policy') || str_contains($msg,'cancellation policy')) {
            $hours = setting('cancellation_window_hours', 4);
            return "Our cancellation policy: You can cancel your booking up to **{$hours} hours** before departure for a refund. Refunds are processed within 3-5 business days or instantly to your GhanaBus wallet.";
        }

        return null;
    }

    /**
     * Call the configured AI provider
     */
    private function callProvider(array $messages): string
    {
        $provider = $this->settings->provider ?? 'mock';
        $apiKeyEnv = $this->settings->api_key_env ?? 'OPENAI_API_KEY';
        $apiKey   = env($apiKeyEnv);

        if ($provider === 'mock' || !$apiKey) {
            return $this->getMockResponse($messages[count($messages)-1]['content']);
        }

        try {
            return match($provider) {
                'openai' => $this->callOpenAI($messages, $apiKey),
                'claude' => $this->callClaude($messages, $apiKey),
                default  => $this->getMockResponse($messages[count($messages)-1]['content']),
            };
        } catch (\Exception $e) {
            return "I'm having trouble connecting right now. Please try again shortly.";
        }
    }

    private function callOpenAI(array $messages, string $apiKey): string
    {
        $response = Http::withToken($apiKey)->timeout(15)
            ->post('https://api.openai.com/v1/chat/completions', [
                'model'       => $this->settings->model ?? 'gpt-4o-mini',
                'messages'    => $messages,
                'max_tokens'  => $this->settings->max_tokens ?? 600,
                'temperature' => (float)($this->settings->temperature ?? 0.7),
            ]);
        return $response->json('choices.0.message.content') ?? 'I could not generate a response.';
    }

    private function callClaude(array $messages, string $apiKey): string
    {
        $system   = collect($messages)->where('role','system')->pluck('content')->first() ?? '';
        $filtered = collect($messages)->whereIn('role',['user','assistant'])->values()->toArray();
        $response = Http::withHeaders(['x-api-key'=>$apiKey,'anthropic-version'=>'2023-06-01'])->timeout(15)
            ->post('https://api.anthropic.com/v1/messages', [
                'model'      => $this->settings->model ?? 'claude-haiku-4-5-20251001',
                'max_tokens' => $this->settings->max_tokens ?? 600,
                'system'     => $system,
                'messages'   => $filtered,
            ]);
        return $response->json('content.0.text') ?? 'I could not generate a response.';
    }

    private function getMockResponse(string $message): string
    {
        $msg = strtolower($message);
        if (str_contains($msg,'accra') && str_contains($msg,'kumasi')) return "The Accra → Kumasi route takes approximately 3h 30min and costs from GHS 80. Several operators run this route daily. Would you like me to search for available trips?";
        if (str_contains($msg,'seat')) return "I can help you pick a good seat! Front seats (A1-A3) are great for those who prefer less road vibration. Middle seats offer a balanced experience. Rear seats are best for those travelling in groups. Which do you prefer?";
        if (str_contains($msg,'help') || str_contains($msg,'hi') || str_contains($msg,'hello')) return "Hello! I'm your GhanaBus assistant. I can help you search for buses, check your bookings, explain our policies, or recommend the best seats. What would you like to do?";
        return "I understand you're asking about bus travel in Ghana. Could you be more specific? For example, tell me your origin city, destination, and travel date, and I'll find available trips for you.";
    }

    private function buildPassengerSystemPrompt(User $user): string
    {
        $base = $this->settings->passenger_system_prompt ?? "You are a helpful GhanaBus Connect assistant. You help passengers book intercity bus tickets in Ghana. Be concise, friendly, and helpful. Currency is GHS (Ghana Cedi). Popular routes: Accra→Kumasi, Accra→Takoradi, Accra→Cape Coast.";
        return $base."\nCurrent user: {$user->name}. Logged in: yes. Always protect user privacy.";
    }

    public function getOrCreateSession(User $user, string $context = 'passenger'): AiChatSession
    {
        return AiChatSession::firstOrCreate(
            ['user_id'=>$user->id,'context_type'=>$context,'is_active'=>true],
            ['session_token'=>Str::random(40),'last_active_at'=>now()]
        );
    }

    private function logMessage(AiChatSession $session, User $user, string $role, string $content, ?string $provider = null, ?string $model = null, ?int $latency = null, mixed $tokens = null): void
    {
        AiMessage::create([
            'session_id'  => $session->id,
            'user_id'     => $user->id,
            'role'        => $role,
            'content'     => $content,
            'provider'    => $provider,
            'model'       => $model,
            'tokens_used' => $tokens,
            'latency_ms'  => $latency,
        ]);
    }
}
