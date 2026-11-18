@extends('layouts.app')

@section('title', 'Add Category')

@section('content')

<div class="container-fluid">

    <div class="mb-4">
        <h2 class="mb-1">Add Category</h2>
        <p class="text-muted mb-0">Create a new category</p>
    </div>

    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('categories.store') }}">
                @csrf
                @include('categories._form')
            </form>
        </div>
    </div>

</div>

@endsection
