@extends('layouts.app')

@section('title', 'Add User')

@section('content')

<div class="container-fluid">

    <div class="mb-4">
        <h2 class="mb-1">Add User</h2>
        <p class="text-muted mb-0">Create a new account</p>
    </div>

    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('users.store') }}">
                @csrf
                @include('users._form')
            </form>
        </div>
    </div>

</div>

@endsection
