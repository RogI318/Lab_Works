@extends('layouts.master')

@section('title', 'Add Product')

@section('content')
    <div class="d-flex justify-content-between align-items-center pb-2 mb-3 border-bottom">
        <h2 class="fw-bold">Add New Product</h2>
        <a href="{{ route('products.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back to Inventory
        </a>
    </div>

    <div class="card shadow-hover">
        <div class="card-header bg-gradient-primary text-white">
            <h5 class="mb-0"><i class="bi bi-box-seam me-1"></i>Product Details</h5>
        </div>
        <div class="card-body">
            @include('products._form', [
                'product'  => null,
                'draftKey' => 'product-create-draft',
            ])
        </div>
    </div>
@endsection
