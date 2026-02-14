<div class="tabs-panel team-wrap" id="wc_rb_manage_service" role="tabpanel" aria-hidden="true" aria-labelledby="wc_rb_manage_service-label">
	<div class="wrap">
		<h2>{{ __('Service Settings') }}</h2>

		<div class="wc-rb-grey-bg-box">
			<form data-abide class="needs-validation" novalidate method="post" action="{{ route('tenant.settings.services.update', ['business' => $tenant->slug]) }}">
				@csrf

				<table class="form-table border">
					<tbody>
						<tr>
							<th scope="row">
								<label for="wc_service_sidebar_description">{{ __('Single Service Price Sidebar') }}</label>
							</th>
							<td>
								<label>{{ __('Add some description for prices on single service page sidebar') }}</label>
								<textarea class="form-control" name="wc_service_sidebar_description" id="wc_service_sidebar_description">{{ old('wc_service_sidebar_description', (string) ($serviceSidebarDescriptionUi ?? '')) }}</textarea>
							</td>
						</tr>

						<tr>
							<th scope="row">
								<label for="wc_booking_on_service_page_status">{{ __('Disable Booking on Service Page?') }}</label>
							</th>
							<td>
								@php
									$checked = old('wc_booking_on_service_page_status') !== null
										? (old('wc_booking_on_service_page_status') === 'on')
										: (bool) ($serviceDisableBookingOnServicePageUi ?? false);
								@endphp
								<input type="checkbox" name="wc_booking_on_service_page_status" id="wc_booking_on_service_page_status" {{ $checked ? 'checked' : '' }} />
							</td>
						</tr>

						<tr>
							<th scope="row">
								<label for="wc_service_booking_heading">{{ __('Single Service Price Sidebar') }}</label>
							</th>
							<td>
								<input type="text" class="form-control" name="wc_service_booking_heading" id="wc_service_booking_heading" value="{{ old('wc_service_booking_heading', (string) ($serviceBookingHeadingUi ?? '')) }}" />
							</td>
						</tr>

						<tr>
							<th scope="row">
								<label for="wc_service_booking_form">{{ __('Booking Form') }}</label>
							</th>
							<td>
								@php
									$selected = (string) old('wc_service_booking_form', (string) ($serviceBookingFormUi ?? ''));
								@endphp
								<select class="form-control" name="wc_service_booking_form" id="wc_service_booking_form">
									<option value="">{{ __('Select booking form') }}</option>
									<option value="with_type" {{ $selected === 'with_type' ? 'selected' : '' }}>{{ __('Booking with type, manufacture, device and grouped services') }}</option>
									<option value="without_type" {{ $selected === 'without_type' ? 'selected' : '' }}>{{ __('Booking with manufacture, device and services no types') }}</option>
									<option value="warranty_booking" {{ $selected === 'warranty_booking' ? 'selected' : '' }}>{{ __('Booking without service selection') }}</option>
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
