<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

class CustomResetPassword extends ResetPassword
{
    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable)
    {
        // $this->token adalah token reset password bawaan class parent
        
        // Kita harus menyusun URL manual agar sesuai dengan route web.php yang kita buat tadi
        // url: http://127.0.0.1:8000/reset-password/{token}?email={email}
        $url = url(route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ], false));

        return (new MailMessage)
            ->subject('🔐 Reset Password FinPlan') // Judul Email
            ->greeting('Halo!') // Sapaan
            ->line('Kamu menerima email ini karena ada permintaan untuk mengatur ulang password akunmu.')
            ->action('Buat Password Baru', $url) // Tombol & Link
            ->line('Link ini akan kadaluarsa dalam 60 menit.')
            ->line('Jika kamu tidak merasa meminta ini, abaikan saja email ini.')
            ->salutation('Salam, Tim FinPlan');
    }
}