<?php

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

final class PasswordResetService
{
    public const LINK_SENT_MESSAGE = 'A password reset link has been sent.';

    public const RESET_MESSAGE = 'Your password has been reset successfully.';

    public const INVALID_TOKEN_MESSAGE = 'This password reset link is invalid or has expired.';

    public const ACCOUNT_NOT_FOUND_MESSAGE = "We couldn't find an account associated with this email address.";

    private const ROLE = 'super-admin';

    public function sendResetLink(string $email): bool
    {
        $user = $this->superAdminFor($email);

        if ($user === null) {
            return false;
        }

        Password::sendResetLink(['email' => $user->email]);

        activity()->log("Password reset link requested for {$email}.");

        return true;
    }

    /**
     * @param  array<string, mixed>  $credentials
     */
    public function reset(array $credentials): bool
    {
        if ($this->superAdminFor((string) $credentials['email']) === null) {
            return false;
        }

        $status = Password::reset($credentials, function (User $user, string $password): void {
            $user->forceFill([
                'password' => $password,
                'remember_token' => Str::random(60),
            ])->save();

            event(new PasswordReset($user));
        });

        if ($status !== Password::PASSWORD_RESET) {
            return false;
        }

        activity()->log("Password reset for {$credentials['email']}.");

        return true;
    }

    private function superAdminFor(string $email): ?User
    {
        $user = User::where('email', $email)->first();

        return $user?->hasRole(self::ROLE) === true ? $user : null;
    }
}
