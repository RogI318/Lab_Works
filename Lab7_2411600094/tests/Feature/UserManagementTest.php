<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_registration_no_longer_exists(): void
    {
        $this->get('/register')->assertNotFound();
        $this->post('/register', [
            'name' => 'Test', 'email' => 'x@example.com',
            'password' => 'password', 'password_confirmation' => 'password',
        ])->assertNotFound();
    }

    public function test_guests_are_sent_to_login(): void
    {
        $this->get('/users')->assertRedirect('/login');
    }

    public function test_non_admins_get_403(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $this->actingAs($user)->get('/users')->assertForbidden();
        $this->actingAs($user)->post('/users', [])->assertForbidden();
    }

    public function test_admin_can_open_the_page_and_create_a_user(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)->get('/users')->assertOk();

        $this->actingAs($admin)->post('/users', [
            'name'                  => 'New Staff',
            'email'                 => 'staff@example.com',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
        ])->assertRedirect(route('users.index'));

        $this->assertDatabaseHas('users', ['email' => 'staff@example.com', 'is_admin' => false]);
    }

    public function test_admin_cannot_delete_themselves(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)->delete("/users/{$admin->id}")->assertSessionHas('error');
        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }
}