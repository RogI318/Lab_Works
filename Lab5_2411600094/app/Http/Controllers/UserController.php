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

        return view('users.index', compact('users'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user = User::create([
            'name'              => $data['name'],
            'email'             => $data['email'],
            'password'          => $data['password'],   // hashed by the User model cast
            'is_admin'          => $request->boolean('is_admin'),
            'email_verified_at' => now(),
        ]);

        return redirect()
            ->route('users.index')
            ->with('success', "User \"{$user->name}\" added successfully.");
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'confirmed', Password::defaults()],
        ]);

        $user->name  = $data['name'];
        $user->email = $data['email'];

        // Leave the password alone unless a new one was typed in.
        if (! empty($data['password'])) {
            $user->password = $data['password'];
        }

        // An admin cannot take away their own admin rights (prevents locking everyone out).
        if (! $user->is(auth()->user())) {
            $user->is_admin = $request->boolean('is_admin');
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
        $user->delete();

        return back()->with('success', "User \"{$name}\" deleted successfully.");
    }
}