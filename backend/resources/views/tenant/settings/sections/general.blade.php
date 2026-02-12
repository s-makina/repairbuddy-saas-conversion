<div class="tabs-panel team-wrap{{ $class_general_settings }}" id="panel1" role="tabpanel" aria-hidden="true" aria-labelledby="panel1-label">
	<div class="wrap">
		<h2>{{ __('Settings') }}</h2>

		<form data-abide class="needs-validation" novalidate method="post" action="{{ route('tenant.settings.general.update', ['business' => $tenant->slug]) }}" data-success-class=".main_setting_success_class">
			@csrf
			<table cellpadding="5" cellspacing="5" class="form-table">
				<tr>
					<td>
						<x-settings.field for="wc_rb_business_name" :label="__('Business Name')" :help="__('Name will be used in reports/invoices')">
							<x-settings.input
								name="wc_rb_business_name"
								id="wc_rb_business_name"
								class="regular-text"
								:value="old('wc_rb_business_name', $wc_rb_business_name)"
								type="text"
							/>
						</x-settings.field>
					</td>
					<td>
						<x-settings.field for="wc_rb_business_phone" :label="__('Business Phone')" :help="__('Phone will be used in reports/invoices')">
							<x-settings.input
								name="wc_rb_business_phone"
								id="wc_rb_business_phone"
								class="regular-text"
								:value="old('wc_rb_business_phone', $wc_rb_business_phone)"
								type="text"
							/>
						</x-settings.field>
					</td>
				</tr>

				<tr>
					<td>
						<x-settings.field for="wc_rb_business_address" :label="__('Business Address')" :help="__('Address will be used in reports/invoices')">
							<x-settings.input
								name="wc_rb_business_address"
								id="wc_rb_business_address"
								class="regular-text"
								:value="old('wc_rb_business_address', $wc_rb_business_address)"
								type="text"
							/>
						</x-settings.field>
					</td>
					<td></td>
				</tr>

				<tr>
					<td>
						<x-settings.field for="computer_repair_logo" :label="__('Logo to use')">
							<x-settings.input
								name="computer_repair_logo"
								id="computer_repair_logo"
								class="regular-text"
								:value="old('computer_repair_logo', $computer_repair_logo)"
								type="text"
								:placeholder="__('Enter url of logo')"
							/>
						</x-settings.field>
					</td>
					<td>
						<x-settings.field for="computer_repair_email" :label="__('Email')" :help="__('Where quote forms and other admin emails would be sent.')">
							<x-settings.input
								name="computer_repair_email"
								id="computer_repair_email"
								class="regular-text"
								:value="old('computer_repair_email', $computer_repair_email)"
								type="text"
								:placeholder="__('Where to send emails like Quote and other stuff.')"
							/>
						</x-settings.field>
					</td>
				</tr>

				<tr>
					<td>
						<x-settings.field for="case_number_prefix" :label="sprintf(__('%s prefix'), $casenumber_label_first ?? 'Case')">
							<x-settings.input
								name="case_number_prefix"
								id="case_number_prefix"
								class="regular-text"
								:value="old('case_number_prefix', $case_number_prefix)"
								type="text"
								:placeholder="sprintf(__('%s prefix e.g CHM_ or WC_'), $casenumber_label_first ?? 'Case')"
							/>
						</x-settings.field>
					</td>
					<td>
						<x-settings.field for="case_number_length" :label="sprintf(__('%s Length for string in %s before timestamp'), $casenumber_label_first ?? 'Case', $casenumber_label_none ?? 'Case')">
							<x-settings.input
								name="case_number_length"
								id="case_number_length"
								class="regular-text"
								:value="old('case_number_length', $case_number_length)"
								type="number"
								min="1"
							/>
						</x-settings.field>
					</td>
				</tr>
			</table>

			<table cellpadding="5" cellspacing="5" class="form-table">
			<tr>
				<th scope="row"><label for="wc_job_status_cr_notice">{{ __( 'Email Customer' ) }}</label></th>
				<td>
					<x-settings.toggle name="wc_job_status_cr_notice" id="wc_job_status_cr_notice" :checked="(bool) $send_notice" />
					<p class="description">{{ __( 'Email customer everytime job status is changed.' ) }}</p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="wcrb_attach_pdf_in_customer_emails">{{ __( 'Attach PDF' ) }}</label></th>
				<td>
					<x-settings.toggle name="wcrb_attach_pdf_in_customer_emails" id="wcrb_attach_pdf_in_customer_emails" :checked="(bool) $attach_pdf" />
					<p class="description">{{ __( 'Attach PDF with emails to customer about jobs and estimates.' ) }}</p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="wcrb_next_service_date">{{ __( 'Next service date' ) }}</label></th>
				<td>
					<x-settings.toggle name="wcrb_next_service_date" id="wcrb_next_service_date" :checked="(bool) $disableNextServiceDate" />
					<p class="description">{{ __( 'Turn on if you want to see jobs in calendar for next service date.' ) }}</p>
				</td>
			</tr>
			<!-- Work here. -->

			<tr>
				<th scope="row">
					<label for="wc_rb_gdpr_acceptance">{{ __( 'GDPR Acceptance on Book and Quote forms' ) }}</label>
				</th>
				<td>
					<table class="form-table no-padding-table">
						<tr>
							<td>
							<input 
								name="wc_rb_gdpr_acceptance" 
								id="wc_rb_gdpr_acceptance" 
								class="regular-text" 
								value="{{ old('wc_rb_gdpr_acceptance', $wc_rb_gdpr_acceptance) }}" 
								type="text" 
								placeholder="{{ __( 'GDPR Acceptance text label for booking and quote' ) }}" />
							</td>
							<td>
							<input 
								name="wc_rb_gdpr_acceptance_link_label" 
								id="wc_rb_gdpr_acceptance_link_label" 
								class="regular-text" 
								value="{{ old('wc_rb_gdpr_acceptance_link_label', $wc_rb_gdpr_acceptance_link_label) }}" 
								type="text" 
								placeholder="{{ __( 'Privacy policy' ) }}" />
							</td>
							<td>
							<input 
								name="wc_rb_gdpr_acceptance_link" 
								id="wc_rb_gdpr_acceptance_link" 
								class="regular-text" 
								value="{{ old('wc_rb_gdpr_acceptance_link', $wc_rb_gdpr_acceptance_link) }}" 
								type="text" 
								placeholder="{{ __( 'Privacy policy or terms link' ) }}" />
							</td>
						</tr>
					</table>	
				</td>
			</tr>

			<tr>
				<th scope="row">
					<label for="wc_primary_country">{{ __( 'Default Country' ) }}</label>
				</th>
				<td>
					<x-settings.select
						name="wc_primary_country"
						id="wc_primary_country"
						class="form-control"
						:options="($countries ?? [])"
						:value="(string) old('wc_primary_country', $wc_primary_country)"
					/>
				</td>
			</tr>

			<tr>
				<th scope="row">
					<label for="wc_enable_woo_products">
						{{ __( 'Disable Parts and Use WooCommerce Products' ) }}
					</label>
				</th>
				<td>
					@if (! ($woocommerce_activated ?? true))
						{{ __( 'Please install and activate WooCommerce to use it. Otherwise you can rely on parts by our plugin.' ) }}
					@else
						<x-settings.toggle name="wc_enable_woo_products" id="wc_enable_woo_products" :checked="(bool) $useWooProducts" />
					@endif
				</td>
			</tr>

			<tr>
				<th scope="row">
					<label for="wcrb_disable_statuscheck_serial">
						{{ __( 'Disable status check by device serial number' ) }}
					</label>
				</th>
				<td>
					<x-settings.toggle name="wcrb_disable_statuscheck_serial" id="wcrb_disable_statuscheck_serial" :checked="(bool) $disableStatusCheckSerial" />
				</td>
			</tr>

			<x-settings.submit-row>
				<input type="hidden" name="form_type" value="wcrb_main_setting_form" />
				<input type="hidden" name="wc_rep_settings" value="1" />
				&nbsp;
			</x-settings.submit-row>
		</table>
		<div class="main_setting_success_class"></div>
		</form>
	</div>
</div><!-- tab 1 ends -->
