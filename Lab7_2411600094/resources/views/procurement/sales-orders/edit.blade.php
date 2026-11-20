@extends('layouts.master')

@section('content')

<div class="d-flex justify-content-between align-items-center pb-2 mb-3 border-bottom flex-wrap gap-2">
    <h2 class="mb-0">Edit {{ $order->reference_number }}</h2>
    <a href="{{ route('procurement.sales-orders.show', $order) }}" class="btn btn-outline-secondary">← Back to Order</a>
</div>

<div class="card stat-card">
    <div class="card-body">
        <form action="{{ route('procurement.sales-orders.update', $order) }}" method="POST">
            @csrf
            @method('PUT')
            @include('procurement.sales-orders._form')
            <button type="submit" class="btn" style="background-color: var(--theme-primary); color: white;">Save Changes</button>
        </form>
    </div>
</div>

@endsection