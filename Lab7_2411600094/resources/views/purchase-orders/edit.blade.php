@extends('layouts.master')

@section('title', 'Edit Purchase Order')

@section('content')
    <div class="d-flex justify-content-between align-items-center pb-2 mb-3 border-bottom">
        <h2 class="fw-bold">Edit {{ $order->reference_number }}</h2>
        <a href="{{ route('purchase-orders.show', $order) }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back to the Order
        </a>
    </div>

    <div class="alert alert-warning d-flex justify-content-between align-items-center flex-wrap gap-2">
        <span class="mb-0">
            <i class="bi bi-pencil-square me-1"></i>
            This order is still a draft, so its lines and dates may change.
            Once it is submitted for approval it locks.
        </span>
        <span class="badge bg-secondary">{{ $order->statusLabel() }}</span>
    </div>

    @include('purchase-orders._form', ['mode' => 'edit'])
@endsection
