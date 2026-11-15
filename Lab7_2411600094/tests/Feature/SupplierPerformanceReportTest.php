<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Lab 7 Part 6: supplier performance tracking.
 *
 * The sheet asks for four things, and each has a test that can only pass if the
 * thing is actually true:
 *
 *   1. the five metrics are computed correctly — asserted against numbers worked
 *      out by hand here, on a data set built so every formula has a case that
 *      distinguishes it (an on-time order, a late one, a short line, an order
 *      still in flight);
 *   2. the whole scorecard is ONE query, not six per vendor — counted from the
 *      query log, cold and warm;
 *   3. the cache is real and the Part 4 observer is what keeps it honest, even
 *      for a date-filtered read whose key the observer cannot name;
 *   4. the report page exists, gates correctly, ranks only on a sample worth
 *      ranking on, and says so.
 *
 * sqlite, array cache, sync queue, array mail: nothing here reaches lab5_db, a
 * worker or a mailbox.
 */
class SupplierPerformanceReportTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $manager;
    private User $warehouse;
    private User $staff;
    /** @var array<int, Product>  two, because one order cannot line the same SKU twice */
    private array $products;
    private int $sequence = 0;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin     = $this->makeUser('ops@rogiemart.test', User::ROLE_ADMIN);
        $this->manager   = $this->makeUser('buying@rogiemart.test', User::ROLE_MANAGER);
        $this->warehouse = $this->makeUser('dock@rogiemart.test', User::ROLE_WAREHOUSE);
        $this->staff     = $this->makeUser('till@rogiemart.test', User::ROLE_STAFF);

        $brand    = Brand::create(['name' => 'Cruzbuilt']);
        $category = Category::create(['name' => 'Lumber']);

        $this->products = [
            Product::create([
                'name'          => 'Plywood 12mm 4x8',
                'sku'           => 'PLY-12-48',
                'brand_id'      => $brand->id,
                'category_id'   => $category->id,
                'quantity'      => 40,
                'reorder_level' => 20,
                'unit_price'    => 640.00,
            ]),
            Product::create([
                'name'          => 'Treated Lumber 2x4x10',
                'sku'           => 'LMB-TRT-2410',
                'brand_id'      => $brand->id,
                'category_id'   => $category->id,
                'quantity'      => 60,
                'reorder_level' => 25,
                'unit_price'    => 380.00,
            ]),
        ];

        // The report's default window is "the last six months", so the clock is
        // pinned: every test below can name its dates and know whether they are
        // inside that window or outside it.
        Carbon::setTestNow('2026-11-15 09:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    /* ==================================================================
       Step 1 — the five formulas
       ================================================================== */

    public function test_every_metric_is_the_number_worked_out_by_hand(): void
    {
        // Quoted lead time 7 days, so the variance has something to compare with.
        $vendor = $this->makeSupplier('Metro Lumber Supply', 7);

        // (a) on time, 2 lines, both complete: promise 2026-05-10, landed 05-08.
        //     sent 05-05 -> 3 calendar days.
        $early = $this->order($vendor, PurchaseOrder::STATUS_RECEIVED, '2026-05-01', '2026-05-10', '2026-05-05', '2026-05-08 15:45', [
            [10, 10, 100.00, 0],
            [4, 4, 250.00, 1],
        ]);

        // (b) late by 3 days, one line complete, one line 2 short of 6:
        //     sent 06-05, landed 06-13 -> 8 days.
        $late = $this->order($vendor, PurchaseOrder::STATUS_RECEIVED, '2026-06-01', '2026-06-10', '2026-06-05', '2026-06-13 09:00', [
            [6, 4, 100.00, 1],
            [20, 20, 50.00, 0],
        ]);

        // (c) still on the road: counts as an order and as money, never as a delivery.
        $road = $this->order($vendor, PurchaseOrder::STATUS_SENT, '2026-07-01', '2026-07-08', '2026-07-02', null, [
            [5, 0, 100.00, 0],
        ]);

        $row = app(\App\Services\SupplierPerformanceService::class)->forSupplier($vendor->id);

        $this->assertNotNull($row, 'A vendor with placed orders must appear on the scorecard.');

        // total_orders: a, b, c — three orders actually given to the vendor.
        $this->assertSame(3, $row['total_orders']);

        // on_time: 1 of the 2 closed receipts, so 50 % and never a blank 100 %.
        $this->assertSame(2, $row['received_orders']);
        $this->assertSame(1, $row['on_time_orders']);
        $this->assertSame(50.0, $row['on_time_percentage']);

        // average days: (3 + 8) / 2 = 5.5 against a quoted 7 -> 1.5 days early.
        $this->assertSame(5.5, $row['average_delivery_days']);
        $this->assertSame(-1.5, $row['lead_time_variance_days']);

        // accuracy: 3 of the 4 lines on the closed receipts arrived as ordered.
        $this->assertSame(4, $row['lines_received']);
        $this->assertSame(3, $row['lines_exact']);
        $this->assertSame(75.0, $row['order_accuracy_percentage']);

        // value: the three header totals, added once each — the lines join must
        // not multiply a three-line order's money by its line count.
        $this->assertSame(
            round((float) $early->total + (float) $late->total + (float) $road->total, 2),
            round($row['total_order_value'], 2)
        );
    }

    public function test_a_partially_received_order_is_an_order_but_not_a_delivery(): void
    {
        $vendor = $this->makeSupplier('AquaLine Plumbing Depot', 8);

        // receive() clears received_at again unless the last unit landed.
        $this->order($vendor, PurchaseOrder::STATUS_PARTIALLY_RECEIVED, '2026-05-01', '2026-05-09', '2026-05-02', null, [
            [10, 4, 100.00],
        ]);

        $row = app(\App\Services\SupplierPerformanceService::class)->forSupplier($vendor->id);

        $this->assertSame(1, $row['total_orders']);
        $this->assertSame(0, $row['received_orders']);
        $this->assertNull($row['on_time_percentage'], 'Nothing has landed, so nothing can be scored.');
        $this->assertNull($row['average_delivery_days']);
        $this->assertNull($row['order_accuracy_percentage']);
        $this->assertSame(0, $row['lines_received'], 'The 4 units aboard are not a signed delivery.');
        $this->assertGreaterThan(0, $row['total_order_value']);
        $this->assertSame('bg-secondary', $row['rating_badge_class']);
    }

    /* ==================================================================
       Step 1 — one query, and the cache around it
       ================================================================== */

    public function test_the_whole_scorecard_costs_one_purchase_orders_query(): void
    {
        $first  = $this->makeSupplier('Metro Lumber Supply', 7);
        $second = $this->makeSupplier('AquaLine Plumbing Depot', 8);

        foreach ([$first, $second] as $vendor) {
            $this->order($vendor, PurchaseOrder::STATUS_RECEIVED, '2026-05-01', '2026-05-10', '2026-05-05', '2026-05-08 15:45', [[10, 10, 100.00]]);
            $this->order($vendor, PurchaseOrder::STATUS_SENT, '2026-06-01', '2026-06-10', '2026-06-02', null, [[6, 0, 100.00]]);
        }

        $service = app(\App\Services\SupplierPerformanceService::class);

        DB::flushQueryLog();
        DB::enableQueryLog();
        $cold = $service->forAll();
        $coldQueries = $this->orderQueries(DB::getQueryLog());

        DB::flushQueryLog();
        $warm = $service->forAll();
        $warmQueries = $this->orderQueries(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertCount(2, $cold['rows'], 'Both vendors answered from one trip to the database.');
        $this->assertSame(1, $coldQueries, 'A per-supplier loop is what this replaced.');
        $this->assertSame(0, $warmQueries, 'The warm read answered from the cache.');
        $this->assertSame($cold, $warm);
    }

    public function test_the_date_range_is_part_of_the_cache_key(): void
    {
        $vendor = $this->makeSupplier('Metro Lumber Supply', 7);

        $this->order($vendor, PurchaseOrder::STATUS_RECEIVED, '2026-01-05', '2026-01-12', '2026-01-06', '2026-01-09 10:00', [[10, 10, 100.00]]);
        $this->order($vendor, PurchaseOrder::STATUS_RECEIVED, '2026-03-05', '2026-03-12', '2026-03-06', '2026-03-20 10:00', [[10, 10, 100.00]]);

        $service = app(\App\Services\SupplierPerformanceService::class);

        $january = $service->forSupplier($vendor->id, '2026-01-01', '2026-01-31');
        $march   = $service->forSupplier($vendor->id, '2026-03-01', '2026-03-31');
        $both    = $service->forSupplier($vendor->id, '2026-01-01', '2026-03-31');

        // If the window were missing from the key, the second call would be
        // served the first answer and these three would be identical.
        $this->assertSame(100.0, $january['on_time_percentage']);
        $this->assertSame(0.0, $march['on_time_percentage']);
        $this->assertSame(50.0, $both['on_time_percentage']);
    }

    public function test_receiving_an_order_invalidates_the_filtered_cache_too(): void
    {
        $vendor = $this->makeSupplier('Metro Lumber Supply', 7);

        $order = $this->order($vendor, PurchaseOrder::STATUS_SENT, '2026-05-01', '2026-05-10', '2026-05-02', null, [[10, 0, 100.00]]);

        $service = app(\App\Services\SupplierPerformanceService::class);
        $window  = ['2026-05-01', '2026-05-31'];

        // Warm both the ranged scorecard and its trend bucket, so what follows
        // proves the flush reached the keys the observer cannot name.
        $service->forAll(...$window);
        $service->deliveryTrend(...$window);

        DB::flushQueryLog();
        DB::enableQueryLog();

        // The Part 2 workflow: the last unit lands, so the order closes.
        foreach ($order->items as $item) {
            $item->update(['received_quantity' => $item->quantity]);
        }

        $order->update(['status' => PurchaseOrder::STATUS_RECEIVED, 'received_at' => Carbon::parse('2026-05-10 16:00')]);

        $after = $service->forSupplier($vendor->id, ...$window);
        $trend = collect($service->deliveryTrend(...$window))->firstWhere('month', '2026-05');

        $rebuilt = $this->orderQueries(DB::getQueryLog()) > 0;
        DB::disableQueryLog();

        $this->assertTrue($rebuilt, 'The observer must clear what the report reads, ranged or not.');
        $this->assertSame(1, $after['received_orders']);
        $this->assertSame(100.0, $after['on_time_percentage']);
        $this->assertSame(1, $trend['on_time']);
        $this->assertSame(0, $trend['late']);
    }

    /* ==================================================================
       Step 2 — the report page
       ================================================================== */

    public function test_the_report_shows_the_table_the_filter_and_both_charts(): void
    {
        $vendor = $this->makeSupplier('Metro Lumber Supply', 7);

        $this->order($vendor, PurchaseOrder::STATUS_RECEIVED, '2026-05-01', '2026-05-10', '2026-05-02', '2026-05-08 16:00', [[10, 10, 100.00]]);
        $this->order($vendor, PurchaseOrder::STATUS_RECEIVED, '2026-06-01', '2026-06-10', '2026-06-02', '2026-06-14 16:00', [[10, 8, 100.00]]);

        $page = $this->actingAs($this->admin)
            ->get('/reports/supplier-performance?from=2026-05-01&to=2026-11-15')
            ->assertOk()
            ->assertSee('Supplier Performance')
            ->assertSee('Metro Lumber Supply')
            ->assertSee('name="from"', false)
            ->assertSee('value="2026-05-01"', false)
            ->assertSee('On-time %', false)
            ->assertSee('vs quoted lead time')
            ->assertSee('id="trendChart"', false)
            ->assertSee('id="supplierChart"', false);

        // Sortable columns: a th that cannot be sorted would carry no data-sort.
        $html = $page->getContent();

        $this->assertStringContainsString('class="sortable', $html);
        $this->assertStringContainsString('data-sort-value', $html);

        // One on time, one late, out of the two closed receipts.
        $this->assertStringContainsString('"on_time":1', $html);
        $this->assertStringContainsString('"late":1', $html);
    }

    public function test_only_suppliers_with_a_sample_worth_ranking_are_ranked(): void
    {
        $measured = $this->makeSupplier('Metro Lumber Supply', 7);
        $thin     = $this->makeSupplier('AquaLine Plumbing Depot', 8);

        // Two closed receipts: rankable.
        $this->order($measured, PurchaseOrder::STATUS_RECEIVED, '2026-06-01', '2026-06-10', '2026-06-02', '2026-06-08 16:00', [[10, 10, 100.00]]);
        $this->order($measured, PurchaseOrder::STATUS_RECEIVED, '2026-07-01', '2026-07-10', '2026-07-02', '2026-07-09 16:00', [[10, 10, 100.00]]);

        // One closed receipt: not a pattern, so it is listed and explained.
        $this->order($thin, PurchaseOrder::STATUS_RECEIVED, '2026-06-01', '2026-06-10', '2026-06-02', '2026-06-08 16:00', [[10, 10, 100.00]]);

        $rows = collect(app(\App\Services\SupplierPerformanceService::class)->forAll()['rows'])->keyBy('supplier_id');

        $this->assertTrue($rows[$measured->id]['is_rankable']);
        $this->assertFalse($rows[$thin->id]['is_rankable']);

        $this->actingAs($this->manager)
            ->get('/reports/supplier-performance')
            ->assertOk()
            ->assertSee('Low sample')
            ->assertSee('1 supplier(s) left out of the ranking')
            ->assertSee('Only 1 supplier(s) qualify for a ranking in this window')
            ->assertSee('Bottom 0', false);
    }

    public function test_a_window_with_nothing_measured_says_so_instead_of_printing_empty_charts(): void
    {
        $vendor = $this->makeSupplier('Metro Lumber Supply', 7);

        // Placed, but nothing has landed: the report has money to show and no verdict.
        $this->order($vendor, PurchaseOrder::STATUS_SENT, '2026-05-01', '2026-05-10', '2026-05-02', null, [[10, 0, 100.00]]);

        $this->actingAs($this->admin)
            ->get('/reports/supplier-performance?from=2026-05-01&to=2026-05-31')
            ->assertOk()
            ->assertDontSee('id="trendChart"', false)
            ->assertDontSee('id="supplierChart"', false)
            ->assertSee('Nothing has been received in this window.');
    }

    public function test_a_nonsense_date_falls_back_to_the_default_window(): void
    {
        $this->actingAs($this->admin)
            ->get('/reports/supplier-performance?from=%3Cscript%3E&to=2026-02-30')
            ->assertOk();

        // The default window is the six calendar months ending today, and the
        // form must show the window actually used rather than the junk sent.
        $expected = Carbon::now()->subMonths(5)->startOfMonth()->toDateString();

        $this->assertStringContainsString(
            'name="from" class="form-control" value="'.$expected.'"',
            $this->actingAs($this->admin)->get('/reports/supplier-performance')->getContent()
        );
    }

    /* ==================================================================
       Step 1c — the placeholders Part 6 replaces all read one source
       ================================================================== */

    public function test_the_accessor_the_widget_and_the_report_agree(): void
    {
        $vendor = $this->makeSupplier('Metro Lumber Supply', 7);

        $this->order($vendor, PurchaseOrder::STATUS_RECEIVED, '2026-06-01', '2026-06-10', '2026-06-02', '2026-06-08 16:00', [[10, 10, 100.00]]);
        $this->order($vendor, PurchaseOrder::STATUS_RECEIVED, '2026-07-01', '2026-07-10', '2026-07-02', '2026-07-20 16:00', [[10, 10, 100.00]]);

        $row = $vendor->performance();

        // Part 1's accessor, still the same value it always meant.
        $this->assertSame(50.0, $row['on_time_percentage']);
        $this->assertSame(50.0, $vendor->performance_rating);
        $this->assertSame('bg-danger', $vendor->performanceBadgeClass());

        // Part 5's polled widget, served from the same aggregate.
        $widget = $this->actingAs($this->admin)->getJson('/api/dashboard/supplier-performance')->assertOk()->json('data.0');

        $this->assertSame('Metro Lumber Supply', $widget['name']);
        $this->assertSame(50.0, (float) $widget['percent']);
        $this->assertSame('bg-danger', $widget['bar_class']);

        // Part 6's report page.
        $this->actingAs($this->admin)
            ->get('/reports/supplier-performance')
            ->assertOk()
            ->assertSee('50.0 %')
            ->assertSee('+5 days late', false);
    }

    /* ==================================================================
       Step 2 — who may read it
       ================================================================== */

    public function test_the_report_is_for_the_people_who_choose_vendors(): void
    {
        foreach ([$this->admin, $this->manager] as $user) {
            $this->actingAs($user)->get('/reports/supplier-performance')->assertOk();
        }

        foreach ([$this->warehouse, $this->staff] as $user) {
            $this->actingAs($user)
                ->get('/reports/supplier-performance')
                ->assertForbidden()
                ->assertDontSee('Supplier Performance', false);
        }
    }

    public function test_the_sidebar_links_it_for_the_roles_that_may_open_it(): void
    {
        $this->actingAs($this->admin)->get('/dashboard')->assertOk()
            ->assertSee('reports/supplier-performance', false);

        $this->actingAs($this->warehouse)->get('/dashboard')->assertOk()
            ->assertDontSee('reports/supplier-performance', false);
    }

    /* ==================================================================
       Helpers
       ================================================================== */

    private function makeUser(string $email, string $role): User
    {
        return User::create([
            'name'     => ucfirst($role).' User',
            'email'    => $email,
            'password' => 'password',
            'role'     => $role,
        ]);
    }

    private function makeSupplier(string $name, int $leadDays): Supplier
    {
        return Supplier::create([
            'name'               => $name,
            'contact_person'     => 'Romy Dela Cruz',
            'email'              => strtolower(str_replace(' ', '-', $name)).'@test',
            'phone'              => '0917-000-0000',
            'address'            => 'Quezon City',
            'payment_terms'      => 'Net 30',
            'lead_time_days'     => $leadDays,
            'minimum_order_amount' => 0,
            'is_active'          => true,
        ]);
    }

    /**
     * One order with its lines. A line is [ordered, received, unit price] plus
     * optionally which of the two products it is, since a purchase order cannot
     * carry the same SKU twice; a short delivery is then one number away from a
     * complete one.
     *
     * @param  array<int, array{0: int, 1: int, 2: float, 3?: int}>  $lines
     */
    private function order(
        Supplier $vendor,
        string $status,
        string $ordered,
        string $expected,
        ?string $sent,
        ?string $received,
        array $lines
    ): PurchaseOrder {
        $order = PurchaseOrder::create([
            'reference_number'       => 'PO-P6-'.str_pad((string) ++$this->sequence, 4, '0', STR_PAD_LEFT),
            'supplier_id'            => $vendor->id,
            'status'                 => $status,
            'order_date'             => $ordered,
            'expected_delivery_date' => $expected,
            'sent_at'                => $sent,
            'received_at'            => $received,
            'created_by'             => $this->manager->id,
            'is_auto_generated'      => false,
        ]);

        foreach ($lines as $line) {
            [$quantity, $arrived, $price] = $line;

            $order->items()->create([
                'product_id'        => $this->products[$line[3] ?? 0]->id,
                'quantity'          => $quantity,
                'unit_price'        => $price,
                'received_quantity' => $arrived,
            ]);
        }

        $order->load('items')->calculateTotal();
        $order->save();

        return $order;
    }

    /** @param  array<int, array{query: string, bindings: array<mixed>}>  $log */
    private function orderQueries(array $log): int
    {
        return count(array_filter($log, fn (array $q): bool => str_contains($q['query'], 'purchase_orders')));
    }
}
