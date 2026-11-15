<?php

namespace App\Observers;

use App\Mail\PurchaseOrderApproved;
use App\Mail\PurchaseOrderCreated;
use App\Mail\PurchaseOrderSent;
use App\Models\PurchaseOrder;
use App\Models\User;
use App\Services\ProcurementMetricsService;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Lab 7 Part 4 Step 4: the one place a status change becomes an audit entry,
 * a piece of paper and a mail.
 *
 * Same shape as the Lab 6 InventoryTransactionObserver: the model event is the
 * fact, so the fact is recorded where the fact is detected. A controller that
 * mails "your order was approved" itself can be bypassed by the reorder engine,
 * a queued job or next term's API route — an observer cannot.
 *
 * Three rules hold this together:
 *   1. `wasChanged('status')` gates everything, so saving a note on an approved
 *      order sends no mail and approving twice in a row mails once.
 *   2. Mail is queued and every send is wrapped: a mail server that is down,
 *      empty or unreachable must never turn into a failed approval.
 *   3. Recipients are read from the roles of the moment, never stored on the
 *      order — a manager who joins tomorrow must start receiving submissions.
 */
class PurchaseOrderObserver
{
    /** draft -> pending -> approved -> sent ... */
    public function updated(PurchaseOrder $order): void
    {
        if (! $order->wasChanged('status')) {
            return;   // an edit is not a transition
        }

        $this->record($order);
        $this->mail($order);

        // Parts 5 and 6 cache the open-order widget and the supplier scorecard;
        // neither may keep showing the numbers from before this move.
        $this->forgetCachedReports();
    }

    /**
     * A soft-deleted draft stops being a commitment, so the history keeps the
     * reason it disappeared and the cached counts have to notice too.
     */
    public function deleted(PurchaseOrder $order): void
    {
        $this->guard(
            fn () => $order->logActivity('deleted', sprintf(
                'Draft %s was deleted by %s.',
                $order->reference_number,
                $this->actor($order)
            ), $order->activityContext())
        );

        $this->forgetCachedReports();
    }

    /* ==================================================================
       The audit trail
       ================================================================== */

    /**
     * Wording is unchanged from Part 2's controller, because the show page
     * renders these rows as the order's approval history and old orders already
     * carry them.
     */
    private function record(PurchaseOrder $order): void
    {
        $status = $order->status;
        $ref    = $order->reference_number;
        $actor  = $this->actor($order);
        $extra  = $order->activityContext();

        if ($status === PurchaseOrder::STATUS_PENDING) {
            $order->logActivity('submitted', sprintf('%s was submitted for approval.', $ref), $extra);

            return;
        }

        if ($status === PurchaseOrder::STATUS_APPROVED) {
            $order->logActivity('approved', sprintf(
                '%s (PHP %s) was approved by %s.',
                $ref,
                number_format((float) $order->total, 2),
                $order->approvedBy?->name ?? $actor
            ), $extra + ['total' => (float) $order->total]);

            return;
        }

        if ($status === PurchaseOrder::STATUS_REJECTED) {
            $order->logActivity('rejected', sprintf(
                '%s was rejected by %s: %s',
                $ref,
                $order->rejectedBy?->name ?? $actor,
                $order->rejection_reason
            ), $extra + ['reason' => (string) $order->rejection_reason]);

            return;
        }

        if ($status === PurchaseOrder::STATUS_SENT) {
            $order->logActivity('sent', sprintf(
                '%s was sent to %s (%s).',
                $ref,
                $order->supplier->name,
                $order->supplier->email ?: 'no e-mail on file'
            ), $extra);

            return;
        }

        if ($status === PurchaseOrder::STATUS_CANCELLED) {
            $order->logActivity('cancelled', sprintf(
                '%s was cancelled by %s: %s',
                $ref,
                $actor,
                $extra['reason'] ?? 'no reason recorded'
            ), $extra);

            return;
        }

        if (in_array($status, [PurchaseOrder::STATUS_RECEIVED, PurchaseOrder::STATUS_PARTIALLY_RECEIVED], true)) {
            // Receive is the one transition with detail a status column cannot
            // express — which lines landed and what is still owed — so the
            // controller hands it over as context instead of writing the row.
            $order->logActivity('received', sprintf(
                '%d line(s) received against %s: %s. %s',
                count($extra['lines'] ?? []),
                $ref,
                implode(', ', $extra['lines'] ?? []),
                ($extra['outstanding'] ?? 0) === 0
                    ? 'The order is complete.'
                    : number_format((int) ($extra['outstanding'] ?? 0)) . ' unit(s) still outstanding.'
            ), $extra);

            return;
        }

        // Any move this code does not name still belongs in the history.
        $order->logActivity('status_changed', sprintf(
            '%s moved to %s by %s.',
            $ref,
            $order->statusLabel(),
            $actor
        ), $extra);
    }

    /* ==================================================================
       The mail
       ================================================================== */

    private function mail(PurchaseOrder $order): void
    {
        match ($order->status) {
            // Managers and administrators: the order now asks for their sign-off.
            PurchaseOrder::STATUS_PENDING => $this->queueMail(
                $this->approvers(),
                new PurchaseOrderCreated($order)
            ),

            // The buyer, who can finally tell the vendor.
            PurchaseOrder::STATUS_APPROVED => $this->queueMail(
                $this->creatorOf($order),
                new PurchaseOrderApproved($order)
            ),

            // The vendor gets the document; the buyer keeps a copy.
            PurchaseOrder::STATUS_SENT => $this->queueSentMail($order),

            default => null,   // rejected / cancelled / received mail is not part of Part 4's four mailables
        };
    }

    /**
     * Supplier in "To", creator in "Cc" — and if the vendor has no address on
     * file, the copy becomes the whole message rather than the mail vanishing.
     */
    private function queueSentMail(PurchaseOrder $order): void
    {
        $supplier = $order->supplier?->email;
        $creator  = $this->creatorOf($order);

        if (blank($supplier)) {
            $this->queueMail($creator, new PurchaseOrderSent($order));

            Log::info(sprintf(
                'Purchase order %s was sent without a vendor mail: %s has no e-mail address on file.',
                $order->reference_number,
                $order->supplier?->name ?? 'the supplier'
            ));

            return;
        }

        $this->guard(function () use ($order, $supplier, $creator): void {
            $pending = Mail::to($supplier);

            // A creator who is also the vendor's contact person should not
            // read the same order twice.
            $cc = collect($creator)
                ->reject(fn (string $address): bool => strcasecmp($address, (string) $supplier) === 0)
                ->values()
                ->all();

            if ($cc !== []) {
                $pending = $pending->cc($cc);
            }

            $pending->queue(new PurchaseOrderSent($order));
        }, $order);
    }

    /**
     * @param  array<int, string>  $addresses
     */
    private function queueMail(array $addresses, Mailable $mailable): void
    {
        if ($addresses === []) {
            return;
        }

        $this->guard(fn () => Mail::to($addresses)->queue($mailable));
    }

    /**
     * Nobody's day should end because a mail server hiccuped: the transition is
     * already committed and correct, the notification is a copy of it.
     */
    private function guard(callable $send, ?PurchaseOrder $order = null): void
    {
        try {
            $send();
        } catch (Throwable $e) {
            Log::error(sprintf(
                'Purchase order mail could not be queued%s: %s',
                $order ? ' for ' . $order->reference_number : '',
                $e->getMessage()
            ), ['exception' => $e]);
        }
    }

    /* ==================================================================
       Recipients
       ================================================================== */

    /** @return array<int, string> */
    private function approvers(): array
    {
        return User::query()
            // Everyone who holds the sign-off: the "may not approve your own"
            // rule belongs to PurchaseOrderPolicy, not to the address list, so
            // a manager who submitted still hears about it and just cannot be
            // the one who closes it. Warehouse staff and clerks are left out.
            ->withRoles([User::ROLE_ADMIN, User::ROLE_MANAGER])
            ->whereNotNull('email')
            ->pluck('email')
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /** @return array<int, string> */
    private function creatorOf(PurchaseOrder $order): array
    {
        return collect([$order->createdBy?->email])
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function actor(PurchaseOrder $order): string
    {
        return auth()->user()?->name
            ?? $order->createdBy?->name
            ?? 'the system';
    }

    private function forgetCachedReports(): void
    {
        // Part 5: one list of keys in config/procurement.php, one flush call —
        // a widget added in Part 6 is invalidated without touching this class.
        ProcurementMetricsService::flushWidgets();
    }
}
