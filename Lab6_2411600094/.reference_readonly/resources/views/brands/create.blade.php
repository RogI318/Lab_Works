@extends('layouts.app')

@section('title', 'Add Brand')

@section('content')

<div class="container-fluid">

    <div class="mb-4">
        <h2 class="mb-1">Add Brand</h2>
        <p class="text-muted mb-0">Create a new brand</p>
    </div>

    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('brands.store') }}">
                @csrf
                @include('brands._form')
            </form>
        </div>
    </div>

</div>

@endsection
