<div class="tabs-panel team-wrap" id="wc_rb_manage_bookings" role="tabpanel" aria-hidden="true" aria-labelledby="wc_rb_manage_bookings-label">
	<div class="wrap">
		<h2>{{ __('Booking Settings') }}</h2>

		<div class="wc-rb-grey-bg-box">
			<h2>{{ __('Booking Email To Customer') }}</h2>
			<form data-abide class="needs-validation" novalidate method="post" action="{{ route('tenant.settings.bookings.update', ['business' => $tenant->slug]) }}">
				@csrf

				<table class="form-table border">
					<tbody>
						<tr>
							<th scope="row">
								<label for="booking_email_subject_to_customer">{{ __('Email subject') }}</label>
							</th>
							<td>
								<input
									type="text"
									id="booking_email_subject_to_customer"
									name="booking_email_subject_to_customer"
									class="regular-text"
									value="{{ old('booking_email_subject_to_customer', (string) ($bookingEmailSubjectCustomerUi ?? '')) }}"
								/>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="booking_email_body_to_customer">{{ __('Email body') }}</label>
							</th>
							<td>
								<textarea id="booking_email_body_to_customer" name="booking_email_body_to_customer" rows="6" class="large-text">{{ old('booking_email_body_to_customer', (string) ($bookingEmailBodyCustomerUi ?? '')) }}</textarea>
								<p class="description">{{ __('Available Keywords') }} {{ '{' . '{customer_full_name}' . '}' }} {{ '{' . '{customer_device_label}' . '}' }} {{ '{' . '{status_check_link}' . '}' }} {{ '{' . '{start_anch_status_check_link}' . '}' }} {{ '{' . '{end_anch_status_check_link}' . '}' }} {{ '{' . '{order_invoice_details}' . '}' }} {{ '{' . '{job_id}' . '}' }} {{ '{' . '{case_number}' . '}' }}</p>
							</td>
						</tr>
					</tbody>
				</table>

				<h2>{{ __('Booking email to administrator') }}</h2>
				<table class="form-table border">
					<tbody>
						<tr>
							<th scope="row">
								<label for="booking_email_subject_to_admin">{{ __('Email subject') }}</label>
							</th>
							<td>
								<input
									type="text"
									id="booking_email_subject_to_admin"
									name="booking_email_subject_to_admin"
									class="regular-text"
									value="{{ old('booking_email_subject_to_admin', (string) ($bookingEmailSubjectAdminUi ?? '')) }}"
								/>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="booking_email_body_to_admin">{{ __('Email body') }}</label>
							</th>
							<td>
								<textarea id="booking_email_body_to_admin" name="booking_email_body_to_admin" rows="6" class="large-text">{{ old('booking_email_body_to_admin', (string) ($bookingEmailBodyAdminUi ?? '')) }}</textarea>
								<p class="description">{{ __('Available Keywords') }} {{ '{' . '{customer_full_name}' . '}' }} {{ '{' . '{customer_device_label}' . '}' }} {{ '{' . '{status_check_link}' . '}' }} {{ '{' . '{start_anch_status_check_link}' . '}' }} {{ '{' . '{end_anch_status_check_link}' . '}' }} {{ '{' . '{order_invoice_details}' . '}' }} {{ '{' . '{job_id}' . '}' }} {{ '{' . '{case_number}' . '}' }}</p>
							</td>
						</tr>
					</tbody>
				</table>

				<table class="form-table border">
					<tbody>
						<tr>
							<th scope="row">
								<label for="wcrb_turn_booking_forms_to_jobs">{{ __('Booking & Quote Forms') }}</label>
							</th>
							<td>
								<input type="checkbox" name="wcrb_turn_booking_forms_to_jobs" id="wcrb_turn_booking_forms_to_jobs" {{ ($turnBookingFormsToJobsUi ?? false) ? 'checked' : '' }} />
								<label for="wcrb_turn_booking_forms_to_jobs">{{ __('Send booking forms & quote forms to jobs instead of estimates') }}</label>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="wcrb_turn_off_other_device_brands">{{ __('Other Devices & Brands') }}</label>
							</th>
							<td>
								<input type="checkbox" name="wcrb_turn_off_other_device_brands" id="wcrb_turn_off_other_device_brands" {{ ($turnOffOtherDeviceBrandsUi ?? false) ? 'checked' : '' }} />
								<label for="wcrb_turn_off_other_device_brands">{{ __('Turn off other option for devices and brands') }}</label>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="wcrb_turn_off_other_service">{{ __('Other Service') }}</label>
							</th>
							<td>
								<input type="checkbox" name="wcrb_turn_off_other_service" id="wcrb_turn_off_other_service" {{ ($turnOffOtherServiceUi ?? false) ? 'checked' : '' }} />
								<label for="wcrb_turn_off_other_service">{{ __('Turn off other service option') }}</label>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="wcrb_turn_off_service_price">{{ __('Disable Service Prices') }}</label>
							</th>
							<td>
								<input type="checkbox" name="wcrb_turn_off_service_price" id="wcrb_turn_off_service_price" {{ ($turnOffServicePriceUi ?? false) ? 'checked' : '' }} />
								<label for="wcrb_turn_off_service_price">{{ __('Turn off prices from services') }}</label>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="wcrb_turn_off_idimei_booking">{{ __('Disable ID/IMEI Field') }}</label>
							</th>
							<td>
								<input type="checkbox" name="wcrb_turn_off_idimei_booking" id="wcrb_turn_off_idimei_booking" {{ ($turnOffIdImeiBookingUi ?? false) ? 'checked' : '' }} />
								<label for="wcrb_turn_off_idimei_booking">{{ __('Turn off id/imei field from booking form') }}</label>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="wc_booking_default_type">{{ __('Default Device Type') }}</label>
							</th>
							<td>
								<select name="wc_booking_default_type" id="wc_booking_default_type" class="regular-text">
									<option value="">{{ __('Select') }}</option>
									@foreach (($deviceTypesForBookings ?? collect()) as $dt)
										<option value="{{ (string) $dt->id }}" {{ (string) old('wc_booking_default_type', $bookingDefaultTypeIdUi ?? '') === (string) $dt->id ? 'selected' : '' }}>{{ (string) $dt->name }}</option>
									@endforeach
								</select>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="wc_booking_default_brand">{{ __('Default Device Brand') }}</label>
							</th>
							<td>
								<select name="wc_booking_default_brand" id="wc_booking_default_brand" class="regular-text">
									<option value="">{{ __('Select') }}</option>
									@foreach (($deviceBrandsForBookings ?? collect()) as $db)
										<option value="{{ (string) $db->id }}" {{ (string) old('wc_booking_default_brand', $bookingDefaultBrandIdUi ?? '') === (string) $db->id ? 'selected' : '' }}>{{ (string) $db->name }}</option>
									@endforeach
								</select>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="wc_booking_default_device">{{ __('Default Device') }}</label>
							</th>
							<td>
								<select name="wc_booking_default_device" id="wc_booking_default_device" class="regular-text">
									<option value="">{{ __('Select') }}</option>
									@foreach (($devicesForBookings ?? collect()) as $d)
										@php
											$label = trim((string) ($d->model ?? ''));
											if ($label === '') {
												$label = 'Device #' . $d->id;
											}
										@endphp
										<option value="{{ (string) $d->id }}" {{ (string) old('wc_booking_default_device', $bookingDefaultDeviceIdUi ?? '') === (string) $d->id ? 'selected' : '' }}>{{ $label }}</option>
									@endforeach
								</select>
							</td>
						</tr>
					</tbody>
				</table>

				<button type="submit" class="button button-primary">{{ __('Update Options') }}</button>
			</form>
		</div>
	</div>
</div>
