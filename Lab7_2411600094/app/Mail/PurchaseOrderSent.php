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
 * Lab 7 Part 4: the vendor's copy of the order, with the document attached.
 *
 * This is the mail that makes the workflow mean something — until it is sent,
 * an approved order is only an internal opinion. Addressing (supplier in "To",
 * creator in "Cc") is done by PurchaseOrderObserver so that one place decides
 * who hears about a status change; this class only knows what the order says.
 */
class PurchaseOrderSent extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public PurchaseOrder $order)
    {
        $this->order->loadMissing(['supplier', 'items.product', 'createdBy']);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: sprintf('Purchase order %s from %s', $this->order->reference_number, config('app.name')),
        );
    }

    public function content(): Content
    {
        $this->order->loadMissing(['supplier', 'items.product', 'createdBy']);

        return new Content(
            view: 'emails.purchase-order-sent',
            with: ['order' => $this->order],
        );
    }

    /** The same PDF view Part 8 will export from, so the two can never differ. */
    protected function attachments(): array
    {
        return [
            Attachment::fromData(
                fn (): string => Pdf::loadView('pdf.purchase-order', ['order' => $this->order])
                    // Same document the buyer uploads for themselves in Part 8; the
                    // option keeps the vendor's copy 27 KB instead of 859 KB (see
                    // PurchaseOrderCreated for the reason).
                    ->setOptions(['enable_font_subsetting' => true], true)
                    ->setPaper('a4')
                    ->output(),
                sprintf('%s.pdf', $this->order->reference_number)
            )->withMime('application/pdf'),
        ];
    }
}
