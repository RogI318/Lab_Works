@extends('layouts.master')

@section('title', 'New Purchase Order')

@section('content')
    <div class="d-flex justify-content-between align-items-center pb-2 mb-3 border-bottom">
        <h2 class="fw-bold">New Purchase Order</h2>
        <a href="{{ route('purchase-orders.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back to Purchase Orders
        </a>
    </div>

    @include('purchase-orders._form', ['mode' => 'create'])
@endsection
