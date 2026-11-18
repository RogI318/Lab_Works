@extends('layouts.app')

@section('title', 'Edit Category')

@section('content')

<div class="container-fluid">

    <div class="mb-4">
        <h2 class="mb-1">Edit Category</h2>
        <p class="text-muted mb-0">{{ $category->name }}</p>
    </div>

    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('categories.update', $category) }}">
                @csrf
                @method('PUT')
                @include('categories._form')
            </form>
        </div>
    </div>

</div>

@endsection
