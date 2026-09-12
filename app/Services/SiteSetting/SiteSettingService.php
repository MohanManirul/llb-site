<?php

namespace App\Services\SiteSetting;

use App\Models\SiteSetting;

final class SiteSettingService
{
    public function show(): SiteSetting
    {
        return SiteSetting::current();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(array $data): SiteSetting
    {
        $setting = SiteSetting::query()->find(SiteSetting::ROW_ID) ?? new SiteSetting(['id' => SiteSetting::ROW_ID]);

        $setting->forceFill(['id' => SiteSetting::ROW_ID])->fill($data)->save();

        SiteSetting::forgetCache();

        return $setting->refresh();
    }

    /**
     * The shape the header, the footer and the blade shell read. Falls back to
     * nothing rather than to a hard-coded brand, so an empty field is visible
     * in the UI instead of silently showing an old value.
     *
     * @return array<string, mixed>
     */
    public function shared(): array
    {
        $setting = SiteSetting::current();

        return [
            'name' => $setting->translated('name'),
            'slogan' => $setting->translated('slogan'),
            'logo_url' => $setting->logo_url,
            'favicon_url' => $setting->favicon_url,
            'whatsapp_url' => $setting->whatsapp_url,
            'facebook_url' => $setting->facebook_url,
            'email' => $setting->email,
            'phone' => $setting->phone,
        ];
    }
}
