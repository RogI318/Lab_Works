<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Notifications\AccountDeleted;
use App\Notifications\AccountUpdated;
use App\Notifications\ConfirmAccountChange;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;

class UserController extends Controller
{
    // index() - table of all users, newest first (10 per page)
    public function index()
    {
        $this->authorizeAdmin();

        $users = User::latest('id')->paginate(10)->withQueryString();

        return view('users.index', compact('users'));
    }

    // create() - show the Add User form
    public function create()
    {
        $this->authorizeAdmin();

        return view('users.create');
    }

    // store() - save a new user
    public function store(Request $request)
    {
        $this->authorizeAdmin();

        $validated = $this->validateUser($request);

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
            'password' => $validated['password'], // hashed automatically by the User model
            'email_verified_at' => now(),
        ]);

        return redirect()->route('users.index')
            ->with('success', 'User added successfully.');
    }

    // show() - details of one user
    public function show(User $user)
    {
        $this->authorizeAdmin();

        return view('users.show', compact('user'));
    }

    // edit() - show the Edit User form
    public function edit(User $user)
    {
        $this->authorizeAdmin();

        return view('users.edit', compact('user'));
    }

    // update() - save changes to a user
    public function update(Request $request, User $user)
    {
        $this->authorizeAdmin();

        $validated = $this->validateUser($request, $user);

        // Admins cannot change their own role (so there is always an admin left)
        if ($user->id === auth()->id()) {
            $validated['role'] = $user->role;
        }

        $emailChanged = strtolower($validated['email']) !== strtolower($user->email);
        $passwordChanged = ! empty($validated['password']);

        // Name and role are saved right away.
        $user->fill(['name' => $validated['name'], 'role' => $validated['role']]);

        // Email and password only change after the user confirms them by email.
        $changes = [];

        if ($emailChanged) {
            $user->pending_email = $validated['email'];
            $changes[] = 'email address';
        } else {
            // The admin put the old address back, so drop any waiting email change.
            $user->pending_email = null;
        }

        if ($passwordChanged) {
            $user->pending_password = Hash::make($validated['password']);
            $changes[] = 'password';
        }

        $user->pending_changes_at = $user->hasPendingChanges() ? now() : null;
        $user->save();

        // LAB 6: tell the user by email what the admin changed (name / role)
        $changedNow = [];
        if ($user->wasChanged('name')) {
            $changedNow[] = 'name';
        }
        if ($user->wasChanged('role')) {
            $changedNow[] = 'role';
        }
        if ($changedNow && $user->id !== auth()->id()) {
            $adminName = auth()->user()->name;
            $this->sendMail(fn () => $user->notify(new AccountUpdated($changedNow, $adminName)));
        }

        $message = 'User updated successfully.';

        if ($changes) {
            // A new address proves ownership when the email changes; otherwise use the current one.
            $sendTo = $user->pending_email ?? $user->email;

            try {
                Notification::route('mail', $sendTo)->notify(new ConfirmAccountChange($user, $changes));
                $message .= " A confirmation email was sent to {$sendTo}. "
                    .'The '.implode(' and ', $changes).' will change once the user confirms it.';
            } catch (\Throwable $e) {
                report($e);

                return redirect()->route('users.index')
                    ->with('success', $message)
                    ->with('error', "Could not send the confirmation email to {$sendTo}. "
                        .'Check the mail settings in .env, then save the user again to resend it.');
            }
        }

        return redirect()->route('users.index')
            ->with('success', $message);
    }

    // destroy() - delete a user
    public function destroy(User $user)
    {
        $this->authorizeAdmin();

        // You cannot delete your own account here
        if ($user->id === auth()->id()) {
            return back()->withErrors(['user' => 'You cannot delete your own account.']);
        }

        // Admin accounts cannot be deleted by another admin. Only staff can be deleted.
        if ($user->isAdmin()) {
            return back()->withErrors([
                'user' => "Cannot delete {$user->name}: admin accounts cannot be deleted. Only staff accounts can be deleted.",
            ]);
        }

        $name = $user->name;
        $email = $user->email;
        $adminName = auth()->user()->name;

        $user->delete();

        // LAB 6: the account is gone, so the email goes to the saved address
        $this->sendMail(function () use ($email, $name, $adminName) {
            Notification::route('mail', $email)->notify(new AccountDeleted($name, $adminName));
        });

        return redirect()->route('users.index')
            ->with('success', 'User deleted successfully.');
    }

    // Only admins can manage users
    private function authorizeAdmin(): void
    {
        abort_unless(auth()->user()->isAdmin(), 403);
    }

    // Validation rules, shared by store() and update()
    // On update, the email may stay the same and the password is optional.
    private function validateUser(Request $request, ?User $user = null): array
    {
        $emailRule = 'required|email|max:255|unique:users,email';
        $passwordRule = 'required|string|min:8|confirmed';

        if ($user) {
            $emailRule .= ",{$user->id}";
            $passwordRule = 'nullable|string|min:8|confirmed';
        }

        return $request->validate([
            'name' => 'required|string|max:255',
            'email' => $emailRule,
            'role' => 'required|in:admin,staff',
            'password' => $passwordRule,
        ], [
            'email.unique' => 'This email is already registered.',
            'role.in' => 'Role must be Admin or Staff.',
            'password.min' => 'Password must be at least 8 characters.',
            'password.confirmed' => 'Password confirmation does not match.',
        ]);
    }

    // LAB 6: if sending the email fails (for example no internet), the edit or delete still succeeds.
    // The error is written to storage/logs/laravel.log.
    private function sendMail(callable $send): void
    {
        try {
            $send();
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
