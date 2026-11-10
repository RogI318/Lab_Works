@extends('layouts.master')

@section('title', 'Profile')

@section('content')

    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
        <h2 class="fw-bold">Profile</h2>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card shadow-hover mb-4">
                <div class="card-header bg-gradient-primary text-white">
                    <h5 class="mb-0">Profile Information</h5>
                </div>
                <div class="card-body">
                    @include('profile.partials.update-profile-information-form')
                </div>
            </div>

            <div class="card shadow-hover mb-4">
                <div class="card-header bg-gradient-primary text-white">
                    <h5 class="mb-0">Update Password</h5>
                </div>
                <div class="card-body">
                    @include('profile.partials.update-password-form')
                </div>
            </div>

            <div class="card shadow-hover mb-4">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0">Delete Account</h5>
                </div>
                <div class="card-body">
                    @include('profile.partials.delete-user-form')
                </div>
            </div>
        </div>
    </div>

@endsection