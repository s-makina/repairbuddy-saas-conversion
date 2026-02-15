@extends('tenant.layouts.myaccount', ['title' => $pageTitle ?? __('Edit Client')])

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
						<h5 class="card-title mb-0">{{ __('Edit Client') }}</h5>
					</div>
					<div class="card-body">
						<form method="post" action="{{ route('tenant.operations.clients.update', ['business' => $tenant->slug, 'client' => $client->id]) }}">
							@csrf
							<div class="row g-3">
								<div class="col-12">
									<div class="row align-items-start">
										<label for="first_name" class="col-sm-3 col-form-label">{{ __('First name') }} *</label>
										<div class="col-sm-9">
											@php
												$fullName = is_string($client->name ?? null) ? trim((string) $client->name) : '';
												$nameParts = $fullName !== '' ? preg_split('/\s+/', $fullName, -1, PREG_SPLIT_NO_EMPTY) : [];
												$firstFromName = is_array($nameParts) && count($nameParts) > 0 ? (string) $nameParts[0] : '';
												$lastFromName = is_array($nameParts) && count($nameParts) > 1 ? trim(implode(' ', array_slice($nameParts, 1))) : '';
											@endphp
											<input type="text" name="first_name" id="first_name" class="form-control @error('first_name') is-invalid @enderror" value="{{ old('first_name', (string) ($client->first_name ?? $firstFromName)) }}" required>
											@error('first_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
										</div>
									</div>
								</div>

								<div class="col-12">
									<div class="row align-items-start">
										<label for="last_name" class="col-sm-3 col-form-label">{{ __('Last name') }}</label>
										<div class="col-sm-9">
											<input type="text" name="last_name" id="last_name" class="form-control @error('last_name') is-invalid @enderror" value="{{ old('last_name', (string) ($client->last_name ?? $lastFromName)) }}">
											@error('last_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
										</div>
									</div>
								</div>

								<div class="col-12">
									<div class="row align-items-start">
										<label for="email" class="col-sm-3 col-form-label">{{ __('Email') }} *</label>
										<div class="col-sm-9">
											<input type="email" name="email" id="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', (string) ($client->email ?? '')) }}" required>
											@error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
										</div>
									</div>
								</div>

								<div class="col-12">
									<div class="row align-items-start">
										<label for="phone" class="col-sm-3 col-form-label">{{ __('Phone') }}</label>
										<div class="col-sm-9">
											<input type="text" name="phone" id="phone" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone', (string) ($client->phone ?? '')) }}">
											@error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
										</div>
									</div>
								</div>

								<div class="col-12">
									<div class="row align-items-start">
										<label for="company" class="col-sm-3 col-form-label">{{ __('Company') }}</label>
										<div class="col-sm-9">
											<input type="text" name="company" id="company" class="form-control @error('company') is-invalid @enderror" value="{{ old('company', (string) ($client->company ?? '')) }}">
											@error('company')<div class="invalid-feedback">{{ $message }}</div>@enderror
										</div>
									</div>
								</div>

								<div class="col-12">
									<div class="row align-items-start">
										<label for="tax_id" class="col-sm-3 col-form-label">{{ __('Tax ID') }}</label>
										<div class="col-sm-9">
											<input type="text" name="tax_id" id="tax_id" class="form-control @error('tax_id') is-invalid @enderror" value="{{ old('tax_id', (string) ($client->tax_id ?? '')) }}">
											@error('tax_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
										</div>
									</div>
								</div>

								<hr class="my-2">

								<div class="col-12">
									<div class="row align-items-start">
										<label for="address_line1" class="col-sm-3 col-form-label">{{ __('Address line 1') }}</label>
										<div class="col-sm-9">
											<input type="text" name="address_line1" id="address_line1" class="form-control @error('address_line1') is-invalid @enderror" value="{{ old('address_line1', (string) ($client->address_line1 ?? '')) }}">
											@error('address_line1')<div class="invalid-feedback">{{ $message }}</div>@enderror
										</div>
									</div>
								</div>

								<div class="col-12">
									<div class="row align-items-start">
										<label for="address_line2" class="col-sm-3 col-form-label">{{ __('Address line 2') }}</label>
										<div class="col-sm-9">
											<input type="text" name="address_line2" id="address_line2" class="form-control @error('address_line2') is-invalid @enderror" value="{{ old('address_line2', (string) ($client->address_line2 ?? '')) }}">
											@error('address_line2')<div class="invalid-feedback">{{ $message }}</div>@enderror
										</div>
									</div>
								</div>

								<div class="col-12">
									<div class="row align-items-start">
										<label for="address_city" class="col-sm-3 col-form-label">{{ __('City') }}</label>
										<div class="col-sm-9">
											<input type="text" name="address_city" id="address_city" class="form-control @error('address_city') is-invalid @enderror" value="{{ old('address_city', (string) ($client->address_city ?? '')) }}">
											@error('address_city')<div class="invalid-feedback">{{ $message }}</div>@enderror
										</div>
									</div>
								</div>

								<div class="col-12">
									<div class="row align-items-start">
										<label for="address_postal_code" class="col-sm-3 col-form-label">{{ __('Postal code') }}</label>
										<div class="col-sm-9">
											<input type="text" name="address_postal_code" id="address_postal_code" class="form-control @error('address_postal_code') is-invalid @enderror" value="{{ old('address_postal_code', (string) ($client->address_postal_code ?? '')) }}">
											@error('address_postal_code')<div class="invalid-feedback">{{ $message }}</div>@enderror
										</div>
									</div>
								</div>

								<div class="col-12">
									<div class="row align-items-start">
										<label for="address_state" class="col-sm-3 col-form-label">{{ __('State / Province') }}</label>
										<div class="col-sm-9">
											<input type="text" name="address_state" id="address_state" class="form-control @error('address_state') is-invalid @enderror" value="{{ old('address_state', (string) ($client->address_state ?? '')) }}">
											@error('address_state')<div class="invalid-feedback">{{ $message }}</div>@enderror
										</div>
									</div>
								</div>

								<div class="col-12">
									<div class="row align-items-start">
										<label for="address_country" class="col-sm-3 col-form-label">{{ __('Country (2-letter)') }}</label>
										<div class="col-sm-9">
											<input type="text" name="address_country" id="address_country" class="form-control @error('address_country') is-invalid @enderror" value="{{ old('address_country', (string) ($client->address_country ?? '')) }}" maxlength="2">
											@error('address_country')<div class="invalid-feedback">{{ $message }}</div>@enderror
											<div class="form-text">{{ __('Example: US, ZA, DE') }}</div>
										</div>
									</div>
								</div>

								<div class="col-12">
									<div class="d-flex justify-content-end gap-2">
										<a class="btn btn-outline-secondary" href="{{ route('tenant.operations.clients.index', ['business' => $tenant->slug]) }}">{{ __('Cancel') }}</a>
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
