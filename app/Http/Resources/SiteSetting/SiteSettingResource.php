<?php

namespace App\Http\Resources\SiteSetting;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SiteSettingResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'name_bn' => $this->name_bn,
            'name_en' => $this->name_en,
            'slogan_bn' => $this->slogan_bn,
            'slogan_en' => $this->slogan_en,
            'logo_url' => $this->logo_url,
            'favicon_url' => $this->favicon_url,
            'whatsapp_url' => $this->whatsapp_url,
            'facebook_url' => $this->facebook_url,
            'email' => $this->email,
            'phone' => $this->phone,
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
