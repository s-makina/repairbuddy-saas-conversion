@extends('tenant.layouts.myaccount', ['title' => $pageTitle ?? __('Edit Role')])

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
			<div class="col-12 col-lg-9 col-xl-8">
				<div class="card">
					<div class="card-header">
						<h5 class="card-title mb-0">{{ __('Edit Role') }}</h5>
					</div>
					<div class="card-body">
						<form method="post" action="{{ route('tenant.settings.roles.update', ['business' => $tenant->slug, 'role' => $role->id]) }}">
							@csrf
							<div class="row g-3">
								<div class="col-12">
									<div class="row align-items-start">
										<label for="name" class="col-sm-3 col-form-label">{{ __('Name') }} *</label>
										<div class="col-sm-9">
											<input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', (string) ($role->name ?? '')) }}" required>
											@error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
										</div>
									</div>
								</div>

								<div class="col-12">
									<label class="form-label fw-semibold">{{ __('Permissions') }}</label>
									<div class="row g-2">
										@php
											$selected = (array) old('permission_ids', $selectedPermissionIds ?? []);
											$selected = array_map(fn ($v) => (string) $v, $selected);
										@endphp
										@forelse (($permissions ?? []) as $perm)
											@php
												$pid = (int) ($perm['id'] ?? 0);
												$pname = (string) ($perm['name'] ?? '');
											@endphp
											<div class="col-12 col-md-6">
												<div class="form-check">
													<input class="form-check-input" type="checkbox" name="permission_ids[]" id="perm_{{ $pid }}" value="{{ $pid }}" @checked(in_array((string) $pid, $selected, true))>
													<label class="form-check-label" for="perm_{{ $pid }}">{{ $pname }}</label>
												</div>
											</div>
										@empty
											<div class="col-12">
												<div class="text-muted">{{ __('No permissions found.') }}</div>
											</div>
										@endforelse
									</div>
								</div>

								<div class="col-12">
									<div class="d-flex justify-content-end gap-2">
										<a class="btn btn-outline-secondary" href="{{ route('tenant.settings.roles.index', ['business' => $tenant->slug]) }}">{{ __('Cancel') }}</a>
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
