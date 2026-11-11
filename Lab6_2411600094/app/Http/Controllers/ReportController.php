<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\InventoryTransaction;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Lab 5 built the Inventory Valuation report; Lab 6 Part 9 Step 2 adds the
 * Stock Movement and Low Stock reports next to it, each with a CSV export.
 */
class ReportController extends Controller
{
    private const TYPES = ['valuation', 'movement', 'low-stock'];

    public function index(Request $request)
    {
        $range = $this->range($request);

        return view('reports.index', [
            'report'         => in_array($request->report, self::TYPES) ? $request->report : 'valuation',
            'range'          => $range,
            'movement'       => $this->movementRows($range[0], $range[1]),
            'lowStockReport' => $this->lowStockReport(),
        ] + $this->reportData());
    }

    /** Part 9 Step 2: one export endpoint, three report shapes. */
    public function export(Request $request): StreamedResponse
    {
        return match (in_array($request->report, self::TYPES) ? $request->report : 'valuation') {
            'movement'  => $this->exportMovement($this->range($request)),
            'low-stock' => $this->exportLowStock(),
            default     => $this->exportValuation(),
        };
    }

    /* ---------- Part 9 exports ---------- */

    private function exportMovement(array $range): StreamedResponse
    {
        [$from, $to] = $range;

        return $this->csv(
            "stock_movement_{$from}_to_{$to}.csv",
            ['Date', 'Time', 'SKU', 'Product', 'Type', 'Reason', 'Qty change', 'Balance after', 'Reference', 'Recorded by'],
            $this->movementRows($from, $to)->map(fn (InventoryTransaction $t) => [
                $t->created_at->toDateString(),
                $t->created_at->format('H:i'),
                $t->product->sku,
                $t->product->name,
                $t->typeLabel(),
                $t->isAdjustment() ? $t->reasonLabel() : '',
                $t->signedQuantity(),
                $t->balance_after,
                $t->reference_number,
                $t->user?->name ?? 'System',
            ])
        );
    }

    private function exportLowStock(): StreamedResponse
    {
        return $this->csv(
            'low_stock_report_' . now()->format('Y_m_d') . '.csv',
            ['SKU', 'Product', 'Category', 'On Hand', 'Reorder Level', 'Deficit', 'Suggested Order', 'Unit Price', 'Status'],
            $this->lowStockReport()->map(fn (Product $p) => [
                $p->sku,
                $p->name,
                $p->category->name,
                $p->quantity,
                $p->reorder_level,
                max(0, $p->reorder_level - $p->quantity),
                $p->unitQuantityMissing(),
                number_format((float) $p->unit_price, 2),
                $p->stockStatus(),
            ])
        );
    }

    /** Lab 5 export, unchanged columns, with the grand total as the last line. */
    private function exportValuation(): StreamedResponse
    {
        $rows = Product::with('category', 'brand')->orderBy('name')->get()->map(fn (Product $p) => [
            $p->sku, $p->name, $p->category->name, $p->brand?->name, $p->supplier,
            $p->quantity, $p->reorder_level, number_format((float) $p->unit_price, 2),
            number_format($p->stockValue(), 2), $p->stockStatus(),
        ]);

        $rows->push(['', '', '', '', '', '', '', 'TOTAL INVENTORY VALUE', number_format(
            (float) Product::sum(DB::raw('quantity * unit_price')), 2
        ), '']);

        return $this->csv(
            'inventory_valuation_' . now()->format('Y_m_d') . '.csv',
            ['SKU', 'Item', 'Category', 'Brand', 'Supplier', 'Qty', 'Reorder Level', 'Unit Price', 'Total Value', 'Status'],
            $rows
        );
    }


    /* ---------- Part 9 report bodies ---------- */

    /**
     * Every movement in the range, oldest first, each carrying the quantity
     * that stood after it. The balances come from Product::transactionHistory()
     * so the report and the product page can never disagree.
     */
    private function movementRows(?string $from, ?string $to): \Illuminate\Support\Collection
    {
        return Product::query()
            ->orderBy('name')
            ->get()
            ->flatMap(function (Product $product) use ($from, $to) {
                return $product->transactionHistory($from, $to)
                    ->each(fn (InventoryTransaction $t) => $t->setRelation('product', $product));
            })
            ->sortBy(fn (InventoryTransaction $t) => [$t->created_at->timestamp, $t->id])
            ->values();
    }

    /** docx wording: products at or below their reorder level, empties first. */
    private function lowStockReport(): \Illuminate\Support\Collection
    {
        return Product::with('category')
            ->belowReorderLevel()
            ->orderBy('quantity')
            ->orderBy('name')
            ->get();
    }

    /** Default window = the last 30 days, so the report opens on something. */
    private function range(Request $request): array
    {
        return [
            $request->date_from ?: now()->subDays(30)->toDateString(),
            $request->date_to ?: now()->toDateString(),
        ];
    }

    private function csv(string $filename, array $headings, \Illuminate\Support\Collection $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($headings, $rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, $headings);
            foreach ($rows as $row) {
                fputcsv($out, (array) $row);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    /* ---------- Lab 5 valuation figures (kept as they were) ---------- */

    private function reportData(): array
    {
        $byCategory = Category::withCount('products')
            ->withSum('products', 'quantity')
            ->get()
            ->map(function (Category $category) {
                $value = (float) $category->products()
                    ->selectRaw('COALESCE(SUM(quantity * unit_price), 0) as total')
                    ->first()->total;

                return [
                    'name'     => $category->name,
                    'items'    => $category->products_count,
                    'units'    => (int) ($category->products_sum_quantity ?? 0),
                    'value'    => $value,
                    'low_stock' => $category->products()->lowStock()->count(),
                ];
            });

        $byBrand = Brand::withCount('products')->get()->map(function (Brand $brand) {
            return [
                'name'  => $brand->name,
                'items' => $brand->products_count,
                'value' => (float) $brand->products()
                    ->selectRaw('COALESCE(SUM(quantity * unit_price), 0) as total')
                    ->value('total'),
            ];
        });

        $suppliers = Product::selectRaw('supplier, COUNT(*) as items, SUM(quantity * unit_price) as total_value')
            ->whereNotNull('supplier')
            ->groupBy('supplier')
            ->orderByDesc('total_value')
            ->get();

        return [
            'totals' => [
                'products'        => Product::count(),
                'units'           => (int) Product::sum('quantity'),
                'value'           => (float) Product::sum(DB::raw('quantity * unit_price')),
                'low_stock'       => Product::lowStock()->count(),
                'out_of_stock'    => Product::outOfStock()->count(),
                'stock_in_units'  => (int) InventoryTransaction::where('type', InventoryTransaction::TYPE_IN)->sum('quantity'),
                'stock_out_units' => (int) InventoryTransaction::where('type', InventoryTransaction::TYPE_OUT)->sum('quantity'),
            ],
            'byCategory'  => $byCategory,
            'byBrand'     => $byBrand,
            'suppliers'   => $suppliers,
        ];
    }
}
