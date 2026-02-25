@extends('tenant.layouts.customer')

@section('title', 'My Devices — ' . ($tenant->name ?? 'My Portal'))

@section('content')

    {{-- Page header --}}
    <div class="cp-page-header">
        <h1 class="cp-page-title">My Devices</h1>
        <p class="cp-page-subtitle">Devices registered to your account.</p>
    </div>

    {{-- Devices --}}
    <div class="cp-card">
        <div class="cp-card-body" style="padding:0;">
            @if($devices->isEmpty())
                <div class="cp-empty">
                    <div class="cp-empty-icon"><i class="bi bi-phone"></i></div>
                    <div class="cp-empty-title">No devices registered</div>
                    <div class="cp-empty-text">Devices will be added when you book a repair.</div>
                </div>
            @else
                <table class="cp-table">
                    <thead>
                        <tr>
                            <th>Device</th>
                            <th>Label</th>
                            <th>Serial</th>
                            <th>Notes</th>
                            <th>Added</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($devices as $customerDevice)
                            <tr>
                                <td>
                                    <div style="font-weight:600;">
                                        {{ $customerDevice->device?->name ?? 'Unknown Device' }}
                                    </div>
                                </td>
                                <td>{{ $customerDevice->label ?? '—' }}</td>
                                <td>
                                    @if($customerDevice->serial)
                                        <code style="font-size:.75rem; background:#f1f5f9; padding:.15rem .4rem; border-radius:4px;">
                                            {{ $customerDevice->serial }}
                                        </code>
                                    @else
                                        <span style="color:var(--cp-text-3);">—</span>
                                    @endif
                                </td>
                                <td style="font-size:.78rem; color:var(--cp-text-2); max-width:200px;">
                                    {{ \Illuminate\Support\Str::limit($customerDevice->notes, 50) ?? '—' }}
                                </td>
                                <td style="color:var(--cp-text-3); font-size:.78rem; white-space:nowrap;">
                                    {{ $customerDevice->created_at?->format('M d, Y') ?? '—' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>

        @if($devices->hasPages())
            <div class="cp-card-footer">
                <div class="cp-pagination">
                    {{ $devices->links('pagination::bootstrap-5') }}
                </div>
            </div>
        @endif
    </div>

@endsection
