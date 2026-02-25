@extends('tenant.layouts.customer')

@section('title', 'My Estimates — ' . ($tenant->name ?? 'My Portal'))

@section('content')

    {{-- Page header --}}
    <div class="cp-page-header">
        <h1 class="cp-page-title">My Estimates</h1>
        <p class="cp-page-subtitle">View all repair estimates sent to you.</p>
    </div>

    {{-- Estimates Table --}}
    <div class="cp-card">
        <div class="cp-card-body" style="padding:0;">
            @if($estimates->isEmpty())
                <div class="cp-empty">
                    <div class="cp-empty-icon"><i class="bi bi-file-earmark-x"></i></div>
                    <div class="cp-empty-title">No estimates yet</div>
                    <div class="cp-empty-text">When the shop sends you an estimate, it will appear here.</div>
                </div>
            @else
                <table class="cp-table">
                    <thead>
                        <tr>
                            <th>Estimate #</th>
                            <th>Title</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th>Sent</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($estimates as $estimate)
                            <tr>
                                <td>
                                    <strong style="color:var(--cp-brand);">
                                        #{{ $estimate->case_number ?? $estimate->id }}
                                    </strong>
                                </td>
                                <td>
                                    <div style="font-weight:600;">{{ $estimate->title ?? '—' }}</div>
                                    @if($estimate->case_detail)
                                        <div style="font-size:.72rem; color:var(--cp-text-3); margin-top:.15rem;">
                                            {{ \Illuminate\Support\Str::limit($estimate->case_detail, 60) }}
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    @php
                                        $status = $estimate->status ?? 'draft';
                                    @endphp
                                    @if($status === 'approved')
                                        <span class="cp-badge cp-badge-success">Approved</span>
                                    @elseif($status === 'rejected')
                                        <span class="cp-badge cp-badge-danger">Rejected</span>
                                    @elseif($status === 'sent')
                                        <span class="cp-badge cp-badge-primary">Sent</span>
                                    @elseif($status === 'pending')
                                        <span class="cp-badge cp-badge-warning">Pending</span>
                                    @else
                                        <span class="cp-badge cp-badge-default">{{ ucfirst($status) }}</span>
                                    @endif
                                </td>
                                <td style="color:var(--cp-text-3); font-size:.78rem; white-space:nowrap;">
                                    {{ $estimate->created_at?->format('M d, Y') ?? '—' }}
                                </td>
                                <td style="color:var(--cp-text-3); font-size:.78rem; white-space:nowrap;">
                                    {{ $estimate->sent_at?->format('M d, Y') ?? '—' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>

        @if($estimates->hasPages())
            <div class="cp-card-footer">
                <div class="cp-pagination">
                    {{ $estimates->links('pagination::bootstrap-5') }}
                </div>
            </div>
        @endif
    </div>

@endsection
