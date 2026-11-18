@extends('layouts.app')

@section('title', 'Category Management')

@section('content')

<div class="container-fluid">

    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1">Category Management</h2>
            <p class="text-muted mb-0">Group products into categories</p>
        </div>

        <a href="{{ route('categories.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i>
            Add Category
        </a>
    </div>

    {{-- Shows the message when a delete is blocked --}}
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card mb-3">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle mb-0">
                    <thead>
                        <tr class="border-accent text-uppercase small">
                            <th>#</th>
                            <th>Category Name</th>
                            <th>Description</th>
                            <th class="text-center">Products</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($categories as $item)
                            <tr>
                                <td>{{ $categories->firstItem() + $loop->index }}</td>
                                <td class="fw-bold">{{ $item->name }}</td>
                                <td>{{ $item->description ? \Illuminate\Support\Str::limit($item->description, 60) : '-' }}</td>
                                <td class="text-center">
                                    <span class="badge bg-info text-dark">{{ $productCounts[$item->name] ?? 0 }}</span>
                                </td>
                                <td class="text-center text-nowrap">
                                    <a href="{{ route('categories.show', $item) }}" class="btn btn-sm btn-outline-secondary">View</a>
                                    <a href="{{ route('categories.edit', $item) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                    <form method="POST" action="{{ route('categories.destroy', $item) }}" class="d-inline"
                                        onsubmit="return confirm('Delete this category?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted py-4">No categories yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="mt-3">
        {{ $categories->links('pagination::bootstrap-5') }}
    </div>

</div>

@endsection
