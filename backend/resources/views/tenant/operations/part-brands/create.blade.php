@extends('tenant.layouts.myaccount', ['title' => $pageTitle ?? __('Add Part Brand')])

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

		<div class="row justify-content-center g-10">
			<div class="col-12 col-lg-7 col-xl-8">
				<div class="card">
					<div class="card-header">
						<h5 class="card-title mb-0">{{ __('Add Part Brand') }}</h5>
					</div>
					<div class="card-body">
						<form method="post" action="{{ route('tenant.operations.part_brands.store', ['business' => $tenant->slug]) }}" enctype="multipart/form-data">
							@csrf
							<div class="row g-3">
								<div class="col-12">
									<div class="row align-items-start">
										<label for="name" class="col-sm-3 col-form-label">{{ __('Name') }} *</label>
										<div class="col-sm-9">
											<input
												type="text"
												name="name"
												id="name"
												class="form-control @error('name') is-invalid @enderror"
												value="{{ old('name', '') }}"
												required
											>
											@error('name')
												<div class="invalid-feedback">{{ $message }}</div>
											@enderror
										</div>
									</div>
								</div>
								<div class="col-12">
									<div class="row align-items-start">
										<label for="description" class="col-sm-3 col-form-label">{{ __('Description') }}</label>
										<div class="col-sm-9">
											<textarea
												name="description"
												id="description"
												rows="4"
												class="form-control @error('description') is-invalid @enderror"
											>{{ old('description', '') }}</textarea>
											@error('description')
												<div class="invalid-feedback">{{ $message }}</div>
											@enderror
										</div>
									</div>
								</div>
								<div class="col-12">
									<div class="row align-items-start">
										<label for="parent_id" class="col-sm-3 col-form-label">{{ __('Parent brand') }}</label>
										<div class="col-sm-9">
											<select name="parent_id" id="parent_id" class="form-select @error('parent_id') is-invalid @enderror">
												<option value="">{{ __('None') }}</option>
												@foreach (($parentOptions ?? []) as $k => $v)
													<option value="{{ $k }}" @selected((string) old('parent_id', '') === (string) $k)>{{ $v }}</option>
												@endforeach
											</select>
											@error('parent_id')
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
									<div class="d-flex justify-content-end gap-2">
										<a class="btn btn-outline-secondary" href="{{ route('tenant.operations.part_brands.index', ['business' => $tenant->slug]) }}">{{ __('Cancel') }}</a>
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
						<h5 class="card-title mb-0">{{ __('Recent part brands') }}</h5>
					</div>
					<div class="card-body p-0">
						<div class="list-group list-group-flush">
							@forelse (($recentBrands ?? []) as $b)
								@php
									$editUrl = route('tenant.operations.part_brands.edit', ['business' => $tenant->slug, 'brand' => $b->id]);
								@endphp
								<div class="list-group-item d-flex align-items-center justify-content-between">
									<div class="text-truncate">
										<div class="fw-normal text-truncate">{{ (string) ($b->name ?? '') }}</div>
										@if (! empty($b->description))
											<div class="small text-muted text-truncate">{{ (string) $b->description }}</div>
										@endif
									</div>
									<a class="btn btn-sm btn-outline-primary ms-3" href="{{ $editUrl }}" title="{{ __('Edit') }}" aria-label="{{ __('Edit') }}">
										<i class="bi bi-pencil"></i>
									</a>
								</div>
							@empty
								<div class="list-group-item">
									<div class="text-muted">{{ __('No part brands yet.') }}</div>
								</div>
							@endforelse
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
@endsection
