@extends('tenant.layouts.myaccount', ['title' => $pageTitle ?? __('Add Type')])

@section('content')
	<div class="container-fluid p-3">
		@if ($errors->any())
			<div class="notice notice-error">
				<p>{{ __( 'Please fix the errors below.' ) }}</p>
			</div>
		@endif

		<x-settings.card :title="__('Add Type')">
			<form method="post" action="{{ route('tenant.operations.brand_types.store', ['business' => $tenant->slug]) }}">
				@csrf
				<div class="row g-3">
					<div class="col-md-4">
						<x-settings.field for="name" :label="__('Type name')" errorKey="name" class="wcrb-settings-field">
							<x-settings.input name="name" id="name" :value="old('name', '')" />
						</x-settings.field>
					</div>
					<div class="col-md-4">
						<x-settings.field for="parent_id" :label="__('Parent')" errorKey="parent_id" class="wcrb-settings-field">
							<x-settings.select name="parent_id" id="parent_id" :options="$parentOptions" :value="old('parent_id', '')" />
						</x-settings.field>
					</div>
					<div class="col-md-4">
						<x-settings.field for="description" :label="__('Description')" errorKey="description" class="wcrb-settings-field">
							<x-settings.input name="description" id="description" :value="old('description', '')" />
						</x-settings.field>
					</div>
				</div>
				<x-settings.actions>
					<button type="submit" class="button button-primary">{{ __('Save') }}</button>
					<a class="btn btn-outline-secondary" href="{{ route('tenant.operations.brand_types.index', ['business' => $tenant->slug]) }}">{{ __('Cancel') }}</a>
				</x-settings.actions>
			</form>
		</x-settings.card>
	</div>
@endsection
