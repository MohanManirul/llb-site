<?php

namespace App\Services\Auth;

use App\Models\Client;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

final class ClientAuthService
{
    /**
     * @param  array<string, mixed>  $credentials
     */
    public function attemptSession(array $credentials, bool $remember = false): Client
    {
        if (! Auth::guard('client-web')->attempt($credentials, $remember)) {
            activity()->log("Failed client sign in attempt for {$credentials['email']}.");

            throw ValidationException::withMessages([
                'email' => 'The provided credentials are incorrect.',
            ]);
        }

        $client = Auth::guard('client-web')->user();

        if (! $client->is_active) {
            Auth::guard('client-web')->logout();

            throw ValidationException::withMessages([
                'email' => 'This account is inactive.',
            ]);
        }

        return $client;
    }

    public function logoutSession(): void
    {
        Auth::guard('client-web')->logout();
    }

    /**
     * @param  array<string, mixed>  $credentials
     * @return array{client: Client, token: string}
     */
    public function login(array $credentials): array
    {
        $client = Client::where('email', $credentials['email'])->first();

        if (! $client || $client->password === null || ! Hash::check($credentials['password'], $client->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        if (! $client->is_active) {
            throw ValidationException::withMessages([
                'email' => ['This account is inactive.'],
            ]);
        }

        return [
            'client' => $client,
            'token' => $client->createToken('client-api')->plainTextToken,
        ];
    }
}
