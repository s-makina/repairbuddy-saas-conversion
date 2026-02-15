@extends('tenant.layouts.myaccount', ['title' => $pageTitle ?? __('Edit Part')])

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
			<div class="col-12 col-lg-5 col-xl-5">
				<div class="card">
					<div class="card-header">
						<h5 class="card-title mb-0">{{ __('Edit Part') }}</h5>
					</div>
					<div class="card-body">
						<form method="post" action="{{ route('tenant.operations.parts.update', ['business' => $tenant->slug, 'part' => $part->id]) }}">
							@csrf
							<div class="row g-3">
								<div class="col-12">
									<div class="row align-items-start">
										<label for="name" class="col-sm-3 col-form-label">{{ __('Name') }} *</label>
										<div class="col-sm-9">
											<input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', (string) ($part->name ?? '')) }}" required>
											@error('name')
												<div class="invalid-feedback">{{ $message }}</div>
											@enderror
										</div>
									</div>
								</div>

								<div class="col-12">
									<div class="row align-items-start">
										<label for="part_brand_id" class="col-sm-3 col-form-label">{{ __('Brand') }}</label>
										<div class="col-sm-9">
											<select name="part_brand_id" id="part_brand_id" class="form-select @error('part_brand_id') is-invalid @enderror">
												@foreach (($brandOptions ?? []) as $k => $v)
													<option value="{{ $k }}" @selected((string) old('part_brand_id', (string) ($part->part_brand_id ?? '')) === (string) $k)>{{ $v }}</option>
												@endforeach
											</select>
											@error('part_brand_id')
												<div class="invalid-feedback">{{ $message }}</div>
											@enderror
										</div>
									</div>
								</div>

								<div class="col-12">
									<div class="row align-items-start">
										<label for="part_type_id" class="col-sm-3 col-form-label">{{ __('Type') }}</label>
										<div class="col-sm-9">
											<select name="part_type_id" id="part_type_id" class="form-select @error('part_type_id') is-invalid @enderror">
												@foreach (($typeOptions ?? []) as $k => $v)
													<option value="{{ $k }}" @selected((string) old('part_type_id', (string) ($part->part_type_id ?? '')) === (string) $k)>{{ $v }}</option>
												@endforeach
											</select>
											@error('part_type_id')
												<div class="invalid-feedback">{{ $message }}</div>
											@enderror
										</div>
									</div>
								</div>

								<div class="col-12">
									<div class="row align-items-start">
										<label for="manufacturing_code" class="col-sm-3 col-form-label">{{ __('Manufacturing Code') }} *</label>
										<div class="col-sm-9">
											<input type="text" name="manufacturing_code" id="manufacturing_code" class="form-control @error('manufacturing_code') is-invalid @enderror" value="{{ old('manufacturing_code', (string) ($part->manufacturing_code ?? '')) }}" required>
											@error('manufacturing_code')
												<div class="invalid-feedback">{{ $message }}</div>
											@enderror
										</div>
									</div>
								</div>

								<div class="col-12">
									<div class="row align-items-start">
										<label for="stock_code" class="col-sm-3 col-form-label">{{ __('Stock Code') }}</label>
										<div class="col-sm-9">
											<input type="text" name="stock_code" id="stock_code" class="form-control @error('stock_code') is-invalid @enderror" value="{{ old('stock_code', (string) ($part->stock_code ?? '')) }}">
											@error('stock_code')
												<div class="invalid-feedback">{{ $message }}</div>
											@enderror
										</div>
									</div>
								</div>

								<div class="col-12">
									<div class="row align-items-start">
										<label for="price" class="col-sm-3 col-form-label">{{ __('Price') }} *</label>
										<div class="col-sm-9">
											@php
												$priceUi = '';
												if (is_numeric($part->price_amount_cents ?? null)) {
													$priceUi = number_format(((int) $part->price_amount_cents) / 100, 2, '.', '');
												}
											@endphp
											<div class="input-group">
												<input type="number" step="any" name="price" id="price" class="form-control @error('price') is-invalid @enderror" value="{{ old('price', $priceUi) }}" required>
												<span class="input-group-text">{{ $tenantCurrency ?? '' }}</span>
												@error('price')
													<div class="invalid-feedback">{{ $message }}</div>
												@enderror
											</div>
											<input type="hidden" name="price_currency" value="{{ old('price_currency', (string) ($part->price_currency ?? ($tenantCurrency ?? ''))) }}">
										</div>
									</div>
								</div>

								<div class="col-12">
									<div class="row align-items-start">
										<label for="warranty" class="col-sm-3 col-form-label">{{ __('Warranty') }}</label>
										<div class="col-sm-9">
											<input type="text" name="warranty" id="warranty" class="form-control @error('warranty') is-invalid @enderror" value="{{ old('warranty', (string) ($part->warranty ?? '')) }}">
											@error('warranty')
												<div class="invalid-feedback">{{ $message }}</div>
											@enderror
										</div>
									</div>
								</div>

								<div class="col-12">
									<div class="row align-items-start">
										<label for="core_features" class="col-sm-3 col-form-label">{{ __('Core features') }}</label>
										<div class="col-sm-9">
											<textarea name="core_features" id="core_features" rows="4" class="form-control @error('core_features') is-invalid @enderror">{{ old('core_features', (string) ($part->core_features ?? '')) }}</textarea>
											@error('core_features')
												<div class="invalid-feedback">{{ $message }}</div>
											@enderror
										</div>
									</div>
								</div>

								<div class="col-12">
									<div class="row align-items-start">
										<label for="capacity" class="col-sm-3 col-form-label">{{ __('Capacity') }}</label>
										<div class="col-sm-9">
											<input type="text" name="capacity" id="capacity" class="form-control @error('capacity') is-invalid @enderror" value="{{ old('capacity', (string) ($part->capacity ?? '')) }}">
											@error('capacity')
												<div class="invalid-feedback">{{ $message }}</div>
											@enderror
										</div>
									</div>
								</div>

								<div class="col-12">
									<div class="row align-items-start">
										<label for="installation_charges" class="col-sm-3 col-form-label">{{ __('Installation charges') }}</label>
										<div class="col-sm-9">
											@php
												$installUi = '';
												if (is_numeric($part->installation_charges_amount_cents ?? null)) {
													$installUi = number_format(((int) $part->installation_charges_amount_cents) / 100, 2, '.', '');
												}
											@endphp
											<div class="input-group">
												<input type="number" step="any" name="installation_charges" id="installation_charges" class="form-control @error('installation_charges') is-invalid @enderror" value="{{ old('installation_charges', $installUi) }}">
												<span class="input-group-text">{{ $tenantCurrency ?? '' }}</span>
												@error('installation_charges')
													<div class="invalid-feedback">{{ $message }}</div>
												@enderror
											</div>
											<input type="hidden" name="installation_charges_currency" value="{{ old('installation_charges_currency', (string) ($part->installation_charges_currency ?? ($tenantCurrency ?? ''))) }}">
										</div>
									</div>
								</div>

								<div class="col-12">
									<div class="row align-items-start">
										<label for="installation_message" class="col-sm-3 col-form-label">{{ __('Installation message') }}</label>
										<div class="col-sm-9">
											<input type="text" name="installation_message" id="installation_message" class="form-control @error('installation_message') is-invalid @enderror" value="{{ old('installation_message', (string) ($part->installation_message ?? '')) }}">
											@error('installation_message')
												<div class="invalid-feedback">{{ $message }}</div>
											@enderror
										</div>
									</div>
								</div>

								<div class="col-12">
									<div class="d-flex justify-content-end gap-2">
										<a class="btn btn-outline-secondary" href="{{ route('tenant.operations.parts.index', ['business' => $tenant->slug]) }}">{{ __('Cancel') }}</a>
										<button type="submit" class="btn btn-primary">{{ __('Save') }}</button>
									</div>
								</div>
							</div>
						</form>
					</div>
				</div>
			</div>
			<div class="col-12 col-lg-7 col-xl-7">
				<div class="card" id="part_device_pricing">
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

				var url = wcrbBuildUrl(@json(route('tenant.operations.parts.price_overrides.section', ['business' => $tenant->slug, 'part' => $part->id])), {
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
						var pageName = (function () {
							var sectionEl = container.querySelector('.wcrb-price-override-section');
							if (!sectionEl) {
								return '';
							}
							var v = sectionEl.getAttribute('data-page-name');
							return v ? String(v) : '';
						})();

						var page = (pageName && url.searchParams.get(pageName))
							? url.searchParams.get(pageName)
							: (url.searchParams.get('page') || '1');
						var qInput = container.querySelector('.wcrb-price-override-search input[type="text"]');
						var q = qInput ? String(qInput.value || '') : '';
						wcrbFetchSection(container, { q: q, page: parseInt(page, 10) || 1 });
					} catch (err) {
						return;
					}
				});
			}

			var containers = Array.prototype.slice.call(document.querySelectorAll('.wcrb-override-container'));
			containers.forEach(function (c) {
				wcrbWireContainer(c);
				wcrbFetchSection(c, { q: '', page: 1 });
			});
		})();
	</script>
	<script>
		(function () {
			if (!window.jQuery || !window.jQuery.fn || typeof window.jQuery.fn.select2 !== 'function') {
				return;
			}

			var $type = window.jQuery('#part_type_id');
			if ($type.length) {
				$type.select2({ width: '100%', allowClear: true, placeholder: @json(__('None')) });
			}

			var $brand = window.jQuery('#part_brand_id');
			if ($brand.length) {
				$brand.select2({ width: '100%', allowClear: true, placeholder: @json(__('None')) });
			}

			var $tax = window.jQuery('#tax_id');
			if ($tax.length) {
				$tax.select2({ width: '100%', allowClear: true, placeholder: @json(__('Select tax')) });
			}
		})();
	</script>
@endpush
