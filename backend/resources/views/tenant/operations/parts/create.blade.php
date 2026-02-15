@extends('tenant.layouts.myaccount', ['title' => $pageTitle ?? __('Add Part')])

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
						<h5 class="card-title mb-0">{{ __('Add Part') }}</h5>
					</div>
					<div class="card-body">
						<form method="post" action="{{ route('tenant.operations.parts.store', ['business' => $tenant->slug]) }}">
							@csrf
							<div class="row g-3">
								<div class="col-12">
									<div class="row align-items-start">
										<label for="name" class="col-sm-3 col-form-label">{{ __('Name') }} *</label>
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
										<label for="part_brand_id" class="col-sm-3 col-form-label">{{ __('Brand') }}</label>
										<div class="col-sm-9">
											<select name="part_brand_id" id="part_brand_id" class="form-select @error('part_brand_id') is-invalid @enderror">
												@foreach (($brandOptions ?? []) as $k => $v)
													<option value="{{ $k }}" @selected((string) old('part_brand_id', '') === (string) $k)>{{ $v }}</option>
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
													<option value="{{ $k }}" @selected((string) old('part_type_id', '') === (string) $k)>{{ $v }}</option>
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
											<input type="text" name="manufacturing_code" id="manufacturing_code" class="form-control @error('manufacturing_code') is-invalid @enderror" value="{{ old('manufacturing_code', '') }}" required>
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
											<input type="text" name="stock_code" id="stock_code" class="form-control @error('stock_code') is-invalid @enderror" value="{{ old('stock_code', '') }}">
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
											<div class="input-group">
												<input type="number" step="any" name="price" id="price" class="form-control @error('price') is-invalid @enderror" value="{{ old('price', '') }}" required>
												<span class="input-group-text">{{ $tenantCurrency ?? '' }}</span>
												@error('price')
													<div class="invalid-feedback">{{ $message }}</div>
												@enderror
											</div>
											<input type="hidden" name="price_currency" value="{{ old('price_currency', $tenantCurrency ?? '') }}">
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

								<div class="col-12">
									<div class="row align-items-start">
										<label for="core_features" class="col-sm-3 col-form-label">{{ __('Core features') }}</label>
										<div class="col-sm-9">
											<textarea name="core_features" id="core_features" rows="4" class="form-control @error('core_features') is-invalid @enderror">{{ old('core_features', '') }}</textarea>
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
											<input type="text" name="capacity" id="capacity" class="form-control @error('capacity') is-invalid @enderror" value="{{ old('capacity', '') }}">
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
											<div class="input-group">
												<input type="number" step="any" name="installation_charges" id="installation_charges" class="form-control @error('installation_charges') is-invalid @enderror" value="{{ old('installation_charges', '') }}">
												<span class="input-group-text">{{ $tenantCurrency ?? '' }}</span>
												@error('installation_charges')
													<div class="invalid-feedback">{{ $message }}</div>
												@enderror
											</div>
											<input type="hidden" name="installation_charges_currency" value="{{ old('installation_charges_currency', $tenantCurrency ?? '') }}">
										</div>
									</div>
								</div>

								<div class="col-12">
									<div class="row align-items-start">
										<label for="installation_message" class="col-sm-3 col-form-label">{{ __('Installation message') }}</label>
										<div class="col-sm-9">
											<input type="text" name="installation_message" id="installation_message" class="form-control @error('installation_message') is-invalid @enderror" value="{{ old('installation_message', '') }}">
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
			<div class="col-12 col-lg-5 col-xl-4">
				<div class="card">
					<div class="card-header">
						<h5 class="card-title mb-0">{{ __('Recent parts') }}</h5>
					</div>
					<div class="card-body p-0">
						<div class="list-group list-group-flush">
							@forelse (($recentParts ?? []) as $p)
								@php
									$editUrl = route('tenant.operations.parts.edit', ['business' => $tenant->slug, 'part' => $p->id]);
								@endphp
								<div class="list-group-item d-flex align-items-center justify-content-between">
									<div class="text-truncate">
										<div class="fw-normal text-truncate">{{ (string) ($p->name ?? '') }}</div>
										<div class="small text-muted text-truncate">
											{{ (string) ($p->brand?->name ?? '') }}@if (! empty($p->brand?->name) && ! empty($p->type?->name)){{ ' · ' }}@endif{{ (string) ($p->type?->name ?? '') }}
										</div>
									</div>
									<a class="btn btn-sm btn-outline-primary ms-3" href="{{ $editUrl }}" title="{{ __('Edit') }}" aria-label="{{ __('Edit') }}">
										<i class="bi bi-pencil"></i>
									</a>
								</div>
							@empty
								<div class="list-group-item">
									<div class="text-muted">{{ __('No parts yet.') }}</div>
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
