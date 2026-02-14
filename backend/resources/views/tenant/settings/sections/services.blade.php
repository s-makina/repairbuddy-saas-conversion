<div class="tabs-panel team-wrap" id="wc_rb_manage_service" role="tabpanel" aria-hidden="true" aria-labelledby="wc_rb_manage_service-label">
	<div class="wrap">
		<h2>{{ __('Service Settings') }}</h2>

		<form data-abide class="needs-validation" novalidate method="post" action="{{ route('tenant.settings.services.update', ['business' => $tenant->slug]) }}">
			@csrf
			<div class="wcrb-settings-form">
				<div class="wcrb-settings-card">
					<h3 class="wcrb-settings-card-title">{{ __('Service Settings') }}</h3>
					<div class="wcrb-settings-card-body">
						<div class="row g-3">
							<div class="col-12">
								<x-settings.field for="wc_service_sidebar_description" :label="__('Single Service Price Sidebar')" :help="__('Add some description for prices on single service page sidebar')" errorKey="wc_service_sidebar_description" class="wcrb-settings-field">
									<x-settings.textarea
										name="wc_service_sidebar_description"
										id="wc_service_sidebar_description"
										:rows="4"
										:value="old('wc_service_sidebar_description', (string) ($serviceSidebarDescriptionUi ?? ''))"
									/>
								</x-settings.field>
							</div>

							<div class="col-12">
								@php
									$checked = (string) old('wc_booking_on_service_page_status', ($serviceDisableBookingOnServicePageUi ?? false) ? 'on' : 'off') === 'on';
								@endphp
								<div class="wcrb-settings-option" style="border-bottom: 0; padding-bottom: 6px; margin-bottom: 6px;">
									<div class="wcrb-settings-option-head">
										<div class="wcrb-settings-option-control">
											<x-settings.toggle
												name="wc_booking_on_service_page_status"
												id="wc_booking_on_service_page_status"
												:checked="$checked"
												value="on"
												uncheckedValue="off"
											/>
										</div>
										<label for="wc_booking_on_service_page_status" class="wcrb-settings-option-title">{{ __('Disable Booking on Service Page?') }}</label>
									</div>
									<div class="wcrb-settings-option-description"></div>
								</div>
							</div>

							<div class="col-md-6">
								<x-settings.field for="wc_service_booking_heading" :label="__('Booking Heading')" errorKey="wc_service_booking_heading" class="wcrb-settings-field">
									<x-settings.input
										name="wc_service_booking_heading"
										id="wc_service_booking_heading"
										:value="old('wc_service_booking_heading', (string) ($serviceBookingHeadingUi ?? ''))"
									/>
								</x-settings.field>
							</div>

							<div class="col-md-6">
								@php
									$selected = (string) old('wc_service_booking_form', (string) ($serviceBookingFormUi ?? ''));
								@endphp
								<x-settings.field for="wc_service_booking_form" :label="__('Booking Form')" errorKey="wc_service_booking_form" class="wcrb-settings-field">
									<x-settings.select
										name="wc_service_booking_form"
										id="wc_service_booking_form"
										:options="[
											'' => __('Select booking form'),
											'with_type' => __('Booking with type, manufacture, device and grouped services'),
											'without_type' => __('Booking with manufacture, device and services no types'),
											'warranty_booking' => __('Booking without service selection'),
										]"
										:value="$selected"
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
