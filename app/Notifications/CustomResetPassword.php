<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;
use Filament\Facades\Filament;
class CustomResetPassword extends ResetPassword
{
    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable)
    {
        // GUNAKAN INI: Generator Link Asli Filament (Otomatis Signed & Valid)
        $url = Filament::getResetPasswordUrl($this->token, $notifiable);

        return (new MailMessage)
            ->subject('🔐 Reset Password FinPlan')
            ->greeting('Halo!')
            ->line('Kamu menerima email ini karena ada permintaan untuk mengatur ulang password akunmu.')
            ->action('Buat Password Baru', $url)
            ->line('Link ini akan kadaluarsa dalam 60 menit.')
            ->line('Jika kamu tidak merasa meminta ini, abaikan saja email ini.')
            ->salutation('Salam, Tim FinPlan');
    }
}