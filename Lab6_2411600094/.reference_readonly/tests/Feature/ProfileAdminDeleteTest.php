<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileAdminDeleteTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_does_not_see_the_delete_account_section(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get('/profile')
            ->assertOk()
            ->assertDontSee('Delete Account');
    }

    public function test_staff_still_sees_the_delete_account_section(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);

        $this->actingAs($staff)->get('/profile')
            ->assertOk()
            ->assertSee('Delete Account');
    }

    public function test_admin_cannot_delete_their_account_with_a_direct_request(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->delete('/profile', ['password' => 'password'])
            ->assertForbidden();

        $this->assertNotNull($admin->fresh());
    }
}
