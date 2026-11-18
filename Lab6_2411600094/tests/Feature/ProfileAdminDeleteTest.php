<?php
namespace Tests\Feature;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class ProfileAdminDeleteTest extends TestCase
{
    use RefreshDatabase;
    public function test_admin_cannot_delete_their_account_with_a_direct_request(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin)->delete('/profile', ['password' => 'password'])->assertForbidden();
        $this->assertNotNull($admin->fresh());
    }
}
