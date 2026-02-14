<div class="tabs-panel team-wrap" id="wcrb_estimates_tab" role="tabpanel" aria-hidden="true" aria-labelledby="wcrb_estimates_tab-label">
	<div class="wrap">
		<h2>{{ __('Estimates') }}</h2>
		<p>{{ __('Estimates settings allow you to configure how estimates and quotes are managed in your repair shop.') }}</p>
		@php
			$oldEstimateEmailSubjectCustomer = old('estimate_email_subject_to_customer');
			$estimateEmailSubjectCustomerValue = (is_string($oldEstimateEmailSubjectCustomer) && trim($oldEstimateEmailSubjectCustomer) !== '')
				? $oldEstimateEmailSubjectCustomer
				: (string) ($estimateEmailSubjectCustomerUi ?? '');

			$oldEstimateEmailBodyCustomer = old('estimate_email_body_to_customer');
			$estimateEmailBodyCustomerValue = (is_string($oldEstimateEmailBodyCustomer) && trim($oldEstimateEmailBodyCustomer) !== '')
				? $oldEstimateEmailBodyCustomer
				: (string) ($estimateEmailBodyCustomerUi ?? '');

			$oldEstimateApproveSubjectAdmin = old('estimate_approve_email_subject_to_admin');
			$estimateApproveEmailSubjectAdminValue = (is_string($oldEstimateApproveSubjectAdmin) && trim($oldEstimateApproveSubjectAdmin) !== '')
				? $oldEstimateApproveSubjectAdmin
				: (string) ($estimateApproveEmailSubjectAdminUi ?? '');

			$oldEstimateApproveBodyAdmin = old('estimate_approve_email_body_to_admin');
			$estimateApproveEmailBodyAdminValue = (is_string($oldEstimateApproveBodyAdmin) && trim($oldEstimateApproveBodyAdmin) !== '')
				? $oldEstimateApproveBodyAdmin
				: (string) ($estimateApproveEmailBodyAdminUi ?? '');

			$oldEstimateRejectSubjectAdmin = old('estimate_reject_email_subject_to_admin');
			$estimateRejectEmailSubjectAdminValue = (is_string($oldEstimateRejectSubjectAdmin) && trim($oldEstimateRejectSubjectAdmin) !== '')
				? $oldEstimateRejectSubjectAdmin
				: (string) ($estimateRejectEmailSubjectAdminUi ?? '');

			$oldEstimateRejectBodyAdmin = old('estimate_reject_email_body_to_admin');
			$estimateRejectEmailBodyAdminValue = (is_string($oldEstimateRejectBodyAdmin) && trim($oldEstimateRejectBodyAdmin) !== '')
				? $oldEstimateRejectBodyAdmin
				: (string) ($estimateRejectEmailBodyAdminUi ?? '');
		@endphp

		<form data-abide class="needs-validation" novalidate method="post" action="{{ route('tenant.settings.estimates.update', ['business' => $tenant->slug]) }}">
			@csrf
			<div class="wcrb-settings-form">
				<div class="wcrb-settings-card">
					<h3 class="wcrb-settings-card-title">{{ __('Estimate Settings') }}</h3>
					<div class="wcrb-settings-card-body">
						<div class="wcrb-settings-option" style="border-bottom: 0; padding-bottom: 10px; margin-bottom: 10px;">
							<div class="wcrb-settings-option-head">
								<div class="wcrb-settings-option-control">
									<x-settings.toggle
										name="estimates_enabled"
										id="estimates_enabled"
										:checked="(bool) ($estimatesEnabledUi ?? false)"
										value="1"
										uncheckedValue="0"
									/>
								</div>
								<label for="estimates_enabled" class="wcrb-settings-option-title">{{ __('Enable Estimates') }}</label>
							</div>
						</div>

						<div class="wcrb-settings-option" style="border-bottom: 0; padding-bottom: 10px; margin-bottom: 10px;">
							<div class="wcrb-settings-option-head">
								<div class="wcrb-settings-option-control">
									<x-settings.toggle
										name="wcrb_turn_booking_forms_to_jobs"
										id="wcrb_turn_booking_forms_to_jobs"
										:checked="(bool) ($turnBookingFormsToJobsUi ?? false)"
										value="1"
										uncheckedValue="0"
									/>
								</div>
								<label for="wcrb_turn_booking_forms_to_jobs" class="wcrb-settings-option-title">{{ __('Send booking forms & quote forms to jobs instead of estimates') }}</label>
							</div>
						</div>

						<div class="grid-x grid-margin-x">
							<div class="cell medium-4 small-12">
								<x-settings.field for="estimate_valid_days" :label="__('Default validity (days)')" class="wcrb-settings-field">
									<x-settings.input
										name="estimate_valid_days"
										id="estimate_valid_days"
										type="number"
										min="1"
										max="365"
										:value="old('estimate_valid_days', (string) ($estimatesValidDaysUi ?? 30))"
									/>
								</x-settings.field>
							</div>
						</div>
					</div>
				</div>

				<div class="wcrb-settings-card">
					<h3 class="wcrb-settings-card-title">{{ __('Estimate Email To Customer') }}</h3>
					<div class="wcrb-settings-card-body">
						<div class="grid-x grid-margin-x">
							<div class="cell medium-12 small-12">
								<x-settings.field for="estimate_email_subject_to_customer" :label="__('Email subject')" class="wcrb-settings-field">
									<x-settings.input
										name="estimate_email_subject_to_customer"
										id="estimate_email_subject_to_customer"
										:value="$estimateEmailSubjectCustomerValue"
										type="text"
									/>
								</x-settings.field>
							</div>
						</div>
						<div class="grid-x grid-margin-x">
							<div class="cell medium-12 small-12">
								<x-settings.field for="estimate_email_body_to_customer" :label="__('Email body')" :help="__('Available Keywords') . ' ' . '{' . '{customer_full_name}' . '}' . ' ' . '{' . '{customer_device_label}' . '}' . ' ' . '{' . '{order_invoice_details}' . '}' . ' ' . '{' . '{job_id}' . '}' . ' ' . '{' . '{case_number}' . '}' . ' ' . '{' . '{start_approve_estimate_link}' . '}' . ' ' . '{' . '{end_approve_estimate_link}' . '}' . ' ' . '{' . '{start_reject_estimate_link}' . '}' . ' ' . '{' . '{end_reject_estimate_link}' . '}'" class="wcrb-settings-field">
									<x-settings.textarea
										name="estimate_email_body_to_customer"
										id="estimate_email_body_to_customer"
										:rows="6"
										:value="$estimateEmailBodyCustomerValue"
									/>
								</x-settings.field>
							</div>
						</div>
					</div>
				</div>

				<div class="wcrb-settings-card">
					<h3 class="wcrb-settings-card-title">{{ __('Estimate approve email to admin') }}</h3>
					<div class="wcrb-settings-card-body">
						<div class="grid-x grid-margin-x">
							<div class="cell medium-12 small-12">
								<x-settings.field for="estimate_approve_email_subject_to_admin" :label="__('Email subject')" class="wcrb-settings-field">
									<x-settings.input
										name="estimate_approve_email_subject_to_admin"
										id="estimate_approve_email_subject_to_admin"
										:value="$estimateApproveEmailSubjectAdminValue"
										type="text"
									/>
								</x-settings.field>
							</div>
						</div>
						<div class="grid-x grid-margin-x">
							<div class="cell medium-12 small-12">
								<x-settings.field for="estimate_approve_email_body_to_admin" :label="__('Email body')" :help="__('Available Keywords') . ' ' . '{' . '{customer_full_name}' . '}' . ' ' . '{' . '{customer_device_label}' . '}' . ' ' . '{' . '{order_invoice_details}' . '}' . ' ' . '{' . '{job_id}' . '}' . ' ' . '{' . '{estimate_id}' . '}' . ' ' . '{' . '{case_number}' . '}'" class="wcrb-settings-field">
									<x-settings.textarea
										name="estimate_approve_email_body_to_admin"
										id="estimate_approve_email_body_to_admin"
										:rows="6"
										:value="$estimateApproveEmailBodyAdminValue"
									/>
								</x-settings.field>
							</div>
						</div>
					</div>
				</div>

				<div class="wcrb-settings-card">
					<h3 class="wcrb-settings-card-title">{{ __('Estimate reject email to admin') }}</h3>
					<div class="wcrb-settings-card-body">
						<div class="grid-x grid-margin-x">
							<div class="cell medium-12 small-12">
								<x-settings.field for="estimate_reject_email_subject_to_admin" :label="__('Email subject')" class="wcrb-settings-field">
									<x-settings.input
										name="estimate_reject_email_subject_to_admin"
										id="estimate_reject_email_subject_to_admin"
										:value="$estimateRejectEmailSubjectAdminValue"
										type="text"
									/>
								</x-settings.field>
							</div>
						</div>
						<div class="grid-x grid-margin-x">
							<div class="cell medium-12 small-12">
								<x-settings.field for="estimate_reject_email_body_to_admin" :label="__('Email body')" :help="__('Available Keywords') . ' ' . '{' . '{customer_full_name}' . '}' . ' ' . '{' . '{customer_device_label}' . '}' . ' ' . '{' . '{order_invoice_details}' . '}' . ' ' . '{' . '{estimate_id}' . '}' . ' ' . '{' . '{case_number}' . '}'" class="wcrb-settings-field">
									<x-settings.textarea
										name="estimate_reject_email_body_to_admin"
										id="estimate_reject_email_body_to_admin"
										:rows="6"
										:value="$estimateRejectEmailBodyAdminValue"
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
