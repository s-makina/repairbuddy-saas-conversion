@extends('tenant.layouts.myaccount', ['title' => 'Reviews'])

@section('content')
@php
$sing_device_label = is_string($sing_device_label ?? null) ? (string) $sing_device_label : 'Device';

$userRoles = [];
if (is_array($userRoles ?? null)) {
    $userRoles = $userRoles;
} elseif (is_string($userRole ?? null) && $userRole !== '') {
    $userRoles = [(string) $userRole];
} elseif (is_object($user ?? null) && is_string($user->role ?? null) && $user->role !== '') {
    $userRoles = [(string) $user->role];
}
$userRoles = array_values(array_unique(array_filter(array_map('strval', $userRoles))));

$is_admin_user = (bool) ($is_admin_user ?? (! empty(array_intersect(['administrator', 'store_manager', 'technician'], $userRoles))));
$colspan = $is_admin_user ? 9 : 8;

$can_view_reviews = (bool) ($can_view_reviews ?? (! empty(array_intersect(['administrator', 'store_manager', 'customer', 'technician'], $userRoles))));

$reviews_data = is_array($reviews_data ?? null) ? $reviews_data : [];
$stats_html = (string) ($reviews_data['stats'] ?? '');
$filters_html = (string) ($reviews_data['filters'] ?? '');
$rows_html = (string) ($reviews_data['rows'] ?? '');
$pagination_html = (string) ($reviews_data['pagination'] ?? '');
@endphp

@if ( $can_view_reviews )
    <main class="dashboard-content container-fluid py-4">
        <div class="alert alert-danger">
            <i class="bi bi-exclamation-triangle me-2"></i>
            {{ __( 'You do not have permission to view reviews.' ) }}
        </div>
    </main>
@else
<!-- Reviews Content -->
<main class="dashboard-content container-fluid py-4">
    
    <!-- Stats Overview -->
    {!! $stats_html !!}
    
    <!-- Search and Filters -->
    {!! $filters_html !!}
    
    <!-- Reviews Table -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">
                <i class="bi bi-star me-2"></i>
                {{ $is_admin_user ? __( 'All Reviews' ) : __( 'My Reviews' ) }}
            </h5>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="window.print()">
                    <i class="bi bi-printer me-1"></i>
                    {{ __( 'Print' ) }}
                </button>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive reviewslistcustomer" id="reviewslistcustomer">
                <div class="aj_msg"></div>
                <table class="table table-hover mb-0 table-striped">
                    <thead class="table-light">
                        <tr>
                            @if ( $is_admin_user )
                            <th class="ps-4">{{ __( 'ID' ) }}</th>
                            @endif
                            <th class="ps-4">{{ __( 'Job ID' ) }}</th>
                            <th>{{ __( 'Case Number' ) }}</th>
                            <th>{{ $sing_device_label }}</th>
                            <th>{{ __( 'Order Date' ) }}</th>
                            @if ( ! $is_admin_user )
                            <th>{{ __( 'Order Total' ) }}</th>
                            @endif
                            <th>{{ __( 'Rating' ) }}</th>
                            <th>{{ __( 'Review Summary' ) }}</th>
                            @if ( $is_admin_user )
                            <th>{{ __( 'Customer' ) }}</th>
                            @endif
                            <th class="text-end pe-4">{{ __( 'Actions' ) }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        {!! $rows_html !!}
                    </tbody>
                </table>
            </div>
        </div>
        <!-- Pagination -->
        {!! $pagination_html !!}
    </div>
    
</main>
@endif
@endsection
