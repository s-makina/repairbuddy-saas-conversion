@extends('tenant.layouts.myaccount', ['title' => $pageTitle ?? __('Edit Brand')])

@section('content')
	<div class="container-fluid p-3">
		@if ($errors->any())
			<div class="notice notice-error">
				<p>{{ __( 'Please fix the errors below.' ) }}</p>
			</div>
		@endif

		<x-settings.card :title="__('Edit Brand')">
			<form method="post" action="{{ route('tenant.operations.brands.update', ['business' => $tenant->slug, 'brand' => $brand->id]) }}">
				@csrf
				<div class="row g-3">
					<div class="col-md-6">
						<x-settings.field for="name" :label="__('Brand name')" errorKey="name" class="wcrb-settings-field">
							<x-settings.input name="name" id="name" :value="old('name', (string) ($brand->name ?? ''))" />
						</x-settings.field>
					</div>
				</div>
				<x-settings.actions>
					<button type="submit" class="button button-primary">{{ __('Save') }}</button>
					<a class="btn btn-outline-secondary" href="{{ route('tenant.operations.brands.index', ['business' => $tenant->slug]) }}">{{ __('Cancel') }}</a>
				</x-settings.actions>
			</form>
		</x-settings.card>
	</div>
@endsection
