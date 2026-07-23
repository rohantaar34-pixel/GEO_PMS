<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class BrandingController extends Controller
{
    public function index()
    {
        $settings = SystemSetting::current();

        return view('settings.branding.index', compact('settings'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'system_name' => ['required', 'string', 'max:255'],
            'system_short_name' => ['nullable', 'string', 'max:60'],
            'system_tagline' => ['nullable', 'string', 'max:255'],
            'primary_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'logo' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,svg', 'max:2048'],
            'remove_logo' => ['nullable', Rule::in(['1'])],
        ]);

        $settings = SystemSetting::query()->firstOrCreate(
            ['settings_key' => 'default'],
            SystemSetting::defaults()
        );

        if ($request->boolean('remove_logo') && $settings->logo_path) {
            Storage::disk('public')->delete($settings->logo_path);
            $settings->logo_path = null;
        }

        if ($request->hasFile('logo')) {
            if ($settings->logo_path) {
                Storage::disk('public')->delete($settings->logo_path);
            }

            $settings->logo_path = $request->file('logo')->store('branding/logos', 'public');
        }

        $settings->fill([
            'system_name' => $validated['system_name'],
            'system_short_name' => $validated['system_short_name'] ?? '',
            'system_tagline' => $validated['system_tagline'] ?? '',
            'primary_color' => strtoupper($validated['primary_color']),
        ])->save();

        return redirect()
            ->route('settings.branding.index')
            ->with('success', 'Branding settings updated successfully.');
    }
}
