<div class="tabs-panel team-wrap" id="wcrb_styling" role="tabpanel" aria-hidden="true" aria-labelledby="wcrb_styling-label">
	<div class="wrap">
		<h2>{{ __('Styling & Labels') }}</h2>

		<div class="wc-rb-grey-bg-box">
			<form data-abide class="needs-validation" novalidate method="post" action="{{ route('tenant.settings.styling.update', ['business' => $tenant->slug]) }}">
				@csrf

				<h2>{{ __('Labels') }}</h2>
				<table class="form-table border">
					<tbody>
						<tr>
							<td>
								<label for="delivery_date_label">
									{{ __('Delivery Date label') }}
									<input type="text" id="delivery_date_label" class="form-control" name="delivery_date_label" value="{{ old('delivery_date_label', (string) ($deliveryLabelUi ?? '')) }}" />
								</label>
							</td>
							<td>
								<label for="pickup_date_label">
									{{ __('Pickup Date label') }}
									<input type="text" id="pickup_date_label" class="form-control" name="pickup_date_label" value="{{ old('pickup_date_label', (string) ($pickupLabelUi ?? '')) }}" />
								</label>
							</td>
						</tr>
						<tr>
							<td>
								<label for="nextservice_date_label">
									{{ __('Next Service Date label') }}
									<input type="text" id="nextservice_date_label" class="form-control" name="nextservice_date_label" value="{{ old('nextservice_date_label', (string) ($nextServiceLabelUi ?? '')) }}" />
								</label>
							</td>
							<td>
								<label for="casenumber_label">
									{{ __('Case Number label') }}
									<input type="text" id="casenumber_label" class="form-control" name="casenumber_label" value="{{ old('casenumber_label', (string) ($caseNumberLabelUi ?? '')) }}" />
								</label>
							</td>
						</tr>
					</tbody>
				</table>

				<h2>{{ __('Styling') }}</h2>
				<table class="form-table border">
					<tbody>
						<tr>
							<th scope="row"><label for="primary_color">{{ __('Primary Color') }}</label></th>
							<td><input type="color" id="primary_color" class="form-control" name="primary_color" value="{{ old('primary_color', (string) ($primaryColorUi ?? '#063e70')) }}" /></td>
						</tr>
						<tr>
							<th scope="row"><label for="secondary_color">{{ __('Secondary Color') }}</label></th>
							<td><input type="color" id="secondary_color" class="form-control" name="secondary_color" value="{{ old('secondary_color', (string) ($secondaryColorUi ?? '#fd6742')) }}" /></td>
						</tr>
					</tbody>
				</table>

				<button type="submit" class="button button-primary">{{ __('Update Options') }}</button>
			</form>
		</div>
	</div>
</div>
