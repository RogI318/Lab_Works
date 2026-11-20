<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryOrderRouteTest extends TestCase
{
    use RefreshDatabase;

    public function test_missing_order_redirects_to_dashboard(): void
    {
        $user = User::factory()->create(['is_admin' => true]);

        $this->actingAs($user)
            ->get('/orders/1')
            ->assertRedirect(route('dashboard'));

        $this->get(route('dashboard'))->assertOk();
    }
}
