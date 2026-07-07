@extends('superadmin.layouts.app')

@section('title', 'Dashboard Superadmin')

@section('content')
<div class="row">
    <div class="col-lg-3 col-md-6 col-sm-6 col-12">
        <div class="info-box">
            <span class="info-box-icon bg-info elevation-1"><i class="fas fa-store"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Total Tenant</span>
                <span class="info-box-number">{{ $total_businesses }}</span>
            </div>
        </div>
    </div>

    <div class="col-lg-3 col-md-6 col-sm-6 col-12">
        <div class="info-box">
            <span class="info-box-icon bg-success elevation-1"><i class="fas fa-check-circle"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Tenant Aktif</span>
                <span class="info-box-number">{{ $active_businesses }}</span>
            </div>
        </div>
    </div>

    <div class="col-lg-3 col-md-6 col-sm-6 col-12">
        <div class="info-box">
            <span class="info-box-icon bg-warning elevation-1"><i class="fas fa-times-circle"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Tenant Nonaktif</span>
                <span class="info-box-number">{{ $inactive_businesses }}</span>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header with-border">
                <h3 class="card-title">Daftar Tenant</h3>
            </div>
            <div class="card-body">
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
                        @forelse ($businesses as $business)
                            <tr>
                                <td>{{ $business->id }}</td>
                                <td>{{ $business->name }}</td>
                                <td>{{ $business->owner_name }}</td>
                                <td>{{ $business->phone }}</td>
                                <td>
                                    @if ($business->is_active)
                                        <span class="badge badge-success">Aktif</span>
                                    @else
                                        <span class="badge badge-danger">Nonaktif</span>
                                    @endif
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-primary" onclick="alert('Edit: {{ $business->id }}')">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-sm btn-warning" onclick="alert('Toggle: {{ $business->id }}')">
                                        <i class="fas fa-toggle-on"></i>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted">Belum ada data tenant</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
