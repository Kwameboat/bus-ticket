<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function index(Request $request, $group = 'general')
    {
        return view('admin.settings.index', compact('group'));
    }

    public function update(Request $request, $group)
    {
        return back()->with('success', 'Settings updated.');
    }

    public function pwa()
    {
        return view('admin.settings.index', ['group' => 'pwa']);
    }

    public function updatePwa(Request $request)
    {
        return back()->with('success', 'PWA settings updated.');
    }

    public function aiSettings()
    {
        return view('admin.settings.ai');
    }

    public function updateAiSettings(Request $request)
    {
        return back()->with('success', 'AI settings updated.');
    }
}
