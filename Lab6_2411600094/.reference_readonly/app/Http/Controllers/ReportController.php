<?php
// LAB 6, PART 9: Reports (stock movement, low stock, valuation, CSV export)
// Created by: php artisan make:controller ReportController
// app/Http/Controllers/ReportController.php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\InventoryTransaction;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ReportController extends Controller
{
    // index() - the three reports on one page
    public function index(Request $request)
    {
        [$from, $to] = $this->dateRange($request);

        // Stock Movement Report (date range)
        $movements = $this->movementQuery($from, $to)->get();

        // Low Stock Report
        $lowStock = Product::lowStock()->orderBy('quantity')->get();

        // Inventory Valuation Report (by category)
        $valuation = Product::select('category')
            ->selectRaw('SUM(quantity) as total_quantity')
            ->selectRaw('SUM(quantity * unit_price) as total_value')
            ->groupBy('category')
            ->orderBy('category')
            ->get();

        $grandTotal = $valuation->sum('total_value');

        return view('reports.index', compact('from', 'to', 'movements', 'lowStock', 'valuation', 'grandTotal'));
    }

    // export() - CSV download of the stock movement report
    public function export(Request $request)
    {
        [$from, $to] = $this->dateRange($request);

        // LAB 6: a CSV download is an action, so it goes in the audit log
        AuditLog::record('exported', null, null, ['from' => (string) $from, 'to' => (string) $to], 'Transactions report (CSV)');

        return response()->streamDownload(function () use ($from, $to) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Date', 'SKU', 'Product', 'Type', 'Quantity', 'Reference', 'User']);

            foreach ($this->movementQuery($from, $to)->get() as $t) {
                fputcsv($out, [
                    $t->created_at,
                    $t->product?->sku,
                    $t->product?->name,
                    $t->type,
                    $t->signed_quantity,
                    $t->reference,
                    $t->user?->name,
                ]);
            }

            fclose($out);
        }, "stock-movement-{$from}-to-{$to}.csv", ['Content-Type' => 'text/csv']);
    }

    // Shared by the page and the CSV so both show the same rows
    private function movementQuery(string $from, string $to)
    {
        return InventoryTransaction::with(['product', 'user'])
            ->whereDate('created_at', '>=', $from)
            ->whereDate('created_at', '<=', $to)
            ->recent();
    }

    // Validate the date range, default to the last 30 days
    private function dateRange(Request $request): array
    {
        $request->validate([
            'from' => 'nullable|date',
            'to' => ['nullable', 'date', Rule::when($request->filled('from'), 'after_or_equal:from')],
        ], [
            'to.after_or_equal' => 'The "To" date must be on or after the "From" date.',
        ]);

        return [
            $request->input('from') ?: now()->subDays(30)->toDateString(),
            $request->input('to') ?: now()->toDateString(),
        ];
    }
}
