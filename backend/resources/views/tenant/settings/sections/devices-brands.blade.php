<div class="tabs-panel team-wrap" id="wc_rb_manage_devices" role="tabpanel" aria-hidden="true" aria-labelledby="wc_rb_manage_devices-label">
	<div class="wrap">
		<h2>{{ __('Brands & Devices') }}</h2>

		<div class="wc-rb-grey-bg-box">
			<h3>{{ __('Device Settings') }}</h3>

			<form data-abide class="needs-validation" novalidate method="post" action="{{ route('tenant.settings.devices_brands.update', ['business' => $tenant->slug]) }}">
				@csrf
				<table class="form-table border">
					<tbody>
						<tr>
							<th scope="row">
								<label for="enablePinCodeField">{{ __('Enable Pin Code Field in Jobs page') }}</label>
							</th>
							<td>
								<input
									type="checkbox"
									name="enablePinCodeField"
									id="enablePinCodeField"
									{{ ($devicesBrandsUi['enablePinCodeField'] ?? false) ? 'checked' : '' }}
								/>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="showPinCodeInDocuments">{{ __('Show Pin Code in Invoices/Emails/Status Check') }}</label>
							</th>
							<td>
								<input
									type="checkbox"
									name="showPinCodeInDocuments"
									id="showPinCodeInDocuments"
									{{ ($devicesBrandsUi['showPinCodeInDocuments'] ?? false) ? 'checked' : '' }}
								/>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="useWooProductsAsDevices">{{ __('Replace devices & brands with WooCommerce products') }}</label>
							</th>
							<td>
								<input
									type="checkbox"
									name="useWooProductsAsDevices"
									id="useWooProductsAsDevices"
									{{ ($devicesBrandsUi['useWooProductsAsDevices'] ?? false) ? 'checked' : '' }}
								/>
							</td>
						</tr>

						<tr>
							<th scope="row">{{ __('Other Labels') }}</th>
							<td>
								<table class="form-table no-padding-table">
									<tr>
										<td>
											<label>
												{{ __('Note label like Device Note') }}
												<input
													name="labels[note]"
													class="regular-text"
													value="{{ old('labels.note', (string) (($devicesBrandsUi['labels']['note'] ?? '') ?? '')) }}"
													type="text"
													placeholder="{{ __('Note') }}"
												/>
											</label>
										</td>
										<td>
											<label>
												{{ __('Pin Code/Password Label') }}
												<input
													name="labels[pin]"
													class="regular-text"
													value="{{ old('labels.pin', (string) (($devicesBrandsUi['labels']['pin'] ?? '') ?? '')) }}"
													type="text"
													placeholder="{{ __('Pin Code/Password') }}"
												/>
											</label>
										</td>
									</tr>
								</table>
							</td>
						</tr>

						<tr>
							<th scope="row">{{ __('Device Label') }}</th>
							<td>
								<table class="form-table no-padding-table">
									<tr>
										<td>
											<label>
												{{ __('Singular device label') }}
												<input
													name="labels[device]"
													class="regular-text"
													value="{{ old('labels.device', (string) (($devicesBrandsUi['labels']['device'] ?? '') ?? '')) }}"
													type="text"
													placeholder="{{ __('Device') }}"
												/>
											</label>
										</td>
									</tr>
								</table>
							</td>
						</tr>

						<tr>
							<th scope="row">{{ __('Device Brand Label') }}</th>
							<td>
								<table class="form-table no-padding-table">
									<tr>
										<td>
											<label>
												{{ __('Singular device brand label') }}
												<input
													name="labels[deviceBrand]"
													class="regular-text"
													value="{{ old('labels.deviceBrand', (string) (($devicesBrandsUi['labels']['deviceBrand'] ?? '') ?? '')) }}"
													type="text"
													placeholder="{{ __('Device Brand') }}"
												/>
											</label>
										</td>
									</tr>
								</table>
							</td>
						</tr>

						<tr>
							<th scope="row">{{ __('Device Type Label') }}</th>
							<td>
								<table class="form-table no-padding-table">
									<tr>
										<td>
											<label>
												{{ __('Singular device type label') }}
												<input
													name="labels[deviceType]"
													class="regular-text"
													value="{{ old('labels.deviceType', (string) (($devicesBrandsUi['labels']['deviceType'] ?? '') ?? '')) }}"
													type="text"
													placeholder="{{ __('Device Type') }}"
												/>
											</label>
										</td>
									</tr>
								</table>
							</td>
						</tr>

						<tr>
							<th scope="row">
								<label for="labels_imei">{{ __('ID/IMEI Label') }}</label>
							</th>
							<td>
								<input
									name="labels[imei]"
									id="labels_imei"
									class="regular-text"
									value="{{ old('labels.imei', (string) (($devicesBrandsUi['labels']['imei'] ?? '') ?? '')) }}"
									type="text"
									placeholder="{{ __('ID/IMEI') }}"
								/>
							</td>
						</tr>

						<tr>
							<th scope="row">
								<label for="pickupDeliveryEnabled">{{ __('Offer pickup and delivery?') }}</label>
							</th>
							<td>
								<input
									type="checkbox"
									name="pickupDeliveryEnabled"
									id="pickupDeliveryEnabled"
									{{ old('pickupDeliveryEnabled') !== null ? (old('pickupDeliveryEnabled') === 'on' ? 'checked' : '') : (($pickupDeliveryEnabled ?? false) ? 'checked' : '') }}
								/>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="pickupCharge">{{ __('Pick up charge') }}</label>
							</th>
							<td>
								<input
									name="pickupCharge"
									id="pickupCharge"
									class="regular-text"
									value="{{ old('pickupCharge', (string) ($pickupCharge ?? '')) }}"
									type="text"
								/>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="deliveryCharge">{{ __('Delivery charge') }}</label>
							</th>
							<td>
								<input
									name="deliveryCharge"
									id="deliveryCharge"
									class="regular-text"
									value="{{ old('deliveryCharge', (string) ($deliveryCharge ?? '')) }}"
									type="text"
								/>
							</td>
						</tr>

						<tr>
							<th scope="row">
								<label for="rentalEnabled">{{ __('Offer device rental?') }}</label>
							</th>
							<td>
								<input
									type="checkbox"
									name="rentalEnabled"
									id="rentalEnabled"
									{{ old('rentalEnabled') !== null ? (old('rentalEnabled') === 'on' ? 'checked' : '') : (($rentalEnabled ?? false) ? 'checked' : '') }}
								/>
							</td>
						</tr>
						<tr>
							<th scope="row">{{ __('Device rent') }}</th>
							<td>
								<table class="form-table no-padding-table">
									<tr>
										<td>
											<label>
												{{ __('Device rent per day') }}
												<input
													name="rentalPerDay"
													class="regular-text"
													value="{{ old('rentalPerDay', (string) ($rentalPerDay ?? '')) }}"
													type="text"
												/>
											</label>
										</td>
										<td>
											<label>
												{{ __('Device rent per week') }}
												<input
													name="rentalPerWeek"
													class="regular-text"
													value="{{ old('rentalPerWeek', (string) ($rentalPerWeek ?? '')) }}"
													type="text"
												/>
											</label>
										</td>
									</tr>
								</table>
							</td>
						</tr>

						@php
							$fields = is_array($additionalDeviceFields ?? null) ? $additionalDeviceFields : [];
							$maxRows = max(1, min(10, count($fields) + 1));
						@endphp
						@for ($i = 0; $i < $maxRows; $i++)
							@php
								$row = $fields[$i] ?? [];
								$rowId = is_array($row) ? (string) ($row['id'] ?? '') : '';
								$rowLabel = is_array($row) ? (string) ($row['label'] ?? '') : '';
								$dBooking = is_array($row) && ($row['displayInBookingForm'] ?? false);
								$dInvoice = is_array($row) && ($row['displayInInvoice'] ?? false);
								$dCustomer = is_array($row) && ($row['displayForCustomer'] ?? false);
							@endphp
							<tr>
								<td>
									<label>
										{{ __('Field label') }}
										<input
											class="regular-text"
											name="additionalDeviceFields[{{ $i }}][label]"
											value="{{ old('additionalDeviceFields.' . $i . '.label', $rowLabel) }}"
											type="text"
										/>
									</label>
									<input type="hidden" name="additionalDeviceFields[{{ $i }}][id]" value="{{ old('additionalDeviceFields.' . $i . '.id', $rowId) }}" />
									<input type="hidden" name="additionalDeviceFields[{{ $i }}][type]" value="text" />
								</td>
								<td>
									<label>
										{{ __('In booking form?') }}
										<input
											type="checkbox"
											name="additionalDeviceFields[{{ $i }}][displayInBookingForm]"
											value="1"
											{{ old('additionalDeviceFields.' . $i . '.displayInBookingForm') !== null ? 'checked' : ($dBooking ? 'checked' : '') }}
										/>
									</label>
								</td>
								<td>
									<label>
										{{ __('In invoice?') }}
										<input
											type="checkbox"
											name="additionalDeviceFields[{{ $i }}][displayInInvoice]"
											value="1"
											{{ old('additionalDeviceFields.' . $i . '.displayInInvoice') !== null ? 'checked' : ($dInvoice ? 'checked' : '') }}
										/>
									</label>
								</td>
								<td>
									<label>
										{{ __('In customer output?') }}
										<input
											type="checkbox"
											name="additionalDeviceFields[{{ $i }}][displayForCustomer]"
											value="1"
											{{ old('additionalDeviceFields.' . $i . '.displayForCustomer') !== null ? 'checked' : ($dCustomer ? 'checked' : '') }}
										/>
									</label>
								</td>
							</tr>
						@endfor
					</tbody>
				</table>
				<button type="submit" class="button button-primary">{{ __('Update Options') }}</button>
			</form>
		</div>

		<div class="wc-rb-grey-bg-box">
			<h3>{{ __('Manage Brands') }}</h3>

			<form data-abide class="needs-validation" novalidate method="post" action="{{ route('tenant.settings.device_brands.store', ['business' => $tenant->slug]) }}">
				@csrf
				<table class="form-table border">
					<tbody>
						<tr>
							<th scope="row">
								<label for="rb_brand_name">{{ __('Add brand') }}</label>
							</th>
							<td>
								<input
									name="name"
									id="rb_brand_name"
									class="regular-text"
									value="{{ old('name', '') }}"
									type="text"
									placeholder="{{ __('Brand name') }}"
									required
								/>
								<button class="button button-primary" type="submit">{{ __('Add') }}</button>
							</td>
						</tr>
					</tbody>
				</table>
			</form>

			<table class="wp-list-table widefat fixed striped posts">
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
