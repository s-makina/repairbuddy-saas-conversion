<div class="tabs-panel team-wrap" id="wcrb_signature_workflow" role="tabpanel" aria-hidden="true" aria-labelledby="wcrb_signature_workflow-label">
	<div class="wrap">
		<h2>{{ __('Digital Signature Workflow') }}</h2>
		<p>{{ __('Configure digital signature workflow for repair orders and customer approvals.') }}</p>

		@php
			$pickupEnabled = (bool) old('pickup_enabled', (bool) ($signaturePickupEnabledUi ?? false));
			$pickupTriggerStatus = (string) old('pickup_trigger_status', (string) ($signaturePickupTriggerStatusUi ?? ''));
			$pickupEmailSubject = (string) old('pickup_email_subject', (string) ($signaturePickupEmailSubjectUi ?? ''));
			$pickupEmailTemplate = (string) old('pickup_email_template', (string) ($signaturePickupEmailTemplateUi ?? ''));
			$pickupSmsText = (string) old('pickup_sms_text', (string) ($signaturePickupSmsTextUi ?? ''));
			$pickupAfterStatus = (string) old('pickup_after_status', (string) ($signaturePickupAfterStatusUi ?? ''));

			$deliveryEnabled = (bool) old('delivery_enabled', (bool) ($signatureDeliveryEnabledUi ?? false));
			$deliveryTriggerStatus = (string) old('delivery_trigger_status', (string) ($signatureDeliveryTriggerStatusUi ?? ''));
			$deliveryEmailSubject = (string) old('delivery_email_subject', (string) ($signatureDeliveryEmailSubjectUi ?? ''));
			$deliveryEmailTemplate = (string) old('delivery_email_template', (string) ($signatureDeliveryEmailTemplateUi ?? ''));
			$deliverySmsText = (string) old('delivery_sms_text', (string) ($signatureDeliverySmsTextUi ?? ''));
			$deliveryAfterStatus = (string) old('delivery_after_status', (string) ($signatureDeliveryAfterStatusUi ?? ''));

			$statusOptions = collect(($allJobStatuses ?? $jobStatuses ?? collect()))
				->filter(fn ($st) => is_string($st->code ?? null) && trim((string) $st->code) !== '')
				->mapWithKeys(function ($st) {
					$code = trim((string) $st->code);
					$label = (string) ($st->label ?? $code);
					return [$code => $label];
				})
				->prepend(__('Select job status'), '')
				->all();
		@endphp

		<form data-abide class="needs-validation" novalidate method="post" action="{{ route('tenant.settings.signature.update', ['business' => $tenant->slug]) }}">
			@csrf
			<div class="wcrb-settings-form" style="max-width: none; width: 100%;">
				<div class="wcrb-settings-card">
					<div class="wcrb-settings-card-body">
						<h3 class="wcrb-settings-card-title">{{ __('Pickup Signature') }}</h3>
						<div class="row g-3">
							<div class="col-12">
								<div class="wcrb-settings-option" style="border-bottom: 0; padding-bottom: 6px; margin-bottom: 6px;">
									<div class="wcrb-settings-option-head">
										<div class="wcrb-settings-option-control">
											<x-settings.toggle
												name="pickup_enabled"
												id="pickup_enabled"
												:checked="$pickupEnabled"
												value="1"
												uncheckedValue="0"
											/>
										</div>
										<label for="pickup_enabled" class="wcrb-settings-option-title">{{ __('Enable pickup signature request') }}</label>
									</div>
									<div class="wcrb-settings-option-description"></div>
								</div>
							</div>

							<div class="col-md-6">
								<x-settings.field
									for="pickup_trigger_status"
									:label="__('Send Signature request when job enters to status?')"
									:help="__('Select the job status when pickup signature request should be sent.')"
									errorKey="pickup_trigger_status"
									class="wcrb-settings-field"
								>
									<x-settings.select name="pickup_trigger_status" id="pickup_trigger_status" :options="$statusOptions" :value="$pickupTriggerStatus" />
								</x-settings.field>
							</div>

							<div class="col-md-6">
								<x-settings.field for="pickup_after_status" :label="__('Change Job status after signature submission')" :help="__('Select the status to change to after pickup signature is submitted.')" errorKey="pickup_after_status" class="wcrb-settings-field">
									<x-settings.select name="pickup_after_status" id="pickup_after_status" :options="$statusOptions" :value="$pickupAfterStatus" />
								</x-settings.field>
							</div>

							<div class="col-12">
								<x-settings.field for="pickup_email_subject" :label="__('Email Subject')" errorKey="pickup_email_subject" class="wcrb-settings-field">
									<x-settings.input name="pickup_email_subject" id="pickup_email_subject" :value="$pickupEmailSubject" />
								</x-settings.field>
							</div>

							<div class="col-12">
								<p class="description">{{ __('Available keywords') }}: {{ '{' . '{pickup_signature_url}' . '}' }} {{ '{' . '{job_id}' . '}' }} {{ '{' . '{customer_device_label}' . '}' }} {{ '{' . '{case_number}' . '}' }} {{ '{' . '{customer_full_name}' . '}' }} {{ '{' . '{order_invoice_details}' . '}' }}</p>
								<x-settings.field for="pickup_email_template" :label="__('Email Template')" errorKey="pickup_email_template" class="wcrb-settings-field">
									<x-settings.textarea name="pickup_email_template" id="pickup_email_template" :rows="6" :value="$pickupEmailTemplate" />
								</x-settings.field>
							</div>

							<div class="col-12">
								<p class="description">{{ __('Available keywords') }}: {{ '{' . '{pickup_signature_url}' . '}' }} {{ '{' . '{job_id}' . '}' }} {{ '{' . '{customer_device_label}' . '}' }} {{ '{' . '{case_number}' . '}' }} {{ '{' . '{customer_full_name}' . '}' }} {{ '{' . '{order_invoice_details}' . '}' }}</p>
								<x-settings.field for="pickup_sms_text" :label="__('SMS Text')" errorKey="pickup_sms_text" class="wcrb-settings-field">
									<x-settings.textarea name="pickup_sms_text" id="pickup_sms_text" :rows="3" :value="$pickupSmsText" />
								</x-settings.field>
							</div>
						</div>
					</div>
				</div>

				<div class="wcrb-settings-card">
					<div class="wcrb-settings-card-body">
						<h3 class="wcrb-settings-card-title">{{ __('Delivery Signature') }}</h3>
						<div class="row g-3">
							<div class="col-12">
								<div class="wcrb-settings-option" style="border-bottom: 0; padding-bottom: 6px; margin-bottom: 6px;">
									<div class="wcrb-settings-option-head">
										<div class="wcrb-settings-option-control">
											<x-settings.toggle
												name="delivery_enabled"
												id="delivery_enabled"
												:checked="$deliveryEnabled"
												value="1"
												uncheckedValue="0"
											/>
										</div>
										<label for="delivery_enabled" class="wcrb-settings-option-title">{{ __('Enable delivery signature request') }}</label>
									</div>
									<div class="wcrb-settings-option-description"></div>
								</div>
							</div>

							<div class="col-md-6">
								<x-settings.field
									for="delivery_trigger_status"
									:label="__('Send Signature request when job enters to status?')"
									:help="__('Select the job status when delivery signature request should be sent.')"
									errorKey="delivery_trigger_status"
									class="wcrb-settings-field"
								>
									<x-settings.select name="delivery_trigger_status" id="delivery_trigger_status" :options="$statusOptions" :value="$deliveryTriggerStatus" />
								</x-settings.field>
							</div>

							<div class="col-md-6">
								<x-settings.field for="delivery_after_status" :label="__('Change Job status after signature submission')" :help="__('Select the status to change to after delivery signature is submitted.')" errorKey="delivery_after_status" class="wcrb-settings-field">
									<x-settings.select name="delivery_after_status" id="delivery_after_status" :options="$statusOptions" :value="$deliveryAfterStatus" />
								</x-settings.field>
							</div>

							<div class="col-12">
								<x-settings.field for="delivery_email_subject" :label="__('Email Subject')" errorKey="delivery_email_subject" class="wcrb-settings-field">
									<x-settings.input name="delivery_email_subject" id="delivery_email_subject" :value="$deliveryEmailSubject" />
								</x-settings.field>
							</div>

							<div class="col-12">
								<p class="description">{{ __('Available keywords') }}: {{ '{' . '{delivery_signature_url}' . '}' }} {{ '{' . '{job_id}' . '}' }} {{ '{' . '{customer_device_label}' . '}' }} {{ '{' . '{case_number}' . '}' }} {{ '{' . '{customer_full_name}' . '}' }} {{ '{' . '{order_invoice_details}' . '}' }}</p>
								<x-settings.field for="delivery_email_template" :label="__('Email Template')" errorKey="delivery_email_template" class="wcrb-settings-field">
									<x-settings.textarea name="delivery_email_template" id="delivery_email_template" :rows="6" :value="$deliveryEmailTemplate" />
								</x-settings.field>
							</div>

							<div class="col-12">
								<p class="description">{{ __('Available keywords') }}: {{ '{' . '{delivery_signature_url}' . '}' }} {{ '{' . '{job_id}' . '}' }} {{ '{' . '{customer_device_label}' . '}' }} {{ '{' . '{case_number}' . '}' }} {{ '{' . '{customer_full_name}' . '}' }} {{ '{' . '{order_invoice_details}' . '}' }}</p>
								<x-settings.field for="delivery_sms_text" :label="__('SMS Text')" errorKey="delivery_sms_text" class="wcrb-settings-field">
									<x-settings.textarea name="delivery_sms_text" id="delivery_sms_text" :rows="3" :value="$deliverySmsText" />
								</x-settings.field>
							</div>
						</div>
					</div>
				</div>

				<div class="wcrb-settings-actions" style="justify-content: flex-end; padding-top: 8px;">
					<button type="submit" class="button button-primary">{{ __('Update Options') }}</button>
				</div>
			</div>
		</form>
	</div>
</div>
