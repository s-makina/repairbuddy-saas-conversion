@extends('tenant.layouts.myaccount', ['title' => $pageTitle ?? __('Add User')])

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
						<h5 class="card-title mb-0">{{ __('Add User') }}</h5>
					</div>
					<div class="card-body">
						<form method="post" action="{{ route('tenant.settings.users.store', ['business' => $tenant->slug]) }}">
							@csrf
							<div class="row g-3">
								<div class="col-12">
									<div class="row align-items-start">
										<label for="name" class="col-sm-3 col-form-label">{{ __('Name') }} *</label>
										<div class="col-sm-9">
											<input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', '') }}" required>
											@error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
										</div>
									</div>
								</div>

								<div class="col-12">
									<div class="row align-items-start">
										<label for="email" class="col-sm-3 col-form-label">{{ __('Email') }} *</label>
										<div class="col-sm-9">
											<input type="email" name="email" id="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', '') }}" required>
											@error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
										</div>
									</div>
								</div>

								<div class="col-12">
									<div class="row align-items-start">
										<label for="phone" class="col-sm-3 col-form-label">{{ __('Phone') }}</label>
										<div class="col-sm-9">
											<input type="text" name="phone" id="phone" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone', '') }}">
											@error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
										</div>
									</div>
								</div>

								<div class="col-12">
									<div class="row align-items-start">
										<label for="address_line1" class="col-sm-3 col-form-label">{{ __('Address') }}</label>
										<div class="col-sm-9">
											<input type="text" name="address_line1" id="address_line1" class="form-control @error('address_line1') is-invalid @enderror" value="{{ old('address_line1', '') }}">
											@error('address_line1')<div class="invalid-feedback">{{ $message }}</div>@enderror
										</div>
									</div>
								</div>

								<div class="col-12">
									<div class="row align-items-start">
										<label for="address_line2" class="col-sm-3 col-form-label">{{ __('Address line 2') }}</label>
										<div class="col-sm-9">
											<input type="text" name="address_line2" id="address_line2" class="form-control @error('address_line2') is-invalid @enderror" value="{{ old('address_line2', '') }}">
											@error('address_line2')<div class="invalid-feedback">{{ $message }}</div>@enderror
										</div>
									</div>
								</div>

								<div class="col-12">
									<div class="row align-items-start">
										<label for="address_city" class="col-sm-3 col-form-label">{{ __('City') }}</label>
										<div class="col-sm-9">
											<input type="text" name="address_city" id="address_city" class="form-control @error('address_city') is-invalid @enderror" value="{{ old('address_city', '') }}">
											@error('address_city')<div class="invalid-feedback">{{ $message }}</div>@enderror
										</div>
									</div>
								</div>

								<div class="col-12">
									<div class="row align-items-start">
										<label for="address_state" class="col-sm-3 col-form-label">{{ __('State') }}</label>
										<div class="col-sm-9">
											<input type="text" name="address_state" id="address_state" class="form-control @error('address_state') is-invalid @enderror" value="{{ old('address_state', '') }}">
											@error('address_state')<div class="invalid-feedback">{{ $message }}</div>@enderror
										</div>
									</div>
								</div>

								<div class="col-12">
									<div class="row align-items-start">
										<label for="address_postal_code" class="col-sm-3 col-form-label">{{ __('Postal code') }}</label>
										<div class="col-sm-9">
											<input type="text" name="address_postal_code" id="address_postal_code" class="form-control @error('address_postal_code') is-invalid @enderror" value="{{ old('address_postal_code', '') }}">
											@error('address_postal_code')<div class="invalid-feedback">{{ $message }}</div>@enderror
										</div>
									</div>
								</div>

								<div class="col-12">
									<div class="row align-items-start">
										<label for="address_country_code" class="col-sm-3 col-form-label">{{ __('Country') }}</label>
										<div class="col-sm-9">
											<select name="address_country_code" id="address_country_code" class="form-select @error('address_country_code') is-invalid @enderror">
												@php
													$countryOptions = [
														'' => __('Select country'),
														'ZA' => __('South Africa'),
														'NG' => __('Nigeria'),
														'KE' => __('Kenya'),
														'GH' => __('Ghana'),
														'US' => __('United States'),
														'GB' => __('United Kingdom'),
														'CA' => __('Canada'),
														'AU' => __('Australia'),
														'NZ' => __('New Zealand'),
														'IE' => __('Ireland'),
														'DE' => __('Germany'),
														'FR' => __('France'),
														'ES' => __('Spain'),
														'IT' => __('Italy'),
														'NL' => __('Netherlands'),
														'BE' => __('Belgium'),
														'PT' => __('Portugal'),
														'IN' => __('India'),
														'PK' => __('Pakistan'),
														'BD' => __('Bangladesh'),
														'SG' => __('Singapore'),
														'MY' => __('Malaysia'),
														'AE' => __('United Arab Emirates'),
													];
													$oldCountry = strtoupper(trim((string) old('address_country_code', '')));
												@endphp
												@foreach ($countryOptions as $k => $v)
													<option value="{{ $k }}" @selected((string) $oldCountry === (string) $k)>{{ $v }}</option>
												@endforeach
											</select>
											@error('address_country_code')<div class="invalid-feedback">{{ $message }}</div>@enderror
										</div>
									</div>
								</div>

								<div class="col-12">
									<div class="row align-items-start">
										<label for="status" class="col-sm-3 col-form-label">{{ __('Status') }}</label>
										<div class="col-sm-9">
											<select name="status" id="status" class="form-select @error('status') is-invalid @enderror">
												@foreach (($statusOptions ?? []) as $k => $v)
													<option value="{{ $k }}" @selected((string) old('status', 'active') === (string) $k)>{{ $v }}</option>
												@endforeach
											</select>
											@error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
											<div class="form-text">{{ __('Inactive users cannot access the tenant dashboard.') }}</div>
										</div>
									</div>
								</div>

								<div class="col-12">
									<div class="row align-items-start">
										<label for="role_id" class="col-sm-3 col-form-label">{{ __('Role') }} *</label>
										<div class="col-sm-9">
											<select name="role_id" id="role_id" class="form-select @error('role_id') is-invalid @enderror" required>
												@foreach (($roleOptions ?? []) as $k => $v)
													<option value="{{ $k }}" @selected((string) old('role_id', '') === (string) $k)>{{ $v }}</option>
												@endforeach
											</select>
											@error('role_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
										</div>
									</div>
								</div>

								<div class="col-12">
									<div class="row align-items-start">
										<label for="branch_ids" class="col-sm-3 col-form-label">{{ __('Shops') }} *</label>
										<div class="col-sm-9">
											<select name="branch_ids[]" id="branch_ids" class="form-select w-100 @error('branch_ids') is-invalid @enderror" multiple required>
												@foreach (($branchOptions ?? []) as $k => $v)
													<option value="{{ $k }}" @selected(in_array((int) $k, array_map('intval', old('branch_ids', []))))>{{ $v }}</option>
												@endforeach
											</select>
											@error('branch_ids')<div class="invalid-feedback">{{ $message }}</div>@enderror
											<div class="form-text">{{ __('Assign this user to one or more shops (branches).') }}</div>
										</div>
									</div>
								</div>

								<div class="col-12">
									<div class="row align-items-start">
										<label for="password" class="col-sm-3 col-form-label">{{ __('Password') }} *</label>
										<div class="col-sm-9">
											<input type="password" name="password" id="password" class="form-control @error('password') is-invalid @enderror" required>
											@error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
											<div class="form-text">{{ __('Min 8 chars. Use a strong password.') }}</div>
										</div>
									</div>
								</div>

								<div class="col-12">
									<div class="row align-items-start">
										<label for="password_confirmation" class="col-sm-3 col-form-label">{{ __('Confirm') }} *</label>
										<div class="col-sm-9">
											<input type="password" name="password_confirmation" id="password_confirmation" class="form-control" required>
										</div>
									</div>
								</div>

								<div class="col-12">
									<div class="d-flex justify-content-end gap-2">
										<a class="btn btn-outline-secondary" href="{{ route('tenant.settings.users.index', ['business' => $tenant->slug]) }}">{{ __('Cancel') }}</a>
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
						<h5 class="card-title mb-0">{{ __('Recent users') }}</h5>
					</div>
					<div class="card-body p-0">
						<div class="list-group list-group-flush">
							@forelse (($recentUsers ?? []) as $u)
								@php
									$editUrl = route('tenant.settings.users.edit', ['business' => $tenant->slug, 'user' => $u->id]);
								@endphp
								<div class="list-group-item d-flex align-items-center justify-content-between">
									<div class="text-truncate">
										<div class="fw-normal text-truncate">{{ (string) ($u->name ?? '') }}</div>
										<div class="small text-muted text-truncate">{{ (string) ($u->email ?? '') }}</div>
									</div>
									<a class="btn btn-sm btn-outline-primary ms-3" href="{{ $editUrl }}" title="{{ __('Edit') }}" aria-label="{{ __('Edit') }}">
										<i class="bi bi-pencil"></i>
									</a>
								</div>
							@empty
								<div class="list-group-item">
									<div class="text-muted">{{ __('No users yet.') }}</div>
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

			var $branches = window.jQuery('#branch_ids');
			if ($branches.length) {
				$branches.select2({
					width: '100%',
					theme: 'bootstrap-5',
					placeholder: @json(__('Select shops')),
					closeOnSelect: false,
				});
			}
		})();
	</script>
@endpush
