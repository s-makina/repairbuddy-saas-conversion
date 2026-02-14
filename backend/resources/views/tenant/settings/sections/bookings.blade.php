<div class="tabs-panel team-wrap" id="wc_rb_manage_bookings" role="tabpanel" aria-hidden="true" aria-labelledby="wc_rb_manage_bookings-label">
	<div class="wrap">
		<h2>{{ __('Booking Settings') }}</h2>
		@php
			$oldBookingEmailSubjectCustomer = old('booking_email_subject_to_customer');
			$bookingEmailSubjectCustomerValue = (is_string($oldBookingEmailSubjectCustomer) && trim($oldBookingEmailSubjectCustomer) !== '')
				? $oldBookingEmailSubjectCustomer
				: (string) ($bookingEmailSubjectCustomerUi ?? '');

			$oldBookingEmailBodyCustomer = old('booking_email_body_to_customer');
			$bookingEmailBodyCustomerValue = (is_string($oldBookingEmailBodyCustomer) && trim($oldBookingEmailBodyCustomer) !== '')
				? $oldBookingEmailBodyCustomer
				: (string) ($bookingEmailBodyCustomerUi ?? '');

			$oldBookingEmailSubjectAdmin = old('booking_email_subject_to_admin');
			$bookingEmailSubjectAdminValue = (is_string($oldBookingEmailSubjectAdmin) && trim($oldBookingEmailSubjectAdmin) !== '')
				? $oldBookingEmailSubjectAdmin
				: (string) ($bookingEmailSubjectAdminUi ?? '');

			$oldBookingEmailBodyAdmin = old('booking_email_body_to_admin');
			$bookingEmailBodyAdminValue = (is_string($oldBookingEmailBodyAdmin) && trim($oldBookingEmailBodyAdmin) !== '')
				? $oldBookingEmailBodyAdmin
				: (string) ($bookingEmailBodyAdminUi ?? '');

			$bookingSelectedTypeId = (string) old('wc_booking_default_type', $bookingDefaultTypeIdUi ?? '');
			$bookingSelectedBrandId = (string) old('wc_booking_default_brand', $bookingDefaultBrandIdUi ?? '');
			$bookingSelectedDeviceId = (string) old('wc_booking_default_device', $bookingDefaultDeviceIdUi ?? '');
		@endphp

		<form data-abide class="needs-validation" novalidate method="post" action="{{ route('tenant.settings.bookings.update', ['business' => $tenant->slug]) }}">
			@csrf
			<div class="wcrb-settings-form">
				<div class="wcrb-settings-card">
					<h3 class="wcrb-settings-card-title">{{ __('Booking Email To Customer') }}</h3>
					<div class="wcrb-settings-card-body">
						<div class="grid-x grid-margin-x">
							<div class="cell medium-12 small-12">
								<x-settings.field for="booking_email_subject_to_customer" :label="__('Email subject')" class="wcrb-settings-field">
									<x-settings.input
										name="booking_email_subject_to_customer"
										id="booking_email_subject_to_customer"
										:value="$bookingEmailSubjectCustomerValue"
										type="text"
									/>
								</x-settings.field>
							</div>
						</div>
						<div class="grid-x grid-margin-x">
							<div class="cell medium-12 small-12">
								<x-settings.field for="booking_email_body_to_customer" :label="__('Email body')" :help="__('Available Keywords') . ' ' . '{' . '{customer_full_name}' . '}' . ' ' . '{' . '{customer_device_label}' . '}' . ' ' . '{' . '{status_check_link}' . '}' . ' ' . '{' . '{start_anch_status_check_link}' . '}' . ' ' . '{' . '{end_anch_status_check_link}' . '}' . ' ' . '{' . '{order_invoice_details}' . '}' . ' ' . '{' . '{job_id}' . '}' . ' ' . '{' . '{case_number}' . '}'" class="wcrb-settings-field">
									<x-settings.textarea
										name="booking_email_body_to_customer"
										id="booking_email_body_to_customer"
										:rows="6"
										:value="$bookingEmailBodyCustomerValue"
									/>
								</x-settings.field>
							</div>
						</div>
					</div>
				</div>

				<div class="wcrb-settings-card">
					<h3 class="wcrb-settings-card-title">{{ __('Booking email to administrator') }}</h3>
					<div class="wcrb-settings-card-body">
						<div class="grid-x grid-margin-x">
							<div class="cell medium-12 small-12">
								<x-settings.field for="booking_email_subject_to_admin" :label="__('Email subject')" class="wcrb-settings-field">
									<x-settings.input
										name="booking_email_subject_to_admin"
										id="booking_email_subject_to_admin"
										:value="$bookingEmailSubjectAdminValue"
										type="text"
									/>
								</x-settings.field>
							</div>
						</div>
						<div class="grid-x grid-margin-x">
							<div class="cell medium-12 small-12">
								<x-settings.field for="booking_email_body_to_admin" :label="__('Email body')" :help="__('Available Keywords') . ' ' . '{' . '{customer_full_name}' . '}' . ' ' . '{' . '{customer_device_label}' . '}' . ' ' . '{' . '{status_check_link}' . '}' . ' ' . '{' . '{start_anch_status_check_link}' . '}' . ' ' . '{' . '{end_anch_status_check_link}' . '}' . ' ' . '{' . '{order_invoice_details}' . '}' . ' ' . '{' . '{job_id}' . '}' . ' ' . '{' . '{case_number}' . '}'" class="wcrb-settings-field">
									<x-settings.textarea
										name="booking_email_body_to_admin"
										id="booking_email_body_to_admin"
										:rows="6"
										:value="$bookingEmailBodyAdminValue"
									/>
								</x-settings.field>
							</div>
						</div>
					</div>
				</div>

				<div class="wcrb-settings-card">
					<h3 class="wcrb-settings-card-title">{{ __('Booking & Quote Forms') }}</h3>
					<div class="wcrb-settings-card-body">
						<div class="wcrb-settings-option" style="border-bottom: 0; padding-bottom: 6px; margin-bottom: 6px;">
							<div class="wcrb-settings-option-head">
								<div class="wcrb-settings-option-control">
									<x-settings.toggle name="wcrb_turn_booking_forms_to_jobs" id="wcrb_turn_booking_forms_to_jobs" :checked="(bool) ($turnBookingFormsToJobsUi ?? false)" />
								</div>
								<label for="wcrb_turn_booking_forms_to_jobs" class="wcrb-settings-option-title">{{ __('Send booking forms & quote forms to jobs instead of estimates') }}</label>
							</div>
						</div>
						<div class="wcrb-settings-option" style="border-bottom: 0; padding-bottom: 6px; margin-bottom: 6px;">
							<div class="wcrb-settings-option-head">
								<div class="wcrb-settings-option-control">
									<x-settings.toggle name="wcrb_turn_off_other_device_brands" id="wcrb_turn_off_other_device_brands" :checked="(bool) ($turnOffOtherDeviceBrandsUi ?? false)" />
								</div>
								<label for="wcrb_turn_off_other_device_brands" class="wcrb-settings-option-title">{{ __('Turn off other option for devices and brands') }}</label>
							</div>
						</div>
						<div class="wcrb-settings-option" style="border-bottom: 0; padding-bottom: 6px; margin-bottom: 6px;">
							<div class="wcrb-settings-option-head">
								<div class="wcrb-settings-option-control">
									<x-settings.toggle name="wcrb_turn_off_other_service" id="wcrb_turn_off_other_service" :checked="(bool) ($turnOffOtherServiceUi ?? false)" />
								</div>
								<label for="wcrb_turn_off_other_service" class="wcrb-settings-option-title">{{ __('Turn off other service option') }}</label>
							</div>
						</div>
						<div class="wcrb-settings-option" style="border-bottom: 0; padding-bottom: 6px; margin-bottom: 6px;">
							<div class="wcrb-settings-option-head">
								<div class="wcrb-settings-option-control">
									<x-settings.toggle name="wcrb_turn_off_service_price" id="wcrb_turn_off_service_price" :checked="(bool) ($turnOffServicePriceUi ?? false)" />
								</div>
								<label for="wcrb_turn_off_service_price" class="wcrb-settings-option-title">{{ __('Turn off prices from services') }}</label>
							</div>
						</div>
						<div class="wcrb-settings-option" style="border-bottom: 0; padding-bottom: 10px; margin-bottom: 10px;">
							<div class="wcrb-settings-option-head">
								<div class="wcrb-settings-option-control">
									<x-settings.toggle name="wcrb_turn_off_idimei_booking" id="wcrb_turn_off_idimei_booking" :checked="(bool) ($turnOffIdImeiBookingUi ?? false)" />
								</div>
								<label for="wcrb_turn_off_idimei_booking" class="wcrb-settings-option-title">{{ __('Turn off id/imei field from booking form') }}</label>
							</div>
						</div>

						<div class="grid-x grid-margin-x">
							<div class="cell medium-4 small-12">
								<x-settings.field for="wc_booking_default_type" :label="__('Default Device Type')" :help="__('Keep selected on booking page')" class="wcrb-settings-field">
									<x-settings.select
										name="wc_booking_default_type"
										id="wc_booking_default_type"
										:options="collect(($deviceTypesForBookings ?? collect()))->mapWithKeys(fn ($dt) => [(string) $dt->id => (string) $dt->name])->prepend(__('Select'), '')->all()"
										:value="(string) old('wc_booking_default_type', $bookingDefaultTypeIdUi ?? '')"
									/>
								</x-settings.field>
							</div>
							<div class="cell medium-4 small-12">
								<x-settings.field for="wc_booking_default_brand" :label="__('Default Device Brand')" :help="__('Keep selected on booking page')" class="wcrb-settings-field">
									<x-settings.select
										name="wc_booking_default_brand"
										id="wc_booking_default_brand"
										:options="[ '' => __('Select') ]"
										:value="$bookingSelectedBrandId"
									/>
								</x-settings.field>
							</div>
							<div class="cell medium-4 small-12">
								<x-settings.field for="wc_booking_default_device" :label="__('Default Device')" :help="__('Keep selected on booking page')" class="wcrb-settings-field">
									<x-settings.select
										name="wc_booking_default_device"
										id="wc_booking_default_device"
										:options="[ '' => __('Select') ]"
										:value="$bookingSelectedDeviceId"
									/>
								</x-settings.field>
							</div>
						</div>
					</div>
				</div>

				<div class="grid-x grid-margin-x">
					<div class="cell small-12">
						<div class="wcrb-settings-actions">
							<button type="submit" class="button button-primary">{{ __('Save Changes') }}</button>
						</div>
					</div>
				</div>
			</div>
		</form>
	</div>
</div>

@push('page-scripts')
	<script>
		(function () {
			var typeEl = document.getElementById('wc_booking_default_type');
			var brandEl = document.getElementById('wc_booking_default_brand');
			var deviceEl = document.getElementById('wc_booking_default_device');
			if (!typeEl || !brandEl || !deviceEl) {
				return;
			}

			var selectedTypeId = @json($bookingSelectedTypeId);
			var selectedBrandId = @json($bookingSelectedBrandId);
			var selectedDeviceId = @json($bookingSelectedDeviceId);

			var brandsUrl = @json(route('tenant.settings.bookings.brands', ['business' => $tenant->slug]));
			var devicesUrl = @json(route('tenant.settings.bookings.devices', ['business' => $tenant->slug]));

			var setLoading = function (el, loading) {
				if (!el) return;
				if (loading) {
					el.setAttribute('disabled', 'disabled');
				} else {
					el.removeAttribute('disabled');
				}
			};

			var resetSelect = function (el) {
				if (!el) return;
				el.innerHTML = '';
				var opt = document.createElement('option');
				opt.value = '';
				opt.textContent = @json(__('Select'));
				el.appendChild(opt);
			};

			var fillOptions = function (el, items, labelKey, selectedId) {
				resetSelect(el);
				if (!Array.isArray(items)) {
					return;
				}
				items.forEach(function (item) {
					if (!item || item.id === undefined || item.id === null) return;
					var opt = document.createElement('option');
					opt.value = String(item.id);
					opt.textContent = String(item[labelKey] || '');
					if (selectedId !== null && selectedId !== undefined && String(selectedId) !== '' && String(selectedId) === String(item.id)) {
						opt.selected = true;
					}
					el.appendChild(opt);
				});
			};

			var fetchJson = function (url) {
				return fetch(url, { headers: { 'Accept': 'application/json' } })
					.then(function (res) {
						if (!res.ok) throw new Error('Request failed');
						return res.json();
					});
			};

			var loadBrands = function (typeId, preferredBrandId) {
				resetSelect(brandEl);
				resetSelect(deviceEl);
				if (!typeId) {
					return Promise.resolve();
				}

				setLoading(brandEl, true);
				return fetchJson(brandsUrl + '?typeId=' + encodeURIComponent(typeId))
					.then(function (data) {
						fillOptions(brandEl, (data && data.brands) ? data.brands : [], 'name', preferredBrandId);
					})
					.catch(function () {
						resetSelect(brandEl);
					})
					.finally(function () {
						setLoading(brandEl, false);
					});
			};

			var loadDevices = function (typeId, brandId, preferredDeviceId) {
				resetSelect(deviceEl);
				if (!brandId && !typeId) {
					return Promise.resolve();
				}

				var params = [];
				if (typeId) params.push('typeId=' + encodeURIComponent(typeId));
				if (brandId) params.push('brandId=' + encodeURIComponent(brandId));
				var url = devicesUrl + (params.length ? ('?' + params.join('&')) : '');

				setLoading(deviceEl, true);
				return fetchJson(url)
					.then(function (data) {
						fillOptions(deviceEl, (data && data.devices) ? data.devices : [], 'model', preferredDeviceId);
					})
					.catch(function () {
						resetSelect(deviceEl);
					})
					.finally(function () {
						setLoading(deviceEl, false);
					});
			};

			typeEl.addEventListener('change', function () {
				var typeId = typeEl.value;
				selectedTypeId = typeId;
				selectedBrandId = '';
				selectedDeviceId = '';
				loadBrands(typeId, '');
			});

			brandEl.addEventListener('change', function () {
				var typeId = typeEl.value;
				var brandId = brandEl.value;
				selectedBrandId = brandId;
				selectedDeviceId = '';
				loadDevices(typeId, brandId, '');
			});

			var init = function () {
				if (selectedTypeId && typeEl.value !== selectedTypeId) {
					typeEl.value = selectedTypeId;
				}
				if (!typeEl.value) {
					resetSelect(brandEl);
					resetSelect(deviceEl);
					return;
				}

				loadBrands(typeEl.value, selectedBrandId)
					.then(function () {
						if (selectedBrandId && brandEl.value !== selectedBrandId) {
							brandEl.value = selectedBrandId;
						}
						return loadDevices(typeEl.value, brandEl.value, selectedDeviceId);
					});
			};

			init();
		})();
	</script>
@endpush
