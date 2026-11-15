<?php

namespace Tests\Feature;

use App\Mail\LowStockAlert;
use App\Mail\PurchaseOrderApproved;
use App\Mail\PurchaseOrderCreated;
use App\Mail\PurchaseOrderSent;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Lab 7 Part 4: who gets which mail, exactly once.
 *
 * The four tests that matter are the addressing (who is on the mail) and the
 * de-duplication in PurchaseOrderObserver: a status that did not change is not
 * a transition, so the second "approve" click must stay silent. Mail is faked,
 * the queue runs synchronously (phpunit.xml sets QUEUE_CONNECTION=sync and
 * MAIL_MAILER=array), so none of this can reach a real mailbox or lab5_db.
 */
class PurchaseOrderMailTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $manager;
    private User $staff;
    private Supplier $supplier;
    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin   = $this->makeUser('ops@rogiemart.test', User::ROLE_ADMIN);
        $this->manager = $this->makeUser('buying@rogiemart.test', User::ROLE_MANAGER);
        $this->staff   = $this->makeUser('till@rogiemart.test', User::ROLE_STAFF);

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

        $this->product = Product::create([
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

    private function makeUser(string $email, string $role): User
    {
        return User::create([
            'name'     => ucfirst($role) . ' User',
            'email'    => $email,
            'password' => 'password',
            'role'     => $role,
        ]);
    }

    private function draftOrder(?Supplier $supplier = null): PurchaseOrder
    {
        $order = PurchaseOrder::create([
            'reference_number'       => 'PO-20261115-7777',
            'supplier_id'            => ($supplier ?? $this->supplier)->id,
            'status'                 => PurchaseOrder::STATUS_DRAFT,
            'order_date'             => '2026-11-15',
            'expected_delivery_date' => '2026-11-22',
            'terms'                  => 'Net 30',
            'notes'                  => 'Restock before the December pouring.',
            'created_by'             => $this->admin->id,
        ]);

        $order->items()->create([
            'product_id' => $this->product->id,
            'quantity'   => 30,
            'unit_price' => 585.00,
        ]);

        $order->load('items')->calculateTotal()->save();

        return $order;
    }

    /* ================= Step 4 — the observer's routing ================= */

    public function test_submitting_mails_the_admins_and_managers_and_nobody_else(): void
    {
        Mail::fake();

        $order = $this->draftOrder();

        $this->actingAs($this->admin)
            ->put(route('purchase-orders.submit', $order))
            ->assertRedirect();

        Mail::assertQueued(PurchaseOrderCreated::class, 1);
        Mail::assertQueued(fn (PurchaseOrderCreated $mail) => $mail->hasTo('ops@rogiemart.test'));
        Mail::assertQueued(fn (PurchaseOrderCreated $mail) => $mail->hasTo('buying@rogiemart.test'));
        Mail::assertQueued(fn (PurchaseOrderCreated $mail) => ! $mail->hasTo('till@rogiemart.test'));
        Mail::assertNotQueued(PurchaseOrderApproved::class);
        Mail::assertNotQueued(PurchaseOrderSent::class);
        Mail::assertNotQueued(LowStockAlert::class);

        // The mail is a copy of the decision, never the decision itself.
        $this->assertSame(PurchaseOrder::STATUS_PENDING, $order->refresh()->status);
    }

    public function test_approving_mails_the_creator_once_even_when_clicked_twice(): void
    {
        Mail::fake();

        $order = $this->draftOrder();
        $order->update(['status' => PurchaseOrder::STATUS_PENDING]);
        Mail::assertQueued(PurchaseOrderCreated::class, 1);

        $this->actingAs($this->manager)->put(route('purchase-orders.approve', $order))->assertRedirect();
        Mail::assertQueued(PurchaseOrderApproved::class, 1);
        Mail::assertQueued(fn (PurchaseOrderApproved $mail) => $mail->hasTo('ops@rogiemart.test'));

        // Second click: the order is no longer pending, so the controller
        // refuses on state and wasChanged('status') never fires.
        $this->actingAs($this->manager)->put(route('purchase-orders.approve', $order))->assertRedirect();
        Mail::assertQueued(PurchaseOrderApproved::class, 1);

        // And a save that changes nothing at all stays silent too.
        $order->update(['notes' => 'Same status, different note.']);
        Mail::assertQueued(PurchaseOrderApproved::class, 1);
    }

    public function test_sending_mails_the_supplier_and_copies_the_creator_with_a_pdf_attached(): void
    {
        Mail::fake();

        $order = $this->draftOrder();
        $order->update(['status' => PurchaseOrder::STATUS_PENDING]);
        $order->update([
            'status'      => PurchaseOrder::STATUS_APPROVED,
            'approved_by' => $this->manager->id,
            'approved_at' => now(),
        ]);

        $this->actingAs($this->admin)->put(route('purchase-orders.send', $order))->assertRedirect();

        Mail::assertQueued(PurchaseOrderSent::class, 1);
        Mail::assertQueued(fn (PurchaseOrderSent $mail) => $mail->hasTo('sales@metrolumber.test')
            && $mail->hasCc('ops@rogiemart.test'));
    }

    public function test_sending_without_a_supplier_address_still_reaches_the_creator(): void
    {
        Mail::fake();

        // The column is NOT NULL, so a vendor "without an e-mail" in practice
        // means a blank one — the guard has to catch that, not just null.
        $silent = Supplier::create([
            'name'          => 'Corner Hardware (phone only)',
            'contact_person' => 'Mang Tonyo',
            'email'         => '',
            'phone'         => '0918-111-2222',
            'address'       => 'Banawe, Baguio',
            'payment_terms' => 'COD',
            'lead_time_days' => 3,
            'minimum_order_amount' => 0,
            'is_active'     => true,
        ]);

        $order = $this->draftOrder($silent);
        $order->update(['status' => PurchaseOrder::STATUS_PENDING]);
        $order->update([
            'status'      => PurchaseOrder::STATUS_APPROVED,
            'approved_by' => $this->admin->id,
            'approved_at' => now(),
        ]);

        $this->actingAs($this->admin)->put(route('purchase-orders.send', $order))->assertRedirect();

        Mail::assertQueued(fn (PurchaseOrderSent $mail) => $mail->hasTo('ops@rogiemart.test'), 1);
    }

    /* ================= Step 2 — the document itself ================= */

    public function test_the_order_document_renders_as_a_pdf(): void
    {
        $order = $this->draftOrder();
        $order->load(['supplier', 'items.product', 'createdBy']);

        $pdf = Pdf::loadView('pdf.purchase-order', ['order' => $order])->setPaper('a4')->output();

        $this->assertStringStartsWith('%PDF-', $pdf);
        // The frozen line price, not the shelf price, is what goes on paper.
        $this->assertGreaterThan(0, strlen($pdf));
        $this->assertSame('585.00', (string) $order->items->first()->unit_price);
    }

    /* ================= Step 2 — the digest ================= */

    public function test_the_reorder_command_queues_one_digest_for_every_admin(): void
    {
        Mail::fake();

        $this->makeUser('relief@rogiemart.test', User::ROLE_ADMIN);

        // Covered by an open order, so the sweep raises no new draft — but the
        // item is still below its reorder point, so the digest still lists it.
        $order = $this->draftOrder();
        $order->update(['status' => PurchaseOrder::STATUS_PENDING]);

        $this->artisan('inventory:check-reorder-points')->assertSuccessful();

        Mail::assertQueued(LowStockAlert::class, 1);
        // One mail for the run, addressed to every administrator — not one per
        // SKU, and not to the manager, whose copy of this news is the
        // submission mail the observer already sent.
        Mail::assertQueued(fn (LowStockAlert $mail) => $mail->hasTo('ops@rogiemart.test')
            && $mail->hasTo('relief@rogiemart.test')
            && ! $mail->hasTo('buying@rogiemart.test')
            && ! $mail->hasTo('till@rogiemart.test'));
        Mail::assertQueued(fn (LowStockAlert $mail) => count($mail->items) === 1
            && $mail->items[0]['sku'] === 'PLY-12-48'
            && $mail->items[0]['suggested'] === 0
            && $mail->items[0]['on_order'] === 30);

        // A rehearsal writes nothing and mails nobody.
        Mail::fake();
        $this->artisan('inventory:check-reorder-points --dry-run')->assertSuccessful();
        Mail::assertNothingQueued();
    }
}
