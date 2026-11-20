<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Notifications\ReorderPurchaseOrdersNotification;
use App\Services\ReorderService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;
use Throwable;

class CheckReorderPoints extends Command
{
    protected $signature = 'inventory:check-reorder';

    protected $description = 'Create draft purchase orders for products at or below their reorder point';

    public function handle(ReorderService $service): int
    {
        try {
            $result = $service->generatePurchaseOrders();
        } catch (Throwable $exception) {
            $this->error('Reorder check failed: ' . $exception->getMessage());

            return self::FAILURE;
        }

        $this->info("Products at or below their reorder point: {$result['needing']}");

        if ($result['orders']->isEmpty()) {
            $this->info('No new purchase orders were needed.');
        } else {
            $this->table(
                ['Reference', 'Supplier', 'Items', 'Total (PHP)'],
                $result['orders']->map(fn ($order) => [
                    $order->reference_number,
                    $order->supplier->name,
                    $order->items->count(),
                    number_format((float) $order->total, 2),
                ])->all()
            );

            try {
                Notification::send(
                    User::admins()->get(),
                    new ReorderPurchaseOrdersNotification($result['orders']->pluck('reference_number')->all())
                );
            } catch (Throwable $exception) {
                report($exception);
                $this->warn('The purchase orders were created, but the notification could not be sent.');
            }
        }

        if ($result['skipped']->isNotEmpty()) {
            $this->table(
                ['SKU', 'Product', 'Reason'],
                $result['skipped']->map(fn ($row) => [$row['sku'], $row['name'], $row['reason']])->all()
            );
        }

        return self::SUCCESS;
    }
}
