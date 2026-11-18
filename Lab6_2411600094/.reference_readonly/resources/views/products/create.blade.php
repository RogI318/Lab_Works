@extends('layouts.app')

@section('title', 'Add Product')

@section('content')

<h2>Add Product</h2>

<form
    method="POST"
    action="{{ route('products.store') }}"
    class="bg-white p-4 rounded shadow-sm"
>
    @csrf

    @include('products._form')

    <button class="btn btn-primary mt-3">
        Save Product
    </button>

    <a href="{{ route('products.index') }}" class="btn btn-outline-secondary mt-3">
        Cancel
    </a>
</form>

@endsection
