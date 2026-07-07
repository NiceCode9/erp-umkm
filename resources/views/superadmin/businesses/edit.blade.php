@extends('superadmin.layouts.app')

@section('title', 'Edit Business')

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Form Edit Business</h3>
    </div>
    <form action="{{ route('superadmin.businesses.update', $business) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="card-body">
            <div class="mb-3">
                <label for="name" class="form-label">Nama UMKM <span class="text-danger">*</span></label>
                <input type="text" id="name" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $business->name) }}" required>
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label for="owner_name" class="form-label">Nama Pemilik <span class="text-danger">*</span></label>
                <input type="text" id="owner_name" name="owner_name" class="form-control @error('owner_name') is-invalid @enderror" value="{{ old('owner_name', $business->owner_name) }}" required>
                @error('owner_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label for="phone" class="form-label">Telepon <span class="text-danger">*</span></label>
                <input type="text" id="phone" name="phone" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone', $business->phone) }}" required>
                @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label for="address" class="form-label">Alamat <span class="text-danger">*</span></label>
                <textarea id="address" name="address" class="form-control @error('address') is-invalid @enderror" rows="3" required>{{ old('address', $business->address) }}</textarea>
                @error('address')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>
        <div class="card-footer">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Update</button>
            <a href="{{ route('superadmin.businesses.index') }}" class="btn btn-secondary">Batal</a>
        </div>
    </form>
</div>
@endsection
