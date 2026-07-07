@extends('superadmin.layouts.app')

@section('title', 'Kelola Business')

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title">Daftar Business (Tenant)</h3>
        <a href="{{ route('superadmin.businesses.create') }}" class="btn btn-primary">
            <i class="fas fa-plus"></i> Tambah Business
        </a>
    </div>
    <div class="card-body">
        @if($businesses->count() > 0)
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nama UMKM</th>
                        <th>Pemilik</th>
                        <th>Telepon</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($businesses as $business)
                        <tr>
                            <td>{{ $business->id }}</td>
                            <td>{{ $business->name }}</td>
                            <td>{{ $business->owner_name }}</td>
                            <td>{{ $business->phone }}</td>
                            <td>
                                @if($business->is_active)
                                    <span class="badge badge-success">Aktif</span>
                                @else
                                    <span class="badge badge-danger">Nonaktif</span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('superadmin.businesses.edit', $business) }}" class="btn btn-sm btn-primary">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                                @if($business->is_active)
                                    <form action="{{ route('superadmin.businesses.deactivate', $business) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-warning" onclick="return confirm('Nonaktifkan business ini?')">
                                            <i class="fas fa-times-circle"></i> Nonaktifkan
                                        </button>
                                    </form>
                                @else
                                    <form action="{{ route('superadmin.businesses.activate', $business) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-success" onclick="return confirm('Aktifkan business ini?')">
                                            <i class="fas fa-check-circle"></i> Aktifkan
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <div class="text-center py-4">
                <p class="text-muted">Belum ada data business</p>
            </div>
        @endif
    </div>
</div>
@endsection
