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
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Lab 7 Part 5: the procurement dashboard.
 *
 * Three questions, in the order the sheet asks them.
 *   1. Who sees the widgets?  admin / manager / warehouse yes, staff no — and
 *      "no" means the section is not in the HTML at all, not hidden by CSS,
 *      because the @can is also what stops the composers from querying.
 *   2. Do the endpoints agree with the cards?  Same service behind both, so a
 *      test that reads the JSON and reads the page has to see one answer.
 *   3. Is the cache real?  A second call must issue no purchase_orders query,
 *      and approving an order must make the next call see one fewer — the
 *      Part 4 observer, not the TTL, is what keeps the widget honest.
 *
 * phpunit.xml runs this on sqlite with CACHE_STORE=array and MAIL_MAILER=array,
 * so nothing here touches lab5_db, a mailbox or a queue.
 */
class ProcurementDashboardTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $manager;
    private User $warehouse;
    private User $staff;
    private Supplier $supplier;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin     = $this->makeUser('ops@rogiemart.test', User::ROLE_ADMIN);
        $this->manager   = $this->makeUser('buying@rogiemart.test', User::ROLE_MANAGER);
        $this->warehouse = $this->makeUser('dock@rogiemart.test', User::ROLE_WAREHOUSE);
        $this->staff     = $this->makeUser('till@rogiemart.test', User::ROLE_STAFF);

        $this->supplier = Supplier::create([
            'name'          => 'Metro Lumber Supply',
            'contact_person' => 'Rosa Dela Cruz',
            'email'         => 'sales@metrolumber.test',
            'phone'         => '0917-000-0000',
            'address'       => 'Gov. Ave, Quezon City',
            'payment_terms' => 'Net 30',
            'lead_time_days' => 7,
            'minimum_order_amount' => 0,
            'is_active'     => true,
        ]);

        Product::create([
            'name'          => 'Plywood 12mm 4x8',
            'sku'           => 'PLY-12-48',
            'brand_id'      => Brand::create(['name' => 'Cruzbuilt'])->id,
            'category_id'   => Category::create(['name' => 'Lumber'])->id,
            'supplier_id'   => $this->supplier->id,
            'quantity'      => 4,
            'reorder_level' => 20,
            'unit_price'    => 640.00,
        ]);
    }

    /* ==================================================================
       Step 3 — who sees the Procurement Overview
       ================================================================== */

    public function test_the_three_procurement_roles_see_all_four_widgets(): void
    {
        foreach ([$this->admin, $this->manager, $this->warehouse] as $user) {
            $page = $this->actingAs($user)->get('/dashboard')->assertOk();

            foreach (['pending-orders', 'recent-orders', 'supplier-performance', 'reorder-alerts'] as $widget) {
                $page->assertSee('data-widget="'.$widget.'"', false);
            }

            $page->assertSee('Procurement Overview');
        }
    }

    public function test_staff_sees_no_widgets_no_section_and_no_poll_config(): void
    {
        $page = $this->actingAs($this->staff)->get('/dashboard')->assertOk();

        $page->assertDontSee('Procurement Overview')
             ->assertDontSee('data-widget="pending-orders"', false)
             ->assertDontSee('data-widget="reorder-alerts"', false)
             // Lab 6 Part 7 still belongs to staff; only Part 5 is gated.
             ->assertSee('statProducts', false);
    }

    public function test_the_lab6_dashboard_content_still_renders_alongside_the_widgets(): void
    {
        $this->actingAs($this->admin)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Low Stock Alerts')
            ->assertSee('Recently Added Products')
            ->assertSee('Latest Stock Movements')
            ->assertSee('Brands')
            ->assertSee('Categories')
            ->assertSee('statOutOfStock', false)
            ->assertSee('LAB6_REALTIME', false);
    }

    /* ==================================================================
       Step 4 — the endpoints
       ================================================================== */

    public function test_staff_is_refused_the_widget_json_with_a_403(): void
    {
        foreach (['pending-orders', 'recent-orders', 'supplier-performance', 'reorder-alerts'] as $widget) {
            $this->actingAs($this->staff)
                ->getJson('/api/dashboard/'.$widget)
                ->assertForbidden();
        }
    }

    public function test_the_endpoints_answer_the_procurement_roles(): void
    {
        $this->order(PurchaseOrder::STATUS_PENDING, 10, 100.00);

        $json = $this->actingAs($this->manager)
            ->getJson('/api/dashboard/pending-orders')
            ->assertOk()
            ->assertJsonStructure(['data' => ['count', 'value'], 'meta' => ['last_updated']])
            ->json();

        $this->assertSame(1, $json['data']['count']);
        $this->assertSame(1120.0, (float) $json['data']['value']);   // 1000 + 12 % VAT
    }

    public function test_the_widget_json_and_the_rendered_card_agree(): void
    {
        $this->order(PurchaseOrder::STATUS_PENDING, 5, 200.00);

        $count = $this->actingAs($this->admin)->getJson('/api/dashboard/pending-orders')->json('data.count');

        $this->actingAs($this->admin)
            ->get('/dashboard')
            ->assertSee('id="pendingCount"', false)
            ->assertSee('>'.$count.'</div>', false);
    }

    public function test_the_recent_widget_lists_the_newest_five_orders_with_their_supplier(): void
    {
        foreach (range(1, 6) as $n) {
            $this->order(PurchaseOrder::STATUS_DRAFT, 1, 10.00, now()->subDays(10)->addDays($n));
        }

        $rows = $this->actingAs($this->warehouse)
            ->getJson('/api/dashboard/recent-orders')
            ->assertOk()
            ->assertJsonCount(5, 'data')
            ->json('data');

        $this->assertSame('Metro Lumber Supply', $rows[0]['supplier']);
        $this->assertSame('Draft', $rows[0]['status_label']);
    }

    public function test_the_reorder_widget_shows_the_quantity_the_engine_would_order(): void
    {
        $row = $this->actingAs($this->admin)
            ->getJson('/api/dashboard/reorder-alerts')
            ->assertOk()
            ->json('data.0');

        $this->assertSame('PLY-12-48', $row['sku']);
        $this->assertSame(4, $row['on_hand']);
        $this->assertSame(20, $row['point']);      // never below reorder_level
        $this->assertSame(16, $row['suggested']);  // up to the target, nothing on order
        $this->assertSame('Metro Lumber Supply', $row['supplier']);
    }

    public function test_the_performance_widget_ranks_on_time_deliveries(): void
    {
        $late = Supplier::create([
            'name' => 'Late Metals', 'contact_person' => 'Romy Dela Cruz',
            'email' => 'late@metals.test', 'phone' => '0',
            'address' => 'x', 'payment_terms' => 'COD', 'lead_time_days' => 3,
            'minimum_order_amount' => 0, 'is_active' => true,
        ]);

        $this->received($this->supplier->id, '2026-01-10', '2026-01-09 16:30');
        $this->received($this->supplier->id, '2026-01-20', '2026-01-20 09:00');
        $this->received($late->id, '2026-01-10', '2026-01-14 09:00');

        $rows = $this->actingAs($this->manager)
            ->getJson('/api/dashboard/supplier-performance')
            ->assertOk()
            ->json('data');

        $this->assertSame('Metro Lumber Supply', $rows[0]['name']);
        $this->assertSame(100.0, (float) $rows[0]['percent']);   // the 9 Jan 4 pm drop-off counts
        $this->assertSame('bg-success', $rows[0]['bar_class']);
        $this->assertSame('Late Metals', $rows[1]['name']);
        $this->assertSame(0.0, (float) $rows[1]['percent']);
        $this->assertSame('bg-danger', $rows[1]['bar_class']);
    }

    /* ==================================================================
       Step 1 — the cache, and the observer that clears it
       ================================================================== */

    public function test_a_warm_widget_answers_without_touching_the_orders_table(): void
    {
        $this->order(PurchaseOrder::STATUS_PENDING, 1, 10.00);

        $this->actingAs($this->admin)->getJson('/api/dashboard/pending-orders')->assertOk();

        DB::flushQueryLog();
        DB::enableQueryLog();

        $this->actingAs($this->admin)->getJson('/api/dashboard/pending-orders')->assertOk();

        $this->assertSame([], array_values(array_filter(
            DB::getQueryLog(),
            fn (array $q): bool => str_contains($q['query'], 'purchase_orders')
        )));
    }

    public function test_approving_an_order_clears_the_cache_the_widget_reads(): void
    {
        $order = $this->order(PurchaseOrder::STATUS_PENDING, 1, 10.00);

        $before = $this->actingAs($this->admin)->getJson('/api/dashboard/pending-orders')->json('data.count');

        $order->status      = PurchaseOrder::STATUS_APPROVED;
        $order->approved_by = $this->admin->id;
        $order->approved_at = now();
        $order->save();

        $after = $this->actingAs($this->admin)->getJson('/api/dashboard/pending-orders')->json('data.count');

        $this->assertSame(1, $before);
        $this->assertSame(0, $after);
    }

    public function test_the_widget_rows_are_cached_under_the_configured_key(): void
    {
        $key = config('procurement.cache.keys.pending_orders');

        $this->order(PurchaseOrder::STATUS_PENDING, 1, 10.00);
        $this->assertNull(Cache::get($key));

        $this->actingAs($this->admin)->getJson('/api/dashboard/pending-orders')->assertOk();

        $cached = Cache::get($key);

        // What sits in the cache is the plain shaped array, not a collection of
        // PurchaseOrder models carrying relations nobody prints.
        $this->assertSame(['count', 'value', 'generated_at'], array_keys($cached));
        $this->assertSame(1, $cached['count']);
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

    /** One-line order with a real total, dated when told. */
    private function order(string $status, int $quantity, float $unitPrice, ?Carbon $date = null): PurchaseOrder
    {
        $order = PurchaseOrder::create([
            'reference_number'       => 'PO-TEST-'.str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT),
            'supplier_id'            => $this->supplier->id,
            'status'                 => $status,
            'order_date'             => ($date ?? now())->toDateString(),
            'expected_delivery_date' => ($date ?? now())->copy()->addDays(7)->toDateString(),
            'created_by'             => $this->manager->id,
            'is_auto_generated'      => false,
        ]);

        $order->items()->create([
            'product_id' => Product::first()->id,
            'quantity'   => $quantity,
            'unit_price' => $unitPrice,
        ]);

        $order->load('items')->calculateTotal()->save();

        return $order;
    }

    private function received(int $supplierId, string $promised, string $landed): PurchaseOrder
    {
        $order = PurchaseOrder::create([
            'reference_number'       => 'PO-RCV-'.str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT),
            'supplier_id'            => $supplierId,
            'status'                 => PurchaseOrder::STATUS_RECEIVED,
            'order_date'             => Carbon::parse($promised)->subDays(7)->toDateString(),
            'expected_delivery_date' => $promised,
            'received_at'            => $landed,
            'created_by'             => $this->manager->id,
        ]);

        $order->items()->create([
            'product_id'         => Product::first()->id,
            'quantity'           => 10,
            'unit_price'         => 100.00,
            'received_quantity'  => 10,
        ]);

        $order->load('items')->calculateTotal()->save();

        return $order;
    }
}
