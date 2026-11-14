@extends('layouts.master')

@section('title', 'Edit Supplier')

@section('content')
    <div class="d-flex justify-content-between align-items-center pb-2 mb-3 border-bottom">
        <h2 class="fw-bold">Edit Supplier</h2>
        <div class="d-flex gap-2">
            <a href="{{ route('suppliers.show', $supplier) }}" class="btn btn-outline-secondary">
                <i class="bi bi-eye me-1"></i> View Supplier
            </a>
            <a href="{{ route('suppliers.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Back to Suppliers
            </a>
        </div>
    </div>

    <div class="card shadow-hover">
        <div class="card-header bg-gradient-primary text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="bi bi-pencil-square me-1"></i>{{ $supplier->name }}</h5>
            <span class="badge bg-light text-dark">Added {{ $supplier->created_at->format('M d, Y') }}</span>
        </div>
        <div class="card-body">
            @include('suppliers._form')
        </div>
    </div>
@endsection
