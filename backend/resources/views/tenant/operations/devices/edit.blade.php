@extends('tenant.layouts.myaccount', ['title' => $pageTitle ?? __('Edit Device')])

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
						<h5 class="card-title mb-0">{{ __('Edit Device') }}</h5>
					</div>
					<div class="card-body">
						<form method="post" action="{{ route('tenant.operations.devices.update', ['business' => $tenant->slug, 'device' => $device->id]) }}" enctype="multipart/form-data">
							@csrf
							<div class="row g-3">
								<div class="col-12">
									<div class="row align-items-start">
										<label for="model" class="col-sm-3 col-form-label">{{ __('Model') }} *</label>
										<div class="col-sm-9">
											<input type="text" name="model" id="model" class="form-control @error('model') is-invalid @enderror" value="{{ old('model', (string) ($device->model ?? '')) }}" required>
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
											@if (! empty($device->image_url))
												<div class="mb-2">
													<img src="{{ $device->image_url }}" alt="{{ (string) ($device->model ?? __('Device')) }}" class="img-thumbnail" style="max-width: 160px; max-height: 160px; object-fit: contain;" />
												</div>
											@endif
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
													<option value="{{ $k }}" @selected((string) old('device_type_id', (string) ($device->device_type_id ?? '')) === (string) $k)>{{ $v }}</option>
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
													<option value="{{ $k }}" @selected((string) old('device_brand_id', (string) ($device->device_brand_id ?? '')) === (string) $k)>{{ $v }}</option>
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
													<option value="{{ $k }}" @selected((string) old('parent_device_id', (string) ($device->parent_device_id ?? '')) === (string) $k)>{{ $v }}</option>
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
										<label for="disable_in_booking_form" class="col-sm-3 col-form-label">{{ __('Disable in booking forms') }}</label>
										<div class="col-sm-9">
											<div class="form-check form-switch">
												<input class="form-check-input" type="checkbox" role="switch" id="disable_in_booking_form" name="disable_in_booking_form" value="1" @checked((bool) old('disable_in_booking_form', (bool) ($device->disable_in_booking_form ?? false)))>
											</div>
										</div>
									</div>
								</div>
								<div class="col-12">
									<div class="row align-items-start">
										<label for="is_other" class="col-sm-3 col-form-label">{{ __('Is Other device') }}</label>
										<div class="col-sm-9">
											<div class="form-check form-switch">
												<input class="form-check-input" type="checkbox" role="switch" id="is_other" name="is_other" value="1" @checked((bool) old('is_other', (bool) ($device->is_other ?? false)))>
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
						<h5 class="card-title mb-0">{{ __('Device Variations') }}</h5>
					</div>
					<div class="card-body">
						<form method="post" action="{{ route('tenant.operations.devices.variations.store', ['business' => $tenant->slug, 'device' => $device->id]) }}">
							@csrf
							<div class="mb-2">
								<label for="variations_list" class="form-label fw-semibold">{{ __('Add Variations') }}</label>
								<textarea id="variations_list" name="variations_list" rows="4" class="form-control" placeholder="{{ __('Black, 64GB, Silver, 128GB, etc.') }}"></textarea>
								<div class="form-text">{{ __('Enter variations separated by commas. Each variation will be created as a child device.') }}</div>
							</div>
							<button type="submit" class="btn btn-primary">{{ __('Create Variations') }}</button>
						</form>

						@if (!empty($variations ?? null) && count($variations) > 0)
							<hr>
							<h6 class="mb-2">{{ __('Existing Variations') }}</h6>
							<div class="list-group">
								@foreach ($variations as $v)
									<a class="list-group-item list-group-item-action d-flex align-items-center justify-content-between" href="{{ route('tenant.operations.devices.edit', ['business' => $tenant->slug, 'device' => $v->id]) }}">
										<span class="text-truncate">{{ (string) ($v->model ?? '') }}</span>
										<i class="bi bi-pencil"></i>
									</a>
								@endforeach
							</div>
						@endif
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
