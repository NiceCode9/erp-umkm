@extends('app.layouts.app')

@section('content')
<div class="max-w-screen-xl mx-auto px-4 py-16">
    <div class="flex flex-col items-center justify-center min-h-screen text-center">
        <div class="mb-8">
            <i class="fas fa-lock-open text-6xl text-destructive opacity-20"></i>
        </div>

        <h1 class="text-3xl font-bold text-foreground mb-4">Akun Anda Tidak Aktif</h1>

        <p class="text-lg text-muted-foreground mb-2">
            Bisnis Anda dengan UMKM <strong>{{ auth()->user()->business->name ?? 'N/A' }}</strong> sedang tidak aktif.
        </p>

        <p class="text-muted-foreground mb-8 max-w-md">
            Hubungi administrator untuk mengaktifkan kembali akun Anda. Jika Anda memiliki pertanyaan, silakan hubungi tim support.
        </p>

        <div class="space-y-3">
            <form action="{{ route('logout') }}" method="POST" style="display:inline;">
                @csrf
                <button type="submit" class="px-6 py-2 bg-primary text-primary-foreground rounded-[var(--radius)] hover:bg-primary/90 transition">
                    Logout
                </button>
            </form>
        </div>

        <div class="mt-12 p-6 bg-card border border-border rounded-[var(--radius)] max-w-md">
            <h3 class="font-semibold text-foreground mb-3">Informasi Bisnis</h3>
            <div class="space-y-2 text-sm">
                <p>
                    <span class="text-muted-foreground">Nama UMKM:</span>
                    <strong class="text-foreground">{{ auth()->user()->business->name ?? '-' }}</strong>
                </p>
                <p>
                    <span class="text-muted-foreground">Pemilik:</span>
                    <strong class="text-foreground">{{ auth()->user()->business->owner_name ?? '-' }}</strong>
                </p>
                <p>
                    <span class="text-muted-foreground">Status:</span>
                    <strong class="text-destructive">
                        <i class="fas fa-times-circle mr-1"></i> Nonaktif
                    </strong>
                </p>
                @if (auth()->user()->business->deactivated_at)
                    <p>
                        <span class="text-muted-foreground">Tanggal Nonaktif:</span>
                        <strong class="text-foreground">{{ auth()->user()->business->deactivated_at->format('d M Y H:i') }}</strong>
                    </p>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
