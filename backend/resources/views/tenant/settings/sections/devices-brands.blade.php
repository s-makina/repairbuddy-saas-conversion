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
							<div class="cell medium-6 small-12">
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
							<div class="cell medium-6 small-12">
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
						</div>

						<div class="grid-x grid-margin-x">
							<div class="cell medium-6 small-12">
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
						@php
							$displayOptions = [
								'1' => __('Display'),
								'0' => __('Hide'),
							];
							$customerDisplayOptions = [
								'1' => __('Display in Status check, my account, emails'),
								'0' => __('Hide'),
							];
						@endphp

						<div id="wcrbAdditionalDeviceFieldsWrap" style="overflow-x:auto;">
							<table class="wp-list-table widefat fixed striped posts" style="margin-top: 6px;">
								<thead>
									<tr>
										<th>{{ __('Field label') }}</th>
										<th style="width: 110px;">{{ __('Type') }}</th>
										<th style="width: 160px;">{{ __('In booking form?') }}</th>
										<th style="width: 140px;">{{ __('In invoice?') }}</th>
										<th style="width: 320px;">{{ __('In customer output?') }}</th>
										<th style="width: 70px; text-align: right;">{{ __('Actions') }}</th>
									</tr>
								</thead>
								<tbody id="wcrbAdditionalDeviceFieldsRows" data-next-index="{{ (int) $maxRows }}">
									@for ($i = 0; $i < $maxRows; $i++)
										@php
											$row = $fields[$i] ?? [];
											$rowId = is_array($row) ? (string) ($row['id'] ?? '') : '';
											$rowLabel = is_array($row) ? (string) ($row['label'] ?? '') : '';
											$dBooking = is_array($row) && ($row['displayInBookingForm'] ?? false);
											$dInvoice = is_array($row) && ($row['displayInInvoice'] ?? false);
											$dCustomer = is_array($row) && ($row['displayForCustomer'] ?? false);
										@endphp
										<tr class="wcrb-additional-device-field-row">
											<td>
												<x-settings.input
													class="w-100"
													name="additionalDeviceFields[{{ $i }}][label]"
													:value="old('additionalDeviceFields.' . $i . '.label', $rowLabel)"
													type="text"
												/>
												<input type="hidden" name="additionalDeviceFields[{{ $i }}][id]" value="{{ old('additionalDeviceFields.' . $i . '.id', $rowId) }}" />
											</td>
											<td>
												<x-settings.select
													class="w-100"
													name="additionalDeviceFields[{{ $i }}][type]"
													:value="'text'"
													:options="['text' => __('Text')]"
												/>
											</td>
											<td>
												<x-settings.select
													class="w-100"
													name="additionalDeviceFields[{{ $i }}][displayInBookingForm]"
													:value="old('additionalDeviceFields.' . $i . '.displayInBookingForm', $dBooking ? '1' : '0')"
													:options="$displayOptions"
												/>
											</td>
											<td>
												<x-settings.select
													class="w-100"
													name="additionalDeviceFields[{{ $i }}][displayInInvoice]"
													:value="old('additionalDeviceFields.' . $i . '.displayInInvoice', $dInvoice ? '1' : '0')"
													:options="$displayOptions"
												/>
											</td>
											<td>
												<x-settings.select
													class="w-100"
													name="additionalDeviceFields[{{ $i }}][displayForCustomer]"
													:value="old('additionalDeviceFields.' . $i . '.displayForCustomer', $dCustomer ? '1' : '0')"
													:options="$customerDisplayOptions"
												/>
											</td>
											<td style="vertical-align: middle; text-align: right;">
												<button
													type="button"
													class="button button-small wcrb-remove-additional-device-field"
													title="{{ __('Remove') }}"
													style="border-radius: 999px; padding: 0 10px; line-height: 26px; @if($i===0) display:none; @endif"
												>
													<span aria-hidden="true">×</span>
												</button>
											</td>
										</tr>
									@endfor
								</tbody>
							</table>
						</div>

						<div class="wcrb-settings-actions" style="justify-content: flex-end; padding-top: 4px; margin-top: -2px;">
							<button type="button" class="button button-primary" id="wcrbAddAdditionalDeviceField">{{ __('Add device field') }}</button>
						</div>

						<script>
							(function () {
								var wrap = document.getElementById('wcrbAdditionalDeviceFieldsRows');
								var btn = document.getElementById('wcrbAddAdditionalDeviceField');
								if (!wrap || !btn) {
									return;
								}

								function getRows() {
									return wrap.querySelectorAll('.wcrb-additional-device-field-row');
								}

								function reindexRow(row, newIndex) {
									var nameAttr = 'name';
									var elements = row.querySelectorAll('input, select, textarea, label');
									elements.forEach(function (el) {
										if (el.hasAttribute(nameAttr)) {
											el.setAttribute(nameAttr, el.getAttribute(nameAttr).replace(/additionalDeviceFields\[\d+\]/g, 'additionalDeviceFields[' + newIndex + ']'));
										}
									});
									row.querySelectorAll('input[type="text"]').forEach(function (i) {
										i.value = '';
									});
									row.querySelectorAll('input[type="hidden"]').forEach(function (i) {
										if (i.name && i.name.indexOf('[id]') !== -1) {
											i.value = '';
										}
									});
									row.querySelectorAll('select').forEach(function (s) {
										if (s.name && s.name.indexOf('[type]') !== -1) {
											s.value = 'text';
											return;
										}
										s.value = '1';
									});
								}

								wrap.addEventListener('click', function (e) {
									var target = e.target;
									if (!(target instanceof HTMLElement)) {
										return;
									}
									var removeBtn = target.closest('.wcrb-remove-additional-device-field');
									if (removeBtn) {
										e.preventDefault();
										var row = removeBtn.closest('.wcrb-additional-device-field-row');
										if (!row) {
											return;
										}
										var rows = getRows();
										if (rows.length <= 1) {
											return;
										}
										row.remove();
										var updatedRows = getRows();
										updatedRows.forEach(function (r, idx) {
											var b = r.querySelector('.wcrb-remove-additional-device-field');
											if (b) {
												b.style.display = idx === 0 ? 'none' : '';
											}
										});
										wrap.dataset.nextIndex = String(updatedRows.length);
									}
								});

								btn.addEventListener('click', function () {
									var rows = getRows();
									if (rows.length >= 10) {
										return;
									}
									var last = rows[rows.length - 1];
									if (!last) {
										return;
									}
									var clone = last.cloneNode(true);
									var nextIndex = parseInt(wrap.dataset.nextIndex || String(rows.length), 10);
									if (isNaN(nextIndex)) {
										nextIndex = rows.length;
									}
									reindexRow(clone, nextIndex);
									var removeBtn = clone.querySelector('.wcrb-remove-additional-device-field');
									if (removeBtn) {
										removeBtn.style.display = '';
									}
									wrap.appendChild(clone);
									wrap.dataset.nextIndex = String(nextIndex + 1);
								});
							})();
						</script>
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
	</div>
</div>
