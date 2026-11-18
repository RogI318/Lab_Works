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
        return User::factory()->create(['is_admin' => true, 'role' => 'admin']);
    }
    private function payload(User $u, array $more = []): array
    {
        return $more + ['name' => $u->name, 'email' => $u->email, 'password' => '', 'password_confirmation' => '', 'role' => 'staff'];
    }
    public function test_password_change_waits_for_confirmation_then_applies(): void
    {
        Notification::fake();
        $u = User::factory()->create(['is_admin' => false]);
        $old = $u->password;
        $this->actingAs($this->admin())->put('/users/' . $u->id, $this->payload($u, ['password' => 'secure-new-password', 'password_confirmation' => 'secure-new-password']))->assertRedirect(route('users.index'));
        $this->assertSame($old, $u->fresh()->password);
        $url = URL::temporarySignedRoute('account.confirm-changes', now()->addHour(), ['user' => $u->id, 'token' => $u->fresh()->pendingChangesToken()]);
        $this->get($url)->assertRedirect('/login');
        $this->assertTrue(Hash::check('secure-new-password', $u->fresh()->password));
        Notification::assertSentOnDemand(ConfirmAccountChange::class);
    }
    public function test_email_change_waits_for_confirmation_then_applies(): void
    {
        Notification::fake();
        $u = User::factory()->create(['email' => 'old@example.com']);
        $this->actingAs($this->admin())->put('/users/' . $u->id, $this->payload($u, ['email' => 'new@example.com']))->assertRedirect(route('users.index'));
        $this->assertSame('old@example.com', $u->fresh()->email);
        $url = URL::temporarySignedRoute('account.confirm-changes', now()->addHour(), ['user' => $u->id, 'token' => $u->fresh()->pendingChangesToken()]);
        $this->get($url)->assertRedirect('/login');
        $this->assertSame('new@example.com', $u->fresh()->email);
    }
}
