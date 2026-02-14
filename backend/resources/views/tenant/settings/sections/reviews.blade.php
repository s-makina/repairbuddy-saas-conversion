<div class="tabs-panel team-wrap" id="wcrb_reviews_tab" role="tabpanel" aria-hidden="true" aria-labelledby="wcrb_reviews_tab-label">
	<div class="wrap">
		<!-- <h2>{{ __('Job Reviews') }}</h2> -->

		@php
			$requestBySmsChecked = (string) old('request_by_sms', ($reviewsRequestBySmsUi ?? false) ? 'on' : 'off') === 'on';
			$requestByEmailChecked = (string) old('request_by_email', ($reviewsRequestByEmailUi ?? false) ? 'on' : 'off') === 'on';
			$selectedStatus = (string) old('send_request_job_status', (string) ($reviewsSendOnStatusUi ?? ''));
			$interval = (string) old('auto_request_interval', (string) ($reviewsIntervalUi ?? 'disabled'));
		@endphp

		<form data-abide class="needs-validation" novalidate method="post" action="{{ route('tenant.settings.reviews.update', ['business' => $tenant->slug]) }}">
			@csrf
			<div class="wcrb-settings-form">
				<div class="wcrb-settings-card">
					<h3 class="wcrb-settings-card-title">{{ __('Job Reviews') }}</h3>
					<div class="wcrb-settings-card-body">
						<div class="row g-3">
							<div class="col-12">
								<div class="wcrb-settings-option" style="border-bottom: 0; padding-bottom: 6px; margin-bottom: 6px;">
									<div class="wcrb-settings-option-head">
										<div class="wcrb-settings-option-control">
											<x-settings.toggle
												name="request_by_sms"
												id="request_by_sms"
												:checked="$requestBySmsChecked"
												value="on"
												uncheckedValue="off"
											/>
										</div>
										<label for="request_by_sms" class="wcrb-settings-option-title">{{ __('Request Feedback by SMS') }}</label>
									</div>
									<div class="wcrb-settings-option-description">{{ __('Enable SMS notification for feedback request') }}</div>
								</div>
							</div>

							<div class="col-12">
								<div class="wcrb-settings-option" style="border-bottom: 0; padding-bottom: 6px; margin-bottom: 6px;">
									<div class="wcrb-settings-option-head">
										<div class="wcrb-settings-option-control">
											<x-settings.toggle
												name="request_by_email"
												id="request_by_email"
												:checked="$requestByEmailChecked"
												value="on"
												uncheckedValue="off"
											/>
										</div>
										<label for="request_by_email" class="wcrb-settings-option-title">{{ __('Request Feedback by Email') }}</label>
									</div>
									<div class="wcrb-settings-option-description">{{ __('Enable Email notification for feedback request') }}</div>
								</div>
							</div>

							<div class="col-md-6">
								@php
									$statusOptions = collect(($jobStatusesForReviews ?? collect()))
										->filter(fn ($st) => is_string($st->code) && trim((string) $st->code) !== '')
										->mapWithKeys(function ($st) {
											$code = trim((string) $st->code);
											$label = (string) ($st->label ?? $code);
											return [$code => $label];
										})
										->prepend(__('Select job status to send review request'), '')
										->all();
								@endphp
								<x-settings.field
									for="send_request_job_status"
									:label="__('Send review request if job status is')"
									:help="__('When job has the status you selected above only then you can auto or manually request feedback.')"
									errorKey="send_request_job_status"
									class="wcrb-settings-field"
								>
									<x-settings.select
										name="send_request_job_status"
										id="send_request_job_status"
										:options="$statusOptions"
										:value="$selectedStatus"
									/>
								</x-settings.field>
							</div>

							<div class="col-md-6">
								<x-settings.field
									for="auto_request_interval"
									:label="__('Auto feedback request')"
									:help="__('A request for customer feedback will be sent automatically.')"
									errorKey="auto_request_interval"
									class="wcrb-settings-field"
								>
									<x-settings.select
										name="auto_request_interval"
										id="auto_request_interval"
										:options="[
											'disabled' => __('Disabled'),
											'one-notification' => __('1 Notification - After 24 Hours'),
											'two-notifications' => __('2 Notifications - After 24 Hrs and 48 Hrs'),
										]"
										:value="$interval"
									/>
								</x-settings.field>
							</div>

							<div class="col-12">
								<p class="description">{{ __('Available keywords') }}: {{ '{' . '{st_feedback_anch}' . '}' }} {{ '{' . '{end_feedback_anch}' . '}' }} {{ '{' . '{feedback_link}' . '}' }} {{ '{' . '{job_id}' . '}' }} {{ '{' . '{customer_device_label}' . '}' }} {{ '{' . '{case_number}' . '}' }} {{ '{' . '{customer_full_name}' . '}' }}</p>
								<x-settings.field for="email_message" :label="__('Email message to request feedback')" errorKey="email_message" class="wcrb-settings-field">
									<x-settings.textarea
										name="email_message"
										id="email_message"
										:rows="6"
										:value="old('email_message', (string) ($reviewsEmailMessageUi ?? ''))"
									/>
								</x-settings.field>
							</div>

							<div class="col-12">
								<x-settings.field for="email_subject" :label="__('Email subject to request feedback')" errorKey="email_subject" class="wcrb-settings-field">
									<x-settings.input
										name="email_subject"
										id="email_subject"
										:value="old('email_subject', (string) ($reviewsEmailSubjectUi ?? ''))"
									/>
								</x-settings.field>
							</div>

							<div class="col-12">
								<p class="description">{{ __('Available keywords') }}: {{ '{' . '{feedback_link}' . '}' }} {{ '{' . '{job_id}' . '}' }} {{ '{' . '{customer_device_label}' . '}' }} {{ '{' . '{case_number}' . '}' }} {{ '{' . '{customer_full_name}' . '}' }}</p>
								<x-settings.field for="sms_message" :label="__('SMS message to request feedback')" errorKey="sms_message" class="wcrb-settings-field">
									<x-settings.textarea
										name="sms_message"
										id="sms_message"
										:rows="4"
										:value="old('sms_message', (string) ($reviewsSmsMessageUi ?? ''))"
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
