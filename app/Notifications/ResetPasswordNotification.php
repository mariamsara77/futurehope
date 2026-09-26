<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ResetPasswordNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly string $token)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $frontendUrl = rtrim((string) env('FRONTEND_URL', config('app.url')), '/');
        $resetUrl = $frontendUrl . '/reset-password?token=' . urlencode($this->token) . '&email=' . urlencode($notifiable->getEmailForPasswordReset());

        return (new MailMessage)
            ->subject('Future Hope — পাসওয়ার্ড রিসেট করুন')
            ->greeting('আসসালামু আলাইকুম,')
            ->line('আপনার Future Hope অ্যাকাউন্টের পাসওয়ার্ড রিসেট করার অনুরোধ পাওয়া গেছে।')
            ->action('পাসওয়ার্ড রিসেট করুন', $resetUrl)
            ->line('এই লিংকটি সীমিত সময়ের জন্য কার্যকর থাকবে। আপনি এই অনুরোধ না করে থাকলে কোনো পদক্ষেপ নেওয়ার প্রয়োজন নেই।')
            ->salutation('Future Hope টিম');
    }
}
