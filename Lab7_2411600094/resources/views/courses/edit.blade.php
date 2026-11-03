@extends('layouts.master')

@section('title', 'Edit Course')

@section('content')

    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
        <h2 class="fw-bold">Edit Course</h2>
        <a href="{{ route('courses.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back to Courses
        </a>
    </div>

    <div class="card shadow-hover mb-4">
        <div class="card-header bg-gradient-primary text-white"><h5 class="mb-0">Update {{ $course->code }}</h5></div>
        <div class="card-body">
            <form action="{{ route('courses.update', $course) }}" method="POST">
                @method('PUT')
                @include('courses._form')
            </form>
        </div>
    </div>

@endsection