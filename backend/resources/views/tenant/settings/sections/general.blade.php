<div class="tabs-panel team-wrap{{ $class_general_settings }}" id="panel1" role="tabpanel" aria-hidden="true" aria-labelledby="panel1-label">
	<div class="wrap">
		<h2>{{ __('Settings') }}</h2>

		<form data-abide class="needs-validation" novalidate method="post" action="{{ route('tenant.settings.general.update', ['business' => $tenant->slug]) }}" data-success-class=".main_setting_success_class">
			@csrf
			<div class="wcrb-settings-form">
				<div class="grid-x grid-margin-x">
					<div class="cell medium-6 small-12">
						<x-settings.field for="wc_rb_business_name" :label="__('Business Name')" :help="__('Name will be used in reports/invoices')" class="wcrb-settings-field">
							<x-settings.input
								name="wc_rb_business_name"
								id="wc_rb_business_name"
								:value="old('wc_rb_business_name', $wc_rb_business_name)"
								type="text"
							/>
						</x-settings.field>
					</div>
					<div class="cell medium-6 small-12">
						<x-settings.field for="wc_rb_business_phone" :label="__('Business Phone')" :help="__('Phone will be used in reports/invoices')" class="wcrb-settings-field">
							<x-settings.input
								name="wc_rb_business_phone"
								id="wc_rb_business_phone"
								:value="old('wc_rb_business_phone', $wc_rb_business_phone)"
								type="text"
							/>
						</x-settings.field>
					</div>
				</div>

				<div class="grid-x grid-margin-x">
					<div class="cell medium-12 small-12">
						<x-settings.field for="wc_rb_business_address" :label="__('Business Address')" :help="__('Address will be used in reports/invoices')" class="wcrb-settings-field">
							<x-settings.input
								name="wc_rb_business_address"
								id="wc_rb_business_address"
								:value="old('wc_rb_business_address', $wc_rb_business_address)"
								type="text"
							/>
						</x-settings.field>
					</div>
				</div>

				<div class="grid-x grid-margin-x">
					<div class="cell medium-6 small-12">
						<x-settings.field for="computer_repair_logo" :label="__('Logo to use')" :help="__('Logo used on invoices, estimates, and customer-facing pages.')" class="wcrb-settings-field">
							<x-settings.input
								name="computer_repair_logo"
								id="computer_repair_logo"
								:value="old('computer_repair_logo', $computer_repair_logo)"
								type="text"
								:placeholder="__('Enter url of logo')"
							/>
						</x-settings.field>
					</div>
					<div class="cell medium-6 small-12">
						<x-settings.field for="computer_repair_email" :label="__('Email')" :help="__('Where quote forms and other admin emails would be sent.')" class="wcrb-settings-field">
							<x-settings.input
								name="computer_repair_email"
								id="computer_repair_email"
								:value="old('computer_repair_email', $computer_repair_email)"
								type="text"
								:placeholder="__('Where to send emails like Quote and other stuff.')"
							/>
						</x-settings.field>
					</div>
				</div>

				<div class="grid-x grid-margin-x">
					<div class="cell medium-6 small-12">
						<x-settings.field for="case_number_prefix" :label="sprintf(__('%s prefix'), $casenumber_label_first ?? 'Case')" :help="sprintf(__('Shown at the start of every %s number.'), $casenumber_label_none ?? 'case')" class="wcrb-settings-field">
							<x-settings.input
								name="case_number_prefix"
								id="case_number_prefix"
								:value="old('case_number_prefix', $case_number_prefix)"
								type="text"
								:placeholder="sprintf(__('%s prefix e.g CHM_ or WC_'), $casenumber_label_first ?? 'Case')"
							/>
						</x-settings.field>
					</div>
					<div class="cell medium-6 small-12">
						<x-settings.field for="case_number_length" :label="sprintf(__('%s Length for string in %s before timestamp'), $casenumber_label_first ?? 'Case', $casenumber_label_none ?? 'Case')" :help="__('Number of characters before the timestamp is appended.')" class="wcrb-settings-field">
							<x-settings.input
								name="case_number_length"
								id="case_number_length"
								:value="old('case_number_length', $case_number_length)"
								type="number"
								min="1"
							/>
						</x-settings.field>
					</div>
				</div>

				<div class="wcrb-settings-card">
					<h3 class="wcrb-settings-card-title">{{ __('Notifications') }}</h3>
					<div class="wcrb-settings-card-body">
						<div class="wcrb-settings-option">
							<div class="wcrb-settings-option-head">
								<div class="wcrb-settings-option-control">
									<x-settings.toggle name="wc_job_status_cr_notice" id="wc_job_status_cr_notice" :checked="(bool) $send_notice" />
								</div>
								<label for="wc_job_status_cr_notice" class="wcrb-settings-option-title">{{ __( 'Email Customer' ) }}</label>
							</div>
							<p class="description">{{ __( 'Email customer everytime job status is changed.' ) }}</p>
						</div>
						<div class="wcrb-settings-option">
							<div class="wcrb-settings-option-head">
								<div class="wcrb-settings-option-control">
									<x-settings.toggle name="wcrb_attach_pdf_in_customer_emails" id="wcrb_attach_pdf_in_customer_emails" :checked="(bool) $attach_pdf" />
								</div>
								<label for="wcrb_attach_pdf_in_customer_emails" class="wcrb-settings-option-title">{{ __( 'Attach PDF' ) }}</label>
							</div>
							<p class="description">{{ __( 'Attach PDF with emails to customer about jobs and estimates.' ) }}</p>
						</div>
						<div class="wcrb-settings-option">
							<div class="wcrb-settings-option-head">
								<div class="wcrb-settings-option-control">
									<x-settings.toggle name="wcrb_next_service_date" id="wcrb_next_service_date" :checked="(bool) $disableNextServiceDate" />
								</div>
								<label for="wcrb_next_service_date" class="wcrb-settings-option-title">{{ __( 'Next service date' ) }}</label>
							</div>
							<p class="description">{{ __( 'Turn on if you want to see jobs in calendar for next service date.' ) }}</p>
						</div>
					</div>
				</div>

				<div class="wcrb-settings-card">
					<h3 class="wcrb-settings-card-title">{{ __('Compliance') }}</h3>
					<div class="wcrb-settings-card-body">
						<div class="grid-x grid-margin-x">
							<div class="cell medium-4 small-12">
								<x-settings.field for="wc_rb_gdpr_acceptance" :label="__( 'GDPR Acceptance on Book and Quote forms' )" :help="__('Label shown next to the GDPR checkbox on booking and quote forms.')" class="wcrb-settings-field">
									<x-settings.input
										name="wc_rb_gdpr_acceptance"
										id="wc_rb_gdpr_acceptance"
										:value="old('wc_rb_gdpr_acceptance', $wc_rb_gdpr_acceptance)"
										type="text"
										:placeholder="__( 'GDPR Acceptance text label for booking and quote' )"
									/>
								</x-settings.field>
							</div>
							<div class="cell medium-4 small-12">
								<x-settings.field for="wc_rb_gdpr_acceptance_link_label" :label="__('Link label')" :help="__('Clickable text for the privacy policy/terms link.')" class="wcrb-settings-field">
									<x-settings.input
										name="wc_rb_gdpr_acceptance_link_label"
										id="wc_rb_gdpr_acceptance_link_label"
										:value="old('wc_rb_gdpr_acceptance_link_label', $wc_rb_gdpr_acceptance_link_label)"
										type="text"
										:placeholder="__( 'Privacy policy' )"
									/>
								</x-settings.field>
							</div>
							<div class="cell medium-4 small-12">
								<x-settings.field for="wc_rb_gdpr_acceptance_link" :label="__('Link URL')" :help="__('Full URL to your privacy policy or terms page.')" class="wcrb-settings-field">
									<x-settings.input
										name="wc_rb_gdpr_acceptance_link"
										id="wc_rb_gdpr_acceptance_link"
										:value="old('wc_rb_gdpr_acceptance_link', $wc_rb_gdpr_acceptance_link)"
										type="text"
										:placeholder="__( 'Privacy policy or terms link' )"
									/>
								</x-settings.field>
							</div>
						</div>
					</div>
				</div>

				<div class="wcrb-settings-card">
					<h3 class="wcrb-settings-card-title">{{ __('Defaults') }}</h3>
					<div class="wcrb-settings-card-body">
						<div class="grid-x grid-margin-x">
							<div class="cell medium-6 small-12">
								<x-settings.field for="wc_primary_country" :label="__( 'Default Country' )" :help="__('Default country used for new customers and documents.')" class="wcrb-settings-field">
									<x-settings.select
										name="wc_primary_country"
										id="wc_primary_country"
										:options="($countries ?? [])"
										:value="(string) old('wc_primary_country', $wc_primary_country)"
									/>
								</x-settings.field>
							</div>
						</div>
					</div>
				</div>

				<div class="wcrb-settings-card">
					<h3 class="wcrb-settings-card-title">{{ __('Integrations') }}</h3>
					<div class="wcrb-settings-card-body">
						<div class="wcrb-settings-option">
							<div class="wcrb-settings-option-head">
								<div class="wcrb-settings-option-control">
									@if (! ($woocommerce_activated ?? true))
										{{ __( 'Please install and activate WooCommerce to use it. Otherwise you can rely on parts by our plugin.' ) }}
									@else
										<x-settings.toggle name="wc_enable_woo_products" id="wc_enable_woo_products" :checked="(bool) $useWooProducts" />
									@endif
								</div>
								<label for="wc_enable_woo_products" class="wcrb-settings-option-title">{{ __( 'Disable Parts and Use WooCommerce Products' ) }}</label>
							</div>
						</div>

						<div class="wcrb-settings-option">
							<div class="wcrb-settings-option-head">
								<div class="wcrb-settings-option-control">
									<x-settings.toggle name="wcrb_disable_statuscheck_serial" id="wcrb_disable_statuscheck_serial" :checked="(bool) $disableStatusCheckSerial" />
								</div>
								<label for="wcrb_disable_statuscheck_serial" class="wcrb-settings-option-title">{{ __( 'Disable status check by device serial number' ) }}</label>
							</div>
						</div>
					</div>
				</div>

				<div class="grid-x grid-margin-x">
					<div class="cell small-12">
						<div class="wcrb-settings-actions">
							<button type="submit" class="button button-primary">{{ __('Save Changes') }}</button>
							<input type="hidden" name="form_type" value="wcrb_main_setting_form" />
							<input type="hidden" name="wc_rep_settings" value="1" />
						</div>
					</div>
				</div>
			</div>
			<div class="main_setting_success_class"></div>
		</form>
	</div>
</div><!-- tab 1 ends -->
