@extends('tenant.layouts.myaccount', ['title' => $pageTitle ?? __('Add Device')])

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
						<h5 class="card-title mb-0">{{ __('Add Device') }}</h5>
					</div>
					<div class="card-body">
						<form method="post" action="{{ route('tenant.operations.devices.store', ['business' => $tenant->slug]) }}" enctype="multipart/form-data">
							@csrf
							<div class="row g-3">
								<div class="col-12">
									<div class="row align-items-start">
										<label for="model" class="col-sm-3 col-form-label">{{ __('Model') }} *</label>
										<div class="col-sm-9">
											<input type="text" name="model" id="model" class="form-control @error('model') is-invalid @enderror" value="{{ old('model', '') }}" required>
											@error('model')
												<div class="invalid-feedback">{{ $message }}</div>
											@enderror
										</div>
									</div>
								</div>
								<div class="col-12">
									<div class="row align-items-start">
										<label for="image" class="col-sm-3 col-form-label">{{ __('Image') }}</label>
										<div class="col-sm-9">
											<input type="file" name="image" id="image" class="form-control @error('image') is-invalid @enderror" accept=".jpg,.jpeg,.png,.webp" />
											@error('image')
												<div class="invalid-feedback">{{ $message }}</div>
											@enderror
											<div class="form-text">{{ __('JPG, PNG or WEBP. Max size 5MB.') }}</div>
										</div>
									</div>
								</div>
								<div class="col-12">
									<div class="row align-items-start">
										<label for="device_type_id" class="col-sm-3 col-form-label">{{ __('Type') }} *</label>
										<div class="col-sm-9">
											<select name="device_type_id" id="device_type_id" class="form-select @error('device_type_id') is-invalid @enderror" required>
												@foreach (($typeOptions ?? []) as $k => $v)
													<option value="{{ $k }}" @selected((string) old('device_type_id', '') === (string) $k)>{{ $v }}</option>
												@endforeach
											</select>
											@error('device_type_id')
												<div class="invalid-feedback">{{ $message }}</div>
											@enderror
										</div>
									</div>
								</div>
								<div class="col-12">
									<div class="row align-items-start">
										<label for="device_brand_id" class="col-sm-3 col-form-label">{{ __('Brand') }} *</label>
										<div class="col-sm-9">
											<select name="device_brand_id" id="device_brand_id" class="form-select @error('device_brand_id') is-invalid @enderror" required>
												@foreach (($brandOptions ?? []) as $k => $v)
													<option value="{{ $k }}" @selected((string) old('device_brand_id', '') === (string) $k)>{{ $v }}</option>
												@endforeach
											</select>
											@error('device_brand_id')
												<div class="invalid-feedback">{{ $message }}</div>
											@enderror
										</div>
									</div>
								</div>
								<div class="col-12">
									<div class="row align-items-start">
										<label for="parent_device_id" class="col-sm-3 col-form-label">{{ __('Parent device') }}</label>
										<div class="col-sm-9">
											<select name="parent_device_id" id="parent_device_id" class="form-select @error('parent_device_id') is-invalid @enderror">
												@foreach (($parentOptions ?? []) as $k => $v)
													<option value="{{ $k }}" @selected((string) old('parent_device_id', '') === (string) $k)>{{ $v }}</option>
												@endforeach
											</select>
											@error('parent_device_id')
												<div class="invalid-feedback">{{ $message }}</div>
											@enderror
										</div>
									</div>
								</div>
								<div class="col-12">
									<div class="row align-items-start">
										<label for="variations_list" class="col-sm-3 col-form-label">{{ __('Add Variations') }}</label>
										<div class="col-sm-9">
											<textarea id="variations_list" name="variations_list" rows="3" class="form-control @error('variations_list') is-invalid @enderror" placeholder="{{ __('Black, 64GB, Silver, 128GB, etc.') }}">{{ old('variations_list', '') }}</textarea>
											@error('variations_list')
												<div class="invalid-feedback">{{ $message }}</div>
											@enderror
											<div class="form-text">{{ __('Enter variations separated by commas. They will be created as child devices after saving.') }}</div>
										</div>
									</div>
								</div>
								<div class="col-12">
									<div class="row align-items-start">
										<label for="disable_in_booking_form" class="col-sm-3 col-form-label">{{ __('Disable in booking forms') }}</label>
										<div class="col-sm-9">
											<div class="form-check form-switch">
												<input class="form-check-input" type="checkbox" role="switch" id="disable_in_booking_form" name="disable_in_booking_form" value="1" @checked((bool) old('disable_in_booking_form', false))>
											</div>
										</div>
									</div>
								</div>
								<div class="col-12">
									<div class="row align-items-start">
										<label for="is_other" class="col-sm-3 col-form-label">{{ __('Is Other device') }}</label>
										<div class="col-sm-9">
											<div class="form-check form-switch">
												<input class="form-check-input" type="checkbox" role="switch" id="is_other" name="is_other" value="1" @checked((bool) old('is_other', false))>
											</div>
										</div>
									</div>
								</div>
								<div class="col-12">
									<div class="d-flex justify-content-end gap-2">
										<a class="btn btn-outline-secondary" href="{{ route('tenant.operations.devices.index', ['business' => $tenant->slug]) }}">{{ __('Cancel') }}</a>
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
						<h5 class="card-title mb-0">{{ __('Recent devices') }}</h5>
					</div>
					<div class="card-body p-0">
						<div class="list-group list-group-flush">
							@forelse (($recentDevices ?? []) as $d)
								@php
									$editUrl = route('tenant.operations.devices.edit', ['business' => $tenant->slug, 'device' => $d->id]);
								@endphp
								<div class="list-group-item d-flex align-items-center justify-content-between">
									<div class="text-truncate">
										<div class="fw-normal text-truncate">{{ (string) ($d->model ?? '') }}</div>
										<div class="small text-muted text-truncate">
											{{ (string) ($d->brand?->name ?? '') }}@if (! empty($d->brand?->name) && ! empty($d->type?->name)){{ ' · ' }}@endif{{ (string) ($d->type?->name ?? '') }}
										</div>
									</div>
									<a class="btn btn-sm btn-outline-primary ms-3" href="{{ $editUrl }}" title="{{ __('Edit') }}" aria-label="{{ __('Edit') }}">
										<i class="bi bi-pencil"></i>
									</a>
								</div>
							@empty
								<div class="list-group-item">
									<div class="text-muted">{{ __('No devices yet.') }}</div>
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

			var $type = window.jQuery('#device_type_id');
			if ($type.length) {
				$type.select2({ width: '100%' });
			}

			var $brand = window.jQuery('#device_brand_id');
			if ($brand.length) {
				$brand.select2({ width: '100%' });
			}

			var $parent = window.jQuery('#parent_device_id');
			if ($parent.length) {
				$parent.select2({ width: '100%', allowClear: true, placeholder: @json(__('None')) });
			}
		})();
	</script>
@endpush
