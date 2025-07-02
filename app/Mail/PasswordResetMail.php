<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class PasswordResetMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * The token for password reset.
     *
     * @var string
     */
    public $token;

    /**
     * Create a new message instance.
     *
     * @param string $token
     */
    public function __construct(string $token)
    {
        $this->token = $token;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        return $this->subject('Password Reset Request')
                    ->view('emails.password_reset')
                    ->with([
                        'resetUrl' => config('app.url') . '/auth/reset-password?token=' . $this->token,
                    ]);
    }
}