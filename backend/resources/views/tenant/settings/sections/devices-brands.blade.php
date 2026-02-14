<div class="tabs-panel team-wrap" id="wc_rb_manage_devices" role="tabpanel" aria-hidden="true" aria-labelledby="wc_rb_manage_devices-label">
	<div class="wrap">
		<h2>{{ __('Brands & Devices') }}</h2>

		<form data-abide class="needs-validation" novalidate method="post" action="{{ route('tenant.settings.devices_brands.update', ['business' => $tenant->slug]) }}">
			@csrf
			<div class="wcrb-settings-form">
				<div class="wcrb-settings-card">
					<h3 class="wcrb-settings-card-title">{{ __('Pin code') }}</h3>
					<div class="wcrb-settings-card-body">
						<div class="wcrb-settings-option">
							<div class="wcrb-settings-option-head">
								<div class="wcrb-settings-option-control">
									<x-settings.toggle
										name="enablePinCodeField"
										id="enablePinCodeField"
										:checked="old('enablePinCodeField') !== null ? ((string) old('enablePinCodeField') === '1') : (bool) ($devicesBrandsUi['enablePinCodeField'] ?? false)"
										value="1"
										uncheckedValue="0"
									/>
								</div>
								<label for="enablePinCodeField" class="wcrb-settings-option-title">{{ __('Enable pin code field in Jobs') }}</label>
							</div>
						</div>
						<div class="wcrb-settings-option">
							<div class="wcrb-settings-option-head">
								<div class="wcrb-settings-option-control">
									<x-settings.toggle
										name="showPinCodeInDocuments"
										id="showPinCodeInDocuments"
										:checked="old('showPinCodeInDocuments') !== null ? ((string) old('showPinCodeInDocuments') === '1') : (bool) ($devicesBrandsUi['showPinCodeInDocuments'] ?? false)"
										value="1"
										uncheckedValue="0"
									/>
								</div>
								<label for="showPinCodeInDocuments" class="wcrb-settings-option-title">{{ __('Show pin code on invoices, emails, and status check') }}</label>
							</div>
						</div>
					</div>
				</div>

				<div class="wcrb-settings-card">
					<h3 class="wcrb-settings-card-title">{{ __('Integrations') }}</h3>
					<div class="wcrb-settings-card-body">
						<div class="wcrb-settings-option">
							<div class="wcrb-settings-option-head">
								<div class="wcrb-settings-option-control">
									<x-settings.toggle
										name="useWooProductsAsDevices"
										id="useWooProductsAsDevices"
										:checked="old('useWooProductsAsDevices') !== null ? ((string) old('useWooProductsAsDevices') === '1') : (bool) ($devicesBrandsUi['useWooProductsAsDevices'] ?? false)"
										value="1"
										uncheckedValue="0"
									/>
								</div>
								<label for="useWooProductsAsDevices" class="wcrb-settings-option-title">{{ __('Use WooCommerce products instead of devices & brands') }}</label>
							</div>
						</div>
					</div>
				</div>

				<div class="wcrb-settings-card">
					<h3 class="wcrb-settings-card-title">{{ __('Labels') }}</h3>
					<div class="wcrb-settings-card-body">
						<div class="grid-x grid-margin-x">
							<div class="cell medium-6 small-12">
								<x-settings.field for="labels_note" :label="__('Note label')" class="wcrb-settings-field">
									<x-settings.input
										name="labels[note]"
										id="labels_note"
										:value="old('labels.note', (string) (($devicesBrandsUi['labels']['note'] ?? '') ?? ''))"
										type="text"
										:placeholder="__('Note')"
									/>
								</x-settings.field>
							</div>
							<div class="cell medium-6 small-12">
								<x-settings.field for="labels_pin" :label="__('Pin code / password label')" class="wcrb-settings-field">
									<x-settings.input
										name="labels[pin]"
										id="labels_pin"
										:value="old('labels.pin', (string) (($devicesBrandsUi['labels']['pin'] ?? '') ?? ''))"
										type="text"
										:placeholder="__('Pin Code/Password')"
									/>
								</x-settings.field>
							</div>
						</div>

						<div class="grid-x grid-margin-x">
							<div class="cell medium-4 small-12">
								<x-settings.field for="labels_device" :label="__('Device label')" class="wcrb-settings-field">
									<x-settings.input
										name="labels[device]"
										id="labels_device"
										:value="old('labels.device', (string) (($devicesBrandsUi['labels']['device'] ?? '') ?? ''))"
										type="text"
										:placeholder="__('Device')"
									/>
								</x-settings.field>
							</div>
							<div class="cell medium-4 small-12">
								<x-settings.field for="labels_deviceBrand" :label="__('Brand label')" class="wcrb-settings-field">
									<x-settings.input
										name="labels[deviceBrand]"
										id="labels_deviceBrand"
										:value="old('labels.deviceBrand', (string) (($devicesBrandsUi['labels']['deviceBrand'] ?? '') ?? ''))"
										type="text"
										:placeholder="__('Device Brand')"
									/>
								</x-settings.field>
							</div>
							<div class="cell medium-4 small-12">
								<x-settings.field for="labels_deviceType" :label="__('Type label')" class="wcrb-settings-field">
									<x-settings.input
										name="labels[deviceType]"
										id="labels_deviceType"
										:value="old('labels.deviceType', (string) (($devicesBrandsUi['labels']['deviceType'] ?? '') ?? ''))"
										type="text"
										:placeholder="__('Device Type')"
									/>
								</x-settings.field>
							</div>
						</div>

						<div class="grid-x grid-margin-x">
							<div class="cell medium-6 small-12">
								<x-settings.field for="labels_imei" :label="__('ID/IMEI label')" class="wcrb-settings-field">
									<x-settings.input
										name="labels[imei]"
										id="labels_imei"
										:value="old('labels.imei', (string) (($devicesBrandsUi['labels']['imei'] ?? '') ?? ''))"
										type="text"
										:placeholder="__('ID/IMEI')"
									/>
								</x-settings.field>
							</div>
						</div>
					</div>
				</div>

				<div class="wcrb-settings-card">
					<h3 class="wcrb-settings-card-title">{{ __('Pickup & Delivery') }}</h3>
					<div class="wcrb-settings-card-body">
						<div class="wcrb-settings-option">
							<div class="wcrb-settings-option-head">
								<div class="wcrb-settings-option-control">
									<x-settings.toggle
										name="pickupDeliveryEnabled"
										id="pickupDeliveryEnabled"
										:checked="old('pickupDeliveryEnabled') !== null ? ((string) old('pickupDeliveryEnabled') === '1') : (bool) ($pickupDeliveryEnabled ?? false)"
										value="1"
										uncheckedValue="0"
									/>
								</div>
								<label for="pickupDeliveryEnabled" class="wcrb-settings-option-title">{{ __('Offer pickup and delivery') }}</label>
							</div>
						</div>

						<div class="grid-x grid-margin-x">
							<div class="cell medium-6 small-12">
								<x-settings.field for="pickupCharge" :label="__('Pick up charge')" class="wcrb-settings-field">
									<x-settings.input name="pickupCharge" id="pickupCharge" :value="old('pickupCharge', (string) ($pickupCharge ?? ''))" type="text" />
								</x-settings.field>
							</div>
							<div class="cell medium-6 small-12">
								<x-settings.field for="deliveryCharge" :label="__('Delivery charge')" class="wcrb-settings-field">
									<x-settings.input name="deliveryCharge" id="deliveryCharge" :value="old('deliveryCharge', (string) ($deliveryCharge ?? ''))" type="text" />
								</x-settings.field>
							</div>
						</div>
					</div>
				</div>

				<div class="wcrb-settings-card">
					<h3 class="wcrb-settings-card-title">{{ __('Rental') }}</h3>
					<div class="wcrb-settings-card-body">
						<div class="wcrb-settings-option">
							<div class="wcrb-settings-option-head">
								<div class="wcrb-settings-option-control">
									<x-settings.toggle
										name="rentalEnabled"
										id="rentalEnabled"
										:checked="old('rentalEnabled') !== null ? ((string) old('rentalEnabled') === '1') : (bool) ($rentalEnabled ?? false)"
										value="1"
										uncheckedValue="0"
									/>
								</div>
								<label for="rentalEnabled" class="wcrb-settings-option-title">{{ __('Offer device rental') }}</label>
							</div>
						</div>

						<div class="grid-x grid-margin-x">
							<div class="cell medium-6 small-12">
								<x-settings.field for="rentalPerDay" :label="__('Rent per day')" class="wcrb-settings-field">
									<x-settings.input name="rentalPerDay" id="rentalPerDay" :value="old('rentalPerDay', (string) ($rentalPerDay ?? ''))" type="text" />
								</x-settings.field>
							</div>
							<div class="cell medium-6 small-12">
								<x-settings.field for="rentalPerWeek" :label="__('Rent per week')" class="wcrb-settings-field">
									<x-settings.input name="rentalPerWeek" id="rentalPerWeek" :value="old('rentalPerWeek', (string) ($rentalPerWeek ?? ''))" type="text" />
								</x-settings.field>
							</div>
						</div>
					</div>
				</div>

				@php
					$fields = is_array($additionalDeviceFields ?? null) ? $additionalDeviceFields : [];
					$maxRows = max(1, min(10, count($fields) + 1));
				@endphp
				<div class="wcrb-settings-card">
					<h3 class="wcrb-settings-card-title">{{ __('Additional device fields') }}</h3>
					<div class="wcrb-settings-card-body">
						<div class="grid-x grid-margin-x" style="font-weight: 600; margin-bottom: 8px;">
							<div class="cell medium-5 small-12">{{ __('Field label') }}</div>
							<div class="cell medium-2 small-12">{{ __('Booking') }}</div>
							<div class="cell medium-2 small-12">{{ __('Invoice') }}</div>
							<div class="cell medium-3 small-12">{{ __('Customer') }}</div>
						</div>

						@for ($i = 0; $i < $maxRows; $i++)
							@php
								$row = $fields[$i] ?? [];
								$rowId = is_array($row) ? (string) ($row['id'] ?? '') : '';
								$rowLabel = is_array($row) ? (string) ($row['label'] ?? '') : '';
								$dBooking = is_array($row) && ($row['displayInBookingForm'] ?? false);
								$dInvoice = is_array($row) && ($row['displayInInvoice'] ?? false);
								$dCustomer = is_array($row) && ($row['displayForCustomer'] ?? false);
							@endphp
							<div class="grid-x grid-margin-x align-middle" style="margin-bottom: 10px;">
								<div class="cell medium-5 small-12">
									<x-settings.input
										class="w-100"
										name="additionalDeviceFields[{{ $i }}][label]"
										:value="old('additionalDeviceFields.' . $i . '.label', $rowLabel)"
										type="text"
									/>
									<input type="hidden" name="additionalDeviceFields[{{ $i }}][id]" value="{{ old('additionalDeviceFields.' . $i . '.id', $rowId) }}" />
									<input type="hidden" name="additionalDeviceFields[{{ $i }}][type]" value="text" />
								</div>
								<div class="cell medium-2 small-12">
									<x-settings.toggle
										:name="'additionalDeviceFields[' . $i . '][displayInBookingForm]'"
										:id="'additionalDeviceFields_' . $i . '_displayInBookingForm'"
										value="1"
										uncheckedValue="0"
										:checked="old('additionalDeviceFields.' . $i . '.displayInBookingForm') !== null ? ((string) old('additionalDeviceFields.' . $i . '.displayInBookingForm') === '1') : (bool) $dBooking"
									/>
								</div>
								<div class="cell medium-2 small-12">
									<x-settings.toggle
										:name="'additionalDeviceFields[' . $i . '][displayInInvoice]'"
										:id="'additionalDeviceFields_' . $i . '_displayInInvoice'"
										value="1"
										uncheckedValue="0"
										:checked="old('additionalDeviceFields.' . $i . '.displayInInvoice') !== null ? ((string) old('additionalDeviceFields.' . $i . '.displayInInvoice') === '1') : (bool) $dInvoice"
									/>
								</div>
								<div class="cell medium-3 small-12">
									<x-settings.toggle
										:name="'additionalDeviceFields[' . $i . '][displayForCustomer]'"
										:id="'additionalDeviceFields_' . $i . '_displayForCustomer'"
										value="1"
										uncheckedValue="0"
										:checked="old('additionalDeviceFields.' . $i . '.displayForCustomer') !== null ? ((string) old('additionalDeviceFields.' . $i . '.displayForCustomer') === '1') : (bool) $dCustomer"
									/>
								</div>
							</div>
						@endfor
					</div>
				</div>

				<div class="grid-x grid-margin-x">
					<div class="cell small-12">
						<div class="wcrb-settings-actions">
							<button type="submit" class="button button-primary">{{ __('Update Options') }}</button>
						</div>
					</div>
				</div>
			</div>
		</form>

		<div class="wcrb-settings-form" style="margin-top: 20px;">
			<div class="wcrb-settings-card">
				<h3 class="wcrb-settings-card-title">{{ __('Manage Brands') }}</h3>
				<div class="wcrb-settings-card-body">
					<form data-abide class="needs-validation" novalidate method="post" action="{{ route('tenant.settings.device_brands.store', ['business' => $tenant->slug]) }}">
						@csrf
						<div class="grid-x grid-margin-x align-middle">
							<div class="cell medium-6 small-12">
								<x-settings.field for="rb_brand_name" :label="__('Add brand')" class="wcrb-settings-field">
									<x-settings.input
										name="name"
										id="rb_brand_name"
										:value="old('name', '')"
										type="text"
										:placeholder="__('Brand name')"
										:required="true"
									/>
								</x-settings.field>
							</div>
							<div class="cell medium-6 small-12">
								<div class="wcrb-settings-actions" style="justify-content: flex-start; padding-top: 24px;">
									<button class="button button-primary" type="submit">{{ __('Add') }}</button>
								</div>
							</div>
						</div>
					</form>

				<table class="wp-list-table widefat fixed striped posts" style="margin-top: 12px;">
					<thead>
						<tr>
							<th class="column-id">{{ __('ID') }}</th>
							<th>{{ __('Name') }}</th>
							<th>{{ __('Status') }}</th>
							<th class="column-action">{{ __('Actions') }}</th>
						</tr>
					</thead>
					<tbody>
						@if (($deviceBrands ?? collect())->count() > 0)
							@foreach ($deviceBrands as $b)
								<tr>
									<td>{{ (string) $b->id }}</td>
									<td><strong>{{ (string) $b->name }}</strong></td>
									<td>{{ $b->is_active ? 'active' : 'inactive' }}</td>
									<td>
										<form method="post" style="display:inline;" action="{{ route('tenant.settings.device_brands.active', ['business' => $tenant->slug, 'brand' => $b->id]) }}">
											@csrf
											<input type="hidden" name="is_active" value="{{ $b->is_active ? '0' : '1' }}" />
											<button type="submit" class="button button-small">{{ __('Change Status') }}</button>
										</form>
										<form method="post" style="display:inline;" action="{{ route('tenant.settings.device_brands.delete', ['business' => $tenant->slug, 'brand' => $b->id]) }}">
											@csrf
											<button type="submit" class="button button-small">{{ __('Delete') }}</button>
										</form>
									</td>
								</tr>
							@endforeach
						@else
							<tr>
								<td colspan="4">{{ __('No brands yet.') }}</td>
							</tr>
						@endif
					</tbody>
				</table>
				</div>
			</div>
		</div>
	</div>
</div>
