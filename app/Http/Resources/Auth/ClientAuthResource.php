<?php

namespace App\Http\Resources\Auth;

use App\Http\Resources\Client\ClientResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClientAuthResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'client' => new ClientResource($this->resource['client']),
            'token' => $this->resource['token'],
            'token_type' => 'Bearer',
        ];
    }
}
