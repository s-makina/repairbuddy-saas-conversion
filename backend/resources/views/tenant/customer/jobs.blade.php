@extends('tenant.layouts.customer')

@section('title', 'My Jobs — ' . ($tenant->name ?? 'My Portal'))

@section('content')

    {{-- Page header --}}
    <div class="cp-page-header">
        <h1 class="cp-page-title">My Jobs</h1>
        <p class="cp-page-subtitle">Track all your repair jobs and their current status.</p>
    </div>

    {{-- Filters --}}
    <div class="cp-filters">
        <a href="{{ route('tenant.customer.jobs', ['business' => $business, 'status' => 'all']) }}"
           class="cp-filter-pill {{ $statusFilter === 'all' ? 'active' : '' }}">
            All <span class="cp-pill-count">{{ $allCount }}</span>
        </a>
        <a href="{{ route('tenant.customer.jobs', ['business' => $business, 'status' => 'open']) }}"
           class="cp-filter-pill {{ $statusFilter === 'open' ? 'active' : '' }}">
            In Progress <span class="cp-pill-count">{{ $openCount }}</span>
        </a>
        <a href="{{ route('tenant.customer.jobs', ['business' => $business, 'status' => 'completed']) }}"
           class="cp-filter-pill {{ $statusFilter === 'completed' ? 'active' : '' }}">
            Completed <span class="cp-pill-count">{{ $completedCount }}</span>
        </a>
    </div>

    {{-- Jobs Table --}}
    <div class="cp-card">
        <div class="cp-card-body" style="padding:0;">
            @if($jobs->isEmpty())
                <div class="cp-empty">
                    <div class="cp-empty-icon"><i class="bi bi-inbox"></i></div>
                    <div class="cp-empty-title">No jobs found</div>
                    <div class="cp-empty-text">
                        @if($statusFilter !== 'all')
                            No {{ $statusFilter }} jobs. <a href="{{ route('tenant.customer.jobs', ['business' => $business]) }}">View all jobs</a>
                        @else
                            When you book a repair, your jobs will appear here.
                        @endif
                    </div>
                </div>
            @else
                <table class="cp-table">
                    <thead>
                        <tr>
                            <th>Job #</th>
                            <th>Title</th>
                            <th>Status</th>
                            <th>Payment</th>
                            <th>Created</th>
                            <th>Pickup</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($jobs as $job)
                            <tr>
                                <td>
                                    <strong style="color:var(--cp-brand);">
                                        #{{ $job->job_number ?? $job->case_number ?? $job->id }}
                                    </strong>
                                </td>
                                <td>
                                    <div style="font-weight:600;">{{ $job->title ?? '—' }}</div>
                                    @if($job->case_detail)
                                        <div style="font-size:.72rem; color:var(--cp-text-3); margin-top:.15rem;">
                                            {{ \Illuminate\Support\Str::limit($job->case_detail, 60) }}
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    @if($job->closed_at)
                                        <span class="cp-badge cp-badge-success">Completed</span>
                                    @else
                                        <span class="cp-badge cp-badge-warning">
                                            {{ ucfirst(str_replace(['-', '_'], ' ', $job->status_slug ?? 'In Progress')) }}
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    @php
                                        $ps = $job->payment_status_slug ?? 'pending';
                                    @endphp
                                    @if($ps === 'paid')
                                        <span class="cp-badge cp-badge-success">Paid</span>
                                    @elseif($ps === 'partial')
                                        <span class="cp-badge cp-badge-info">Partial</span>
                                    @else
                                        <span class="cp-badge cp-badge-default">{{ ucfirst(str_replace(['-', '_'], ' ', $ps)) }}</span>
                                    @endif
                                </td>
                                <td style="color:var(--cp-text-3); font-size:.78rem; white-space:nowrap;">
                                    {{ $job->created_at?->format('M d, Y') ?? '—' }}
                                </td>
                                <td style="color:var(--cp-text-3); font-size:.78rem; white-space:nowrap;">
                                    {{ $job->pickup_date?->format('M d, Y') ?? '—' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>

        @if($jobs->hasPages())
            <div class="cp-card-footer">
                <div class="cp-pagination">
                    {{ $jobs->appends(['status' => $statusFilter])->links('pagination::bootstrap-5') }}
                </div>
            </div>
        @endif
    </div>

@endsection
