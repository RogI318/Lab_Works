<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\InventoryOrder;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\SalesOrder;
use App\Models\Supplier;
use App\Models\User;
use App\Services\ReorderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ProcurementIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_purchase_orders_use_separate_tables_without_changing_existing_inventory(): void
    {
        Mail::fake();

        $user = User::factory()->create(['is_admin' => true]);
        $brand = Brand::create(['name' => 'Integration Brand']);
        $category = \App\Models\Category::create(['name' => 'Integration Category']);
        $supplier = Supplier::create([
            'name' => 'Integration Supplier',
            'contact_person' => 'Sales Representative',
            'email' => 'supplier@example.test',
            'phone' => '09171234567',
            'is_active' => true,
        ]);
        $product = Product::create([
            'name' => 'Integration Product',
            'sku' => 'INT-001',
            'brand_id' => $brand->id,
            'category_id' => $category->id,
            'supplier_id' => $supplier->id,
            'quantity' => 7,
            'reorder_level' => 2,
            'unit_price' => 125,
        ]);

        $supplier->suppliedProducts()->attach($product, [
            'supplier_price' => 90,
            'is_preferred' => true,
        ]);

        $order = PurchaseOrder::create([
            'reference_number' => PurchaseOrder::generateReferenceNumber(),
            'supplier_id' => $supplier->id,
            'status' => 'draft',
            'order_date' => today(),
            'created_by' => $user->id,
        ]);
        $order->items()->create([
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_price' => $product->costFor($supplier->id),
        ]);
        $order->calculateTotal();

        $this->assertSame(1, PurchaseOrder::count());
        $this->assertSame(0, InventoryOrder::count());
        $this->assertSame(7, $product->fresh()->quantity);
        $this->assertSame(90.0, $product->costFor($supplier->id));
        $this->assertSame(180.0, (float) $order->fresh()->subtotal);
    }

    public function test_reorder_quantity_rounds_up_to_full_supplier_packages(): void
    {
        $brand = Brand::create(['name' => 'Reorder Brand']);
        $category = \App\Models\Category::create(['name' => 'Reorder Category']);
        $supplier = Supplier::create([
            'name' => 'Reorder Supplier',
            'lead_time_days' => 3,
            'is_active' => true,
        ]);
        $product = Product::create([
            'name' => 'Packaged Product',
            'sku' => 'PACK-001',
            'brand_id' => $brand->id,
            'category_id' => $category->id,
            'supplier_id' => $supplier->id,
            'quantity' => 4,
            'reorder_level' => 5,
            'unit_price' => 100,
            'package_size' => 4,
            'min_order_quantity' => 1,
        ]);

        $service = new ReorderService();

        $this->assertSame(5, $service->calculateReorderPoint($product));
        $this->assertSame(8, $service->calculateOptimalOrderQuantity($product));
    }

    public function test_automatic_reordering_creates_one_draft_and_skips_duplicate_open_orders(): void
    {
        Mail::fake();

        $user = User::factory()->create(['is_admin' => true]);
        $brand = Brand::create(['name' => 'Auto Brand']);
        $category = \App\Models\Category::create(['name' => 'Auto Category']);
        $supplier = Supplier::create([
            'name' => 'Auto Supplier',
            'contact_person' => 'Sales Representative',
            'email' => 'auto-supplier@example.test',
            'lead_time_days' => 3,
            'is_active' => true,
        ]);
        $product = Product::create([
            'name' => 'Auto Reorder Product',
            'sku' => 'AUTO-001',
            'brand_id' => $brand->id,
            'category_id' => $category->id,
            'supplier_id' => $supplier->id,
            'quantity' => 4,
            'reorder_level' => 5,
            'unit_price' => 100,
            'package_size' => 4,
            'min_order_quantity' => 1,
        ]);
        $supplier->suppliedProducts()->attach($product, ['supplier_price' => 75]);

        $service = new ReorderService();
        $first = $service->generatePurchaseOrders($user);

        $this->assertCount(1, $first['orders']);
        $order = $first['orders']->first();
        $this->assertSame('draft', $order->status);
        $this->assertTrue($order->is_auto_generated);
        $this->assertSame(8, $order->items->first()->quantity);
        $this->assertSame(75.0, (float) $order->items->first()->unit_price);
        $this->assertSame(4, $product->fresh()->quantity);

        $second = $service->generatePurchaseOrders($user);
        $this->assertCount(0, $second['orders']);
        $this->assertSame(1, PurchaseOrder::count());
    }

    public function test_procurement_pages_render_without_replacing_legacy_order_routes(): void
    {
        Mail::fake();

        $user = User::factory()->create(['is_admin' => true]);
        $brand = Brand::create(['name' => 'Page Test Brand']);
        $category = \App\Models\Category::create(['name' => 'Page Test Category']);
        $supplier = Supplier::create([
            'name' => 'Page Test Supplier',
            'contact_person' => 'Sales Representative',
            'email' => 'page-test@example.test',
            'phone' => '09171234567',
            'is_active' => true,
        ]);
        $product = Product::create([
            'name' => 'Page Test Product',
            'sku' => 'PAGE-001',
            'brand_id' => $brand->id,
            'category_id' => $category->id,
            'supplier_id' => $supplier->id,
            'quantity' => 10,
            'reorder_level' => 2,
            'unit_price' => 50,
        ]);

        $this->actingAs($user)
            ->get(route('procurement.dashboard'))
            ->assertOk();

        $this->get(route('procurement.purchase-orders.index'))->assertOk();
        $this->get(route('procurement.purchase-orders.create'))->assertOk();
        $this->get(route('procurement.sales-orders.index'))->assertOk();
        $this->get(route('procurement.sales-orders.create'))->assertOk();
        $this->get(route('procurement.product-settings.index'))->assertOk();
        $this->get(route('procurement.suppliers.index'))->assertOk();
        $this->get(route('procurement.suppliers.show', $supplier))->assertOk();
        $this->get(route('procurement.supplier-performance'))->assertOk();
        $this->getJson('/api/procurement/dashboard')
            ->assertOk()
            ->assertJsonStructure(['open_purchase_orders', 'products_needing_reorder']);

        $purchaseOrder = PurchaseOrder::create([
            'reference_number' => PurchaseOrder::generateReferenceNumber(),
            'supplier_id' => $supplier->id,
            'status' => 'draft',
            'order_date' => today(),
            'created_by' => $user->id,
        ]);
        $purchaseOrder->items()->create([
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => 40,
        ]);
        $purchaseOrder->calculateTotal();
        $this->get(route('procurement.purchase-orders.show', $purchaseOrder))->assertOk();
        $this->get(route('procurement.purchase-orders.pdf', $purchaseOrder))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
        $this->get(route('procurement.exports.download', ['report' => 'purchase-orders', 'format' => 'csv']))
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $this->get(route('procurement.exports.download', ['report' => 'supplier-performance', 'format' => 'pdf']))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $salesOrder = \App\Models\SalesOrder::create([
            'reference_number' => \App\Models\SalesOrder::generateReferenceNumber(),
            'customer_name' => 'Test Customer',
            'status' => 'draft',
            'order_date' => today(),
            'created_by' => $user->id,
        ]);
        $salesOrder->items()->create([
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => 50,
        ]);
        $salesOrder->calculateTotal();
        $this->get(route('procurement.sales-orders.show', $salesOrder))->assertOk();
        $this->get(route('procurement.sales-orders.print', $salesOrder))->assertOk();

        $this->get(route('purchase-orders.index'))->assertOk();

        $this->assertSame(
            \App\Http\Controllers\InventoryOrderController::class . '@index',
            Route::getRoutes()->getByName('purchase-orders.index')->getActionName()
        );
        $this->assertNotSame(route('purchase-orders.index'), route('procurement.purchase-orders.index'));
    }

    public function test_receipts_and_deliveries_update_stock_through_the_existing_inventory_ledger(): void
    {
        Mail::fake();

        $user = User::factory()->create(['is_admin' => true]);
        $brand = Brand::create(['name' => 'Workflow Brand']);
        $category = \App\Models\Category::create(['name' => 'Workflow Category']);
        $supplier = Supplier::create([
            'name' => 'Workflow Supplier',
            'contact_person' => 'Sales Representative',
            'email' => 'workflow-supplier@example.test',
            'phone' => '09171234567',
            'is_active' => true,
        ]);
        $product = Product::create([
            'name' => 'Workflow Product',
            'sku' => 'WORK-001',
            'brand_id' => $brand->id,
            'category_id' => $category->id,
            'supplier_id' => $supplier->id,
            'quantity' => 5,
            'reorder_level' => 1,
            'unit_price' => 100,
        ]);
        $this->actingAs($user)
            ->put(route('procurement.suppliers.products.update', $supplier), [
                'products' => [[
                    'id' => $product->id,
                    'supplier_price' => 75,
                    'supplier_sku' => 'SUP-001',
                    'is_preferred' => 1,
                ]],
            ])
            ->assertRedirect();

        $this->patch(route('procurement.product-settings.update', $product), [
            'package_size' => 6,
            'min_order_quantity' => 3,
            'cost_price' => 70,
        ])->assertRedirect();

        $this->assertDatabaseHas('product_supplier', [
            'product_id' => $product->id,
            'supplier_id' => $supplier->id,
            'supplier_price' => 75,
            'supplier_sku' => 'SUP-001',
            'is_preferred' => true,
        ]);
        $this->assertSame(6, $product->fresh()->package_size);
        $this->assertSame(3, $product->fresh()->min_order_quantity);
        $this->assertSame(5, $product->fresh()->quantity);
        $this->actingAs($user);

        $purchaseOrder = PurchaseOrder::create([
            'reference_number' => PurchaseOrder::generateReferenceNumber(),
            'supplier_id' => $supplier->id,
            'status' => 'draft',
            'order_date' => today(),
            'created_by' => $user->id,
        ]);
        $purchaseItem = $purchaseOrder->items()->create([
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_price' => 80,
        ]);
        $purchaseOrder->calculateTotal();

        $this->post(route('procurement.purchase-orders.submit', $purchaseOrder));
        $this->post(route('procurement.purchase-orders.approve', $purchaseOrder));
        $this->post(route('procurement.purchase-orders.send', $purchaseOrder));
        $this->post(route('procurement.purchase-orders.receive', $purchaseOrder), [
            'received' => [$purchaseItem->id => 2],
        ])->assertRedirect();

        $this->assertSame('received', $purchaseOrder->fresh()->status);
        $this->assertSame(7, $product->fresh()->quantity);

        $salesOrder = SalesOrder::create([
            'reference_number' => SalesOrder::generateReferenceNumber(),
            'customer_name' => 'Workflow Customer',
            'customer_email' => 'customer@example.test',
            'status' => 'confirmed',
            'order_date' => today(),
            'created_by' => $user->id,
        ]);
        $salesOrder->items()->create([
            'product_id' => $product->id,
            'quantity' => 3,
            'unit_price' => 100,
        ]);
        $salesOrder->calculateTotal();

        $this->post(route('procurement.sales-orders.deliver', $salesOrder))->assertRedirect();

        $this->assertSame('delivered', $salesOrder->fresh()->status);
        $this->assertSame(4, $product->fresh()->quantity);
        $this->assertSame(0, InventoryOrder::count());
    }
}
