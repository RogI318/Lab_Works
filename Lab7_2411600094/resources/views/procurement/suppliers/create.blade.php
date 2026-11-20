@extends('layouts.master')

@section('content')

<div class="d-flex justify-content-between align-items-center pb-2 mb-3 border-bottom">
    <h2>Add New Supplier</h2>
    <a href="{{ route('procurement.suppliers.index') }}" class="btn btn-outline-secondary">← Back to Suppliers</a>
</div>

<div class="card stat-card">
    <div class="card-body">
        <form action="{{ route('procurement.suppliers.store') }}" method="POST">
            @csrf
            @include('procurement.suppliers._form')
            <button type="submit" class="btn" style="background-color: var(--theme-primary); color: white;">Save Supplier</button>
        </form>
    </div>
</div>

@endsection
