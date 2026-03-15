@extends('layouts.admin')
@section('title','AI Settings')
@section('content')
<div class="max-w-2xl">
    <div class="bg-blue-50 border border-blue-200 rounded-2xl p-4 mb-6 flex gap-3">
        <span class="text-2xl">🤖</span>
        <div>
            <p class="font-semibold text-blue-900 text-sm">AI Provider Configuration</p>
            <p class="text-blue-700 text-xs mt-0.5">Configure OpenAI or Claude API keys in your <code>.env</code> file, then select the provider here. Use "mock" for testing without API costs.</p>
        </div>
    </div>

    <div class="bg-white rounded-2xl border border-gray-200 p-6">
        <form method="POST" action="{{ route('admin.settings.ai.update') }}">
            @csrf
            <div class="space-y-5">
                <div class="flex items-center justify-between">
                    <label class="font-semibold text-gray-900">Enable AI Features</label>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="enabled" value="1" class="sr-only peer" {{ $aiSettings->enabled ? 'checked' : '' }}>
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:bg-blue-600 after:content-[''] after:absolute after:top-0.5 after:left-0.5 after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all"></div>
                    </label>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="flex items-center justify-between col-span-2 sm:col-span-1">
                        <label class="text-sm text-gray-700">Passenger Chat</label>
                        <input type="checkbox" name="passenger_chat_enabled" value="1" class="w-4 h-4 rounded text-blue-600" {{ $aiSettings->passenger_chat_enabled ? 'checked' : '' }}>
                    </div>
                    <div class="flex items-center justify-between col-span-2 sm:col-span-1">
                        <label class="text-sm text-gray-700">Admin Insights</label>
                        <input type="checkbox" name="admin_insights_enabled" value="1" class="w-4 h-4 rounded text-blue-600" {{ $aiSettings->admin_insights_enabled ? 'checked' : '' }}>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">AI Provider</label>
                    <select name="provider" class="w-full border border-gray-300 rounded-xl px-3 py-2.5 text-sm focus:ring-2 focus:ring-blue-500">
                        <option value="mock" {{ ($aiSettings->provider ?? 'mock') === 'mock' ? 'selected' : '' }}>Mock (No API key needed)</option>
                        <option value="openai" {{ ($aiSettings->provider ?? '') === 'openai' ? 'selected' : '' }}>OpenAI</option>
                        <option value="claude" {{ ($aiSettings->provider ?? '') === 'claude' ? 'selected' : '' }}>Anthropic Claude</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Model</label>
                    <input type="text" name="model" value="{{ $aiSettings->model ?? 'gpt-4o-mini' }}" placeholder="e.g. gpt-4o-mini"
                           class="w-full border border-gray-300 rounded-xl px-3 py-2.5 text-sm focus:ring-2 focus:ring-blue-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">API Key Environment Variable</label>
                    <input type="text" name="api_key_env" value="{{ $aiSettings->api_key_env ?? 'OPENAI_API_KEY' }}" placeholder="OPENAI_API_KEY"
                           class="w-full border border-gray-300 rounded-xl px-3 py-2.5 text-sm font-mono focus:ring-2 focus:ring-blue-500">
                    <p class="text-xs text-gray-400 mt-1">Name of the .env variable containing your API key (not the key itself)</p>
                </div>

                <div class="grid grid-cols-3 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Max Tokens</label>
                        <input type="number" name="max_tokens" value="{{ $aiSettings->max_tokens ?? 800 }}" min="100" max="4000"
                               class="w-full border border-gray-300 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Temperature</label>
                        <input type="number" name="temperature" value="{{ $aiSettings->temperature ?? 0.7 }}" min="0" max="2" step="0.1"
                               class="w-full border border-gray-300 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Rate Limit/Hour</label>
                        <input type="number" name="rate_limit_per_hour" value="{{ $aiSettings->rate_limit_per_hour ?? 20 }}" min="1" max="100"
                               class="w-full border border-gray-300 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Passenger System Prompt</label>
                    <textarea name="passenger_system_prompt" rows="4"
                              class="w-full border border-gray-300 rounded-xl px-3 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 resize-none font-mono text-xs">{{ $aiSettings->passenger_system_prompt }}</textarea>
                </div>

                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold px-6 py-2.5 rounded-xl text-sm">Save AI Settings</button>
            </div>
        </form>
    </div>
</div>
@endsection
