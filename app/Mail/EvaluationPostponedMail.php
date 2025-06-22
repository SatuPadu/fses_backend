<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Modules\Evaluation\Models\Evaluation;

class EvaluationPostponedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * The evaluation that was postponed.
     *
     * @var Evaluation
     */
    public $evaluation;

    /**
     * The reason for postponement.
     *
     * @var string
     */
    public $reason;

    /**
     * The new date for the evaluation.
     *
     * @var string
     */
    public $postponedTo;

    /**
     * Create a new message instance.
     *
     * @param Evaluation $evaluation
     * @param string $reason
     * @param string $postponedTo
     */
    public function __construct(Evaluation $evaluation, string $reason, string $postponedTo)
    {
        $this->evaluation = $evaluation;
        $this->reason = $reason;
        $this->postponedTo = $postponedTo;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Evaluation Postponed - ' . $this->evaluation->student->name
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.evaluation_postponed',
            with: [
                'evaluation' => $this->evaluation,
                'reason' => $this->reason,
                'postponedTo' => $this->postponedTo,
                'student' => $this->evaluation->student,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     */
    public function attachments(): array
    {
        return [];
    }
} 