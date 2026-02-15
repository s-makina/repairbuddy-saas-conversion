@extends('tenant.layouts.myaccount', ['title' => $pageTitle ?? __('Add Service')])

@section('content')
	<div class="container-fluid p-3">
		@if ($errors->any())
			<div class="alert alert-danger">
				<div class="fw-semibold mb-1">{{ __( 'Please fix the errors below.' ) }}</div>
				<ul class="mb-0">
					@foreach ($errors->all() as $error)
						<li>{{ $error }}</li>
					@endforeach
				</ul>
			</div>
		@endif

		<div class="row justify-content-center">
			<div class="col-12 col-lg-7 col-xl-8">
				<div class="card">
					<div class="card-header">
						<h5 class="card-title mb-0">{{ __('Add Service') }}</h5>
					</div>
					<div class="card-body">
						<form method="post" action="{{ route('tenant.operations.services.store', ['business' => $tenant->slug]) }}">
							@csrf
							<div class="row g-3">
								<div class="col-12">
									<div class="row align-items-start">
										<label for="name" class="col-sm-3 col-form-label">{{ __('Service Name') }} *</label>
										<div class="col-sm-9">
											<input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', '') }}" required>
											@error('name')
												<div class="invalid-feedback">{{ $message }}</div>
											@enderror
										</div>
									</div>
								</div>

								<div class="col-12">
									<div class="row align-items-start">
										<label for="service_type_id" class="col-sm-3 col-form-label">{{ __('Type') }}</label>
										<div class="col-sm-9">
											<select name="service_type_id" id="service_type_id" class="form-select @error('service_type_id') is-invalid @enderror">
												@foreach (($typeOptions ?? []) as $k => $v)
													<option value="{{ $k }}" @selected((string) old('service_type_id', '') === (string) $k)>{{ $v }}</option>
												@endforeach
											</select>
											@error('service_type_id')
												<div class="invalid-feedback">{{ $message }}</div>
											@enderror
										</div>
									</div>
								</div>

								<div class="col-12">
									<div class="row align-items-start">
										<label for="description" class="col-sm-3 col-form-label">{{ __('Description') }}</label>
										<div class="col-sm-9">
											<textarea name="description" id="description" rows="4" class="form-control @error('description') is-invalid @enderror">{{ old('description', '') }}</textarea>
											@error('description')
												<div class="invalid-feedback">{{ $message }}</div>
											@enderror
										</div>
									</div>
								</div>

								<div class="col-12"><hr class="my-1" /></div>

								<div class="col-12">
									<div class="row align-items-start">
										<label for="service_code" class="col-sm-3 col-form-label">{{ __('Service Code') }}</label>
										<div class="col-sm-9">
											<input type="text" name="service_code" id="service_code" class="form-control @error('service_code') is-invalid @enderror" value="{{ old('service_code', '') }}">
											@error('service_code')
												<div class="invalid-feedback">{{ $message }}</div>
											@enderror
										</div>
									</div>
								</div>

								<div class="col-12">
									<div class="row align-items-start">
										<label for="time_required" class="col-sm-3 col-form-label">{{ __('Time Required') }}</label>
										<div class="col-sm-9">
											<input type="text" name="time_required" id="time_required" class="form-control @error('time_required') is-invalid @enderror" value="{{ old('time_required', '') }}">
											@error('time_required')
												<div class="invalid-feedback">{{ $message }}</div>
											@enderror
										</div>
									</div>
								</div>

								<div class="col-12">
									<div class="row align-items-start">
										<label for="warranty" class="col-sm-3 col-form-label">{{ __('Warranty') }}</label>
										<div class="col-sm-9">
											<input type="text" name="warranty" id="warranty" class="form-control @error('warranty') is-invalid @enderror" value="{{ old('warranty', '') }}">
											@error('warranty')
												<div class="invalid-feedback">{{ $message }}</div>
											@enderror
										</div>
									</div>
								</div>

								<div class="col-12"><hr class="my-1" /></div>

								<div class="col-12">
									<div class="row align-items-start">
										<label for="base_price" class="col-sm-3 col-form-label">{{ __('Base Price') }}</label>
										<div class="col-sm-9">
											<div class="input-group">
												<input type="number" step="0.01" min="0" name="base_price" id="base_price" class="form-control @error('base_price') is-invalid @enderror" value="{{ old('base_price', '') }}" placeholder="0.00">
												<span class="input-group-text">{{ $tenantCurrency !== '' ? $tenantCurrency : __('Currency') }}</span>
											</div>
											@error('base_price')
												<div class="invalid-feedback d-block">{{ $message }}</div>
											@enderror
											<div class="form-text">{{ __('Leave empty to disable base pricing.') }}</div>
										</div>
									</div>
								</div>

								<input type="hidden" name="base_price_currency" value="{{ old('base_price_currency', $tenantCurrency) }}" />

								<div class="col-12">
									<div class="row align-items-start">
										<label for="tax_id" class="col-sm-3 col-form-label">{{ __('Tax') }}</label>
										<div class="col-sm-9">
											<select name="tax_id" id="tax_id" class="form-select @error('tax_id') is-invalid @enderror">
												@foreach (($taxOptions ?? []) as $k => $v)
													<option value="{{ $k }}" @selected((string) old('tax_id', '') === (string) $k)>{{ $v }}</option>
												@endforeach
											</select>
											@error('tax_id')
												<div class="invalid-feedback">{{ $message }}</div>
											@enderror
										</div>
									</div>
								</div>

								<div class="col-12">
									<div class="row align-items-start">
										<label class="col-sm-3 col-form-label">{{ __('Availability') }}</label>
										<div class="col-sm-9">
											<div class="form-check form-switch">
												<input class="form-check-input" type="checkbox" role="switch" id="pick_up_delivery_available" name="pick_up_delivery_available" value="1" @checked((bool) old('pick_up_delivery_available', false))>
												<label class="form-check-label" for="pick_up_delivery_available">{{ __('Pick Up & Delivery Available') }}</label>
											</div>
											<div class="form-check form-switch mt-2">
												<input class="form-check-input" type="checkbox" role="switch" id="laptop_rental_available" name="laptop_rental_available" value="1" @checked((bool) old('laptop_rental_available', false))>
												<label class="form-check-label" for="laptop_rental_available">{{ __('Laptop Rental Availability') }}</label>
											</div>
										</div>
									</div>
								</div>

								<div class="col-12">
									<div class="d-flex justify-content-end gap-2">
										<a class="btn btn-outline-secondary" href="{{ route('tenant.operations.services.index', ['business' => $tenant->slug]) }}">{{ __('Cancel') }}</a>
										<button type="submit" class="btn btn-primary">{{ __('Save') }}</button>
									</div>
								</div>
							</div>
						</form>
					</div>
				</div>
			</div>

			<div class="col-12 col-lg-5 col-xl-4">
				<div class="card">
					<div class="card-header">
						<h5 class="card-title mb-0">{{ __('Recent services') }}</h5>
					</div>
					<div class="card-body p-0">
						<div class="list-group list-group-flush">
							@forelse (($recentServices ?? []) as $s)
								@php
									$editUrl = route('tenant.operations.services.edit', ['business' => $tenant->slug, 'service' => $s->id]);
									$subtitle = (string) ($s->type?->name ?? '');
								@endphp
								<div class="list-group-item d-flex align-items-center justify-content-between">
									<div class="text-truncate">
										<div class="fw-normal text-truncate">{{ (string) ($s->name ?? '') }}</div>
										@if ($subtitle !== '')
											<div class="small text-muted text-truncate">{{ $subtitle }}</div>
										@endif
									</div>
									<a class="btn btn-sm btn-outline-primary ms-3" href="{{ $editUrl }}" title="{{ __('Edit') }}" aria-label="{{ __('Edit') }}">
										<i class="bi bi-pencil"></i>
									</a>
								</div>
							@empty
								<div class="list-group-item">
									<div class="text-muted">{{ __('No services yet.') }}</div>
								</div>
							@endforelse
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
@endsection

@push('page-scripts')
	<script>
		(function () {
			if (!window.jQuery || !window.jQuery.fn || typeof window.jQuery.fn.select2 !== 'function') {
				return;
			}

			var $type = window.jQuery('#service_type_id');
			if ($type.length) {
				$type.select2({ width: '100%' });
			}

			var $tax = window.jQuery('#tax_id');
			if ($tax.length) {
				$tax.select2({ width: '100%' });
			}
		})();
	</script>
@endpush
