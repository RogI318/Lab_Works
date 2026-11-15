<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

/**
 * Lab 7 Part 3 Step 4: the reorder sweep's report card for the admins.
 *
 * Database channel only, and deliberately not queued: the bell has to show the
 * result the moment the sweep finishes, and a queued database notification sits
 * in the jobs table until `queue:work` runs, which would make the bell look
 * broken on a machine that has no worker started. The e-mail digest belongs to
 * Part 4, where the mailable and its SMTP settings are built together.
 */
class ReorderSummaryNotification extends Notification
{
    /**
     * @param  array{orders: array<int, array<string, mixed>>, products: int, skipped: array<int, array<string, string>>}  $result
     */
    public function __construct(public array $result)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $references   = array_column($this->result['orders'], 'reference');
        $skippedCount = count($this->result['skipped']);

        return [
            'subject' => sprintf(
                'Reorder check: %d draft purchase order(s) raised',
                count($references)
            ),
            'message' => sprintf(
                '%s across %d product(s). %s. Review them under Purchase Orders.',
                $references === [] ? 'No new order was needed' : implode(', ', $references),
                (int) $this->result['products'],
                $skippedCount > 0
                    ? sprintf('%d short item(s) were passed over', $skippedCount)
                    : 'Nothing was passed over'
            ),
            // No 'quantity' key on purpose: the bell only turns red for an
            // out-of-stock alert, and a summary of paper orders is not one.
            'references'    => $references,
            'order_count'   => count($references),
            'product_count' => (int) $this->result['products'],
            'skipped_count' => $skippedCount,
            // The screen to open, stored as a named route rather than a built
            // URL: this payload is written by the scheduler, outside any
            // request, so route() there resolves against APP_URL instead of
            // the address the buyer is actually browsing.
            'route'        => 'purchase-orders.index',
            'route_params' => ['status' => 'draft'],
        ];
    }
}
