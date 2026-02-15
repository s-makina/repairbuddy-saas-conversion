@extends('tenant.layouts.myaccount', ['title' => $pageTitle ?? __('Edit Service')])

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
						<h5 class="card-title mb-0">{{ __('Edit Service') }}</h5>
					</div>
					<div class="card-body">
						<form method="post" action="{{ route('tenant.operations.services.update', ['business' => $tenant->slug, 'service' => $service->id]) }}">
							@csrf
							<div class="row g-3">
								<div class="col-12">
									<div class="row align-items-start">
										<label for="name" class="col-sm-3 col-form-label">{{ __('Service Name') }} *</label>
										<div class="col-sm-9">
											<input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', (string) ($service->name ?? '')) }}" required>
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
													<option value="{{ $k }}" @selected((string) old('service_type_id', (string) ($service->service_type_id ?? '')) === (string) $k)>{{ $v }}</option>
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
											<textarea name="description" id="description" rows="4" class="form-control @error('description') is-invalid @enderror">{{ old('description', (string) ($service->description ?? '')) }}</textarea>
											@error('description')
												<div class="invalid-feedback">{{ $message }}</div>
											@enderror
										</div>
									</div>
								</div>

								<div class="col-12">
									<div class="row align-items-start">
										<label for="service_code" class="col-sm-3 col-form-label">{{ __('Service Code') }}</label>
										<div class="col-sm-9">
											<input type="text" name="service_code" id="service_code" class="form-control @error('service_code') is-invalid @enderror" value="{{ old('service_code', (string) ($service->service_code ?? '')) }}">
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
											<input type="text" name="time_required" id="time_required" class="form-control @error('time_required') is-invalid @enderror" value="{{ old('time_required', (string) ($service->time_required ?? '')) }}">
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
											<input type="text" name="warranty" id="warranty" class="form-control @error('warranty') is-invalid @enderror" value="{{ old('warranty', (string) ($service->warranty ?? '')) }}">
											@error('warranty')
												<div class="invalid-feedback">{{ $message }}</div>
											@enderror
										</div>
									</div>
								</div>

								@php
									$basePriceUi = '';
									if (is_numeric($service->base_price_amount_cents) && (int) $service->base_price_amount_cents !== 0) {
										$basePriceUi = number_format(((int) $service->base_price_amount_cents) / 100, 2, '.', '');
									}
								@endphp

								<div class="col-12">
									<div class="row align-items-start">
										<label for="base_price" class="col-sm-3 col-form-label">{{ __('Base Price') }}</label>
										<div class="col-sm-9">
											<div class="input-group">
												<input type="number" step="0.01" min="0" name="base_price" id="base_price" class="form-control @error('base_price') is-invalid @enderror" value="{{ old('base_price', $basePriceUi) }}" placeholder="0.00">
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
													<option value="{{ $k }}" @selected((string) old('tax_id', (string) ($service->tax_id ?? '')) === (string) $k)>{{ $v }}</option>
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
											<div class="form-check form-switch d-flex align-items-center">
												<input class="form-check-input" type="checkbox" role="switch" id="pick_up_delivery_available" name="pick_up_delivery_available" value="1" @checked((bool) old('pick_up_delivery_available', (bool) ($service->pick_up_delivery_available ?? false)))>
												<label class="form-check-label ms-2" for="pick_up_delivery_available">{{ __('Pick Up & Delivery Available') }}</label>
											</div>
											<div class="form-check form-switch d-flex align-items-center mt-2">
												<input class="form-check-input" type="checkbox" role="switch" id="laptop_rental_available" name="laptop_rental_available" value="1" @checked((bool) old('laptop_rental_available', (bool) ($service->laptop_rental_available ?? false)))>
												<label class="form-check-label ms-2" for="laptop_rental_available">{{ __('Laptop Rental Availability') }}</label>
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
				<div class="card" id="service_device_pricing">
					<div class="card-header">
						<h5 class="card-title mb-0">{{ __('Set Prices for Devices') }}</h5>
					</div>
					<div class="card-body">
						<div class="alert alert-warning mb-3">
							{{ __('Device price overrides brand price. Brand price overrides type price. Type price overrides base price.') }}
						</div>

						<div class="accordion" id="devicePricingAccordion">
							<div class="accordion-item">
								<h2 class="accordion-header" id="headingType">
									<button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseType" aria-expanded="true" aria-controls="collapseType">
										{{ __('Set price by Device Type') }}
									</button>
								</h2>
								<div id="collapseType" class="accordion-collapse collapse show" aria-labelledby="headingType" data-bs-parent="#devicePricingAccordion">
									<div class="accordion-body">
										<div id="wcrb-override-type" class="wcrb-override-container" data-section="type"></div>
									</div>
								</div>
							</div>

							<div class="accordion-item">
								<h2 class="accordion-header" id="headingBrand">
									<button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseBrand" aria-expanded="false" aria-controls="collapseBrand">
										{{ __('Set price by Device Brand') }}
									</button>
								</h2>
								<div id="collapseBrand" class="accordion-collapse collapse" aria-labelledby="headingBrand" data-bs-parent="#devicePricingAccordion">
									<div class="accordion-body">
										<div id="wcrb-override-brand" class="wcrb-override-container" data-section="brand"></div>
									</div>
								</div>
							</div>

							<div class="accordion-item">
								<h2 class="accordion-header" id="headingDevice">
									<button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseDevice" aria-expanded="false" aria-controls="collapseDevice">
										{{ __('Set price by Device') }}
									</button>
								</h2>
								<div id="collapseDevice" class="accordion-collapse collapse" aria-labelledby="headingDevice" data-bs-parent="#devicePricingAccordion">
									<div class="accordion-body">
										<div id="wcrb-override-device" class="wcrb-override-container" data-section="device"></div>
									</div>
								</div>
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
		(function () {
			function wcrbGetCsrfToken() {
				var el = document.querySelector('meta[name="csrf-token"]');
				return el ? el.getAttribute('content') : '';
			}

			function wcrbBuildUrl(baseUrl, params) {
				var url = new URL(baseUrl, window.location.origin);
				Object.keys(params).forEach(function (k) {
					if (params[k] === null || params[k] === undefined || params[k] === '') {
						return;
					}
					url.searchParams.set(k, String(params[k]));
				});
				return url.toString();
			}

			function wcrbFetchSection(container, opts) {
				if (!container) {
					return;
				}

				var section = container.getAttribute('data-section');
				if (!section) {
					return;
				}

				container.classList.add('opacity-50');

				var url = wcrbBuildUrl(@json(route('tenant.operations.services.price_overrides.section', ['business' => $tenant->slug, 'service' => $service->id])), {
					section: section,
					q: (opts && typeof opts.q === 'string') ? opts.q : '',
					page: (opts && opts.page) ? opts.page : 1,
				});

				fetch(url, {
					method: 'GET',
					headers: {
						'X-Requested-With': 'XMLHttpRequest',
						'X-CSRF-TOKEN': wcrbGetCsrfToken(),
					},
					credentials: 'same-origin',
				})
					.then(function (res) { return res.text(); })
					.then(function (html) {
						container.innerHTML = html;
					})
					.catch(function () {
						container.innerHTML = '<div class="text-danger small">' + @json(__('Failed to load.')) + '</div>';
					})
					.finally(function () {
						container.classList.remove('opacity-50');
					});
			}

			function wcrbWireContainer(container) {
				container.addEventListener('submit', function (e) {
					var form = e.target;
					if (!form || !form.classList || !form.classList.contains('wcrb-price-override-search')) {
						return;
					}
					e.preventDefault();
					var input = form.querySelector('input[type="text"]');
					var q = input ? String(input.value || '') : '';
					wcrbFetchSection(container, { q: q, page: 1 });
				});

				container.addEventListener('click', function (e) {
					var a = e.target && e.target.closest ? e.target.closest('a') : null;
					if (!a) {
						return;
					}
					if (!a.closest('.wcrb-price-override-pagination')) {
						return;
					}
					var href = a.getAttribute('href');
					if (!href) {
						return;
					}
					e.preventDefault();
					try {
						var url = new URL(href, window.location.origin);
						var page = url.searchParams.get('page') || '1';
						var qInput = container.querySelector('.wcrb-price-override-search input[type="text"]');
						var q = qInput ? String(qInput.value || '') : '';
						wcrbFetchSection(container, { q: q, page: parseInt(page, 10) || 1 });
					} catch (err) {
						return;
					}
				});
			}

			var containers = document.querySelectorAll('.wcrb-override-container');
			containers.forEach(function (c) {
				wcrbWireContainer(c);
				wcrbFetchSection(c, { q: '', page: 1 });
			});

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

@push('page-styles')
	<style>
		#service_device_pricing .pagination {
			gap: 0.5rem;
		}
		#service_device_pricing .pagination .page-link {
			border-radius: 0.375rem;
			border: 1px solid var(--bs-border-color);
			background: transparent;
			color: var(--bs-secondary-color);
		}
		#service_device_pricing .pagination .page-item.active .page-link {
			border-color: var(--bs-primary) !important;
			color: var(--bs-primary) !important;
			background-color: #fff !important;
		}
		#service_device_pricing .pagination .page-item.active .page-link:hover,
		#service_device_pricing .pagination .page-item.active .page-link:focus {
			background-color: #fff !important;
		}
		#service_device_pricing .pagination .page-item.disabled .page-link {
			opacity: 0.5;
		}
	</style>
@endpush
