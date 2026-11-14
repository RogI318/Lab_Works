<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    public function index()
    {
        $users = User::orderBy('id')->paginate(10)->withQueryString();

        // Lab 7 Part 1: value => label map the role dropdowns are built from.
        return view('users.index', [
            'users' => $users,
            'roles' => User::ROLES,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', 'confirmed', Password::defaults()],
            // Lab 7 Part 1: the role replaces the single admin tick-box.
            'role'     => ['required', 'string', Rule::in(array_keys(User::ROLES))],
        ], [
            'role.required' => 'Please choose a role for this user.',
            'role.in'       => 'The role must be Administrator, Manager, Warehouse Staff or Staff.',
        ]);

        $user = User::create([
            'name'              => $data['name'],
            'email'             => $data['email'],
            'password'          => $data['password'],   // hashed by the User model cast
            'role'              => $data['role'],       // is_admin follows it — see User::booted()
            'email_verified_at' => now(),
        ]);

        return redirect()
            ->route('users.index')
            ->with('success', "User \"{$user->name}\" added successfully as {$user->roleLabel()}.");
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'confirmed', Password::defaults()],
            'role'     => ['required', 'string', Rule::in(array_keys(User::ROLES))],
        ], [
            'role.required' => 'Please choose a role for this user.',
            'role.in'       => 'The role must be Administrator, Manager, Warehouse Staff or Staff.',
        ]);

        $user->name  = $data['name'];
        $user->email = $data['email'];

        // Leave the password alone unless a new one was typed in.
        if (! empty($data['password'])) {
            $user->password = $data['password'];
        }

        // An admin cannot take away their own admin rights (prevents locking everyone out).
        if (! $user->is(auth()->user())) {
            $user->role = $data['role'];
        }

        $user->save();

        return redirect()
            ->route('users.index')
            ->with('success', "User \"{$user->name}\" updated successfully.");
    }

    public function destroy(User $user)
    {
        if ($user->is(auth()->user())) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        $name = $user->name;

        // Lab 6: the ledger keeps its rows (user_id is nulled), but this user's
        // inbox would otherwise be orphaned in the notifications table.
        $user->notifications()->delete();
        $user->delete();

        return back()->with('success', "User \"{$name}\" deleted successfully.");
    }
}