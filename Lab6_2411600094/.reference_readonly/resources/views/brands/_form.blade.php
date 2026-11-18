@if ($errors->any())
    <div class="alert alert-danger">
        <strong>Please fix the following:</strong>
        <ul class="mb-0">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label">Brand Name</label>
        <input type="text" name="name" class="form-control" maxlength="100" value="{{ old('name', $brand->name ?? '') }}" required>
    </div>
    <div class="col-12">
        <label class="form-label">Description</label>
        <textarea name="description" rows="3" maxlength="255" class="form-control">{{ old('description', $brand->description ?? '') }}</textarea>
    </div>
</div>

<div class="mt-4">
    <button type="submit" class="btn btn-primary">Save Brand</button>
    <a href="{{ route('brands.index') }}" class="btn btn-outline-secondary">Cancel</a>
</div>
