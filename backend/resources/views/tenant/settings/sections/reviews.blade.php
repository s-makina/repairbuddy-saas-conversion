<div class="tabs-panel team-wrap" id="wcrb_reviews_tab" role="tabpanel" aria-hidden="true" aria-labelledby="wcrb_reviews_tab-label">
	<div class="wrap">
		<h2>{{ __('Job Reviews') }}</h2>

		<div class="wc-rb-grey-bg-box">
			<form data-abide class="needs-validation" novalidate method="post" action="{{ route('tenant.settings.reviews.update', ['business' => $tenant->slug]) }}">
				@csrf
				<table class="form-table border">
					<tbody>
						<tr>
							<th scope="row"><label for="request_by_sms">{{ __('Request Feedback by SMS') }}</label></th>
							<td>
								<input type="checkbox" name="request_by_sms" id="request_by_sms" {{ ($reviewsRequestBySmsUi ?? false) ? 'checked' : '' }} />
								<label for="request_by_sms">{{ __('Enable SMS notification for feedback request') }}</label>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="request_by_email">{{ __('Request Feedback by Email') }}</label></th>
							<td>
								<input type="checkbox" name="request_by_email" id="request_by_email" {{ ($reviewsRequestByEmailUi ?? false) ? 'checked' : '' }} />
								<label for="request_by_email">{{ __('Enable Email notification for feedback request') }}</label>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="get_feedback_page_url">{{ __('Get feedback on job page URL') }}</label></th>
							<td>
								<input type="text" id="get_feedback_page_url" name="get_feedback_page_url" class="regular-text" value="{{ old('get_feedback_page_url', (string) ($reviewsFeedbackPageUrlUi ?? '')) }}" />
								<label>{{ __('A page that contains the review form. This will be used to send link to customers so they can leave feedback on jobs.') }}</label>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="send_request_job_status">{{ __('Send review request if job status is') }}</label></th>
							<td>
								@php
									$selectedStatus = (string) old('send_request_job_status', (string) ($reviewsSendOnStatusUi ?? ''));
								@endphp
								<select name="send_request_job_status" class="form-control" id="send_request_job_status">
									<option value="">{{ __('Select job status to send review request') }}</option>
									@foreach (($jobStatusesForReviews ?? collect()) as $st)
										@php
											$code = is_string($st->code) ? trim((string) $st->code) : '';
											if ($code === '') {
												continue;
											}
										@endphp
										<option value="{{ $code }}" {{ ($selectedStatus !== '' && $selectedStatus === $code) ? 'selected' : '' }}>{{ (string) $st->label }}</option>
									@endforeach
								</select>
								<label>{{ __('When job has the status you selected above only then you can auto or manually request feedback.') }}</label>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="auto_request_interval">{{ __('Auto feedback request') }}</label></th>
							<td>
								@php
									$interval = (string) old('auto_request_interval', (string) ($reviewsIntervalUi ?? 'disabled'));
								@endphp
								<select name="auto_request_interval" class="form-control" id="auto_request_interval">
									<option value="disabled" {{ $interval === 'disabled' ? 'selected' : '' }}>{{ __('Disabled') }}</option>
									<option value="one-notification" {{ $interval === 'one-notification' ? 'selected' : '' }}>{{ __('1 Notification - After 24 Hours') }}</option>
									<option value="two-notifications" {{ $interval === 'two-notifications' ? 'selected' : '' }}>{{ __('2 Notifications - After 24 Hrs and 48 Hrs') }}</option>
								</select>
								<label>{{ __('A request for customer feedback will be sent automatically.') }}</label>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="email_message">{{ __('Email message to request feedback') }}</label></th>
							<td>
								<p class="description">{{ __('Available keywords') }}: {{ '{' . '{st_feedback_anch}' . '}' }} {{ '{' . '{end_feedback_anch}' . '}' }} {{ '{' . '{feedback_link}' . '}' }} {{ '{' . '{job_id}' . '}' }} {{ '{' . '{customer_device_label}' . '}' }} {{ '{' . '{case_number}' . '}' }} {{ '{' . '{customer_full_name}' . '}' }}</p>
								<textarea rows="6" name="email_message" id="email_message" class="large-text">{{ old('email_message', (string) ($reviewsEmailMessageUi ?? '')) }}</textarea>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="email_subject">{{ __('Email subject to request feedback') }}</label></th>
							<td>
								<input type="text" class="regular-text" name="email_subject" value="{{ old('email_subject', (string) ($reviewsEmailSubjectUi ?? '')) }}" id="email_subject" />
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="sms_message">{{ __('SMS message to request feedback') }}</label></th>
							<td>
								<p class="description">{{ __('Available keywords') }}: {{ '{' . '{feedback_link}' . '}' }} {{ '{' . '{job_id}' . '}' }} {{ '{' . '{customer_device_label}' . '}' }} {{ '{' . '{case_number}' . '}' }} {{ '{' . '{customer_full_name}' . '}' }}</p>
								<textarea rows="4" name="sms_message" id="sms_message" class="large-text">{{ old('sms_message', (string) ($reviewsSmsMessageUi ?? '')) }}</textarea>
							</td>
						</tr>
					</tbody>
				</table>
				<button type="submit" class="button button-primary">{{ __('Update Options') }}</button>
			</form>
		</div>
	</div>
</div>
