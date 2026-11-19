<?php
namespace App\Http\Controllers;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
class AccountChangeController extends Controller
{
    public function confirm(User $user, string $token): RedirectResponse
    {
        if (!$user->hasPendingChanges() || !hash_equals($user->pendingChangesToken(), $token))
            return redirect()->route('login')->withErrors(['email' => 'This confirmation link is invalid or expired.']);
        if ($user->pending_email && User::where('email', $user->pending_email)->whereKeyNot($user->id)->exists())
            return redirect()->route('login')->withErrors(['email' => 'That email address is already in use.']);
        if ($user->pending_email) {
            $user->email = $user->pending_email;
            $user->email_verified_at = now();
        }
        if ($user->pending_password)
            $user->password = $user->pending_password;
        $user->forceFill(['pending_email' => null, 'pending_password' => null, 'pending_changes_at' => null])->save();
        return redirect()->route('login')->with('status', 'Account changes confirmed.');
    }
}
