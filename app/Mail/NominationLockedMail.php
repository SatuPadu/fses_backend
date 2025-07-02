<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Modules\Evaluation\Models\Evaluation;

class NominationLockedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * The evaluation that was locked.
     *
     * @var Evaluation
     */
    public $evaluation;

    /**
     * Create a new message instance.
     *
     * @param Evaluation $evaluation
     */
    public function __construct(Evaluation $evaluation)
    {
        $this->evaluation = $evaluation;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        return $this->subject('Nomination Locked - ' . $this->evaluation->student->name)
                    ->view('emails.nomination_locked')
                    ->with([
                        'evaluation' => $this->evaluation,
                        'student' => $this->evaluation->student,
                    ]);
    }
} 