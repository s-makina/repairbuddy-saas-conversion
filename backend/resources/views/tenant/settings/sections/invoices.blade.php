<div class="tabs-panel team-wrap{{ $class_invoices_settings }}" id="reportsAInvoices" role="tabpanel" aria-hidden="true" aria-labelledby="panel1-label">
	<div class="wrap">
		<h2>{{ __('Reports & Invoices Settings') }}</h2>

		<form data-abide class="needs-validation" novalidate method="post" action="{{ route('tenant.settings.invoices.update', ['business' => $tenant->slug]) }}" data-success-class=".report_setting_success_class">
			@csrf
			<div class="wcrb-settings-form">
			<div class="wcrb-settings-card">
				<h3 class="wcrb-settings-card-title">{{ __( 'Print Invoice Settings' ) }}</h3>
				<div class="wcrb-settings-card-body">
					<div class="wcrb-settings-option">
						<div class="wcrb-settings-option-head">
							<div class="wcrb-settings-option-control">
								<x-settings.toggle name="wcrb_add_invoice_qr_code" id="wcrb_add_invoice_qr_code" :checked="(bool) $wcrb_add_invoice_qr_code" />
							</div>
							<label for="wcrb_add_invoice_qr_code" class="wcrb-settings-option-title">{{ __( 'Add QR Code to invoice' ) }}</label>
						</div>
						<p class="description">{{ __( 'Add a QR code below invoice for status or history check page later.' ) }}</p>
					</div>

					<div class="grid-x grid-margin-x">
						<div class="cell medium-6 small-12">
							<x-settings.field for="wc_rb_io_thanks_msg" :label="__( 'Footer message on Print Invoice' )" class="wcrb-settings-field">
								<x-settings.input
									name="wc_rb_io_thanks_msg"
									id="wc_rb_io_thanks_msg"
									type="text"
									:value="old('wc_rb_io_thanks_msg', $wc_rb_io_thanks_msg)"
									:placeholder="__( 'Thanks for your business!' )"
								/>
							</x-settings.field>
						</div>
						<div class="cell medium-6 small-12">
							<x-settings.field for="wb_rb_invoice_type" :label="__( 'Invoice Print By' )" class="wcrb-settings-field">
								<x-settings.select
									name="wb_rb_invoice_type"
									id="wb_rb_invoice_type"
									:options="['default' => __('Default (By Items)'), 'by_device' => __('By Devices'), 'by_items' => __('By Items')]"
									:value="(string) old('wb_rb_invoice_type', $wb_rb_invoice_type)"
								/>
							</x-settings.field>
						</div>
					</div>

					<div class="wcrb-settings-card">
						<h3 class="wcrb-settings-card-title">{{ __( 'Display dates on invoices' ) }}</h3>
						<div class="wcrb-settings-card-body">
							<div class="wcrb-settings-option">
								<div class="wcrb-settings-option-head">
									<div class="wcrb-settings-option-control">
										<x-settings.toggle name="pickupdate" id="pickupdate" :checked="(bool) $pickupdate_checked" value="show" uncheckedValue="" />
									</div>
									<label for="pickupdate" class="wcrb-settings-option-title">{{ sprintf( __( 'Show %s as created' ), $pickup_date_label_none ?? 'pickup_date' ) }}</label>
								</div>
							</div>
							<div class="wcrb-settings-option">
								<div class="wcrb-settings-option-head">
									<div class="wcrb-settings-option-control">
										<x-settings.toggle name="deliverydate" id="deliverydate" :checked="(bool) $deliverydate_checked" value="show" uncheckedValue="" />
									</div>
									<label for="deliverydate" class="wcrb-settings-option-title">{{ sprintf( __( 'Show %s' ), $delivery_date_label_none ?? 'delivery_date' ) }}</label>
								</div>
							</div>
							<div class="wcrb-settings-option">
								<div class="wcrb-settings-option-head">
									<div class="wcrb-settings-option-control">
										<x-settings.toggle name="nextservicedate" id="nextservicedate" :checked="(bool) $nextservicedate_checked" value="show" uncheckedValue="" />
									</div>
									<label for="nextservicedate" class="wcrb-settings-option-title">{{ sprintf( __( 'Show %s' ), $nextservice_date_label_none ?? 'nextservice_date' ) }}</label>
								</div>
							</div>
						</div>
					</div>

					<div class="wcrb-settings-card">
						<h3 class="wcrb-settings-card-title">{{ __( 'Terms and Conditions or Disclaimer of service prints below invoice in a new page' ) }}</h3>
						<div class="wcrb-settings-card-body">
							{!! $wcrb_invoice_disclaimer_html !!}
						</div>
					</div>
				</div>
			</div>

			<div class="wcrb-settings-card">
				<h3 class="wcrb-settings-card-title">{{ __( 'Repair Order Settings' ) }}</h3>
				<div class="wcrb-settings-card-body">
					<div class="grid-x grid-margin-x">
						<div class="cell medium-6 small-12">
							<x-settings.field for="repair_order_type" :label="__( 'Repair Order Type' )" class="wcrb-settings-field">
								<x-settings.select
									name="repair_order_type"
									id="repair_order_type"
									:options="['pos_type' => __('With Terms & Conditions QR code to sign by customer'), 'invoice_type' => __('Invoice Type without amounts')]"
									:value="(string) old('repair_order_type', $repair_order_type)"
								/>
							</x-settings.field>
						</div>
						<div class="cell medium-6 small-12">
							<x-settings.field for="repair_order_print_size" :label="__( 'Repair Order Print Size' )" class="wcrb-settings-field">
								<x-settings.select
									name="wc_repair_order_print_size"
									id="repair_order_print_size"
									:options="['default' => __('Default (POS Size)'), 'a4' => __('A4'), 'a5' => __('A5')]"
									:value="(string) old('wc_repair_order_print_size', $wc_repair_order_print_size)"
								/>
							</x-settings.field>
						</div>
					</div>

					<div class="grid-x grid-margin-x">
						<div class="cell medium-6 small-12">
							<x-settings.field for="business_terms" :label="__( 'Terms & Conditions for Repair Order' )" class="wcrb-settings-field">
								<x-settings.input
									name="business_terms"
									id="business_terms"
									type="text"
									:value="old('business_terms', $business_terms)"
									:placeholder="__( 'On Repair Order QR Code would be generated with this link.' )"
								/>
							</x-settings.field>
						</div>
						<div class="cell medium-6 small-12">
							<x-settings.field for="wc_rb_ro_thanks_msg" :label="__( 'Footer message on Repair Order' )" class="wcrb-settings-field">
								<x-settings.input
									name="wc_rb_ro_thanks_msg"
									id="wc_rb_ro_thanks_msg"
									type="text"
									:value="old('wc_rb_ro_thanks_msg', $wc_rb_ro_thanks_msg)"
									:placeholder="__( 'Thanks for your business!' )"
								/>
							</x-settings.field>
						</div>
					</div>

					<div class="wcrb-settings-option">
						<div class="wcrb-settings-option-head">
							<div class="wcrb-settings-option-control">
								<x-settings.toggle name="wc_rb_cr_display_add_on_ro" id="wc_rb_cr_display_add_on_ro" :checked="(bool) $wc_rb_cr_display_add_on_ro" />
							</div>
							<label for="wc_rb_cr_display_add_on_ro" class="wcrb-settings-option-title">{{ __( 'Display Business Address Details' ) }}</label>
						</div>
						<p class="description">{{ __( 'Show business address, email and phone details on repair order.' ) }}</p>
					</div>

					<div class="wcrb-settings-option">
						<div class="wcrb-settings-option-head">
							<div class="wcrb-settings-option-control">
								<x-settings.toggle name="wc_rb_cr_display_add_on_ro_cu" id="wc_rb_cr_display_add_on_ro_cu" :checked="(bool) $wc_rb_cr_display_add_on_ro_cu" />
							</div>
							<label for="wc_rb_cr_display_add_on_ro_cu" class="wcrb-settings-option-title">{{ __( 'Display Customer Email & Address Details' ) }}</label>
						</div>
						<p class="description">{{ __( 'Show customer address, email and phone details on repair order.' ) }}</p>
					</div>
				</div>
			</div>

			<div class="wcrb-settings-actions">
				<button type="submit" class="button button-primary">{{ __('Save Changes') }}</button>
				<div class="report_setting_success_class"></div>
				<input type="hidden" name="form_type" value="wcrb_report_setting_form" />
				<input type="hidden" name="wc_rep_labels_submit" value="1" />
			</div>
			</div>
		</form>
	</div>
</div><!-- tab reportsAInvoices -->
