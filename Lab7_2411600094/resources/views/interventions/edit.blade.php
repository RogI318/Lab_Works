
@extends('layouts.master')

@section('title', 'Edit Intervention')

@section('content')
<div class="mb-4">
    <h2 class="fw-bold">Edit Academic Intervention</h2>
    <p class="text-muted">Update the intervention details.</p>
</div>

<div class="card shadow-sm">
    <div class="card-header bg-warning">
        Edit: {{ $intervention->reference_number }}
    </div>
    <div class="card-body">
        <form action="{{ route('interventions.update', $intervention) }}" method="POST">
            @method('PUT')
            @include('interventions._form', ['buttonText' => 'Update Intervention'])
        </form>
    </div>
</div>
@endsection
