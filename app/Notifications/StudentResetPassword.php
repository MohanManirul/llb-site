<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

class StudentResetPassword extends ResetPassword implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /**
     * @var array<int, int>
     */
    public array $backoff = [10, 60];

    protected function resetUrl($notifiable): string
    {
        return url('/bn/account/reset-password/'.$this->token.'?email='.urlencode($notifiable->getEmailForPasswordReset()));
    }
}
