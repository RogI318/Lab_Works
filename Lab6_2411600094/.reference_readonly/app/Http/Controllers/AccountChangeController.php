<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;

class AccountChangeController extends Controller
{
    public function confirm(User $user, string $token): RedirectResponse
    {
        if (! $user->hasPendingChanges() || ! hash_equals($user->pendingChangesToken(), $token)) {
            return redirect()->route('login')
                ->withErrors(['email' => 'This confirmation link is no longer valid. Ask an admin to send a new one.']);
        }

        // Another account may have taken the address while the change was waiting.
        if ($user->pending_email && User::where('email', $user->pending_email)->whereKeyNot($user->getKey())->exists()) {
            $user->forceFill(['pending_email' => null, 'pending_password' => null, 'pending_changes_at' => null])->save();

            return redirect()->route('login')
                ->withErrors(['email' => 'That email address is already in use, so the change was cancelled.']);
        }

        $changes = [];

        if ($user->pending_email) {
            $user->email = $user->pending_email;
            // The link was delivered to this address, so it is now verified.
            $user->email_verified_at = now();
            $changes[] = 'email';
        }

        if ($user->pending_password) {
            // Already hashed when the admin saved it; the 'hashed' cast leaves a valid hash alone.
            $user->password = $user->pending_password;
            $changes[] = 'password';
        }

        $user->forceFill(['pending_email' => null, 'pending_password' => null, 'pending_changes_at' => null])->save();

        return redirect()->route('login')
            ->with('status', 'Your '.implode(' and ', $changes).' change has been confirmed. You can sign in now.');
    }
}
