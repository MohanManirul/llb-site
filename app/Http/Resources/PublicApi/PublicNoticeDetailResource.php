<?php

namespace App\Http\Resources\PublicApi;

use Illuminate\Http\Request;

class PublicNoticeDetailResource extends PublicNoticeResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            ...parent::toArray($request),
            'body' => $this->translated('body'),
            'level' => $this->whenLoaded('level', fn () => $this->level
                ? ['slug' => $this->level->slug, 'name' => $this->level->translated('name')]
                : null),
        ];
    }
}
