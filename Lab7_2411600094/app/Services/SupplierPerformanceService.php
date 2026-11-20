<?php

namespace App\Services;

use App\Models\PurchaseOrder;
use App\Models\Supplier;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class SupplierPerformanceService
{
    public function forSupplier(Supplier $supplier): array
    {
        $orders = $supplier->purchaseOrders()
            ->with('items')
            ->whereIn('status', ['sent', 'received'])
            ->get();

        return $this->compute($supplier, $orders);
    }

    public function all(): Collection
    {
        $orders = PurchaseOrder::with('items')
            ->whereIn('status', ['sent', 'received'])
            ->get()
            ->groupBy('supplier_id');

        return Supplier::withTrashed()->orderBy('name')->get()
            ->map(fn (Supplier $supplier) => $this->compute($supplier, $orders->get($supplier->id, collect())))
            ->values();
    }

    public function monthlyTrend(int $months = 6): array
    {
        $start = now()->startOfMonth()->subMonths($months - 1);
        $received = PurchaseOrder::where('status', 'received')
            ->whereNotNull('received_at')
            ->whereNotNull('expected_delivery_date')
            ->where('received_at', '>=', $start)
            ->get()
            ->groupBy(fn (PurchaseOrder $order) => $order->received_at->format('Y-m'));

        $labels = [];
        $onTimeRates = [];
        $averageDays = [];

        for ($i = 0; $i < $months; $i++) {
            $month = $start->copy()->addMonths($i);
            $group = $received->get($month->format('Y-m'), collect());
            $labels[] = $month->format('M Y');

            if ($group->isEmpty()) {
                $onTimeRates[] = null;
                $averageDays[] = null;
                continue;
            }

            $onTime = $group->filter(fn (PurchaseOrder $order) => $this->isOnTime($order))->count();
            $onTimeRates[] = round($onTime / $group->count() * 100, 1);
            $averageDays[] = round($group->avg(
                fn (PurchaseOrder $order) => $this->daysBetween($order->order_date, $order->received_at)
            ), 1);
        }

        return ['labels' => $labels, 'on_time_rates' => $onTimeRates, 'average_days' => $averageDays];
    }

    protected function compute(Supplier $supplier, Collection $orders): array
    {
        $delivered = $orders->where('status', 'received')->values();
        $onTime = $delivered->filter(fn (PurchaseOrder $order) => $this->isOnTime($order))->count();
        $ordered = $delivered->sum(fn (PurchaseOrder $order) => $order->items->sum('quantity'));
        $correct = $delivered->sum(fn (PurchaseOrder $order) => $order->items->sum(
            fn ($item) => min((int) $item->received_quantity, (int) $item->quantity)
        ));

        return [
            'supplier' => $supplier,
            'total_orders' => $orders->count(),
            'delivered_orders' => $delivered->count(),
            'on_time_orders' => $onTime,
            'on_time_rate' => $delivered->isEmpty() ? null : round($onTime / $delivered->count() * 100, 1),
            'avg_actual_days' => $delivered->isEmpty() ? null : round($delivered->avg(
                fn (PurchaseOrder $order) => $this->daysBetween($order->order_date, $order->received_at)
            ), 1),
            'avg_quoted_days' => $delivered->isEmpty() ? null : round($delivered->avg(
                fn (PurchaseOrder $order) => $order->expected_delivery_date
                    ? $this->daysBetween($order->order_date, $order->expected_delivery_date)
                    : (int) $supplier->lead_time_days
            ), 1),
            'accuracy_rate' => $ordered > 0 ? round($correct / $ordered * 100, 1) : null,
            'total_value' => round((float) $orders->sum(fn (PurchaseOrder $order) => (float) $order->total), 2),
        ];
    }

    protected function isOnTime(PurchaseOrder $order): bool
    {
        return $order->received_at
            && $order->expected_delivery_date
            && $order->received_at->toDateString() <= $order->expected_delivery_date->toDateString();
    }

    protected function daysBetween(Carbon $from, Carbon $to): int
    {
        return (int) round(
            ($to->copy()->startOfDay()->timestamp - $from->copy()->startOfDay()->timestamp) / 86400
        );
    }
}
