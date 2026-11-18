<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\ConfirmAccountChange;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class UserChangeConfirmationTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function payload(User $user, array $overrides = []): array
    {
        return $overrides + [
            'name' => $user->name,
            'email' => $user->email,
            'password' => '',
            'password_confirmation' => '',
            'role' => $user->role,
        ];
    }

    private function confirmUrl(User $user): string
    {
        $user = $user->fresh();

        return URL::temporarySignedRoute('account.confirm-changes', now()->addHour(), [
            'user' => $user->id,
            'token' => $user->pendingChangesToken(),
        ]);
    }

    public function test_admin_can_assign_a_role_without_any_email(): void
    {
        Notification::fake();
        $user = User::factory()->create(['role' => 'staff']);

        $this->actingAs($this->admin())->put("/users/{$user->id}", $this->payload($user, ['name' => 'Renamed', 'role' => 'admin']))
            ->assertRedirect('/users');

        $this->assertSame('admin', $user->fresh()->role);
        $this->assertSame('Renamed', $user->fresh()->name);
        Notification::assertNothingSent();
    }

    public function test_password_change_waits_for_confirmation_then_applies(): void
    {
        Notification::fake();
        $user = User::factory()->create(['role' => 'staff']);
        $oldHash = $user->password;

        $this->actingAs($this->admin())->put("/users/{$user->id}", $this->payload($user, [
            'password' => 'a-brand-new-password-1', 'password_confirmation' => 'a-brand-new-password-1',
        ]))->assertRedirect('/users');

        $this->assertSame($oldHash, $user->fresh()->password);
        Notification::assertSentOnDemand(ConfirmAccountChange::class,
            fn ($n, $channels, $notifiable) => $notifiable->routes['mail'] === $user->email);

        auth()->logout();
        $this->get($this->confirmUrl($user))->assertRedirect('/login');

        $this->assertTrue(Hash::check('a-brand-new-password-1', $user->fresh()->password));
        $this->assertFalse($user->fresh()->hasPendingChanges());
    }

    public function test_email_change_waits_for_confirmation_then_applies(): void
    {
        Notification::fake();
        $user = User::factory()->create(['role' => 'staff', 'email' => 'old@example.com']);

        $this->actingAs($this->admin())->put("/users/{$user->id}", $this->payload($user, ['email' => 'new@example.com']))
            ->assertRedirect('/users');

        $this->assertSame('old@example.com', $user->fresh()->email);
        $this->assertSame('new@example.com', $user->fresh()->pending_email);
        Notification::assertSentOnDemand(ConfirmAccountChange::class,
            fn ($n, $channels, $notifiable) => $notifiable->routes['mail'] === 'new@example.com');

        auth()->logout();
        $this->get($this->confirmUrl($user))->assertRedirect('/login');

        $this->assertSame('new@example.com', $user->fresh()->email);
        $this->assertNotNull($user->fresh()->email_verified_at);
        $this->assertNull($user->fresh()->pending_email);
    }

    public function test_unsigned_or_outdated_links_do_not_apply_changes(): void
    {
        $user = User::factory()->create(['role' => 'staff', 'email' => 'old@example.com']);
        $user->forceFill(['pending_email' => 'first@example.com'])->save();
        $oldToken = $user->fresh()->pendingChangesToken();

        $this->get("/account/confirm-changes/{$user->id}/{$oldToken}")->assertForbidden();

        // A newer edit changes the pending values, which kills the earlier link.
        $user->forceFill(['pending_email' => 'second@example.com'])->save();
        $stale = URL::temporarySignedRoute('account.confirm-changes', now()->addHour(), ['user' => $user->id, 'token' => $oldToken]);
        $this->get($stale)->assertRedirect('/login');

        $this->assertSame('old@example.com', $user->fresh()->email);
    }
}
