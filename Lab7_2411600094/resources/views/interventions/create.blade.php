
@extends('layouts.master')

@section('title', 'Create Intervention')

@section('content')
<div class="mb-4">
    <h2 class="fw-bold">Create Academic Intervention</h2>
    <p class="text-muted">Record a new academic support request for a student.</p>
</div>

<div class="card shadow-sm">
    <div class="card-header bg-primary text-white">
        New Intervention Details
    </div>
    <div class="card-body">
        <form action="{{ route('interventions.store') }}" method="POST">
            @include('interventions._form', ['buttonText' => 'Save Intervention'])
        </form>
    </div>
</div>
@endsection
