<?php

namespace App\Http\Controllers\Admin;

use App\Support\PlatformSettings;
use Illuminate\Http\Request;

class SettingController extends AdminController
{
    public function edit()
    {
        $settingGroups = PlatformSettings::definitionsWithValues();

        return $this->panelView('admin.pages.settings', [
            'pageTitle' => 'Settings',
            'pageEyebrow' => 'Admin configuration',
            'pageDescription' => 'Configure marketplace safeguards, finance behavior, and operational defaults.',
            'searchPlaceholder' => 'Search settings',
            'stats' => [
                ['label' => 'Platform commission', 'value' => appSetting('platform_commission', 20).'%', 'meta' => 'Default seller fee'],
                ['label' => 'Referral commission', 'value' => appSetting('referral_commission', 5).'%', 'meta' => appSetting('referral_duration', 'First paid order')],
                ['label' => 'Payout hold', 'value' => appSetting('payout_hold_period', 7).' days', 'meta' => 'After order approval'],
                ['label' => 'Gig approval', 'value' => appSetting('manual_gig_approval', true) ? 'Manual' : 'Auto', 'meta' => 'New and edited gigs'],
            ],
            'settingGroups' => $settingGroups,
            'settingsSidebar' => [
                'systemInfo' => [
                    ['label' => 'Admin name', 'value' => config('admin.name')],
                    ['label' => 'Admin email', 'value' => config('admin.email')],
                    ['label' => 'Password env', 'value' => 'ADMIN_PASSWORD'],
                    ['label' => 'Settings cache', 'value' => config('platform_settings.cache_key')],
                ],
                'reviewQueue' => [
                    ['label' => 'Seller documents', 'value' => \App\Models\User::where('verification_status', 'review')->count()],
                    ['label' => 'Gig edits', 'value' => \App\Models\Gig::whereNotIn('status', ['Live', 'Published'])->count()],
                    ['label' => 'Payout holds', 'value' => 'Part 3'],
                ],
                'checklist' => [
                    ['label' => 'Replace demo admin password', 'status' => config('admin.password') === 'password' ? 'Required' : 'Done'],
                    ['label' => 'Role middleware is active', 'status' => 'Done'],
                    ['label' => 'Connect settings to database', 'status' => 'Done'],
                ],
            ],
        ]);
    }

    public function update(Request $request)
    {
        $values = $request->input('settings', []);
        $errors = PlatformSettings::validateInput($values);

        if ($errors !== []) {
            return back()
                ->withErrors($errors)
                ->withInput();
        }

        PlatformSettings::setMany($values);

        return back()->withNotify('success', 'Platform settings were saved and the settings cache was refreshed.', 'Settings saved');
    }
}
