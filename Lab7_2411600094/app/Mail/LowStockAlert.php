<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Lab 7 Part 4 Step 2: the reorder sweep's digest.
 *
 * One mail per run, not one per SKU: the engine can find a dozen short items
 * before breakfast, and a mailbox full of single-product warnings is how
 * warnings stop being read. This replaces nothing — Lab 6's
 * LowStockNotification still raises the per-product bell alert, which is the
 * right shape for "this shelf just went empty right now".
 *
 * Rows come straight from ReorderService::shortItems() and carry the
 * suggested quantity the engine computed, so the mail and the drafts it wrote
 * can never disagree.
 */
class LowStockAlert extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * @param  array<int, array<string, mixed>>  $items  one row per short product
     * @param  int  $drafts  how many of them went onto a new draft order
     */
    public function __construct(
        public array $items,
        public int $drafts = 0,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: sprintf(
                'Low stock digest: %d item(s) at or below reorder point',
                count($this->items)
            ),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.low-stock-alert',
            with: [
                'items'        => $this->items,
                'count'        => count($this->items),
                'raised'       => $this->drafts,
                'checkedAt'    => now()->format('F j, Y, g:i a'),
                // The window the demand figures were measured over, spelled out
                // so nobody reads a 30-day average as a forecast.
                'lookbackDays' => (int) config('procurement.lookback_days'),
            ],
        );
    }
}
