<?php

namespace App\Http\Resources\PublicApi;

use App\Support\Locale;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PublicCollegeResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $name = $this->translated('name');
        $locale = Locale::resolve($request->string('locale')->toString());

        return [
            'value' => $this->id,
            'label' => $name[$locale] ?? $name['en'],
            'label_bn' => $this->name_bn,
            'label_en' => $this->name_en,
            'name' => $name,
            'slug' => $this->slug,
            'district' => $this->translated('district'),
        ];
    }
}
