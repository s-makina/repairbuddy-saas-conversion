@extends('tenant.layouts.customer')

@section('title', 'Dashboard — ' . ($tenant->name ?? 'My Portal'))

@section('content')

    {{-- Page header --}}
    <div class="cp-page-header">
        <h1 class="cp-page-title">Welcome back, {{ $user->first_name ?? $user->name }}!</h1>
        <p class="cp-page-subtitle">Here's an overview of your repairs and account activity.</p>
    </div>

    {{-- ══════ Stats ══════ --}}
    <div class="cp-stats-grid">
        <div class="cp-stat-card">
            <div class="cp-stat-icon blue">
                <i class="bi bi-tools"></i>
            </div>
            <div class="cp-stat-body">
                <div class="cp-stat-label">Total Jobs</div>
                <div class="cp-stat-value">{{ $totalJobs }}</div>
            </div>
        </div>

        <div class="cp-stat-card">
            <div class="cp-stat-icon orange">
                <i class="bi bi-clock-history"></i>
            </div>
            <div class="cp-stat-body">
                <div class="cp-stat-label">In Progress</div>
                <div class="cp-stat-value">{{ $openJobs }}</div>
            </div>
        </div>

        <div class="cp-stat-card">
            <div class="cp-stat-icon green">
                <i class="bi bi-check-circle"></i>
            </div>
            <div class="cp-stat-body">
                <div class="cp-stat-label">Completed</div>
                <div class="cp-stat-value">{{ $completedJobs }}</div>
            </div>
        </div>

        <div class="cp-stat-card">
            <div class="cp-stat-icon purple">
                <i class="bi bi-file-earmark-text"></i>
            </div>
            <div class="cp-stat-body">
                <div class="cp-stat-label">Estimates</div>
                <div class="cp-stat-value">{{ $totalEstimates }}</div>
                @if($pendingEstimates > 0)
                    <div class="cp-stat-sub">{{ $pendingEstimates }} pending</div>
                @endif
            </div>
        </div>

        <div class="cp-stat-card">
            <div class="cp-stat-icon yellow">
                <i class="bi bi-phone"></i>
            </div>
            <div class="cp-stat-body">
                <div class="cp-stat-label">My Devices</div>
                <div class="cp-stat-value">{{ $totalDevices }}</div>
            </div>
        </div>
    </div>

    {{-- ══════ Quick Actions ══════ --}}
    <div class="cp-card">
        <div class="cp-card-header">
            <h2 class="cp-card-title"><i class="bi bi-lightning"></i> Quick Actions</h2>
        </div>
        <div class="cp-card-body">
            <div class="cp-quick-grid">
                <a href="{{ route('tenant.booking.show', ['business' => $business]) }}" class="cp-quick-card">
                    <div class="cp-quick-card-icon" style="background: var(--cp-accent-soft); color: var(--cp-accent);">
                        <i class="bi bi-plus-circle"></i>
                    </div>
                    <span class="cp-quick-card-label">Book a Repair</span>
                </a>
                <a href="{{ route('tenant.customer.jobs', ['business' => $business]) }}" class="cp-quick-card">
                    <div class="cp-quick-card-icon">
                        <i class="bi bi-tools"></i>
                    </div>
                    <span class="cp-quick-card-label">View All Jobs</span>
                </a>
                <a href="{{ route('tenant.customer.estimates', ['business' => $business]) }}" class="cp-quick-card">
                    <div class="cp-quick-card-icon" style="background: var(--cp-info-soft); color: var(--cp-info);">
                        <i class="bi bi-file-earmark-text"></i>
                    </div>
                    <span class="cp-quick-card-label">My Estimates</span>
                </a>
                <a href="{{ route('tenant.customer.devices', ['business' => $business]) }}" class="cp-quick-card">
                    <div class="cp-quick-card-icon" style="background: var(--cp-warning-soft); color: var(--cp-warning);">
                        <i class="bi bi-phone"></i>
                    </div>
                    <span class="cp-quick-card-label">My Devices</span>
                </a>
                <a href="{{ route('tenant.customer.account', ['business' => $business]) }}" class="cp-quick-card">
                    <div class="cp-quick-card-icon" style="background: var(--cp-success-soft); color: var(--cp-success);">
                        <i class="bi bi-person-gear"></i>
                    </div>
                    <span class="cp-quick-card-label">Account Settings</span>
                </a>
            </div>
        </div>
    </div>

    {{-- ══════ Recent Jobs ══════ --}}
    <div class="cp-card">
        <div class="cp-card-header">
            <h2 class="cp-card-title"><i class="bi bi-clock-history"></i> Recent Jobs</h2>
            @if($totalJobs > 0)
                <a href="{{ route('tenant.customer.jobs', ['business' => $business]) }}"
                   style="font-size:.78rem; font-weight:600; color:var(--cp-brand); text-decoration:none;">
                    View All <i class="bi bi-arrow-right"></i>
                </a>
            @endif
        </div>
        <div class="cp-card-body" style="padding:0;">
            @if($recentJobs->isEmpty())
                <div class="cp-empty">
                    <div class="cp-empty-icon"><i class="bi bi-inbox"></i></div>
                    <div class="cp-empty-title">No jobs yet</div>
                    <div class="cp-empty-text">When you book a repair, your jobs will appear here.</div>
                </div>
            @else
                <table class="cp-table">
                    <thead>
                        <tr>
                            <th>Job #</th>
                            <th>Title</th>
                            <th>Status</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recentJobs as $job)
                            <tr>
                                <td>
                                    <strong style="color:var(--cp-brand);">
                                        #{{ $job->job_number ?? $job->case_number ?? $job->id }}
                                    </strong>
                                </td>
                                <td>{{ $job->title ?? '—' }}</td>
                                <td>
                                    @if($job->closed_at)
                                        <span class="cp-badge cp-badge-success">Completed</span>
                                    @else
                                        <span class="cp-badge cp-badge-warning">
                                            {{ ucfirst(str_replace(['-', '_'], ' ', $job->status_slug ?? 'In Progress')) }}
                                        </span>
                                    @endif
                                </td>
                                <td style="color:var(--cp-text-3); font-size:.78rem;">
                                    {{ $job->created_at?->format('M d, Y') ?? '—' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>

@endsection
