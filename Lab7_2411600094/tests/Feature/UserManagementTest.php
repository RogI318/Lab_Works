<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_registration_creates_a_non_admin_user(): void
    {
        $this->post('/register', [
            'name' => 'Test',
            'email' => 'x@example.com',
            'password' => 'password', 'password_confirmation' => 'password',
        ])->assertRedirect(route('verification.notice', absolute: false));

        $this->assertDatabaseHas('users', [
            'email' => 'x@example.com',
            'is_admin' => false,
        ]);
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

        $response = $this->actingAs($admin)->get('/users')->assertOk()
            ->assertSee('Staff')
            ->assertSee('Admin')
            ->assertSee('type="radio"', false);

        $html = $response->getContent();
        $this->assertSame(0, preg_match('/<input[^>]*id="user-add-staff"[^>]*checked/i', $html));
        $this->assertSame(0, preg_match('/<input[^>]*id="user-add-admin"[^>]*checked/i', $html));

        $this->actingAs($admin)->post('/users', [
            'name'                  => 'New Staff',
            'email'                 => 'staff@example.com',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
            'role'                  => 'staff',
        ])->assertRedirect(route('users.index'));

        $this->assertDatabaseHas('users', ['email' => 'staff@example.com', 'is_admin' => false]);
    }

    public function test_admin_can_change_another_users_role(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $staff = User::factory()->create(['is_admin' => false]);

        $this->actingAs($admin)->put(route('users.update', $staff), [
            'name' => $staff->name,
            'email' => $staff->email,
            'role' => 'admin',
        ])->assertRedirect(route('users.index'));

        $this->assertDatabaseHas('users', ['id' => $staff->id, 'is_admin' => true]);

        $this->actingAs($admin)->put(route('users.update', $staff), [
            'name' => $staff->name,
            'email' => $staff->email,
            'role' => 'staff',
        ])->assertRedirect(route('users.index'));

        $this->assertDatabaseHas('users', ['id' => $staff->id, 'is_admin' => false]);
    }

    public function test_admin_cannot_delete_themselves(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)->delete("/users/{$admin->id}")->assertSessionHas('error');
        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }
}