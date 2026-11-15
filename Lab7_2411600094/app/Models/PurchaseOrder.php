<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use App\Observers\PurchaseOrderObserver;
use App\Support\SqlLike;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Lab 7 Part 2 Step 3: a purchase order header.
 *
 * The workflow is
 *   draft --submit--> pending --approve--> approved --send--> sent
 *                       |                                      |
 *                   reject                                 (receive)
 *                       v                                      v
 *                   rejected              partially_received <-> received
 *   draft / pending / approved --cancel--> cancelled
 *
 * Status changes are owned by PurchaseOrderPolicy; the canBe*() helpers here
 * answer only "is this order in a state where the action means anything", so a
 * rule never lives in two places.
 *
 * Lab 7 Part 4 Step 4: what a status change MEANS — the audit row, the mail to
 * the right person, the cache that has gone stale — belongs to
 * PurchaseOrderObserver, so it happens for every caller, not only the web one.
 */
#[ObservedBy(PurchaseOrderObserver::class)]
class PurchaseOrder extends Model
{
    use SoftDeletes, LogsActivity;

    /* ---------- Statuses ---------- */
    public const STATUS_DRAFT              = 'draft';
    public const STATUS_PENDING            = 'pending';
    public const STATUS_APPROVED           = 'approved';
    public const STATUS_SENT               = 'sent';
    public const STATUS_PARTIALLY_RECEIVED = 'partially_received';
    public const STATUS_RECEIVED           = 'received';
    public const STATUS_REJECTED           = 'rejected';
    public const STATUS_CANCELLED          = 'cancelled';

    /** value => label, in workflow order. */
    public const STATUSES = [
        self::STATUS_DRAFT              => 'Draft',
        self::STATUS_PENDING            => 'Pending approval',
        self::STATUS_APPROVED           => 'Approved',
        self::STATUS_SENT               => 'Sent to supplier',
        self::STATUS_PARTIALLY_RECEIVED => 'Partially received',
        self::STATUS_RECEIVED           => 'Received',
        self::STATUS_REJECTED           => 'Rejected',
        self::STATUS_CANCELLED          => 'Cancelled',
    ];

    /**
     * "Active" = the order still expects something from somebody, so the
     * supplier cannot be retired and the order cannot be erased. Rejected and
     * cancelled are finished; received is fulfilled.
     */
    public const ACTIVE_STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_PENDING,
        self::STATUS_APPROVED,
        self::STATUS_SENT,
        self::STATUS_PARTIALLY_RECEIVED,
    ];

    /**
     * "Placed" = the vendor was actually given the order. Lab 7 Part 6 Step 1:
     * this is the denominator of total_orders and total_order_value — a draft, a
     * pending or an approved order is still an intention, and a rejected or
     * cancelled one never left the building, so none of them can be held against
     * a supplier's record.
     */
    public const PLACED_STATUSES = [
        self::STATUS_SENT,
        self::STATUS_PARTIALLY_RECEIVED,
        self::STATUS_RECEIVED,
    ];

    protected $fillable = [
        'reference_number', 'supplier_id', 'status', 'order_date',
        'expected_delivery_date', 'sent_at', 'received_at', 'approved_at', 'rejected_at',
        'subtotal', 'tax', 'total', 'notes', 'terms', 'rejection_reason',
        'is_auto_generated', 'created_by', 'approved_by', 'rejected_by',
    ];

    protected $casts = [
        'order_date'             => 'date',
        'expected_delivery_date' => 'date',
        'sent_at'                => 'datetime',
        'received_at'            => 'datetime',
        'approved_at'            => 'datetime',
        'rejected_at'            => 'datetime',
        'subtotal'               => 'decimal:2',
        'tax'                    => 'decimal:2',
        'total'                  => 'decimal:2',
        'is_auto_generated'      => 'boolean',
    ];

    /* ---------- Audit-trail context (Part 4) ---------- */

    /**
     * Extra wording a caller hands the observer so the history row can say what
     * a status column cannot: which lines landed on a receive, why an order was
     * cancelled. Handing it over sends nothing by itself — the observer reads it
     * once, at the moment the status actually moves.
     *
     * @var array<string, mixed>
     */
    private array $activityContext = [];

    public function withActivityContext(array $context): static
    {
        $this->activityContext = $context;

        return $this;
    }

    /** @return array<string, mixed> */
    public function activityContext(): array
    {
        return $this->activityContext;
    }

    /* ---------- Relationships ---------- */

    /**
     * withTrashed(): retiring a supplier must not turn its past orders into
     * blank rows — an order is a historical fact about a vendor who may be gone.
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class)->withTrashed();
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class)->orderBy('id');
    }

    /** The three actors are separate columns, so three separate relations. */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function rejector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    /** Alias kept for the wording the sheet uses. */
    public function createdBy(): BelongsTo
    {
        return $this->creator();
    }

    public function approvedBy(): BelongsTo
    {
        return $this->approver();
    }

    public function rejectedBy(): BelongsTo
    {
        return $this->rejector();
    }

    /* ---------- Query scopes ---------- */

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', self::ACTIVE_STATUSES);
    }

    public function scopeByStatus(Builder $query, ?string $status): Builder
    {
        return array_key_exists((string) $status, self::STATUSES)
            ? $query->where('status', $status)
            : $query;
    }

    /** Part 7 filtering. */
    public function scopeForSupplier(Builder $query, Supplier|int|null $supplier): Builder
    {
        $id = $supplier instanceof Supplier ? $supplier->id : $supplier;

        return $query->when($id, fn (Builder $q) => $q->where('supplier_id', $id));
    }

    /** Part 7 filtering; inclusive of both day boundaries. */
    public function scopeBetweenDates(Builder $query, ?string $from, ?string $to): Builder
    {
        return $query
            ->when($from, fn (Builder $q) => $q->whereDate('order_date', '>=', $from))
            ->when($to,   fn (Builder $q) => $q->whereDate('order_date', '<=', $to));
    }

    /* ---------- Query scopes (Lab 7 Part 7 Step 2) ---------- */

    /**
     * Reference number, typed the way people remember it: they read the tail
     * ("0007") or the middle date ("20260616"), rarely the whole string.
     */
    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        return SqlLike::any($query, ['reference_number'], $term);
    }

    /** Money range on the grand total, inclusive on both ends. */
    public function scopeAmountBetween(Builder $query, float|int|null $min, float|int|null $max): Builder
    {
        return $query
            ->when($min !== null, fn (Builder $q) => $q->where('total', '>=', $min))
            ->when($max !== null, fn (Builder $q) => $q->where('total', '<=', $max));
    }

    /** Who raised the order — a User model or its id, and null means "anyone". */
    public function scopeCreatedBy(Builder $query, User|int|null $user): Builder
    {
        $id = $user instanceof User ? $user->id : $user;

        return $query->when($id, fn (Builder $q) => $q->where('created_by', (int) $id));
    }

    /* ---------- Money ---------- */

    /**
     * Recomputes subtotal / tax / total from the lines. Always server-side:
     * the browser's arithmetic is a preview, never the stored figure.
     */
    public function calculateTotal(): static
    {
        // Each line is rounded before summing, the same way an invoice adds up.
        $this->subtotal = round($this->items->sum(
            fn (PurchaseOrderItem $item) => round((int) $item->quantity * (float) $item->unit_price, 2)
        ), 2);

        $this->tax   = round($this->subtotal * (float) config('procurement.tax_rate'), 2);
        $this->total = round($this->subtotal + $this->tax, 2);

        return $this;
    }

    public function taxRateLabel(): string
    {
        return rtrim(rtrim(number_format((float) config('procurement.tax_rate') * 100, 2), '0'), '.') . ' %';
    }

    /* ---------- State helpers ---------- */

    public function canBeEdited(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function canBeSubmitted(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function canBeApproved(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function canBeRejected(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function canBeSent(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    /** Only a sent order still owes goods; a received one is closed. */
    public function canBeReceived(): bool
    {
        return in_array($this->status, [self::STATUS_SENT, self::STATUS_PARTIALLY_RECEIVED], true);
    }

    public function canBeCancelled(): bool
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_PENDING, self::STATUS_APPROVED], true);
    }

    public function isActive(): bool
    {
        return in_array($this->status, self::ACTIVE_STATUSES, true);
    }

    /** Units still owed across every line — 0 once the order is complete. */
    public function outstandingUnits(): int
    {
        return max(0, $this->items->sum(fn (PurchaseOrderItem $item) => $item->outstanding_quantity));
    }

    public function orderedUnits(): int
    {
        return (int) $this->items->sum('quantity');
    }

    /** 0-100, used by the progress bar on the show page. */
    public function receivedPercent(): float
    {
        $ordered = $this->orderedUnits();

        return $ordered === 0 ? 0.0 : round($this->items->sum('received_quantity') / $ordered * 100, 1);
    }

    /**
     * Delivery verdict for the Part 6 supplier report: late when the goods
     * landed after the promised date, on time otherwise. Null until received.
     */
    public function deliveryVerdict(): ?string
    {
        if (! $this->received_at || ! $this->expected_delivery_date) {
            return null;
        }

        return $this->received_at->lte($this->expected_delivery_date->copy()->endOfDay())
            ? 'on_time'
            : 'late';
    }

    public function wasShortReceived(): bool
    {
        return $this->status === self::STATUS_RECEIVED
            && $this->items->contains(fn (PurchaseOrderItem $item) => $item->received_quantity < $item->quantity);
    }

    /* ---------- Presentation ---------- */

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst($this->status);
    }

    public function badgeClass(): string
    {
        return match ($this->status) {
            self::STATUS_DRAFT              => 'bg-secondary',
            self::STATUS_PENDING            => 'bg-warning text-dark',
            self::STATUS_APPROVED           => 'bg-info text-dark',
            self::STATUS_SENT               => 'bg-primary',
            self::STATUS_PARTIALLY_RECEIVED => 'bg-warning text-dark',
            self::STATUS_RECEIVED           => 'bg-success',
            self::STATUS_REJECTED,
            self::STATUS_CANCELLED          => 'bg-danger',
            default                         => 'bg-secondary',
        };
    }

    /* ---------- Reference numbers ---------- */

    /**
     * PO-YYYYMMDD-0001, one sequence per order date.
     *
     * The day's rows are locked (FOR UPDATE) inside the transaction, so two
     * buyers clicking Save at the same moment — or the Part 3 command firing
     * while a human is working — serialise instead of both reading the same
     * "last" number. The unique index on reference_number is the safety net;
     * the lock is what keeps us off it. Soft-deleted orders are counted too,
     * because their numbers stay reserved.
     */
    public static function generateReferenceNumber(CarbonInterface|string|null $orderDate = null): string
    {
        $date = $orderDate instanceof CarbonInterface
            ? $orderDate
            : ($orderDate ? Carbon::parse($orderDate) : now());

        $prefix = 'PO-' . $date->format('Ymd');

        return DB::transaction(function () use ($prefix): string {
            $latest = static::withTrashed()
                ->where('reference_number', 'like', $prefix . '-%')
                ->lockForUpdate()
                ->orderByDesc('reference_number')
                ->value('reference_number');

            $sequence = ((int) substr((string) $latest, -4)) + 1;

            return sprintf('%s-%04d', $prefix, $sequence);
        });
    }
}
