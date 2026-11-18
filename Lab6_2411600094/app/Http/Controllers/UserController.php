<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use App\Notifications\AccountUpdated;
use App\Notifications\AccountDeleted;
use App\Notifications\ConfirmAccountChange;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index()
    {
        $users = User::orderBy('id')->paginate(10)->withQueryString();

        return view('users.index', compact('users'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', 'confirmed', Password::defaults()],
            'role' => ['required', Rule::in(['staff', 'admin'])],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],   // hashed by the User model cast
            'is_admin' => $data['role'] === 'admin',
            'role' => $data['role'],
            'role' => $data['role'],
            'email_verified_at' => now(),
        ]);

        return redirect()
            ->route('users.index')
            ->with('success', "User \"{$user->name}\" added successfully.");
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'confirmed', Password::defaults()],
            'role' => ['required', Rule::in(['staff', 'admin'])],
        ]);

        $user->name = $data['name'];
        $emailChanged = strtolower($data['email']) !== strtolower($user->email);
        $passwordChanged = !empty($data['password']);

        // Leave the password alone unless a new one was typed in.
        $changes = [];
        if ($emailChanged) {
            $user->pending_email = $data['email'];
            $changes[] = 'email';
        }
        if ($passwordChanged) {
            $user->pending_password = Hash::make($data['password']);
            $changes[] = 'password';
        }
        $user->pending_changes_at = $changes ? now() : null;

        // An admin cannot take away their own admin rights (prevents locking everyone out).
        if (!$user->is(auth()->user())) {
            $user->role = $data['role'];
            $user->is_admin = $data['role'] === 'admin';
        }

        $user->save();
        if ($user->wasChanged(['name', 'role']) && $user->id !== auth()->id()) {
            try {
                $user->notify(new AccountUpdated(array_values(array_filter(['name' => $user->wasChanged('name') ? 'name' : null, 'role' => $user->wasChanged('role') ? 'role' : null])), auth()->user()->name));
            } catch (\Throwable $e) {
                report($e);
            }
        }
        if ($changes) {
            try {
                Notification::route('mail', $user->pending_email ?? $user->email)->notify(new ConfirmAccountChange($user, $changes));
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return redirect()
            ->route('users.index')
            ->with('success', "User \"{$user->name}\" updated successfully.");
    }

    public function destroy(User $user)
    {
        if ($user->is(auth()->user())) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        // Administrator accounts are protected: an admin cannot delete another admin.
        if ($user->is_admin) {
            return back()->with('error', 'Administrator accounts cannot be deleted.');
        }

        $name = $user->name;
        $email = $user->email;

        // Lab 6: the ledger keeps its rows (user_id is nulled), but this user's
        // inbox would otherwise be orphaned in the notifications table.
        $user->notifications()->delete();
        $user->delete();
        try {
            Notification::route('mail', $email)->notify(new AccountDeleted($name, auth()->user()->name));
        } catch (\Throwable $e) {
            report($e);
        }

        return back()->with('success', "User \"{$name}\" deleted successfully.");
    }
}
