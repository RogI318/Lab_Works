<?php

namespace App\Services;

use App\Models\InventoryTransaction;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

/**
 * Lab 7 Part 7 Step 1: the one place that turns a query string into a
 * database query.
 *
 * Three rules every method here follows:
 *
 *  1. It returns an Eloquent Builder, never a collection. The caller decides
 *     how many rows to show and in what order, so the products page keeps its
 *     paginate(10), the ledger keeps its paginate(20), and an export can still
 *     ask for every row without copying this filtering logic.
 *  2. Nothing that came from the browser reaches SQL as an identifier. Sort
 *     keys are looked up in a whitelist array and replaced by a column name
 *     this file owns; a key nobody listed falls back to the default order
 *     rather than into a query.
 *  3. Free text is escaped before it is wrapped in %wildcards — see
 *     App\Support\SqlLike — and numbers, ids and dates are validated first, so
 *     a hand-edited URL gets an ignored filter instead of a 500.
 *
 * The scopes it calls all live on the models (Part 7 Step 2), so the same
 * combinations stay available to reports, the reorder engine and a future API.
 */
class SearchService
{
    /** Default order of each list, in [column, direction] pairs. */
    public const DEFAULT_SORTS = [
        'products'     => ['name', 'asc'],
        'orders'       => ['order_date', 'desc'],
        'transactions' => ['created_at', 'desc'],
    ];

    /**
     * Sort key => real column. The browser only ever names a key, so a ?sort=
     * can neither see a column we did not offer nor inject an expression.
     */
    public const PRODUCT_SORTS = [
        'name'          => 'name',
        'sku'           => 'sku',
        'category'      => 'category_id',
        'brand'         => 'brand_id',
        'quantity'      => 'quantity',
        'reorder_level' => 'reorder_level',
        'unit_price'    => 'unit_price',
        'newest'        => 'created_at',
    ];

    public const ORDER_SORTS = [
        'reference' => 'reference_number',
        'supplier'  => 'supplier_id',
        'status'    => 'status',
        'ordered'   => 'order_date',
        'expected'  => 'expected_delivery_date',
        'total'     => 'total',
        'received'  => 'received_at',
    ];

    public const TRANSACTION_SORTS = [
        'date'     => 'created_at',
        'product'  => 'product_id',
        'type'     => 'type',
        'quantity' => 'quantity',
        'user'     => 'user_id',
    ];

    /** Columns whose useful first row is the largest or the latest one. */
    private const DESC_BY_DEFAULT = [
        'quantity', 'reorder_level', 'unit_price', 'total', 'minimum_order_amount',
        'lead_time_days', 'order_date', 'expected_delivery_date', 'received_at', 'sent_at',
        'created_at',
    ];

    /* ==================================================================
       Products (inventory)
       ================================================================== */

    /**
     * Accepted filters: search, category_id, brand_id, supplier_id, status
     * (In Stock / Low Stock / Out of Stock), max_lead_time (days), trashed,
     * sort, dir.
     *
     * @param  array<string, mixed>  $filters
     */
    public function searchProducts(array $filters): Builder
    {
        // The deleted-products list is an admin room: Part 7 Step 1 asks for
        // the toggle, Lab 6's gate stays exactly where it was — in the
        // controller that knows who the visitor is.
        $query = ! empty($filters['trashed'])
            ? Product::onlyTrashed()
            : Product::query();

        $query->with(['category', 'brand', 'primarySupplier'])
            ->search($this->text($filters['search'] ?? null))
            ->stockStatus($this->text($filters['status'] ?? null));

        // ids arrive as strings from the form; a value that is not an id is
        // one nobody could have picked from the dropdown, so it is dropped.
        foreach (['category_id' => 'inCategory', 'brand_id' => 'inBrand', 'supplier_id' => 'forSupplier'] as $key => $scope) {
            if ($id = $this->id($filters[$key] ?? null)) {
                $query->$scope($id);
            }
        }

        // "Suppliers that can deliver within N days" is Part 1's scope
        // scopeWithLeadTime reused through the primary-supplier link.
        if ($days = $this->days($filters['max_lead_time'] ?? null)) {
            $query->whereHas('primarySupplier', fn (Builder $q) => $q->withLeadTime($days));
        }

        return $this->sort($query, $filters, self::PRODUCT_SORTS, self::DEFAULT_SORTS['products']);
    }

    /* ==================================================================
       Purchase orders
       ================================================================== */

    /**
     * Accepted filters: search (reference number), supplier, status, from, to,
     * min_amount, max_amount, created_by, sort, dir.
     *
     * @param  array<string, mixed>  $filters
     */
    public function searchPurchaseOrders(array $filters): Builder
    {
        $query = PurchaseOrder::query()
            ->with(['supplier', 'creator'])
            // The list prints a line count per order; without this it would be
            // one COUNT(*) query per row.
            ->withCount('items')
            ->search($this->text($filters['search'] ?? null))
            ->byStatus($this->status($filters['status'] ?? null))
            ->forSupplier($this->id($filters['supplier'] ?? $filters['supplier_id'] ?? null))
            ->betweenDates($this->date($filters['from'] ?? null), $this->date($filters['to'] ?? null))
            ->amountBetween(
                $this->money($filters['min_amount'] ?? null),
                $this->money($filters['max_amount'] ?? null)
            )
            ->createdBy($this->id($filters['created_by'] ?? null));

        return $this->sort($query, $filters, self::ORDER_SORTS, self::DEFAULT_SORTS['orders']);
    }

    /* ==================================================================
       Stock movements (the ledger)
       ================================================================== */

    /**
     * Accepted filters: product, type, user, date_from, date_to, reference,
     * sort, dir.
     *
     * @param  array<string, mixed>  $filters
     */
    public function searchTransactions(array $filters): Builder
    {
        $query = InventoryTransaction::query()
            // A movement names its product and the person who recorded it, and
            // the ledger shows both on every row.
            ->with(['product', 'user'])
            ->forProduct($this->id($filters['product'] ?? $filters['product_id'] ?? null))
            ->ofType($this->text($filters['type'] ?? null))
            ->byUser($this->id($filters['user'] ?? $filters['user_id'] ?? null))
            ->betweenDates(
                $this->date($filters['date_from'] ?? $filters['from'] ?? null),
                $this->date($filters['date_to'] ?? $filters['to'] ?? null)
            )
            ->withReference($this->text($filters['reference'] ?? null));

        return $this->sort($query, $filters, self::TRANSACTION_SORTS, self::DEFAULT_SORTS['transactions']);
    }

    /* ==================================================================
       Suppliers
       ================================================================== */

    /**
     * Accepted filters: search, status (all/active/inactive/deleted),
     * max_lead_time, sort, dir. Kept separate from the three builders above
     * because the supplier list has its own status vocabulary (it is the only
     * one of the four that can be retired rather than deleted).
     *
     * @param  array<string, mixed>  $filters
     */
    public function searchSuppliers(array $filters): Builder
    {
        $query = Supplier::query()->search($this->text($filters['search'] ?? null));

        // The same three-way split SupplierController has always used, now in
        // one place so the counts beside the dropdown stay honest.
        match ($filters['status'] ?? 'all') {
            'active'   => $query->active(),
            'inactive' => $query->inactive(),
            'deleted'  => $query->onlyTrashed(),
            default    => $query,
        };

        if ($days = $this->days($filters['max_lead_time'] ?? null)) {
            $query->withLeadTime($days);
        }

        return $this->sort($query, $filters, [
            'name'          => 'name',
            'lead_time'     => 'lead_time_days',
            'minimum_order' => 'minimum_order_amount',
            'newest'        => 'created_at',
        ], ['name', 'asc']);
    }

    /* ==================================================================
       Shared guards
       ================================================================== */

    /**
     * Apply the requested order — but only from the whitelist. An unknown or
     * missing ?sort= falls back to the list's own default rather than into a
     * query, and every order gets the id as a tiebreaker so two rows that
     * share a value keep the same place from one page load to the next.
     *
     * @param  array<string, string>  $whitelist  sort key => column
     * @param  array{0: string, 1: string}  $default
     */
    private function sort(Builder $query, array $filters, array $whitelist, array $default): Builder
    {
        $key = $this->text($filters['sort'] ?? null) ?? '';

        if (! array_key_exists($key, $whitelist)) {
            [$column, $direction] = $default;
        } else {
            // Sorting a text column reads top-down; a number or a date reads
            // bottom-up (biggest, newest first) unless the caller says otherwise.
            $column    = $whitelist[$key];
            $direction = $this->direction(
                in_array($column, self::DESC_BY_DEFAULT, true) ? 'desc' : 'asc',
                $this->text($filters['dir'] ?? null)
            );
        }

        return $query->orderBy($column, $direction)->orderBy('id', $direction);
    }

    /** Only asc or desc ever reaches orderBy(). */
    private function direction(string $default, mixed $requested): string
    {
        return match (strtolower((string) $requested)) {
            'asc'  => 'asc',
            'desc' => 'desc',
            default => $default,
        };
    }

    /** A single line of text from the query string; an array is not one. */
    private function text(mixed $value): ?string
    {
        return is_scalar($value) ? (string) $value : null;
    }

    /** A positive integer id, or null when the value is not one. */
    private function id(mixed $value): ?int
    {
        $id = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        return $id === false ? null : $id;
    }

    /** A day count for the lead-time filters: 1..365, anything else ignored. */
    private function days(mixed $value): ?int
    {
        $days = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 365]]);

        return $days === false ? null : $days;
    }

    /** A money amount, rounded to the cent the columns store, never negative. */
    private function money(mixed $value): ?float
    {
        if (! is_numeric($value)) {
            return null;
        }

        $amount = round((float) $value, 2);

        return $amount < 0 ? null : $amount;
    }

    /** A real calendar date in the form the <input type="date"> sends. */
    private function date(mixed $value): ?string
    {
        $value = $this->text($value);

        if ($value === null || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }

        try {
            return Carbon::createFromFormat('!Y-m-d', $value)->toDateString() === $value
                ? $value : null;
        } catch (\Throwable) {
            return null;
        }
    }

    /** A status the model actually knows, or null for "every status". */
    private function status(mixed $value): ?string
    {
        $value = $this->text($value);

        return $value !== null && array_key_exists($value, PurchaseOrder::STATUSES) ? $value : null;
    }
}
