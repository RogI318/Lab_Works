@extends('layouts.app')

@section('title', 'Edit Brand')

@section('content')

<div class="container-fluid">

    <div class="mb-4">
        <h2 class="mb-1">Edit Brand</h2>
        <p class="text-muted mb-0">{{ $brand->name }}</p>
    </div>

    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('brands.update', $brand) }}">
                @csrf
                @method('PUT')
                @include('brands._form')
            </form>
        </div>
    </div>

</div>

@endsection
