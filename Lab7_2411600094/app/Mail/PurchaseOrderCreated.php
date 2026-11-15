<?php

namespace App\Mail;

use App\Models\PurchaseOrder;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Lab 7 Part 4: the order has just become pending, so the people who can approve
 * it get the paper — a PDF of the real document, not a screenshot of a form.
 *
 * Queued on purpose (Prompt 0: QUEUE_CONNECTION=database): an SMTP handshake to
 * a Philippine vendor takes seconds, and a manager clicking "Submit" must not
 * watch a spinner for that. Nothing here may be slow enough to notice.
 */
class PurchaseOrderCreated extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * The relations every template walks: the supplier block, one row per line
     * with its product, and the buyer's name in "raised by".
     */
    public function __construct(public PurchaseOrder $order)
    {
        $this->order->loadMissing(['supplier', 'items.product', 'createdBy']);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: sprintf('New purchase order %s awaiting approval', $this->order->reference_number),
        );
    }

    public function content(): Content
    {
        // The queued job keeps the model's key, not its loaded relations, so
        // this is the second load — cheap, and it means the PDF can never be
        // built from a stale snapshot taken when the button was clicked.
        $this->order->loadMissing(['supplier', 'items.product', 'createdBy']);

        return new Content(
            view: 'emails.purchase-order-created',
            with: ['order' => $this->order],
        );
    }

    /**
     * Generated when the message is actually built, which is inside the worker:
     * dompdf rendering of a 30-line order is the slow part of this mail and it
     * must never run in the request that changed the status.
     */
    protected function attachments(): array
    {
        return [
            Attachment::fromData(
                fn (): string => Pdf::loadView('pdf.purchase-order', ['order' => $this->order])
                    // Font subsetting is off in the package default, which embeds
                    // all of DejaVu Sans in every document: 859 KB for the two-line
                    // test order against 27 KB with only the printed glyphs inside.
                    ->setOptions(['enable_font_subsetting' => true], true)
                    ->setPaper('a4')
                    ->output(),
                sprintf('%s.pdf', $this->order->reference_number)
            )->withMime('application/pdf'),
        ];
    }
}
