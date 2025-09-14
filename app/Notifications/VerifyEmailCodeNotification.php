<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class VerifyEmailCodeNotification extends Notification
{
    use Queueable;

    public $code;

    public function __construct($code)
    {
        $this->code = $code;
    }

    public function via($notifiable)
    {
        \Log::info('VerifyEmailCodeNotification::via called', [
            'user_id' => $notifiable->id,
            'email' => $notifiable->email,
        ]);
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        \Log::info('VerifyEmailCodeNotification::toMail called', [
            'user_id' => $notifiable->id,
            'email' => $notifiable->email,
            'code' => $this->code,
            'memory_usage' => memory_get_usage() / 1024 / 1024 . ' MB',
        ]);
    
        $subject = 'Verify Your Email - Trivex Trade';
    
        return (new MailMessage)
            ->subject($subject)
            ->view('vendor.mail.html.message', [
                'lines' => [
                    'Welcome to Trivex Trade!',
                    'Please use the following code to verify your email address:',
                    "{$this->code}",
                    'This code will expire in 15 minutes.',
                    'If you did not request this code, please ignore this email.',
                    'Best regards, The Trivex Trade Team',
                ],
                'subject' => $subject, // Pass the subject to the template
            ]);
    }
}