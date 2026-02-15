@extends('tenant.layouts.myaccount', ['title' => $pageTitle ?? __('Edit Service Type')])

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
						<h5 class="card-title mb-0">{{ __('Edit Service Type') }}</h5>
					</div>
					<div class="card-body">
						<form method="post" action="{{ route('tenant.operations.service_types.update', ['business' => $tenant->slug, 'type' => $type->id]) }}">
							@csrf
							<div class="row g-3">
								<div class="col-12">
									<div class="row align-items-start">
										<label for="name" class="col-sm-3 col-form-label">{{ __('Name') }} *</label>
										<div class="col-sm-9">
											<input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', (string) ($type->name ?? '')) }}" required>
											@error('name')
												<div class="invalid-feedback">{{ $message }}</div>
											@enderror
										</div>
									</div>
								</div>

								<div class="col-12">
									<div class="row align-items-start">
										<label for="parent_id" class="col-sm-3 col-form-label">{{ __('Parent service type') }}</label>
										<div class="col-sm-9">
											<select name="parent_id" id="parent_id" class="form-select @error('parent_id') is-invalid @enderror" data-parent-service-type-select>
												<option value="">{{ __('None') }}</option>
											</select>
											@error('parent_id')
												<div class="invalid-feedback">{{ $message }}</div>
											@enderror
											<div class="form-text">{{ __('Search and select a parent service type (optional).') }}</div>
										</div>
									</div>
								</div>

								<div class="col-12">
									<div class="d-flex justify-content-end gap-2">
										<a class="btn btn-outline-secondary" href="{{ route('tenant.operations.service_types.index', ['business' => $tenant->slug]) }}">{{ __('Cancel') }}</a>
										<button type="submit" class="btn btn-primary">{{ __('Save') }}</button>
									</div>
								</div>
							</div>
						</form>
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

			var $select = window.jQuery('[data-parent-service-type-select]');
			if ($select.length === 0) {
				return;
			}

			$select.select2({
				placeholder: @json(__('None')),
				allowClear: true,
				width: '100%',
				ajax: {
					url: @json(route('tenant.operations.service_types.search', ['business' => $tenant->slug])),
					dataType: 'json',
					delay: 250,
					data: function (params) {
						return {
							q: params.term || '',
							limit: params.term ? 20 : 10,
							sort: params.term ? '' : 'id',
							dir: params.term ? '' : 'desc',
							exclude_id: @json($type->id),
						};
					},
					processResults: function (data) {
						var items = (data && data.service_types) ? data.service_types : [];
						return {
							results: items.map(function (t) {
								return { id: t.id, text: t.name };
							})
						};
					},
					cache: true
				},
				minimumInputLength: 0
			});

			var existingId = @json(old('parent_id', $type->parent_id ?? ''));
			if (existingId) {
				var existingText = @json(($parentOptions ?? [])[(string) ($type->parent_id ?? '')] ?? null);
				if (existingText) {
					var option = new Option(existingText, existingId, true, true);
					$select.append(option).trigger('change');
				}
			}
		})();
	</script>
@endpush
