@extends('layouts.app')

@section('title', 'Profile')

@section('content')

<div class="container-fluid">

    <h2 class="mb-4">Profile</h2>

    <div class="card shadow-hover mb-4">
        <div class="card-body p-4">
            @include('profile.partials.update-profile-information-form')
        </div>
    </div>

    <div class="card shadow-hover mb-4">
        <div class="card-body p-4">
            @include('profile.partials.update-password-form')
        </div>
    </div>

    {{-- Only staff can delete their own account; admins cannot --}}
    @unless (auth()->user()->isAdmin())
        <div class="card shadow-hover mb-4">
            <div class="card-body p-4">
                @include('profile.partials.delete-user-form')
            </div>
        </div>
    @endunless

</div>

@endsection
