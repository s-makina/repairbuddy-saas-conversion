<div class="tabs-panel team-wrap" id="wcrb_styling" role="tabpanel" aria-hidden="true" aria-labelledby="wcrb_styling-label">
	<div class="wrap">
		<h2>{{ __('Styling & Labels') }}</h2>

		<form data-abide class="needs-validation" novalidate method="post" action="{{ route('tenant.settings.styling.update', ['business' => $tenant->slug]) }}">
			@csrf
			<div class="wcrb-settings-form">
				<div class="wcrb-settings-card">
					<h3 class="wcrb-settings-card-title">{{ __('Labels') }}</h3>
					<div class="wcrb-settings-card-body">
						<div class="row g-3">
							<div class="col-md-6">
								<x-settings.field for="delivery_date_label" :label="__('Delivery Date label')" errorKey="delivery_date_label" class="wcrb-settings-field">
									<x-settings.input
										name="delivery_date_label"
										id="delivery_date_label"
										:value="old('delivery_date_label', (string) ($deliveryLabelUi ?? ''))"
									/>
								</x-settings.field>
							</div>
							<div class="col-md-6">
								<x-settings.field for="pickup_date_label" :label="__('Pickup Date label')" errorKey="pickup_date_label" class="wcrb-settings-field">
									<x-settings.input
										name="pickup_date_label"
										id="pickup_date_label"
										:value="old('pickup_date_label', (string) ($pickupLabelUi ?? ''))"
									/>
								</x-settings.field>
							</div>
							<div class="col-md-6">
								<x-settings.field for="nextservice_date_label" :label="__('Next Service Date label')" errorKey="nextservice_date_label" class="wcrb-settings-field">
									<x-settings.input
										name="nextservice_date_label"
										id="nextservice_date_label"
										:value="old('nextservice_date_label', (string) ($nextServiceLabelUi ?? ''))"
									/>
								</x-settings.field>
							</div>
							<div class="col-md-6">
								<x-settings.field for="casenumber_label" :label="__('Case Number label')" errorKey="casenumber_label" class="wcrb-settings-field">
									<x-settings.input
										name="casenumber_label"
										id="casenumber_label"
										:value="old('casenumber_label', (string) ($caseNumberLabelUi ?? ''))"
									/>
								</x-settings.field>
							</div>
						</div>
					</div>
				</div>

				<div class="wcrb-settings-card">
					<h3 class="wcrb-settings-card-title">{{ __('Styling') }}</h3>
					<div class="wcrb-settings-card-body">
						<div class="row g-3">
							<div class="col-md-6">
								<x-settings.field for="primary_color" :label="__('Primary Color')" errorKey="primary_color" class="wcrb-settings-field">
									<x-settings.input
										name="primary_color"
										id="primary_color"
										type="color"
										:value="old('primary_color', (string) ($primaryColorUi ?? '#063e70'))"
										style="height: 42px; padding: 6px;"
									/>
								</x-settings.field>
							</div>
							<div class="col-md-6">
								<x-settings.field for="secondary_color" :label="__('Secondary Color')" errorKey="secondary_color" class="wcrb-settings-field">
									<x-settings.input
										name="secondary_color"
										id="secondary_color"
										type="color"
										:value="old('secondary_color', (string) ($secondaryColorUi ?? '#fd6742'))"
										style="height: 42px; padding: 6px;"
									/>
								</x-settings.field>
							</div>
						</div>
						<div class="wcrb-settings-actions" style="justify-content: flex-end; padding-top: 8px;">
							<button type="submit" class="button button-primary">{{ __('Update Options') }}</button>
						</div>
					</div>
				</div>
			</div>
		</form>
	</div>
</div>
