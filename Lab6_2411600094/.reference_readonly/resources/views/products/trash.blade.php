@extends('layouts.app')

@section('title', 'Trash')

@section('content')

<div class="container-fluid">

    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>
            <h2 class="mb-1">Trash</h2>

            <p class="text-muted mb-0">
                Deleted products stay here with their stock history. Restore one to bring it back to the inventory.
            </p>
        </div>

        <a href="{{ route('products.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>
            Back to Inventory
        </a>

    </div>

    <div class="card shadow-hover">

        <div class="card-body">

            <div class="table-responsive">

                <table class="table table-hover align-middle mb-0">

                    <thead>
                        <tr>
                            <th>SKU</th>
                            <th>Name</th>
                            <th>Category</th>
                            <th>Brand</th>
                            <th>Quantity</th>
                            <th>Deleted</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($products as $p)
                            <tr>
                                <td class="text-nowrap">{{ $p->sku }}</td>
                                <td>{{ $p->name }}</td>
                                <td>{{ $p->category }}</td>
                                <td>{{ $p->brand ?? '-' }}</td>
                                <td>{{ $p->quantity }}</td>
                                <td>{{ $p->deleted_at->format('M d, Y h:i A') }}</td>
                                <td class="text-end">
                                    <form method="POST" action="{{ route('products.restore', $p->id) }}" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-success">
                                            <i class="bi bi-arrow-counterclockwise me-1"></i>
                                            Restore
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">The Trash is empty.</td>
                            </tr>
                        @endforelse
                    </tbody>

                </table>

            </div>

        </div>

    </div>

    <div class="mt-3">
        {{ $products->links('pagination::bootstrap-5') }}
    </div>

</div>

@endsection
