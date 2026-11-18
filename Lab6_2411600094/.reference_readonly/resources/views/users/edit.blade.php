@extends('layouts.app')

@section('title', 'Edit User')

@section('content')

<div class="container-fluid">

    <div class="mb-4">
        <h2 class="mb-1">Edit User</h2>
        <p class="text-muted mb-0">{{ $user->name }}</p>
    </div>

    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('users.update', $user) }}">
                @csrf
                @method('PUT')
                @include('users._form')
            </form>
        </div>
    </div>

</div>

@endsection
