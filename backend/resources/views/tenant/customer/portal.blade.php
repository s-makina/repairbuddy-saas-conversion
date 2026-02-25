@extends('tenant.layouts.public')

@section('title', 'My Portal — ' . ($tenant->name ?? 'RepairBuddy'))

@push('page-styles')
<style>
/* ═══════════════════════════════════════════════════════════
   Customer Portal — Single-page tabbed layout
   Uses Alpine.js for instant section switching
   ═══════════════════════════════════════════════════════════ */

:root {
    --cp-brand: #0ea5e9;
    --cp-brand-soft: #e0f2fe;
    --cp-brand-dark: #0284c7;
    --cp-accent: #fd6742;
    --cp-accent-soft: #fff1ed;
    --cp-success: #22c55e;
    --cp-success-soft: #dcfce7;
    --cp-danger: #ef4444;
    --cp-danger-soft: #fef2f2;
    --cp-warning: #f59e0b;
    --cp-warning-soft: #fef3c7;
    --cp-info: #6366f1;
    --cp-info-soft: #eef2ff;
    --cp-bg: #f8fafc;
    --cp-card: #ffffff;
    --cp-border: #e2e8f0;
    --cp-text: #0f172a;
    --cp-text-2: #475569;
    --cp-text-3: #94a3b8;
    --cp-radius: 12px;
    --cp-radius-sm: 8px;
    --cp-shadow: 0 1px 3px rgba(0,0,0,.06);
    --cp-shadow-md: 0 4px 12px rgba(0,0,0,.07);
}

/* ── Portal wrapper ── */
.cp-portal { margin: -1rem 0 0; padding: 0; }

/* ── Tab Navigation ── */
.cp-tab-bar {
    display: flex;
    gap: .25rem;
    padding: .5rem 0;
    border-bottom: 2px solid var(--cp-border);
    margin-bottom: 1.75rem;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}
.cp-tab-bar::-webkit-scrollbar { height: 0; }
.cp-tab {
    display: inline-flex;
    align-items: center;
    gap: .4rem;
    padding: .55rem 1rem;
    font-size: .82rem;
    font-weight: 500;
    color: var(--cp-text-2);
    background: none;
    border: none;
    border-bottom: 2px solid transparent;
    margin-bottom: -2px;
    cursor: pointer;
    white-space: nowrap;
    transition: all .15s;
    font-family: inherit;
    border-radius: var(--cp-radius-sm) var(--cp-radius-sm) 0 0;
}
.cp-tab:hover { color: var(--cp-brand); background: rgba(14,165,233,.04); }
.cp-tab.active {
    color: var(--cp-brand);
    font-weight: 600;
    border-bottom-color: var(--cp-brand);
    background: rgba(14,165,233,.04);
}
.cp-tab i { font-size: .9rem; }
.cp-tab-badge {
    font-size: .6rem;
    font-weight: 700;
    padding: .1rem .4rem;
    border-radius: 999px;
    background: var(--cp-accent-soft);
    color: var(--cp-accent);
}

/* ── User welcome header ── */
.cp-welcome {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 1rem;
    padding: 1rem 0 0;
}
.cp-welcome-avatar {
    width: 48px; height: 48px;
    display: flex; align-items: center; justify-content: center;
    background: #063e70;
    color: #fff;
    border-radius: 50%;
    font-size: 1.1rem; font-weight: 700;
    flex-shrink: 0;
}
.cp-welcome-name { font-size: 1.2rem; font-weight: 700; color: var(--cp-text); margin: 0; }
.cp-welcome-sub { font-size: .8rem; color: var(--cp-text-3); margin: 0; }

/* ── Stat Cards ── */
.cp-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(170px, 1fr));
    gap: .85rem;
    margin-bottom: 1.75rem;
}
.cp-stat-card {
    background: var(--cp-card);
    border: 1px solid var(--cp-border);
    border-radius: var(--cp-radius);
    padding: 1rem 1.1rem;
    box-shadow: var(--cp-shadow);
    display: flex;
    align-items: flex-start;
    gap: .75rem;
    transition: box-shadow .15s, transform .12s;
}
.cp-stat-card:hover { box-shadow: var(--cp-shadow-md); transform: translateY(-1px); }
.cp-stat-icon {
    width: 40px; height: 40px;
    display: flex; align-items: center; justify-content: center;
    border-radius: var(--cp-radius-sm);
    font-size: 1.05rem; flex-shrink: 0;
}
.cp-stat-icon.blue { background: var(--cp-brand-soft); color: var(--cp-brand); }
.cp-stat-icon.green { background: var(--cp-success-soft); color: var(--cp-success); }
.cp-stat-icon.orange { background: var(--cp-accent-soft); color: var(--cp-accent); }
.cp-stat-icon.purple { background: var(--cp-info-soft); color: var(--cp-info); }
.cp-stat-icon.yellow { background: var(--cp-warning-soft); color: var(--cp-warning); }
.cp-stat-body { flex: 1; min-width: 0; }
.cp-stat-label {
    font-size: .65rem; font-weight: 600;
    text-transform: uppercase; letter-spacing: .04em;
    color: var(--cp-text-3); margin-bottom: .15rem;
}
.cp-stat-value { font-size: 1.4rem; font-weight: 700; color: #063e70; line-height: 1; }
.cp-stat-sub { font-size: .65rem; color: var(--cp-text-3); margin-top: .15rem; }

/* ── Section Card ── */
.cp-card {
    background: var(--cp-card);
    border: 1px solid var(--cp-border);
    border-radius: var(--cp-radius);
    box-shadow: var(--cp-shadow);
    overflow: hidden;
    margin-bottom: 1.25rem;
}
.cp-card-header {
    display: flex; align-items: center; justify-content: space-between;
    padding: .75rem 1.15rem;
    border-bottom: 1px solid var(--cp-border);
}
.cp-card-title {
    font-size: .88rem; font-weight: 700; color: var(--cp-text);
    margin: 0; display: flex; align-items: center; gap: .4rem;
}
.cp-card-body { padding: 1.15rem; }

/* ── Table ── */
.cp-table { width: 100%; border-collapse: collapse; font-size: .82rem; }
.cp-table thead th {
    padding: .55rem .7rem; font-size: .65rem; font-weight: 600;
    text-transform: uppercase; letter-spacing: .04em;
    color: var(--cp-text-3); border-bottom: 2px solid var(--cp-border);
    text-align: left; white-space: nowrap;
}
.cp-table tbody td {
    padding: .6rem .7rem; border-bottom: 1px solid var(--cp-border);
    color: var(--cp-text); vertical-align: middle;
}
.cp-table tbody tr:last-child td { border-bottom: none; }
.cp-table tbody tr:hover { background: rgba(14,165,233,.02); }

/* ── Badges ── */
.cp-badge {
    display: inline-flex; padding: .12rem .5rem;
    font-size: .65rem; font-weight: 600; border-radius: 999px; white-space: nowrap;
}
.cp-badge-success { background: var(--cp-success-soft); color: #15803d; }
.cp-badge-warning { background: var(--cp-warning-soft); color: #b45309; }
.cp-badge-danger { background: var(--cp-danger-soft); color: #dc2626; }
.cp-badge-info { background: var(--cp-info-soft); color: #4338ca; }
.cp-badge-default { background: #f1f5f9; color: var(--cp-text-2); }
.cp-badge-primary { background: var(--cp-brand-soft); color: var(--cp-brand-dark); }

/* ── Empty State ── */
.cp-empty { text-align: center; padding: 2.5rem 1.5rem; }
.cp-empty-icon { font-size: 2.2rem; color: var(--cp-text-3); opacity: .35; margin-bottom: .6rem; }
.cp-empty-title { font-size: .95rem; font-weight: 700; color: var(--cp-text); margin-bottom: .2rem; }
.cp-empty-text { font-size: .8rem; color: var(--cp-text-3); max-width: 340px; margin: 0 auto; }

/* ── Quick Actions grid ── */
.cp-quick-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
    gap: .75rem;
}
.cp-quick-card {
    background: var(--cp-card);
    border: 1px solid var(--cp-border);
    border-radius: var(--cp-radius);
    padding: 1rem;
    display: flex; align-items: center; gap: .7rem;
    text-decoration: none; color: var(--cp-text);
    transition: box-shadow .15s, transform .12s;
    box-shadow: var(--cp-shadow); cursor: pointer;
}
.cp-quick-card:hover { box-shadow: var(--cp-shadow-md); transform: translateY(-2px); text-decoration: none; color: var(--cp-brand); }
.cp-quick-card-icon {
    width: 36px; height: 36px;
    display: flex; align-items: center; justify-content: center;
    background: var(--cp-brand-soft); color: var(--cp-brand);
    border-radius: var(--cp-radius-sm); font-size: .95rem; flex-shrink: 0;
}
.cp-quick-card-label { font-size: .8rem; font-weight: 600; }

/* ── Flash Messages ── */
.cp-flash {
    display: flex; align-items: center; gap: .6rem;
    padding: .6rem 1rem; border-radius: var(--cp-radius-sm);
    font-size: .82rem; font-weight: 500; margin-bottom: 1rem;
    animation: cpFlashIn .3s ease;
}
@keyframes cpFlashIn { from { opacity:0; transform:translateY(-6px); } to { opacity:1; transform:translateY(0); } }
.cp-flash-success { background: var(--cp-success-soft); color: #15803d; border: 1px solid #bbf7d0; }
.cp-flash-error { background: var(--cp-danger-soft); color: #dc2626; border: 1px solid #fecaca; }
.cp-flash-dismiss {
    margin-left: auto; background: none; border: none;
    cursor: pointer; color: inherit; opacity: .6; font-size: 1rem; padding: 0;
}
.cp-flash-dismiss:hover { opacity: 1; }

/* ── Form ── */
.cp-form-group { margin-bottom: 1rem; }
.cp-form-label {
    display: block; font-size: .7rem; font-weight: 600;
    text-transform: uppercase; letter-spacing: .04em;
    color: var(--cp-text-2); margin-bottom: .3rem;
}
.cp-form-input {
    width: 100%; padding: .5rem .7rem; font-size: .84rem;
    color: var(--cp-text); background: #fff;
    border: 1px solid var(--cp-border); border-radius: var(--cp-radius-sm);
    outline: none; transition: border-color .15s, box-shadow .15s;
    font-family: inherit; line-height: 1.5; box-sizing: border-box;
}
.cp-form-input:focus { border-color: var(--cp-brand); box-shadow: 0 0 0 3px rgba(14,165,233,.12); }
.cp-form-error { font-size: .7rem; color: var(--cp-danger); margin-top: .15rem; }
.cp-form-grid { display: grid; gap: 1rem; }
.cp-form-grid-2 { grid-template-columns: 1fr 1fr; }
.cp-btn-primary {
    display: inline-flex; align-items: center; gap: .4rem;
    padding: .5rem 1.15rem; font-size: .82rem; font-weight: 600;
    color: #fff; background: var(--cp-brand); border: none;
    border-radius: var(--cp-radius-sm); cursor: pointer;
    transition: all .15s; box-shadow: 0 1px 3px rgba(14,165,233,.2);
    font-family: inherit;
}
.cp-btn-primary:hover { background: var(--cp-brand-dark); }

/* ── Responsive ── */
@media (max-width: 767.98px) {
    .cp-tab-bar { gap: .1rem; }
    .cp-tab { padding: .45rem .65rem; font-size: .75rem; }
    .cp-tab span.cp-tab-label-text { display: none; }
    .cp-tab i { font-size: 1rem; }
    .cp-stats-grid { grid-template-columns: repeat(2, 1fr); }
    .cp-form-grid-2 { grid-template-columns: 1fr; }
    .cp-quick-grid { grid-template-columns: repeat(2, 1fr); }
    .cp-table { font-size: .75rem; }
    .cp-table thead th, .cp-table tbody td { padding: .45rem .5rem; }
}
@media (max-width: 480px) {
    .cp-stats-grid { grid-template-columns: 1fr; }
    .cp-quick-grid { grid-template-columns: 1fr; }
}
</style>
@endpush

@section('content')

<div class="cp-portal"
     x-data="{
         tab: '{{ $section }}',
         switchTab(t) {
             this.tab = t;
             window.history.replaceState(null, '', '?section=' + t);
         }
     }"
     x-cloak>

    {{-- ═══ Welcome Header ═══ --}}
    <div class="cp-welcome">
        <div class="cp-welcome-avatar">
            {{ strtoupper(substr($user->first_name ?? $user->name ?? 'U', 0, 1)) }}
        </div>
        <div>
            <h1 class="cp-welcome-name">Welcome, {{ $user->first_name ?? $user->name }}!</h1>
            <p class="cp-welcome-sub">{{ $user->email }}</p>
        </div>
    </div>

    {{-- ═══ Flash ═══ --}}
    @if(session('success'))
        <div class="cp-flash cp-flash-success">
            <i class="bi bi-check-circle"></i>
            <span>{{ session('success') }}</span>
            <button class="cp-flash-dismiss" onclick="this.parentElement.remove()">&times;</button>
        </div>
    @endif
    @if(session('error'))
        <div class="cp-flash cp-flash-error">
            <i class="bi bi-exclamation-circle"></i>
            <span>{{ session('error') }}</span>
            <button class="cp-flash-dismiss" onclick="this.parentElement.remove()">&times;</button>
        </div>
    @endif

    {{-- ═══ Tab Navigation ═══ --}}
    <div class="cp-tab-bar">
        <button class="cp-tab" :class="{ 'active': tab === 'dashboard' }" @click="switchTab('dashboard')">
            <i class="bi bi-speedometer2"></i>
            <span class="cp-tab-label-text">Dashboard</span>
        </button>
        <button class="cp-tab" :class="{ 'active': tab === 'jobs' }" @click="switchTab('jobs')">
            <i class="bi bi-tools"></i>
            <span class="cp-tab-label-text">My Jobs</span>
            @if($openJobs > 0)
                <span class="cp-tab-badge">{{ $openJobs }}</span>
            @endif
        </button>
        <button class="cp-tab" :class="{ 'active': tab === 'estimates' }" @click="switchTab('estimates')">
            <i class="bi bi-file-earmark-text"></i>
            <span class="cp-tab-label-text">Estimates</span>
            @if($pendingEstimates > 0)
                <span class="cp-tab-badge">{{ $pendingEstimates }}</span>
            @endif
        </button>
        <button class="cp-tab" :class="{ 'active': tab === 'devices' }" @click="switchTab('devices')">
            <i class="bi bi-phone"></i>
            <span class="cp-tab-label-text">Devices</span>
        </button>
        <button class="cp-tab" :class="{ 'active': tab === 'account' }" @click="switchTab('account')">
            <i class="bi bi-person-circle"></i>
            <span class="cp-tab-label-text">My Account</span>
        </button>
    </div>

    {{-- ╔══════════════════════════════════════════════════════════════╗
         ║  SECTION: Dashboard                                         ║
         ╚══════════════════════════════════════════════════════════════╝ --}}
    <div x-show="tab === 'dashboard'" x-cloak>
        {{-- Stats --}}
        <div class="cp-stats-grid">
            <div class="cp-stat-card">
                <div class="cp-stat-icon blue"><i class="bi bi-tools"></i></div>
                <div class="cp-stat-body">
                    <div class="cp-stat-label">Total Jobs</div>
                    <div class="cp-stat-value">{{ $totalJobs }}</div>
                </div>
            </div>
            <div class="cp-stat-card">
                <div class="cp-stat-icon orange"><i class="bi bi-clock-history"></i></div>
                <div class="cp-stat-body">
                    <div class="cp-stat-label">In Progress</div>
                    <div class="cp-stat-value">{{ $openJobs }}</div>
                </div>
            </div>
            <div class="cp-stat-card">
                <div class="cp-stat-icon green"><i class="bi bi-check-circle"></i></div>
                <div class="cp-stat-body">
                    <div class="cp-stat-label">Completed</div>
                    <div class="cp-stat-value">{{ $completedJobs }}</div>
                </div>
            </div>
            <div class="cp-stat-card">
                <div class="cp-stat-icon purple"><i class="bi bi-file-earmark-text"></i></div>
                <div class="cp-stat-body">
                    <div class="cp-stat-label">Estimates</div>
                    <div class="cp-stat-value">{{ $totalEstimates }}</div>
                    @if($pendingEstimates > 0)
                        <div class="cp-stat-sub">{{ $pendingEstimates }} pending</div>
                    @endif
                </div>
            </div>
            <div class="cp-stat-card">
                <div class="cp-stat-icon yellow"><i class="bi bi-phone"></i></div>
                <div class="cp-stat-body">
                    <div class="cp-stat-label">My Devices</div>
                    <div class="cp-stat-value">{{ $devices->count() }}</div>
                </div>
            </div>
        </div>

        {{-- Quick Actions --}}
        <div class="cp-card" style="margin-bottom:1.5rem;">
            <div class="cp-card-header">
                <h2 class="cp-card-title"><i class="bi bi-lightning"></i> Quick Actions</h2>
            </div>
            <div class="cp-card-body">
                <div class="cp-quick-grid">
                    <a href="{{ route('tenant.booking.show', ['business' => $business]) }}" class="cp-quick-card">
                        <div class="cp-quick-card-icon" style="background:var(--cp-accent-soft);color:var(--cp-accent);">
                            <i class="bi bi-plus-circle"></i>
                        </div>
                        <span class="cp-quick-card-label">Book a Repair</span>
                    </a>
                    <div class="cp-quick-card" @click="switchTab('jobs')">
                        <div class="cp-quick-card-icon"><i class="bi bi-tools"></i></div>
                        <span class="cp-quick-card-label">View All Jobs</span>
                    </div>
                    <div class="cp-quick-card" @click="switchTab('estimates')">
                        <div class="cp-quick-card-icon" style="background:var(--cp-info-soft);color:var(--cp-info);">
                            <i class="bi bi-file-earmark-text"></i>
                        </div>
                        <span class="cp-quick-card-label">My Estimates</span>
                    </div>
                    <div class="cp-quick-card" @click="switchTab('devices')">
                        <div class="cp-quick-card-icon" style="background:var(--cp-warning-soft);color:var(--cp-warning);">
                            <i class="bi bi-phone"></i>
                        </div>
                        <span class="cp-quick-card-label">My Devices</span>
                    </div>
                    <div class="cp-quick-card" @click="switchTab('account')">
                        <div class="cp-quick-card-icon" style="background:var(--cp-success-soft);color:var(--cp-success);">
                            <i class="bi bi-person-gear"></i>
                        </div>
                        <span class="cp-quick-card-label">Account Settings</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Recent Jobs --}}
        <div class="cp-card">
            <div class="cp-card-header">
                <h2 class="cp-card-title"><i class="bi bi-clock-history"></i> Recent Jobs</h2>
                @if($totalJobs > 0)
                    <a href="javascript:void(0)" @click="switchTab('jobs')"
                       style="font-size:.78rem;font-weight:600;color:var(--cp-brand);text-decoration:none;">
                        View All <i class="bi bi-arrow-right"></i>
                    </a>
                @endif
            </div>
            <div class="cp-card-body" style="padding:0;">
                @if($jobs->isEmpty())
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
                            @foreach($jobs->take(5) as $job)
                                <tr>
                                    <td><strong style="color:var(--cp-brand);">#{{ $job->job_number ?? $job->case_number ?? $job->id }}</strong></td>
                                    <td>{{ $job->title ?? '—' }}</td>
                                    <td>
                                        @if($job->closed_at)
                                            <span class="cp-badge cp-badge-success">Completed</span>
                                        @else
                                            <span class="cp-badge cp-badge-warning">{{ ucfirst(str_replace(['-','_'],' ',$job->status_slug ?? 'In Progress')) }}</span>
                                        @endif
                                    </td>
                                    <td style="color:var(--cp-text-3);font-size:.78rem;">{{ $job->created_at?->format('M d, Y') ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>
    </div>

    {{-- ╔══════════════════════════════════════════════════════════════╗
         ║  SECTION: My Jobs                                           ║
         ╚══════════════════════════════════════════════════════════════╝ --}}
    <div x-show="tab === 'jobs'" x-cloak>
        <div class="cp-card">
            <div class="cp-card-header">
                <h2 class="cp-card-title"><i class="bi bi-tools"></i> All Jobs</h2>
                <span style="font-size:.75rem;color:var(--cp-text-3);">{{ $totalJobs }} total</span>
            </div>
            <div class="cp-card-body" style="padding:0;">
                @if($jobs->isEmpty())
                    <div class="cp-empty">
                        <div class="cp-empty-icon"><i class="bi bi-inbox"></i></div>
                        <div class="cp-empty-title">No jobs found</div>
                        <div class="cp-empty-text">When you book a repair, your jobs will appear here.</div>
                    </div>
                @else
                    <div style="overflow-x:auto;">
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
                                    <td><strong style="color:var(--cp-brand);">#{{ $job->job_number ?? $job->case_number ?? $job->id }}</strong></td>
                                    <td>
                                        <div style="font-weight:600;">{{ $job->title ?? '—' }}</div>
                                        @if($job->case_detail)
                                            <div style="font-size:.7rem;color:var(--cp-text-3);margin-top:.1rem;">
                                                {{ \Illuminate\Support\Str::limit($job->case_detail, 60) }}
                                            </div>
                                        @endif
                                    </td>
                                    <td>
                                        @if($job->closed_at)
                                            <span class="cp-badge cp-badge-success">Completed</span>
                                        @else
                                            <span class="cp-badge cp-badge-warning">{{ ucfirst(str_replace(['-','_'],' ',$job->status_slug ?? 'In Progress')) }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        @php $ps = $job->payment_status_slug ?? 'pending'; @endphp
                                        @if($ps === 'paid')
                                            <span class="cp-badge cp-badge-success">Paid</span>
                                        @elseif($ps === 'partial')
                                            <span class="cp-badge cp-badge-info">Partial</span>
                                        @else
                                            <span class="cp-badge cp-badge-default">{{ ucfirst(str_replace(['-','_'],' ',$ps)) }}</span>
                                        @endif
                                    </td>
                                    <td style="color:var(--cp-text-3);font-size:.78rem;white-space:nowrap;">{{ $job->created_at?->format('M d, Y') ?? '—' }}</td>
                                    <td style="color:var(--cp-text-3);font-size:.78rem;white-space:nowrap;">{{ $job->pickup_date?->format('M d, Y') ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- ╔══════════════════════════════════════════════════════════════╗
         ║  SECTION: My Estimates                                      ║
         ╚══════════════════════════════════════════════════════════════╝ --}}
    <div x-show="tab === 'estimates'" x-cloak>
        <div class="cp-card">
            <div class="cp-card-header">
                <h2 class="cp-card-title"><i class="bi bi-file-earmark-text"></i> All Estimates</h2>
                <span style="font-size:.75rem;color:var(--cp-text-3);">{{ $totalEstimates }} total</span>
            </div>
            <div class="cp-card-body" style="padding:0;">
                @if($estimates->isEmpty())
                    <div class="cp-empty">
                        <div class="cp-empty-icon"><i class="bi bi-file-earmark-x"></i></div>
                        <div class="cp-empty-title">No estimates yet</div>
                        <div class="cp-empty-text">When the shop sends you an estimate, it will appear here.</div>
                    </div>
                @else
                    <div style="overflow-x:auto;">
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
                                    <td><strong style="color:var(--cp-brand);">#{{ $estimate->case_number ?? $estimate->id }}</strong></td>
                                    <td>
                                        <div style="font-weight:600;">{{ $estimate->title ?? '—' }}</div>
                                        @if($estimate->case_detail)
                                            <div style="font-size:.7rem;color:var(--cp-text-3);margin-top:.1rem;">
                                                {{ \Illuminate\Support\Str::limit($estimate->case_detail, 60) }}
                                            </div>
                                        @endif
                                    </td>
                                    <td>
                                        @php $st = $estimate->status ?? 'draft'; @endphp
                                        @if($st === 'approved')
                                            <span class="cp-badge cp-badge-success">Approved</span>
                                        @elseif($st === 'rejected')
                                            <span class="cp-badge cp-badge-danger">Rejected</span>
                                        @elseif($st === 'sent')
                                            <span class="cp-badge cp-badge-primary">Sent</span>
                                        @elseif($st === 'pending')
                                            <span class="cp-badge cp-badge-warning">Pending</span>
                                        @else
                                            <span class="cp-badge cp-badge-default">{{ ucfirst($st) }}</span>
                                        @endif
                                    </td>
                                    <td style="color:var(--cp-text-3);font-size:.78rem;white-space:nowrap;">{{ $estimate->created_at?->format('M d, Y') ?? '—' }}</td>
                                    <td style="color:var(--cp-text-3);font-size:.78rem;white-space:nowrap;">{{ $estimate->sent_at?->format('M d, Y') ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- ╔══════════════════════════════════════════════════════════════╗
         ║  SECTION: My Devices                                        ║
         ╚══════════════════════════════════════════════════════════════╝ --}}
    <div x-show="tab === 'devices'" x-cloak>
        <div class="cp-card">
            <div class="cp-card-header">
                <h2 class="cp-card-title"><i class="bi bi-phone"></i> My Devices</h2>
                <span style="font-size:.75rem;color:var(--cp-text-3);">{{ $devices->count() }} registered</span>
            </div>
            <div class="cp-card-body" style="padding:0;">
                @if($devices->isEmpty())
                    <div class="cp-empty">
                        <div class="cp-empty-icon"><i class="bi bi-phone"></i></div>
                        <div class="cp-empty-title">No devices registered</div>
                        <div class="cp-empty-text">Devices will be added when you book a repair.</div>
                    </div>
                @else
                    <div style="overflow-x:auto;">
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
                            @foreach($devices as $d)
                                <tr>
                                    <td style="font-weight:600;">{{ $d->device?->name ?? 'Unknown Device' }}</td>
                                    <td>{{ $d->label ?? '—' }}</td>
                                    <td>
                                        @if($d->serial)
                                            <code style="font-size:.72rem;background:#f1f5f9;padding:.1rem .35rem;border-radius:4px;">{{ $d->serial }}</code>
                                        @else
                                            <span style="color:var(--cp-text-3);">—</span>
                                        @endif
                                    </td>
                                    <td style="font-size:.78rem;color:var(--cp-text-2);max-width:180px;">{{ \Illuminate\Support\Str::limit($d->notes, 50) ?? '—' }}</td>
                                    <td style="color:var(--cp-text-3);font-size:.78rem;white-space:nowrap;">{{ $d->created_at?->format('M d, Y') ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- ╔══════════════════════════════════════════════════════════════╗
         ║  SECTION: My Account                                        ║
         ╚══════════════════════════════════════════════════════════════╝ --}}
    <div x-show="tab === 'account'" x-cloak>
        {{-- Personal Info --}}
        <div class="cp-card">
            <div class="cp-card-header">
                <h2 class="cp-card-title"><i class="bi bi-person"></i> Personal Information</h2>
            </div>
            <div class="cp-card-body">
                <form method="POST" action="{{ route('tenant.customer.account.update', ['business' => $business]) }}">
                    @csrf

                    <div class="cp-form-grid cp-form-grid-2">
                        <div class="cp-form-group">
                            <label class="cp-form-label" for="first_name">First Name *</label>
                            <input type="text" id="first_name" name="first_name" class="cp-form-input"
                                   value="{{ old('first_name', $user->first_name) }}" required>
                            @error('first_name') <div class="cp-form-error">{{ $message }}</div> @enderror
                        </div>
                        <div class="cp-form-group">
                            <label class="cp-form-label" for="last_name">Last Name</label>
                            <input type="text" id="last_name" name="last_name" class="cp-form-input"
                                   value="{{ old('last_name', $user->last_name) }}">
                            @error('last_name') <div class="cp-form-error">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="cp-form-grid cp-form-grid-2">
                        <div class="cp-form-group">
                            <label class="cp-form-label" for="email">Email Address *</label>
                            <input type="email" id="email" name="email" class="cp-form-input"
                                   value="{{ old('email', $user->email) }}" required>
                            @error('email') <div class="cp-form-error">{{ $message }}</div> @enderror
                        </div>
                        <div class="cp-form-group">
                            <label class="cp-form-label" for="phone">Phone</label>
                            <input type="tel" id="phone" name="phone" class="cp-form-input"
                                   value="{{ old('phone', $user->phone) }}" placeholder="+1 555 123 4567">
                            @error('phone') <div class="cp-form-error">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="cp-form-group">
                        <label class="cp-form-label" for="company">Company</label>
                        <input type="text" id="company" name="company" class="cp-form-input"
                               value="{{ old('company', $user->company) }}" placeholder="Optional">
                        @error('company') <div class="cp-form-error">{{ $message }}</div> @enderror
                    </div>

                    <hr style="border:0;border-top:1px solid var(--cp-border);margin:1.25rem 0;">

                    <h3 style="font-size:.8rem;font-weight:700;color:var(--cp-text);margin-bottom:.85rem;">
                        <i class="bi bi-geo-alt" style="color:var(--cp-brand);"></i> Address
                    </h3>

                    <div class="cp-form-group">
                        <label class="cp-form-label" for="address">Street Address</label>
                        <input type="text" id="address" name="address" class="cp-form-input"
                               value="{{ old('address', $user->address) }}">
                        @error('address') <div class="cp-form-error">{{ $message }}</div> @enderror
                    </div>

                    <div class="cp-form-grid cp-form-grid-2">
                        <div class="cp-form-group">
                            <label class="cp-form-label" for="city">City</label>
                            <input type="text" id="city" name="city" class="cp-form-input"
                                   value="{{ old('city', $user->city) }}">
                        </div>
                        <div class="cp-form-group">
                            <label class="cp-form-label" for="state">State / Province</label>
                            <input type="text" id="state" name="state" class="cp-form-input"
                                   value="{{ old('state', $user->state) }}">
                        </div>
                    </div>

                    <div class="cp-form-grid cp-form-grid-2">
                        <div class="cp-form-group">
                            <label class="cp-form-label" for="zip">ZIP / Postal Code</label>
                            <input type="text" id="zip" name="zip" class="cp-form-input"
                                   value="{{ old('zip', $user->zip) }}">
                        </div>
                        <div class="cp-form-group">
                            <label class="cp-form-label" for="country">Country</label>
                            <input type="text" id="country" name="country" class="cp-form-input"
                                   value="{{ old('country', $user->country) }}">
                        </div>
                    </div>

                    <div style="padding-top:.85rem;border-top:1px solid var(--cp-border);margin-top:.85rem;">
                        <button type="submit" class="cp-btn-primary">
                            <i class="bi bi-check-lg"></i> Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Account Info --}}
        <div class="cp-card">
            <div class="cp-card-header">
                <h2 class="cp-card-title"><i class="bi bi-shield-check"></i> Account Information</h2>
            </div>
            <div class="cp-card-body">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
                    <div>
                        <div class="cp-form-label" style="margin-bottom:.1rem;">Account Created</div>
                        <div style="font-size:.84rem;color:var(--cp-text);">{{ $user->created_at?->format('F j, Y') ?? '—' }}</div>
                    </div>
                    <div>
                        <div class="cp-form-label" style="margin-bottom:.1rem;">Email Verified</div>
                        <div style="font-size:.84rem;">
                            @if($user->email_verified_at)
                                <span style="color:var(--cp-success);"><i class="bi bi-check-circle-fill"></i> Verified</span>
                            @else
                                <span style="color:var(--cp-warning);"><i class="bi bi-exclamation-circle"></i> Not verified</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

@endsection

@push('page-scripts')
<script>
    // Ensure Alpine is available (public layout may already load it via Livewire)
    document.addEventListener('alpine:init', () => {});
</script>
@endpush
