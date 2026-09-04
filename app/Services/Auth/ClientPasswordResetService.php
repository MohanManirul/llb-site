<?php

namespace App\Services\Auth;

use App\Models\Client;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

final class ClientPasswordResetService
{
    public const LINK_SENT_MESSAGE = 'A password reset link has been sent.';

    public const RESET_MESSAGE = 'Your password has been reset successfully.';

    public const INVALID_TOKEN_MESSAGE = 'This password reset link is invalid or has expired.';

    public const ACCOUNT_NOT_FOUND_MESSAGE = "We couldn't find an account associated with this email address.";

    private const BROKER = 'clients';

    public function sendResetLink(string $email): bool
    {
        $client = $this->eligibleClient($email);

        if ($client === null) {
            return false;
        }

        Password::broker(self::BROKER)->sendResetLink(['email' => $client->email]);

        activity()->performedOn($client)->log('Client password reset link requested.');

        return true;
    }

    /**
     * @param  array<string, mixed>  $credentials
     */
    public function reset(array $credentials): bool
    {
        $client = $this->eligibleClient((string) $credentials['email']);

        if ($client === null) {
            return false;
        }

        $status = Password::broker(self::BROKER)->reset($credentials, function (Client $client, string $password): void {
            $client->forceFill([
                'password' => $password,
                'remember_token' => Str::random(60),
            ])->save();

            event(new PasswordReset($client));
        });

        if ($status !== Password::PASSWORD_RESET) {
            return false;
        }

        activity()->performedOn($client)->log('Client password reset.');

        return true;
    }

    private function eligibleClient(string $email): ?Client
    {
        return Client::where('email', $email)
            ->where('is_active', true)
            ->first();
    }
}
