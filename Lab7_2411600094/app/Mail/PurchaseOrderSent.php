<?php

namespace App\Mail;

use App\Models\PurchaseOrder;
use App\Services\PdfService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PurchaseOrderSent extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public PurchaseOrder $purchaseOrder)
    {
        $this->afterCommit();
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: "New Purchase Order {$this->purchaseOrder->reference_number}");
    }

    public function content(): Content
    {
        $this->purchaseOrder->loadMissing(['supplier', 'items.product']);

        return new Content(view: 'procurement.emails.purchase-order-sent', with: ['po' => $this->purchaseOrder]);
    }

    public function attachments(): array
    {
        if (! PdfService::available()) {
            return [];
        }

        $po = $this->purchaseOrder->loadMissing(['supplier', 'items.product', 'createdBy']);

        return [
            Attachment::fromData(
                fn () => PdfService::output('procurement.purchase-orders.pdf', ['po' => $po]),
                "{$po->reference_number}.pdf"
            )->withMime('application/pdf'),
        ];
    }
}
