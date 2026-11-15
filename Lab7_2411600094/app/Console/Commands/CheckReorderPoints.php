<?php

namespace App\Console\Commands;

use App\Mail\LowStockAlert;
use App\Models\User;
use App\Notifications\ReorderSummaryNotification;
use App\Services\ReorderService;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Lab 7 Part 3 Step 4: the daily reorder sweep.
 *
 * Run by the scheduler at config('procurement.reorder_check_time') and by hand
 * whenever somebody wants to see the arithmetic:
 *   php artisan inventory:check-reorder-points --dry-run
 *   php artisan inventory:check-reorder-points
 *
 * Drafts, never orders: what this writes still has to be approved by a person
 * (Part 2's workflow), which is why the engine stops at status = draft.
 */
class CheckReorderPoints extends Command
{
    protected $signature = 'inventory:check-reorder-points
                            {--dry-run : Report what would be raised and write nothing}';

    protected $description = 'Detect products below their reorder point and raise one draft purchase order per supplier';

    public function __construct(private readonly ReorderService $reorder)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $this->info(sprintf(
            'Reorder check — demand window %d day(s), VAT %s%s',
            (int) config('procurement.lookback_days'),
            rtrim(rtrim(number_format((float) config('procurement.tax_rate') * 100, 2), '0'), '.') . ' %',
            $dryRun ? ' — DRY RUN, nothing will be written' : ''
        ));

        try {
            $result = $this->reorder->generatePurchaseOrders($dryRun);
        } catch (Throwable $e) {
            // A failed sweep must be visible in the log, not just in whatever
            // shell happened to run it (the scheduler keeps no output on its own).
            Log::error('Reorder check failed: ' . $e->getMessage(), ['exception' => $e]);
            $this->error('The reorder check failed: ' . $e->getMessage());

            return self::FAILURE;
        }

        $this->renderOrders($result['orders'], $dryRun);
        $this->renderSkipped($result['skipped']);
        $this->notifyAdmins($result, $dryRun);

        return self::SUCCESS;
    }

    /** @param  array<int, array<string, mixed>>  $orders */
    private function renderOrders(array $orders, bool $dryRun): void
    {
        if ($orders === []) {
            $this->components->info('Every stocked item is above its reorder point — nothing to raise.');

            return;
        }

        $this->table(
            ['Supplier', 'PO reference', 'Items', 'Units', 'Subtotal', 'VAT', 'Total'],
            array_map(fn (array $order): array => [
                $order['supplier']->name,
                $dryRun ? '(would create)' : $order['reference'],
                count($order['lines']),
                number_format($order['units']),
                'PHP ' . number_format($order['subtotal'], 2),
                'PHP ' . number_format($order['tax'], 2),
                'PHP ' . number_format($order['total'], 2) . ($order['warning'] !== null ? '  !' : ''),
            ], $orders)
        );

        $this->table(
            ['SKU', 'Product', 'On hand', 'Reorder point', 'Order', 'Unit cost', 'Line total'],
            array_merge(...array_map(
                fn (array $order): array => $order['lines']
                    ->map(fn (array $line): array => [
                        $line['sku'],
                        $line['name'],
                        number_format($line['on_hand']),
                        number_format($line['point']),
                        number_format($line['quantity']),
                        'PHP ' . number_format($line['unit_price'], 2),
                        'PHP ' . number_format($line['line_total'], 2),
                    ])->all(),
                $orders
            ))
        );

        foreach ($orders as $order) {
            if ($order['warning'] !== null) {
                $this->components->warn($order['supplier']->name . ': ' . $order['warning']);
            }
        }

        $this->components->info(sprintf(
            '%s %d draft order(s) covering %d product(s).',
            $dryRun ? 'Would create' : 'Raised',
            count($orders),
            array_sum(array_map(fn (array $order) => count($order['lines']), $orders))
        ));
    }

    /** @param  array<int, array<string, string>>  $skipped */
    private function renderSkipped(array $skipped): void
    {
        if ($skipped === []) {
            return;
        }

        $this->components->warn(sprintf('%d short item(s) were passed over:', count($skipped)));

        $this->table(
            ['SKU', 'Product', 'Reason'],
            array_map(fn (array $row): array => [$row['sku'], $row['name'], $row['reason']], $skipped)
        );
    }

    /** @param  array{orders: array<int, array<string, mixed>>, products: int, skipped: array<int, array<string, string>>}  $result */
    private function notifyAdmins(array $result, bool $dryRun): void
    {
        // A rehearsal is for the person typing the command, not for the bell or
        // the mailbox.
        if ($dryRun) {
            return;
        }

        $admins = User::where('is_admin', true)->orderBy('id')->get();

        if ($result['orders'] !== []) {
            foreach ($admins as $admin) {
                $admin->notify(new ReorderSummaryNotification($result));
            }

            $this->info(sprintf('Notified %d administrator(s).', $admins->count()));
        }

        // Lab 7 Part 4 Step 2: the e-mail digest replaces the placeholder comment
        // this method used to end with. It goes out even when the sweep raised
        // nothing new — the rows then all read "covered", which is the honest
        // answer to "are we still short?"
        $this->sendDigest($admins, $result['products']);
    }

    /**
     * One queued mail carrying every short product, addressed to every
     * administrator — not one mail per SKU.
     *
     * @param  Collection<int, User>  $admins
     */
    private function sendDigest(Collection $admins, int $raised): void
    {
        $rows = $this->reorder->shortItems();

        if ($rows === []) {
            return;
        }

        $addresses = $admins->pluck('email')->filter()->unique()->values()->all();

        if ($addresses === []) {
            $this->components->warn('No administrator has an e-mail address, so the digest was not sent.');

            return;
        }

        try {
            Mail::to($addresses)->queue(new LowStockAlert($rows, $raised));

            // Under QUEUE_CONNECTION=database the command only files the job, so
            // say where the mail actually is; a sync queue has already built it.
            $this->info(sprintf(
                '%s the low-stock digest — %d item(s) for %d address(es).%s',
                config('queue.default') === 'sync' ? 'Sent' : 'Queued',
                count($rows),
                count($addresses),
                config('queue.default') === 'sync' ? '' : ' Run php artisan queue:work to send it.'
            ));
        } catch (Throwable $e) {
            // The drafts and the bell alert are already written; a mail server
            // that is offline is a log line, not a failed sweep.
            Log::error('Low-stock digest could not be queued: ' . $e->getMessage(), ['exception' => $e]);
            $this->components->warn('The low-stock digest could not be queued: ' . $e->getMessage());
        }
    }
}
