@extends('layouts.master')

@section('title', 'Add Supplier')

@section('content')
    <div class="d-flex justify-content-between align-items-center pb-2 mb-3 border-bottom">
        <h2 class="fw-bold">Add New Supplier</h2>
        <a href="{{ route('suppliers.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back to Suppliers
        </a>
    </div>

    <div class="card shadow-hover">
        <div class="card-header bg-gradient-primary text-white">
            <h5 class="mb-0"><i class="bi bi-truck me-1"></i>Supplier Details</h5>
        </div>
        <div class="card-body">
            @include('suppliers._form', ['supplier' => null])
        </div>
    </div>
@endsection
