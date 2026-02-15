@extends('tenant.layouts.myaccount', ['title' => $pageTitle ?? __('Add Client')])

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
						<h5 class="card-title mb-0">{{ __('Add Client') }}</h5>
					</div>
					<div class="card-body">
						<form method="post" action="{{ route('tenant.operations.clients.store', ['business' => $tenant->slug]) }}">
							@csrf
							<div class="row g-3">
								<div class="col-12">
									<div class="row align-items-start">
										<label for="first_name" class="col-sm-3 col-form-label">{{ __('First name') }} *</label>
										<div class="col-sm-9">
											<input type="text" name="first_name" id="first_name" class="form-control @error('first_name') is-invalid @enderror" value="{{ old('first_name', '') }}" required>
											@error('first_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
										</div>
									</div>
								</div>

								<div class="col-12">
									<div class="row align-items-start">
										<label for="last_name" class="col-sm-3 col-form-label">{{ __('Last name') }}</label>
										<div class="col-sm-9">
											<input type="text" name="last_name" id="last_name" class="form-control @error('last_name') is-invalid @enderror" value="{{ old('last_name', '') }}">
											@error('last_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
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
										<label for="company" class="col-sm-3 col-form-label">{{ __('Company') }}</label>
										<div class="col-sm-9">
											<input type="text" name="company" id="company" class="form-control @error('company') is-invalid @enderror" value="{{ old('company', '') }}">
											@error('company')<div class="invalid-feedback">{{ $message }}</div>@enderror
										</div>
									</div>
								</div>

								<div class="col-12">
									<div class="row align-items-start">
										<label for="tax_id" class="col-sm-3 col-form-label">{{ __('Tax ID') }}</label>
										<div class="col-sm-9">
											<input type="text" name="tax_id" id="tax_id" class="form-control @error('tax_id') is-invalid @enderror" value="{{ old('tax_id', '') }}">
											@error('tax_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
										</div>
									</div>
								</div>

								<div class="col-12">
									<div class="row align-items-start">
										<label for="address_line1" class="col-sm-3 col-form-label">{{ __('Address line 1') }}</label>
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
										<label for="address_postal_code" class="col-sm-3 col-form-label">{{ __('Postal code') }}</label>
										<div class="col-sm-9">
											<input type="text" name="address_postal_code" id="address_postal_code" class="form-control @error('address_postal_code') is-invalid @enderror" value="{{ old('address_postal_code', '') }}">
											@error('address_postal_code')<div class="invalid-feedback">{{ $message }}</div>@enderror
										</div>
									</div>
								</div>

								<div class="col-12">
									<div class="row align-items-start">
										<label for="address_state" class="col-sm-3 col-form-label">{{ __('State / Province') }}</label>
										<div class="col-sm-9">
											<input type="text" name="address_state" id="address_state" class="form-control @error('address_state') is-invalid @enderror" value="{{ old('address_state', '') }}">
											@error('address_state')<div class="invalid-feedback">{{ $message }}</div>@enderror
										</div>
									</div>
								</div>

								<div class="col-12">
									<div class="row align-items-start">
										<label for="address_country" class="col-sm-3 col-form-label">{{ __('Country') }}</label>
										<div class="col-sm-9">
											<select name="address_country" id="address_country" class="form-select @error('address_country') is-invalid @enderror">
												<option value="">{{ __('Select a country') }}</option>
												@php
													$countries = [
														'Afghanistan','Albania','Algeria','Andorra','Angola','Antigua and Barbuda','Argentina','Armenia','Australia','Austria','Azerbaijan',
														'Bahamas','Bahrain','Bangladesh','Barbados','Belarus','Belgium','Belize','Benin','Bhutan','Bolivia','Bosnia and Herzegovina','Botswana','Brazil','Brunei','Bulgaria','Burkina Faso','Burundi',
														'Cabo Verde','Cambodia','Cameroon','Canada','Central African Republic','Chad','Chile','China','Colombia','Comoros','Congo (Congo-Brazzaville)','Costa Rica','Croatia','Cuba','Cyprus','Czechia (Czech Republic)',
														'Democratic Republic of the Congo','Denmark','Djibouti','Dominica','Dominican Republic',
														'Ecuador','Egypt','El Salvador','Equatorial Guinea','Eritrea','Estonia','Eswatini (fmr. Swaziland)','Ethiopia',
														'Fiji','Finland','France',
														'Gabon','Gambia','Georgia','Germany','Ghana','Greece','Grenada','Guatemala','Guinea','Guinea-Bissau','Guyana',
														'Haiti','Honduras','Hungary',
														'Iceland','India','Indonesia','Iran','Iraq','Ireland','Israel','Italy',
														'Jamaica','Japan','Jordan',
														'Kazakhstan','Kenya','Kiribati','Kuwait','Kyrgyzstan',
														'Laos','Latvia','Lebanon','Lesotho','Liberia','Libya','Liechtenstein','Lithuania','Luxembourg',
														'Madagascar','Malawi','Malaysia','Maldives','Mali','Malta','Marshall Islands','Mauritania','Mauritius','Mexico','Micronesia','Moldova','Monaco','Mongolia','Montenegro','Morocco','Mozambique','Myanmar (formerly Burma)',
														'Namibia','Nauru','Nepal','Netherlands','New Zealand','Nicaragua','Niger','Nigeria','North Korea','North Macedonia','Norway',
														'Oman',
														'Pakistan','Palau','Panama','Papua New Guinea','Paraguay','Peru','Philippines','Poland','Portugal',
														'Qatar',
														'Romania','Russia','Rwanda',
														'Saint Kitts and Nevis','Saint Lucia','Saint Vincent and the Grenadines','Samoa','San Marino','Sao Tome and Principe','Saudi Arabia','Senegal','Serbia','Seychelles','Sierra Leone','Singapore','Slovakia','Slovenia','Solomon Islands','Somalia','South Africa','South Korea','South Sudan','Spain','Sri Lanka','Sudan','Suriname','Sweden','Switzerland','Syria',
														'Taiwan','Tajikistan','Tanzania','Thailand','Timor-Leste','Togo','Tonga','Trinidad and Tobago','Tunisia','Turkey','Turkmenistan','Tuvalu',
														'Uganda','Ukraine','United Arab Emirates','United Kingdom','United States','Uruguay','Uzbekistan',
														'Vanuatu','Vatican City','Venezuela','Vietnam',
														'Yemen',
														'Zambia','Zimbabwe',
													];
													$selectedCountry = old('address_country', '');
												@endphp
												@foreach ($countries as $country)
													<option value="{{ $country }}" @selected($selectedCountry === $country)>{{ $country }}</option>
												@endforeach
											</select>
											<input type="hidden" name="address_country_code" id="address_country_code" value="{{ old('address_country_code', '') }}">
											@error('address_country')<div class="invalid-feedback">{{ $message }}</div>@enderror
											@error('address_country_code')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
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

			<div class="col-12 col-lg-4 col-xl-4">
				<div class="card">
					<div class="card-header">
						<h5 class="card-title mb-0">{{ __('Recent clients') }}</h5>
					</div>
					<div class="card-body p-0">
						<div class="list-group list-group-flush">
							@forelse (($recentClients ?? []) as $c)
								@php
									$editUrl = route('tenant.operations.clients.edit', ['business' => $tenant->slug, 'client' => $c->id]);
								@endphp
								<div class="list-group-item d-flex align-items-center justify-content-between">
									<div class="text-truncate">
										<div class="fw-normal text-truncate">{{ (string) ($c->name ?? '') }}</div>
										<div class="small text-muted text-truncate">{{ (string) ($c->email ?? '') }}</div>
									</div>
									<a class="btn btn-sm btn-outline-primary ms-3" href="{{ $editUrl }}" title="{{ __('Edit') }}" aria-label="{{ __('Edit') }}">
										<i class="bi bi-pencil"></i>
									</a>
								</div>
							@empty
								<div class="list-group-item">
									<div class="text-muted">{{ __('No clients yet.') }}</div>
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

			var $country = window.jQuery('#address_country');
			var $code = window.jQuery('#address_country_code');

			var countryCodes = {
				'South Africa': 'ZA',
				'United States': 'US',
				'United Kingdom': 'GB',
				'Germany': 'DE',
				'France': 'FR',
				'Italy': 'IT',
				'Spain': 'ES',
				'Netherlands': 'NL',
				'Belgium': 'BE',
				'Switzerland': 'CH',
				'Austria': 'AT',
				'Sweden': 'SE',
				'Norway': 'NO',
				'Denmark': 'DK',
				'Finland': 'FI',
				'Ireland': 'IE',
				'Portugal': 'PT',
				'Poland': 'PL',
				'Czechia (Czech Republic)': 'CZ',
				'Greece': 'GR',
				'Hungary': 'HU',
				'Romania': 'RO',
				'Bulgaria': 'BG',
				'Croatia': 'HR',
				'Slovakia': 'SK',
				'Slovenia': 'SI',
				'Estonia': 'EE',
				'Latvia': 'LV',
				'Lithuania': 'LT',
				'Canada': 'CA',
				'Australia': 'AU',
				'New Zealand': 'NZ',
				'India': 'IN',
				'Japan': 'JP',
				'China': 'CN',
				'Singapore': 'SG',
				'United Arab Emirates': 'AE'
			};

			function updateCode() {
				var name = ($country.val() || '').toString();
				$code.val(countryCodes[name] || '');
			}

			if ($country.length) {
				$country.select2({ width: '100%', placeholder: @json(__('Select a country')), allowClear: true });
				$country.on('change', updateCode);
				updateCode();
			}
		})();
	</script>
@endpush
