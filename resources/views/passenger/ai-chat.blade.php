@extends('layouts.app')
@section('title','AI Assistant')
@section('content')
<div class="max-w-2xl mx-auto px-4 py-8 flex flex-col" style="height: calc(100vh - 160px)">
    <div class="mb-4">
        <h1 class="text-xl font-bold text-gray-900 flex items-center gap-2">🤖 GhanaBus AI Assistant</h1>
        <p class="text-sm text-gray-500 mt-1">Ask me anything about bus travel in Ghana</p>
    </div>

    <!-- Chat Window -->
    <div class="flex-1 bg-white rounded-2xl border border-gray-200 flex flex-col overflow-hidden" x-data="aiChat()" x-init="init()">
        <!-- Messages -->
        <div id="chat-messages" class="flex-1 overflow-y-auto p-5 space-y-4">
            <!-- Welcome message -->
            <div class="flex gap-3">
                <div class="w-8 h-8 bg-brand-100 rounded-full flex items-center justify-center flex-shrink-0">🤖</div>
                <div class="bg-gray-100 rounded-2xl rounded-tl-none px-4 py-3 max-w-xs">
                    <p class="text-sm text-gray-800">Hello! I'm your GhanaBus assistant. I can help you find buses, check your bookings, or answer questions about travel in Ghana. What would you like to know?</p>
                </div>
            </div>

            @foreach($messages as $msg)
            @if($msg->role === 'user')
            <div class="flex gap-3 justify-end">
                <div class="bg-brand-600 text-white rounded-2xl rounded-tr-none px-4 py-3 max-w-xs">
                    <p class="text-sm">{{ $msg->content }}</p>
                </div>
                <div class="w-8 h-8 bg-brand-600 rounded-full flex items-center justify-center flex-shrink-0 text-white text-xs font-bold">
                    {{ substr(auth()->user()->name,0,1) }}
                </div>
            </div>
            @elseif($msg->role === 'assistant')
            <div class="flex gap-3">
                <div class="w-8 h-8 bg-brand-100 rounded-full flex items-center justify-center flex-shrink-0">🤖</div>
                <div class="bg-gray-100 rounded-2xl rounded-tl-none px-4 py-3 max-w-xs lg:max-w-sm">
                    <p class="text-sm text-gray-800 whitespace-pre-line">{{ $msg->content }}</p>
                </div>
            </div>
            @endif
            @endforeach

            <!-- Typing indicator -->
            <div x-show="typing" class="flex gap-3">
                <div class="w-8 h-8 bg-brand-100 rounded-full flex items-center justify-center flex-shrink-0">🤖</div>
                <div class="bg-gray-100 rounded-2xl rounded-tl-none px-4 py-3">
                    <div class="flex gap-1">
                        <span class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay:0ms"></span>
                        <span class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay:150ms"></span>
                        <span class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay:300ms"></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Suggestion chips -->
        <div class="px-4 pb-2 flex gap-2 flex-wrap">
            @foreach(['Find bus Accra to Kumasi','Show my bookings','What\'s my wallet balance?','Refund policy'] as $chip)
            <button @click="sendMessage('{{ $chip }}')" class="text-xs bg-brand-50 hover:bg-brand-100 text-brand-600 font-medium px-3 py-1.5 rounded-full transition border border-brand-200">
                {{ $chip }}
            </button>
            @endforeach
        </div>

        <!-- Input -->
        <div class="p-4 border-t border-gray-100">
            <form @submit.prevent="sendFromInput()" class="flex gap-2">
                <input type="text" x-model="inputText" placeholder="Ask anything about bus travel..."
                       :disabled="typing"
                       class="flex-1 border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-brand-500 focus:border-transparent disabled:opacity-50">
                <button type="submit" :disabled="!inputText.trim() || typing"
                        class="bg-brand-600 hover:bg-brand-700 disabled:opacity-50 text-white font-bold px-4 py-2.5 rounded-xl transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                </button>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
function aiChat() {
    return {
        inputText: '',
        typing: false,
        csrfToken: document.querySelector('meta[name=csrf-token]').content,

        init() { this.scrollToBottom(); },

        scrollToBottom() {
            this.$nextTick(() => {
                const el = document.getElementById('chat-messages');
                if (el) el.scrollTop = el.scrollHeight;
            });
        },

        sendFromInput() { if (this.inputText.trim()) this.sendMessage(this.inputText.trim()); },

        async sendMessage(text) {
            if (!text || this.typing) return;
            this.inputText = '';
            this.typing = true;
            this.appendMessage('user', text);
            this.scrollToBottom();

            try {
                const res = await fetch('{{ route("passenger.ai.send") }}', {
                    method: 'POST',
                    headers: { 'Content-Type':'application/json','X-CSRF-TOKEN':this.csrfToken },
                    body: JSON.stringify({ message: text })
                });
                const data = await res.json();
                this.appendMessage('assistant', data.reply || 'Sorry, I could not process that.');
            } catch(e) {
                this.appendMessage('assistant', 'Network error. Please check your connection.');
            } finally {
                this.typing = false;
                this.scrollToBottom();
            }
        },

        appendMessage(role, content) {
            const container = document.getElementById('chat-messages');
            const div = document.createElement('div');
            div.className = 'flex gap-3' + (role === 'user' ? ' justify-end' : '');
            if (role === 'user') {
                div.innerHTML = `<div class="bg-brand-600 text-white rounded-2xl rounded-tr-none px-4 py-3 max-w-xs"><p class="text-sm">${this.escHtml(content)}</p></div><div class="w-8 h-8 bg-brand-600 rounded-full flex items-center justify-center flex-shrink-0 text-white text-xs font-bold">{{ substr(auth()->user()->name,0,1) }}</div>`;
            } else {
                div.innerHTML = `<div class="w-8 h-8 bg-brand-100 rounded-full flex items-center justify-center flex-shrink-0">🤖</div><div class="bg-gray-100 rounded-2xl rounded-tl-none px-4 py-3 max-w-xs lg:max-w-sm"><p class="text-sm text-gray-800 whitespace-pre-line">${this.escHtml(content)}</p></div>`;
            }
            container.appendChild(div);
        },

        escHtml(s) { return s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }
    }
}
</script>
@endpush
@endsection
