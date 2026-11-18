@extends('layouts.app')

@section('title', 'Edit Product')

@section('content')

<h2>Edit Product</h2>

<form
    method="POST"
    action="{{ route('products.update', $product) }}"
    class="bg-white p-4 rounded shadow-sm"
>
    @csrf
    @method('PUT')

    @include('products._form')

    <button class="btn btn-primary mt-3">
        Update Product
    </button>

    <a href="{{ route('products.index') }}" class="btn btn-outline-secondary mt-3">
        Cancel
    </a>
</form>

@endsection
