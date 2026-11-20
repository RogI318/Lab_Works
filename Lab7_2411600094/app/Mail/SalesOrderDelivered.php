<?php

namespace App\Mail;

use App\Models\SalesOrder;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class SalesOrderDelivered extends Mailable
{
    public function __construct(public SalesOrder $order)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: "Your order {$this->order->reference_number} was delivered");
    }

    public function content(): Content
    {
        $this->order->loadMissing(['items.product', 'createdBy']);

        return new Content(view: 'procurement.emails.sales-order-delivered', with: ['order' => $this->order]);
    }
}
