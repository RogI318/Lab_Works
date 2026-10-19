@extends('layouts.master')

@section('title', 'Add Student')

@section('content')

    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
        <h2 class="fw-bold">Add New Student</h2>
        <a href="{{ route('students.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back to Roster
        </a>
    </div>

    <div class="card shadow-hover mb-4">
        <div class="card-header bg-gradient-primary text-white">
            <h5 class="mb-0">Student Details</h5>
        </div>
        <div class="card-body">
            <form action="{{ route('students.store') }}" method="POST">
                @include('students._form')
            </form>
        </div>
    </div>

@endsection