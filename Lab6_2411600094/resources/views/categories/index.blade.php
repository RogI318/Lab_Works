@extends('layouts.master')

@section('title', 'Categories')

@section('content')
    <div class="d-flex justify-content-between align-items-center pb-2 mb-3 border-bottom">
        <h2 class="fw-bold">Product Categories</h2>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#category-add">
            <i class="bi bi-plus-circle me-1"></i> Add Category
        </button>
    </div>

    <div class="card shadow-hover">
        <div class="card-header bg-gradient-primary text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">All Categories</h5>
            <span class="badge bg-light text-dark">{{ $categories->total() }} categories</span>
        </div>
        <div class="card-body">
            @include('partials.item-table', ['items' => $categories, 'module' => 'category'])

            <div class="mt-3">{{ $categories->links('pagination::bootstrap-5') }}</div>
        </div>
    </div>

    @include('partials.item-modal', ['item' => null, 'module' => 'category'])
@endsection
