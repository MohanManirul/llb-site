<?php

namespace App\Services\StudentApi;

use App\Models\Student;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;

final class StudentAuthService
{
    public function register(array $data): Student
    {
        $student = Student::create($data);

        Auth::guard('student')->login($student);

        $student->forceFill(['last_login_at' => now()])->save();

        return $student;
    }

    public function attemptLogin(array $credentials, bool $remember): Student
    {
        $attempted = Auth::guard('student')->attempt([
            'email' => $credentials['email'],
            'password' => $credentials['password'],
        ], $remember);

        if (! $attempted) {
            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        $student = Auth::guard('student')->user();

        if (! $student->is_active) {
            Auth::guard('student')->logout();

            throw ValidationException::withMessages([
                'email' => 'This account has been deactivated.',
            ]);
        }

        $student->forceFill(['last_login_at' => now()])->save();

        return $student;
    }

    public function logout(): void
    {
        Auth::guard('student')->logout();
    }

    public function updateProfile(Student $student, array $data): Student
    {
        if (empty($data['password'])) {
            unset($data['password']);
        }

        $student->update($data);

        return $student->fresh(['program']);
    }

    public function sendResetLink(string $email): void
    {
        $status = Password::broker('students')->sendResetLink(['email' => $email]);

        if (! in_array($status, [Password::RESET_LINK_SENT, Password::RESET_THROTTLED, Password::INVALID_USER], true)) {
            throw ValidationException::withMessages(['email' => __($status)]);
        }
    }

    public function resetPassword(array $data): void
    {
        $status = Password::broker('students')->reset(
            $data,
            function (Student $student, string $password) {
                $student->forceFill(['password' => $password])->save();
            },
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages(['email' => __($status)]);
        }
    }
}
