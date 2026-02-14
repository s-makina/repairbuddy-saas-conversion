@extends('tenant.layouts.myaccount', ['title' => $pageTitle ?? __('Edit Device')])

@section('content')
	<div class="container-fluid p-3">
		@if ($errors->any())
			<div class="notice notice-error">
				<p>{{ __( 'Please fix the errors below.' ) }}</p>
			</div>
		@endif

		<x-settings.card :title="__('Edit Device')">
			<form method="post" action="{{ route('tenant.operations.devices.update', ['business' => $tenant->slug, 'device' => $device->id]) }}">
				@csrf
				<div class="row g-3">
					<div class="col-md-4">
						<x-settings.field for="model" :label="__('Model')" errorKey="model" class="wcrb-settings-field">
							<x-settings.input name="model" id="model" :value="old('model', (string) ($device->model ?? ''))" />
						</x-settings.field>
					</div>
					<div class="col-md-3">
						<x-settings.field for="device_type_id" :label="__('Type')" errorKey="device_type_id" class="wcrb-settings-field">
							<x-settings.select name="device_type_id" id="device_type_id" :options="$typeOptions" :value="old('device_type_id', (string) ($device->device_type_id ?? ''))" />
						</x-settings.field>
					</div>
					<div class="col-md-3">
						<x-settings.field for="device_brand_id" :label="__('Brand')" errorKey="device_brand_id" class="wcrb-settings-field">
							<x-settings.select name="device_brand_id" id="device_brand_id" :options="$brandOptions" :value="old('device_brand_id', (string) ($device->device_brand_id ?? ''))" />
						</x-settings.field>
					</div>
					<div class="col-md-2">
						<x-settings.field for="parent_device_id" :label="__('Parent')" errorKey="parent_device_id" class="wcrb-settings-field">
							<x-settings.select name="parent_device_id" id="parent_device_id" :options="$parentOptions" :value="old('parent_device_id', (string) ($device->parent_device_id ?? ''))" />
						</x-settings.field>
					</div>
					<div class="col-md-6">
						@php
							$disableChecked = (bool) old('disable_in_booking_form', (bool) ($device->disable_in_booking_form ?? false));
						@endphp
						<x-settings.option-toggle
							name="disable_in_booking_form"
							id="disable_in_booking_form"
							:checked="$disableChecked"
							value="1"
							uncheckedValue="0"
							:label="__('Disable in booking forms')"
							:description="''"
						/>
					</div>
					<div class="col-md-6">
						@php
							$otherChecked = (bool) old('is_other', (bool) ($device->is_other ?? false));
						@endphp
						<x-settings.option-toggle
							name="is_other"
							id="is_other"
							:checked="$otherChecked"
							value="1"
							uncheckedValue="0"
							:label="__('Is Other device')"
							:description="''"
						/>
					</div>
				</div>
				<x-settings.actions>
					<button type="submit" class="button button-primary">{{ __('Save') }}</button>
					<a class="btn btn-outline-secondary" href="{{ route('tenant.operations.devices.index', ['business' => $tenant->slug]) }}">{{ __('Cancel') }}</a>
				</x-settings.actions>
			</form>
		</x-settings.card>
	</div>
@endsection
