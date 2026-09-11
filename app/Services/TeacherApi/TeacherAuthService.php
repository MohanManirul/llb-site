<?php

namespace App\Services\TeacherApi;

use App\Models\Teacher;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;

final class TeacherAuthService
{
    /**
     * Registration deliberately does not sign the teacher in: accounts start
     * inactive and stay unusable until an admin approves them.
     */
    public function register(array $data): Teacher
    {
        return Teacher::create($data);
    }

    public function attemptLogin(array $credentials, bool $remember): Teacher
    {
        $attempted = Auth::guard('teacher')->attempt([
            'email' => $credentials['email'],
            'password' => $credentials['password'],
        ], $remember);

        if (! $attempted) {
            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        $teacher = Auth::guard('teacher')->user();

        if (! $teacher->is_active) {
            Auth::guard('teacher')->logout();

            throw ValidationException::withMessages([
                'email' => 'Your account is pending admin approval.',
            ]);
        }

        $teacher->forceFill(['last_login_at' => now()])->save();

        return $teacher;
    }

    public function logout(): void
    {
        Auth::guard('teacher')->logout();
    }

    public function updateProfile(Teacher $teacher, array $data): Teacher
    {
        if (empty($data['password'])) {
            unset($data['password']);
        }

        $teacher->update($data);

        return $teacher->fresh(['college']);
    }

    public function sendResetLink(string $email): void
    {
        $status = Password::broker('teachers')->sendResetLink(['email' => $email]);

        if (! in_array($status, [Password::RESET_LINK_SENT, Password::RESET_THROTTLED, Password::INVALID_USER], true)) {
            throw ValidationException::withMessages(['email' => __($status)]);
        }
    }

    public function resetPassword(array $data): void
    {
        $status = Password::broker('teachers')->reset(
            $data,
            function (Teacher $teacher, string $password) {
                $teacher->forceFill(['password' => $password])->save();
            },
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages(['email' => __($status)]);
        }
    }
}
