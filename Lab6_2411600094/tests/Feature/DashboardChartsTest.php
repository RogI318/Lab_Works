<?php

namespace Tests\Feature;

use App\Http\Controllers\DashboardController;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardChartsTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_renders_charts_from_inventory_data(): void
    {
        $category = Category::create(['name' => 'Tools']);
        $brand = Brand::create(['name' => 'Acme']);

        Product::create([
            'name' => 'Hammer',
            'sku' => 'TOOL-001',
            'brand_id' => $brand->id,
            'category_id' => $category->id,
            'quantity' => 20,
            'reorder_level' => 5,
            'unit_price' => 2.50,
        ]);
        Product::create([
            'name' => 'Wrench',
            'sku' => 'TOOL-002',
            'brand_id' => $brand->id,
            'category_id' => $category->id,
            'quantity' => 3,
            'reorder_level' => 5,
            'unit_price' => 10.00,
        ]);
        Product::create([
            'name' => 'Drill',
            'sku' => 'TOOL-003',
            'brand_id' => $brand->id,
            'category_id' => $category->id,
            'quantity' => 0,
            'reorder_level' => 5,
            'unit_price' => 8.00,
        ]);

        $this->actingAs(User::factory()->create(['is_admin' => false]))
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Stock Status')
            ->assertSee('Products per Category')
            ->assertSee('Inventory Value by Category')
            ->assertSee('Lowest Stock vs Reorder Level')
            ->assertSee('statusChart')
            ->assertSee('categoryCountChart')
            ->assertSee('categoryValueChart')
            ->assertSee('stockLevelChart');

        $viewData = app(DashboardController::class)->index()->getData();

        $this->assertSame([1, 1, 1], $viewData['statusChart']['data']);
        $this->assertSame(['Tools'], $viewData['categoryCountChart']['labels']->all());
        $this->assertSame([3], $viewData['categoryCountChart']['data']->all());
        $this->assertEquals([80], $viewData['categoryValueChart']['data']->all());
        $this->assertSame(['Drill', 'Wrench', 'Hammer'], $viewData['stockLevelChart']['labels']->all());
        $this->assertSame([0, 3, 20], $viewData['stockLevelChart']['quantity']->all());
    }
}
