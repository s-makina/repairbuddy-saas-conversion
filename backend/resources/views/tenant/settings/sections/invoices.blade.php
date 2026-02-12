<div class="tabs-panel team-wrap{{ $class_invoices_settings }}" id="reportsAInvoices" role="tabpanel" aria-hidden="true" aria-labelledby="panel1-label">
	<div class="wrap">
		<h2>{{ __('Reports & Invoices Settings') }}</h2>

		<form data-abide class="needs-validation" novalidate method="post" action="{{ route('tenant.settings.invoices.update', ['business' => $tenant->slug]) }}" data-success-class=".report_setting_success_class">
			@csrf
			<table cellpadding="5" cellspacing="5" class="form-table">
				<tr>
					<th scrope="row" colspan="2">
						<h3>{{ __( 'Print Invoice Settings' ) }}</h3>
					</th>
				</tr>
				
				<tr><th scope="row"><label for="wcrb_add_invoice_qr_code">{{ __( 'Add QR Code to invoice' ) }}</label></th>
					<td>
						<x-settings.toggle name="wcrb_add_invoice_qr_code" id="wcrb_add_invoice_qr_code" :checked="(bool) $wcrb_add_invoice_qr_code" />
						<p class="description">{{ __( 'Add a QR code below invoice for status or history check page later.' ) }}</p>
					</td></tr>

				<tr>
					<th scope="row">
						<label for="wc_rb_io_thanks_msg">{{ __( 'Footer message on Print Invoice' ) }}</label>
					</th>
					<td>
						<x-settings.input
							name="wc_rb_io_thanks_msg"
							id="wc_rb_io_thanks_msg"
							class="regular-text"
							type="text"
							:value="old('wc_rb_io_thanks_msg', $wc_rb_io_thanks_msg)"
							:placeholder="__( 'Thanks for your business!' )"
						/>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="wb_rb_invoice_type">
							{{ __( 'Invoice Print By' ) }}
						</label>
					</th>
					<td>
						<x-settings.select
							name="wb_rb_invoice_type"
							id="wb_rb_invoice_type"
							class="form-control"
							:options="['default' => __('Default (By Items)'), 'by_device' => __('By Devices'), 'by_items' => __('By Items')]"
							:value="(string) old('wb_rb_invoice_type', $wb_rb_invoice_type)"
						/>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="wcrb_display_dates_on_invoices">
							{{ __( 'Display dates on invoices' ) }}
						</label>
					</th>
					<td>
						<fieldset class="large-12 cell">
							<input type="hidden" name="pickupdate" value="" />
							<input name="pickupdate" id="pickupdate" {{ $pickupdate_checked }} type="checkbox" value="show" />
							<label for="pickupdate">{{ sprintf( __( 'Show %s as created' ), $pickup_date_label_none ?? 'pickup_date' ) }} </label>
							<input type="hidden" name="deliverydate" value="" />
							<input name="deliverydate" id="deliverydate" {{ $deliverydate_checked }} type="checkbox" value="show" />
							<label for="deliverydate">{{ sprintf( __( 'Show %s' ), $delivery_date_label_none ?? 'delivery_date' ) }} </label>
							<input type="hidden" name="nextservicedate" value="" />
							<input name="nextservicedate" id="nextservicedate" {{ $nextservicedate_checked }} type="checkbox" value="show" />
							<label for="nextservicedate">{{ sprintf( __( 'Show %s' ), $nextservice_date_label_none ?? 'nextservice_date' ) }} </label>
						</fieldset>
					</td>
				</tr>

				<!-- Disclaimer /-->
				<tr>
					<th scope="row">
						<label for="wcrb_invoice_disclaimer">
							{{ __( 'Terms and Conditions or Disclaimer of service prints below invoice in a new page' ) }}
						</label>
					</th>
					<td>
						{!! $wcrb_invoice_disclaimer_html !!}
					</td>
				</tr>
				<!-- Disclaimer /-->


				<tr>
					<th scrope="row" colspan="2">
						<h3>{{ __( 'Repair Order Settings' ) }}</h3>
					</th>
				</tr>

				<tr>
					<th scope="row">
						<label for="repair_order_type">
							{{ __( 'Repair Order Type' ) }}
						</label>
					</th>
					<td>
						<x-settings.select
							name="repair_order_type"
							id="repair_order_type"
							class="form-control"
							:options="['pos_type' => __('With Terms & Conditions QR code to sign by customer'), 'invoice_type' => __('Invoice Type without amounts')]"
							:value="(string) old('repair_order_type', $repair_order_type)"
						/>
					</td>
				</tr>

				<tr>
					<th scope="row">
						<label for="business_terms">{{ __( 'Terms & Conditions for Repair Order' ) }}</label>
					</th>
					<td>
						<x-settings.input
							name="business_terms"
							id="business_terms"
							class="regular-text"
							type="text"
							:value="old('business_terms', $business_terms)"
							:placeholder="__( 'On Repair Order QR Code would be generated with this link.' )"
						/>
					</td>
				</tr>

				<tr>
					<th scope="row">
						<label for="repair_order_print_size">
							{{ __( 'Repair Order Print Size' ) }}
						</label>
					</th>
					<td>
						<x-settings.select
							name="wc_repair_order_print_size"
							id="repair_order_print_size"
							class="form-control"
							:options="['default' => __('Default (POS Size)'), 'a4' => __('A4'), 'a5' => __('A5')]"
							:value="(string) old('wc_repair_order_print_size', $wc_repair_order_print_size)"
						/>
					</td>
				</tr>

				<tr>
					<th scope="row">
						<label for="wc_rb_cr_display_add_on_ro">{{ __( 'Display Business Address Details' ) }}</label>
					</th>
					<td>
						<x-settings.toggle name="wc_rb_cr_display_add_on_ro" id="wc_rb_cr_display_add_on_ro" :checked="(bool) $wc_rb_cr_display_add_on_ro" />
						<p class="description">{{ __( 'Show business address, email and phone details on repair order.' ) }}</p>
					</td>
				</tr>

				<tr>
					<th scope="row">
						<label for="wc_rb_cr_display_add_on_ro_cu">{{ __( 'Display Customer Email & Address Details' ) }}</label>
					</th>
					<td>
						<x-settings.toggle name="wc_rb_cr_display_add_on_ro_cu" id="wc_rb_cr_display_add_on_ro_cu" :checked="(bool) $wc_rb_cr_display_add_on_ro_cu" />
						<p class="description">{{ __( 'Show customer address, email and phone details on repair order.' ) }}</p>
					</td>
				</tr>

				<tr>
					<th scope="row">
						<label for="wc_rb_ro_thanks_msg">{{ __( 'Footer message on Repair Order' ) }}</label>
					</th>
					<td>
						<x-settings.input
							name="wc_rb_ro_thanks_msg"
							id="wc_rb_ro_thanks_msg"
							class="regular-text"
							type="text"
							:value="old('wc_rb_ro_thanks_msg', $wc_rb_ro_thanks_msg)"
							:placeholder="__( 'Thanks for your business!' )"
						/>
					</td>
				</tr>

				<x-settings.submit-row>
					<div class="report_setting_success_class"></div>
					<input type="hidden" name="form_type" value="wcrb_report_setting_form" />
					<input type="hidden" name="wc_rep_labels_submit" value="1" />
				</x-settings.submit-row>
			</table>
		</form>
	</div>
</div><!-- tab reportsAInvoices -->
