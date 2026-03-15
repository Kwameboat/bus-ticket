@extends('layouts.admin')
@section('title','Settings — '.ucfirst($group))
@section('content')
<div class="flex gap-4 mb-6 flex-wrap">
    @foreach($groups as $g)
    <a href="{{ route('admin.settings.index',$g) }}"
       class="px-4 py-2 rounded-xl text-sm font-medium transition {{ $group === $g ? 'bg-blue-600 text-white' : 'bg-white border border-gray-200 text-gray-600 hover:border-blue-300' }}">
        {{ ucfirst($g) }}
    </a>
    @endforeach
</div>

<div class="bg-white rounded-2xl border border-gray-200 p-6 max-w-2xl">
    <form method="POST" action="{{ route('admin.settings.update',$group) }}">
        @csrf
        @if($settings->isEmpty())
            <p class="text-gray-400 text-sm text-center py-8">No settings in this group yet.</p>
        @else
        <div class="space-y-4">
            @foreach($settings as $key => $setting)
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">{{ $setting->label ?? $key }}</label>
                @if($setting->description)
                    <p class="text-xs text-gray-400 mb-1">{{ $setting->description }}</p>
                @endif
                @if($setting->cast_type === 'bool' || $setting->cast_type === 'boolean')
                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="settings[{{ $key }}]" value="1" {{ $setting->value ? 'checked' : '' }}
                               class="w-4 h-4 rounded text-blue-600">
                        <span class="text-sm text-gray-600">Enabled</span>
                    </label>
                @else
                    <input type="{{ in_array($setting->cast_type,['int','float','integer']) ? 'number' : 'text' }}"
                           name="settings[{{ $key }}]" value="{{ $setting->value }}"
                           class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-blue-500">
                @endif
            </div>
            @endforeach
        </div>
        <div class="mt-6 pt-4 border-t border-gray-100">
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold px-6 py-2.5 rounded-xl text-sm">Save Settings</button>
        </div>
        @endif
    </form>
</div>
@endsection
