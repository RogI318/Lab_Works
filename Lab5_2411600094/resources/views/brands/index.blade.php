@extends('layouts.master')

@section('title', 'Brands')

@section('content')
    <div class="d-flex justify-content-between align-items-center pb-2 mb-3 border-bottom">
        <h2 class="fw-bold">Brands</h2>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#brand-add">
            <i class="bi bi-plus-circle me-1"></i> Add Brand
        </button>
    </div>

    <div class="card shadow-hover">
        <div class="card-header bg-gradient-primary text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">All Brands</h5>
            <span class="badge bg-light text-dark">{{ $brands->total() }} brands</span>
        </div>
        <div class="card-body">
            @include('partials.item-table', ['items' => $brands, 'module' => 'brand'])

            <div class="mt-3">{{ $brands->links('pagination::bootstrap-5') }}</div>
        </div>
    </div>

    @include('partials.item-modal', ['item' => null, 'module' => 'brand'])
@endsection
