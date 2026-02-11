@extends('tenant.layouts.myaccount', ['title' => 'Devices'])

@section('content')
@php
    $devices_data = is_array($devices_data ?? null) ? $devices_data : ['stats' => '', 'filters' => '', 'rows' => '', 'pagination' => ''];

    $wc_device_label = is_string($wc_device_label ?? null) ? (string) $wc_device_label : __('Devices');
    $sing_device_label = is_string($sing_device_label ?? null) ? (string) $sing_device_label : __('Device');
    $wc_device_id_imei_label = is_string($wc_device_id_imei_label ?? null) ? (string) $wc_device_id_imei_label : __('ID/IMEI');
    $wc_pin_code_label = is_string($wc_pin_code_label ?? null) ? (string) $wc_pin_code_label : __('Pin Code/Password');

    $is_admin_user = (bool) ($is_admin_user ?? false);
    $add_device_form_html = is_string($add_device_form_html ?? null) ? (string) $add_device_form_html : '';
@endphp
<!-- Devices Content -->
<main class="dashboard-content container-fluid py-4">
    
    <!-- Stats Overview -->
    {!! $devices_data['stats'] ?? '' !!}
    
    <!-- Search and Filters -->
    {!! $devices_data['filters'] ?? '' !!}
    
    <!-- Devices Table -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">
                <i class="bi bi-device-hdd me-2"></i>
                {{ $is_admin_user ? sprintf( __( 'All %s' ), $wc_device_label ) : sprintf( __( 'My %s' ), $wc_device_label ) }}
            </h5>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addDeviceModal">
                    <i class="bi bi-plus-circle me-1"></i>
                    {{ sprintf( __( 'Add %s' ), $sing_device_label ) }}
                </button>
                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="window.print()">
                    <i class="bi bi-printer me-1"></i>
                    {{ __( 'Print' ) }}
                </button>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive deviceslistcustomer" id="deviceslistcustomer">
                <div class="aj_msg"></div>
                <table class="table table-hover mb-0 table-striped">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">{{ __( 'ID' ) }}</th>
                            <th>{{ $sing_device_label }}</th>
                            <th>{{ $wc_device_id_imei_label }}</th>
                            <th>{{ $wc_pin_code_label }}</th>
                            <th>{{ sprintf( __( '%s Details' ), $sing_device_label ) }}</th>
                            @if ( $is_admin_user )
                            <th>{{ __( 'Customer' ) }}</th>
                            @endif
                            <th class="text-end pe-4">{{ __( 'Actions' ) }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        {!! $devices_data['rows'] ?? '' !!}
                    </tbody>
                </table>
            </div>
        </div>
        <!-- Pagination -->
        {!! $devices_data['pagination'] ?? '' !!}
    </div>
    
</main>
{!! $add_device_form_html !!}
@endsection
