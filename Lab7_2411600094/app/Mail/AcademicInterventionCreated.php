
<?php

namespace App\Mail;

use App\Models\AcademicIntervention;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AcademicInterventionCreated extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public AcademicIntervention $intervention
    ) {}

    public function envelope(): \Illuminate\Mail\Mailables\Envelope
    {
        return new \Illuminate\Mail\Mailables\Envelope(
            subject: 'New Academic Intervention Created'
        );
    }

    public function content(): \Illuminate\Mail\Mailables\Content
    {
        return new \Illuminate\Mail\Mailables\Content(
            view: 'emails.academic-intervention-created'
        );
    }
}
