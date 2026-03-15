<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{Setting, Booking, Payment, Trip, User, Operator};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class SettingsController extends Controller
{
    public function index(string $group = 'general')
    {
        $settings = Setting::where('group', $group)->orderBy('key')->get()->keyBy('key');
        $groups   = ['general','payment','booking','pwa','ai','notification','operator'];
        return view('admin.settings.index', compact('settings','group','groups'));
    }

    public function update(Request $request, string $group)
    {
        foreach ($request->settings ?? [] as $key => $value) {
            Setting::set($key, is_array($value) ? json_encode($value) : (string)$value);
        }
        Cache::flush(); // Clear all setting caches
        return back()->with('success', 'Settings saved.');
    }

    public function pwa()
    {
        $settings = Setting::where('group','pwa')->get()->keyBy('key');
        return view('admin.settings.pwa', compact('settings'));
    }

    public function updatePwa(Request $request)
    {
        $request->validate([
            'pwa_app_name'    => 'required|string|max:50',
            'pwa_short_name'  => 'required|string|max:12',
            'pwa_theme_color' => 'required|string|max:7',
            'pwa_bg_color'    => 'required|string|max:7',
        ]);
        foreach ($request->only(['pwa_app_name','pwa_short_name','pwa_theme_color','pwa_bg_color','pwa_description','pwa_install_delay','pwa_re_show_days']) as $k=>$v) {
            Setting::set($k, $v);
        }
        // Regenerate manifest
        $this->regenerateManifest($request);
        return back()->with('success','PWA settings updated. Manifest regenerated.');
    }

    private function regenerateManifest(Request $request): void
    {
        $manifest = [
            'name'             => $request->pwa_app_name,
            'short_name'       => $request->pwa_short_name,
            'description'      => $request->pwa_description ?? 'Book bus tickets in Ghana',
            'start_url'        => '/?source=pwa',
            'display'          => 'standalone',
            'theme_color'      => $request->pwa_theme_color,
            'background_color' => $request->pwa_bg_color,
            'scope'            => '/',
            'icons'            => [
                ['src'=>'/pwa-icons/icon-192.png','sizes'=>'192x192','type'=>'image/png','purpose'=>'any'],
                ['src'=>'/pwa-icons/icon-512.png','sizes'=>'512x512','type'=>'image/png','purpose'=>'any'],
                ['src'=>'/pwa-icons/maskable-512.png','sizes'=>'512x512','type'=>'image/png','purpose'=>'maskable'],
            ],
        ];
        file_put_contents(public_path('manifest.json'), json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    public function aiSettings()
    {
        $aiSettings = \App\Models\AiSettings::first() ?? new \App\Models\AiSettings();
        return view('admin.settings.ai', compact('aiSettings'));
    }

    public function updateAiSettings(Request $request)
    {
        $data = $request->validate([
            'provider'                  => 'required|in:openai,claude,mock',
            'model'                     => 'required|string|max:100',
            'api_key_env'               => 'required|string|max:100',
            'max_tokens'                => 'required|integer|min:100|max:4000',
            'temperature'               => 'required|numeric|min:0|max:2',
            'rate_limit_per_hour'       => 'required|integer|min:1|max:100',
            'passenger_system_prompt'   => 'nullable|string|max:3000',
            'admin_system_prompt'       => 'nullable|string|max:3000',
        ]);
        $data['enabled']                    = $request->boolean('enabled');
        $data['passenger_chat_enabled']     = $request->boolean('passenger_chat_enabled');
        $data['admin_insights_enabled']     = $request->boolean('admin_insights_enabled');
        $data['seat_recommendations_enabled'] = $request->boolean('seat_recommendations_enabled');
        \App\Models\AiSettings::updateOrCreate([], $data);
        return back()->with('success','AI settings updated.');
    }
}
