<?php

namespace App\Mail;

use App\Models\Supplier;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class SupplierUpdated extends Mailable
{
    public function __construct(public Supplier $supplier)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: "Supplier details updated: {$this->supplier->name}");
    }

    public function content(): Content
    {
        return new Content(
            view: 'procurement.emails.supplier-updated',
            with: ['supplier' => $this->supplier],
        );
    }
}
