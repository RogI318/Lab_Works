<?php

namespace App\Mail;

use App\Models\AcademicIntervention;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AcademicInterventionApproved extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public AcademicIntervention $intervention
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Academic Intervention Approved'
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.academic-intervention-approved'
        );
    }
}