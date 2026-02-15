@extends('tenant.layouts.myaccount', ['title' => $pageTitle ?? __('Edit Shop')])

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
			<div class="col-12 col-lg-8 col-xl-7">
				<div class="card">
					<div class="card-header">
						<h5 class="card-title mb-0">{{ __('Edit Shop') }}</h5>
					</div>
					<div class="card-body">
						<form method="post" action="{{ route('tenant.settings.shops.update', ['business' => $tenant->slug, 'branch' => $branch->id]) }}">
							@csrf
							<div class="row g-3">
								<div class="col-12">
									<div class="row align-items-start">
										<label for="name" class="col-sm-3 col-form-label">{{ __('Name') }} *</label>
										<div class="col-sm-9">
											<input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', (string) ($branch->name ?? '')) }}" required>
											@error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
										</div>
									</div>
								</div>

								<div class="col-12">
									<div class="row align-items-start">
										<label for="code" class="col-sm-3 col-form-label">{{ __('Code') }} *</label>
										<div class="col-sm-9">
											<input type="text" name="code" id="code" class="form-control @error('code') is-invalid @enderror" value="{{ old('code', (string) ($branch->code ?? '')) }}" required maxlength="16">
											@error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
											<div class="form-text">{{ __('Short code like HQ, CPT, etc.') }}</div>
										</div>
									</div>
								</div>

								<div class="col-12">
									<div class="row align-items-start">
										<label for="phone" class="col-sm-3 col-form-label">{{ __('Phone') }}</label>
										<div class="col-sm-9">
											<input type="text" name="phone" id="phone" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone', (string) ($branch->phone ?? '')) }}">
											@error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
										</div>
									</div>
								</div>

								<div class="col-12">
									<div class="row align-items-start">
										<label for="email" class="col-sm-3 col-form-label">{{ __('Email') }}</label>
										<div class="col-sm-9">
											<input type="email" name="email" id="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', (string) ($branch->email ?? '')) }}">
											@error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
										</div>
									</div>
								</div>

								<div class="col-12">
									<div class="row align-items-start">
										<label for="address_line1" class="col-sm-3 col-form-label">{{ __('Address') }}</label>
										<div class="col-sm-9">
											<input type="text" name="address_line1" id="address_line1" class="form-control @error('address_line1') is-invalid @enderror" value="{{ old('address_line1', (string) ($branch->address_line1 ?? '')) }}">
											@error('address_line1')<div class="invalid-feedback">{{ $message }}</div>@enderror
										</div>
									</div>
								</div>

								<div class="col-12">
									<div class="row align-items-start">
										<label for="address_line2" class="col-sm-3 col-form-label">{{ __('Address line 2') }}</label>
										<div class="col-sm-9">
											<input type="text" name="address_line2" id="address_line2" class="form-control @error('address_line2') is-invalid @enderror" value="{{ old('address_line2', (string) ($branch->address_line2 ?? '')) }}">
											@error('address_line2')<div class="invalid-feedback">{{ $message }}</div>@enderror
										</div>
									</div>
								</div>

								<div class="col-12">
									<div class="row align-items-start">
										<label for="address_city" class="col-sm-3 col-form-label">{{ __('City') }}</label>
										<div class="col-sm-9">
											<input type="text" name="address_city" id="address_city" class="form-control @error('address_city') is-invalid @enderror" value="{{ old('address_city', (string) ($branch->address_city ?? '')) }}">
											@error('address_city')<div class="invalid-feedback">{{ $message }}</div>@enderror
										</div>
									</div>
								</div>

								<div class="col-12">
									<div class="row align-items-start">
										<label for="address_state" class="col-sm-3 col-form-label">{{ __('State') }}</label>
										<div class="col-sm-9">
											<input type="text" name="address_state" id="address_state" class="form-control @error('address_state') is-invalid @enderror" value="{{ old('address_state', (string) ($branch->address_state ?? '')) }}">
											@error('address_state')<div class="invalid-feedback">{{ $message }}</div>@enderror
										</div>
									</div>
								</div>

								<div class="col-12">
									<div class="row align-items-start">
										<label for="address_postal_code" class="col-sm-3 col-form-label">{{ __('Postal code') }}</label>
										<div class="col-sm-9">
											<input type="text" name="address_postal_code" id="address_postal_code" class="form-control @error('address_postal_code') is-invalid @enderror" value="{{ old('address_postal_code', (string) ($branch->address_postal_code ?? '')) }}">
											@error('address_postal_code')<div class="invalid-feedback">{{ $message }}</div>@enderror
										</div>
									</div>
								</div>

								<div class="col-12">
									<div class="row align-items-start">
										<label for="address_country" class="col-sm-3 col-form-label">{{ __('Country') }}</label>
										<div class="col-sm-9">
											<select name="address_country" id="address_country" class="form-select @error('address_country') is-invalid @enderror">
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
													$oldCountry = strtoupper(trim((string) old('address_country', (string) ($branch->address_country ?? ''))));
												@endphp
												@foreach ($countryOptions as $k => $v)
													<option value="{{ $k }}" @selected((string) $oldCountry === (string) $k)>{{ $v }}</option>
												@endforeach
											</select>
											@error('address_country')<div class="invalid-feedback">{{ $message }}</div>@enderror
										</div>
									</div>
								</div>

								<div class="col-12">
									<div class="row align-items-center">
										<label for="is_active" class="col-sm-3 col-form-label">{{ __('Active') }}</label>
										<div class="col-sm-9">
											<div class="d-flex align-items-center gap-2">
												<div class="form-check form-switch m-0">
													@php
														$active = old('is_active', (int) ($branch->is_active ?? 0) ? 1 : 0);
													@endphp
													<input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" @checked((bool) $active)>
												</div>
												<label class="mb-0" for="is_active">{{ __('Enabled') }}</label>
											</div>
											@error('is_active')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
										</div>
									</div>
								</div>

								<div class="col-12">
									<div class="d-flex justify-content-end gap-2">
										<a class="btn btn-outline-secondary" href="{{ route('tenant.settings.shops.index', ['business' => $tenant->slug]) }}">{{ __('Cancel') }}</a>
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
