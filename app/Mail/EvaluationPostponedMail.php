<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
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
     * Build the message.
     */
    public function build()
    {
        return $this->subject('Evaluation Postponed - ' . $this->evaluation->student->name)
                    ->view('emails.evaluation_postponed')
                    ->with([
                        'evaluation' => $this->evaluation,
                        'reason' => $this->reason,
                        'postponedTo' => $this->postponedTo,
                        'student' => $this->evaluation->student,
                    ]);
    }
} 