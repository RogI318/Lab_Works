<?php

namespace App\Mail;

use App\Models\PurchaseOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Lab 7 Part 4: the buyer hears that their own order was signed off.
 *
 * The creator alone — the approval already went to the creator's own inbox as
 * part of nothing else, and the managers saw it as a pending-order mail. No PDF
 * here: this is a heads-up to go and send the order, and the document is what
 * PurchaseOrderSent hands to the vendor.
 */
class PurchaseOrderApproved extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public PurchaseOrder $order)
    {
        $this->order->loadMissing(['supplier', 'items.product', 'createdBy', 'approver']);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: sprintf('Purchase order %s approved', $this->order->reference_number),
        );
    }

    public function content(): Content
    {
        $this->order->loadMissing(['supplier', 'items.product', 'createdBy', 'approver']);

        return new Content(
            view: 'emails.purchase-order-approved',
            with: [
                'order'         => $this->order,
                // Whose signature it is matters to the person waiting to buy.
                'approverName'  => $this->order->approver?->name ?? 'a manager',
            ],
        );
    }
}
