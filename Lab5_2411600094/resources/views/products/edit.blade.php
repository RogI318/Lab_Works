@extends('layouts.master')

@section('title', 'Edit Product')

@section('content')
    <div class="d-flex justify-content-between align-items-center pb-2 mb-3 border-bottom">
        <h2 class="fw-bold">Edit Product: {{ $product->name }}</h2>
        <a href="{{ route('products.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back to Inventory
        </a>
    </div>

    <div class="card shadow-hover">
        <div class="card-header bg-gradient-primary text-white">
            <h5 class="mb-0"><i class="bi bi-pencil-square me-1"></i>Update Product Information</h5>
        </div>
        <div class="card-body">
            @include('products._form')
        </div>
    </div>
@endsection
