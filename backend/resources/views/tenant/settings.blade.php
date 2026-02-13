@extends('tenant.layouts.myaccount', ['title' => 'Settings'])

@push('page-styles')
	<link rel="stylesheet" href="https://s.w.org/wp-includes/css/dashicons.css">
	<link rel="stylesheet" href="{{ asset('repairbuddy/plugin/css/foundation.min.css') }}">
	<link rel="stylesheet" href="{{ asset('repairbuddy/plugin/css/style.css') }}">
	<link rel="stylesheet" href="{{ asset('repairbuddy/plugin/css/admin-style.css') }}">
	<link rel="stylesheet" href="{{ asset('repairbuddy/plugin/css/wp-admin-shim.css') }}">
	<link rel="stylesheet" href="{{ asset('repairbuddy/plugin/css/switchcolorscheme.css') }}">
@endpush

@push('page-scripts')
	<script src="{{ asset('repairbuddy/plugin/js/foundation.min.js') }}"></script>
	<script>
		if (window.jQuery && window.jQuery.fn && window.jQuery.fn.foundation) {
			window.jQuery(document).foundation();

			(function ($) {
				var $tabs = $('#example-tabs');
				if (!($tabs.length && $tabs.foundation)) {
					return;
				}

				var storageKey = 'repairbuddy_settings_active_tab';
				var restoreTab = function () {
					var hash = window.location.hash;
					var target = (hash && $(hash).length) ? hash : null;
					if (!target) {
						var stored = null;
						try {
							stored = window.localStorage ? window.localStorage.getItem(storageKey) : null;
						} catch (e) {
							stored = null;
						}
						target = (stored && $(stored).length) ? stored : null;
					}
					if (target && $tabs.foundation) {
						try {
							$tabs.foundation('selectTab', target);
						} catch (e) {
						}
					}
				};

				restoreTab();

				$tabs.on('change.zf.tabs', function (event, $tab) {
					var $a = ($tab && $tab.find) ? $tab.find('a').first() : null;
					var href = $a && $a.length ? $a.attr('href') : null;
					if (!href || href.charAt(0) !== '#') {
						return;
					}

					try {
						window.history.replaceState(null, '', href);
					} catch (e) {
						window.location.hash = href;
					}

					try {
						if (window.localStorage) {
							window.localStorage.setItem(storageKey, href);
						}
					} catch (e) {
					}
				});

				$(window).on('hashchange', function () {
					restoreTab();
				});
			})(window.jQuery);
		}
	</script>
@endpush

@section('content')
	<div class="main-container computer-repair">
		<div class="grid-x grid-container grid-margin-x grid-padding-y fluid" style="width:100%;">
			<div class="small-12 cell">
				<div class="form-update-message"></div>
			</div>
			<div class="large-12 medium-12 small-12 cell">
				
				<div class="team-wrap grid-x" data-equalizer data-equalize-on="medium">
					<div class="cell medium-2 thebluebg sidebarmenu">
						<div class="the-brand-logo">
							<a href="{{ $logoURL }}" target="_blank">
								<img src="{{ $logolink }}" alt="RepairBuddy CRM Logo" />
							</a>
						</div>
						<ul class="vertical tabs thebluebg" data-tabs="82ulyt-tabs" id="example-tabs">
							<li class="tabs-title{{ $class_settings }}" role="presentation">
								<a href="#main_page" role="tab" aria-controls="main_page" aria-selected="false" id="main_page-label">
									<h2>{{ __('Dashboard') }}</h2>
								</a>
							</li>
							<li class="tabs-title{{ $class_general_settings }}" role="presentation">
								<a href="#panel1" role="tab" aria-controls="panel1" aria-selected="false" id="panel1-label">
									<h2>{{ __('General Settings') }}</h2>
								</a>
							</li>
							<li class="tabs-title{{ $class_currency_settings }}" role="presentation">
								<a href="#currencyFormatting" role="tab" aria-controls="currencyFormatting" aria-selected="true" id="currencyFormatting-label">
									<h2>{{ __('Currency') }}</h2>
								</a>
							</li>
							<li class="tabs-title{{ $class_invoices_settings }}" role="presentation">
								<a href="#reportsAInvoices" role="tab" aria-controls="reportsAInvoices" aria-selected="true" id="reportsAInvoices-label">
									<h2>{{ __('Reports & Invoices') }}</h2>
								</a>
							</li>
							<li class="tabs-title{{ $class_status }}" role="presentation">
								<a href="#panel3" role="tab" aria-controls="panel3" aria-selected="true" id="panel3-label">
									<h2>{{ __('Job Status') }}</h2>
								</a>
							</li>
							{!! $settings_tab_menu_items_html ?? '' !!}
							<li class="tabs-title{{ $class_activation }}" role="presentation">
								<a href="#panel4" role="tab" aria-controls="panel4" aria-selected="true" id="panel4-label">
									<h2>{{ __('Activation') }}</h2>
								</a>
							</li>
							<li class="thespacer"><hr></li>
							<li class="tabs-title" role="presentation">
								<a href="#documentation" role="tab" aria-controls="documentation" aria-selected="true" id="documentation-label">
									<h2>{{ __('Shortcodes & Support') }}</h2>
								</a>
							</li>
							@if (! $repairbuddy_whitelabel)
							<li class="tabs-title" role="presentation">
								<a href="#addons" role="tab" aria-controls="addons" aria-selected="true" id="addons-label">
									<h2>{{ __('Addons') }}</h2>
								</a>
							</li>
							@endif
							<li class="thespacer"><hr></li>
							<li class="external-title">
								<a href="{{ $contactURL }}" target="_blank">
									<h2><span class="dashicons dashicons-buddicons-pm"></span> {{ __('Contact Us') }}</h2>
								</a>
							</li>
							@if (! $repairbuddy_whitelabel)
							<li class="external-title">
								<a href="https://www.facebook.com/WebfulCreations" target="_blank">
									<h2><span class="dashicons dashicons-facebook"></span> {{ __('Chat With Us') }}</h2>
								</a>
							</li>
							@endif
						</ul>
					</div>
					
					<div class="cell medium-10 thewhitebg contentsideb">
						<div class="tabs-content vertical" data-tabs-content="example-tabs">
						
							<div class="tabs-panel team-wrap{{ $class_settings }}" id="main_page" role="tabpanel" aria-hidden="true" aria-labelledby="main_page-label">
							{!! $dashoutput_html !!}
							</div>
							<!-- Main page ends /-->

							<div class="tabs-panel team-wrap{{ $class_general_settings }}" id="panel1" role="tabpanel" aria-hidden="true" aria-labelledby="panel1-label">
								<div class="wrap">
									<h2>{{ __('Settings') }}</h2>

									<form data-async data-abide class="needs-validation" novalidate method="post" data-success-class=".main_setting_success_class">
										<table cellpadding="5" cellspacing="5" class="form-table">
											<tr>
												<td>
													<label for="menu_name">{{ __('Menu Name e.g Computer Repair') }}
													<input 
														name="menu_name" 
														id="menu_name" 
														class="regular-text" 
														value="{{ $menu_name_p }}" 
														type="text" 
														placeholder="{{ __('Enter Menu Name Default Computer Repair') }}"/></label>
												</td>
												<td>
													<label for="wc_rb_business_name">
														{{ __('Business Name') }}
														<small>{{ __('Name will be used in reports/invoices') }}</small>
													<input 
														name="wc_rb_business_name" 
														id="wc_rb_business_name" 
														class="regular-text" 
														value="{{ $wc_rb_business_name }}" 
														type="text" /></label>
												</td>
											</tr>

											<tr>
												<td>
													<label for="wc_rb_business_phone">
														{{ __('Business Phone') }}
														<small>{{ __('Phone will be used in reports/invoices') }}</small>
													<input 
														name="wc_rb_business_phone" 
														id="wc_rb_business_phone" 
														class="regular-text" 
														value="{{ $wc_rb_business_phone }}" 
														type="text" /></label>
												</td>
												<td>
													<label for="wc_rb_business_address">
														{{ __('Business Address') }}
														<small>{{ __('Address will be used in reports/invoices') }}</small>
													<input 
														name="wc_rb_business_address" 
														id="wc_rb_business_address" 
														class="regular-text" 
														value="{{ $wc_rb_business_address }}" 
														type="text" /></label>
												</td>
											</tr>

											<tr>
												<td>
													<label for="menu_name">{{ __('Logo to use') }}
													<input 
														name="computer_repair_logo" 
														id="computer_repair_logo" 
														class="regular-text" 
														value="{{ $computer_repair_logo }}" 
														type="text" 
														placeholder="{{ __('Enter url of logo') }}"/></label>
												</td>
												<td>
													<label for="menu_name">{{ __('Email') }}<small> {{ __('Where quote forms and other admin emails would be sent.') }}</small>
													<input 
														name="computer_repair_email" 
														id="computer_repair_email" 
														class="regular-text" 
														value="{{ $computer_repair_email }}" 
														type="text" 
														placeholder="{{ __('Where to send emails like Quote and other stuff.') }}"/></label>
												</td>
											</tr>
											<tr>
												<td>
													<label for="case_number_prefix">{{ sprintf(__('%s prefix'), $casenumber_label_first ?? 'Case') }}
													<input 
														name="case_number_prefix" 
														id="case_number_prefix" 
														class="regular-text" 
														value="{{ $case_number_prefix }}" 
														type="text" 
														placeholder="{{ sprintf(__('%s prefix e.g CHM_ or WC_'), $casenumber_label_first ?? 'Case') }}"/></label>
												</td>
												<td>
													<label for="case_number_length">{{ sprintf(__('%s Length for string in %s before timestamp'), $casenumber_label_first ?? 'Case', $casenumber_label_none ?? 'Case') }}
													<input 
														name="case_number_length" 
														id="case_number_length" 
														class="regular-text" 
														value="{{ $case_number_length }}" 
														type="number" 
														value="6" 
														min="1" 
														/></label>
												</td>
											</tr>
											</table>

											<table cellpadding="5" cellspacing="5" class="form-table">
											<tr>
												<th scope="row"><label for="wc_job_status_cr_notice">{{ __( 'Email Customer' ) }}</label></th>
												<td><input type="checkbox" {{ $send_notice }} name="wc_job_status_cr_notice" id="wc_job_status_cr_notice" /><p class="description">{{ __( 'Email customer everytime job status is changed.' ) }}</p></td>
											</tr>
											<tr>
												<th scope="row"><label for="wcrb_attach_pdf_in_customer_emails">{{ __( 'Attach PDF' ) }}</label></th>
												<td><input type="checkbox" {{ $attach_pdf }} name="wcrb_attach_pdf_in_customer_emails" id="wcrb_attach_pdf_in_customer_emails" />
												<p class="description">{{ __( 'Attach PDF with emails to customer about jobs and estimates.' ) }}</p></td>
											</tr>
											<tr>
												<th scope="row"><label for="wcrb_next_service_date">{{ __( 'Next service date' ) }}</label></th>
												<td><input type="checkbox" {{ $disableNextServiceDate }} name="wcrb_next_service_date" 
													id="wcrb_next_service_date" />
													<p class="description">{{ __( 'Turn on if you want to see jobs in calendar for next service date.' ) }}</p></td>
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
																value="{{ $wc_rb_gdpr_acceptance }}" 
																type="text" 
																placeholder="{{ __( 'GDPR Acceptance text label for booking and quote' ) }}" />
															</td>
															<td>
															<input 
																name="wc_rb_gdpr_acceptance_link_label" 
																id="wc_rb_gdpr_acceptance_link_label" 
																class="regular-text" 
																value="{{ $wc_rb_gdpr_acceptance_link_label }}" 
																type="text" 
																placeholder="{{ __( 'Privacy policy' ) }}" />
															</td>
															<td>
															<input 
																name="wc_rb_gdpr_acceptance_link" 
																id="wc_rb_gdpr_acceptance_link" 
																class="regular-text" 
																value="{{ $wc_rb_gdpr_acceptance_link }}" 
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
													<select name="wc_primary_country" id="wc_primary_country" class="form-control">
														{!! $countries_options_html ?? '' !!}
													</select>
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
														<input type="checkbox" {{ $useWooProducts }} name="wc_enable_woo_products" id="wc_enable_woo_products" />
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
													<input type="checkbox" {{ $disableStatusCheckSerial }} name="wcrb_disable_statuscheck_serial" 
													id="wcrb_disable_statuscheck_serial" />
												</td>
											</tr>

											<tr>
												<td>
													<input 
														class="button button-primary" 
														type="Submit"  
														value="{{ __( 'Save Changes' ) }}"/>
												</td>
												<td>
													{!! $nonce_main_setting_html !!}
													<input type="hidden" name="form_type" value="wcrb_main_setting_form" />
													<input type="hidden" name="wc_rep_settings" value="1" />
													&nbsp;
												</td>
											</tr>
										</table>
										<div class="main_setting_success_class"></div>
									</form>
								</div>
							</div><!-- tab 1 ends -->
						
							<div class="tabs-panel team-wrap{{ $class_currency_settings }}" id="currencyFormatting" 
							role="tabpanel" aria-hidden="true" aria-labelledby="panel1-label">
									<div class="wrap">
										<h2>
											{{ __( 'Currency Settings' ) }}
										</h2>

									<form data-async data-abide class="needs-validation" novalidate method="post" data-success-class=".currency_setting_success_class">
									<table class="form-table">
									<tbody>
										<tr valign="top">
											<th scope="row" class="titledesc">
												<label for="wc_cr_selected_currency">{{ __( 'Currency' ) }}</label>
											</th>
											<td class="forminp forminp-select">
												<select name="wc_cr_selected_currency" id="wc_cr_selected_currency">
												{!! $wc_cr_currency_options_html !!}
												</select>
											</td>
										</tr>
										<tr valign="top">
											<th scope="row" class="titledesc">
												<label for="wc_cr_currency_position">
													{{ __( 'Currency position' ) }}
												</label>
											</th>
											<td class="forminp forminp-select">
												<select name="wc_cr_currency_position" id="wc_cr_currency_position">
													{!! $wc_cr_currency_position_options_html !!}
												</select>
											</td>
										</tr>
										<tr valign="top">
											<th scope="row" class="titledesc">
												<label for="wc_cr_thousand_separator">
													{{ __( 'Thousand separator' ) }}
												</label>
											</th>
											<td class="forminp forminp-text">
												<input name="wc_cr_thousand_separator" id="wc_cr_thousand_separator" type="text" 
												style="width:50px;" value="{{ $wc_cr_thousand_separator }}"> 							
											</td>
										</tr>
										<tr valign="top">
											<th scope="row" class="titledesc">
												<label for="wc_cr_decimal_separator">
													{{ __( 'Decimal separator' ) }}
												</label>
											</th>
											<td class="forminp forminp-text">
												<input name="wc_cr_decimal_separator" id="wc_cr_decimal_separator" type="text" style="width:50px;" 
												value="{{ $wc_cr_decimal_separator }}"> 							
											</td>
										</tr>
										<tr valign="top">
											<th scope="row" class="titledesc">
												<label for="wc_cr_number_of_decimals">
													{{ __( 'Number of decimals' ) }}
												</label>
											</th>
											<td class="forminp forminp-number">
												<input name="wc_cr_number_of_decimals" id="wc_cr_number_of_decimals" type="number" style="width:50px;" 
												value="{{ $wc_cr_number_of_decimals }}" min="0" step="1"> 							
											</td>
										</tr>
										<tr>
											<td>
												<input 
													class="button button-primary" 
													type="Submit"  
													value="{{ __( 'Save Changes' ) }}"/>
											</td>
											<td>
												<div class="currency_setting_success_class"></div>
												{!! $nonce_currency_setting_html !!}
												<input type="hidden" name="form_type" value="wcrb_currency_setting_form" />
												<input type="hidden" name="wc_rep_currency_submit" value="1" />
											</td>
										</tr>
									</tbody>
									</table>
									</form>
								</div>
							</div><!-- tab CurrencyFormatting -->

							<div class="tabs-panel team-wrap{{ $class_invoices_settings }}" id="reportsAInvoices" role="tabpanel" aria-hidden="true" aria-labelledby="panel1-label">
								<div class="wrap">
									<h2>{{ __('Reports & Invoices Settings') }}</h2>

									<form data-async data-abide class="needs-validation" novalidate method="post" data-success-class=".report_setting_success_class">
										<table cellpadding="5" cellspacing="5" class="form-table">
											<tr>
												<th scrope="row" colspan="2">
													<h3>{{ __( 'Print Invoice Settings' ) }}</h3>
												</th>
											</tr>
											
											<tr><th scope="row"><label for="wcrb_add_invoice_qr_code">{{ __( 'Add QR Code to invoice' ) }}</label></th>
												<td><input type="checkbox" {{ $wcrb_add_invoice_qr_code }} name="wcrb_add_invoice_qr_code" id="wcrb_add_invoice_qr_code" />
													<p class="description">{{ __( 'Add a QR code below invoice for status or history check page later.' ) }}</p>
												</td></tr>

											<tr>
												<th scope="row">
													<label for="wc_rb_io_thanks_msg">{{ __( 'Footer message on Print Invoice' ) }}</label>
												</th>
												<td>
													<input 
														name="wc_rb_io_thanks_msg" 
														id="wc_rb_io_thanks_msg" 
														class="regular-text" 
														value="{{ $wc_rb_io_thanks_msg }}" 
														type="text" 
														placeholder="{{ __( 'Thanks for your business!' ) }}"
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
													<select name="wb_rb_invoice_type" id="wb_rb_invoice_type" class="form-control">
														<option {{ ((string) $wb_rb_invoice_type === 'default') ? 'selected' : '' }} value="default">{{ __( 'Default (By Items)' ) }}</option>
														<option {{ ((string) $wb_rb_invoice_type === 'by_device') ? 'selected' : '' }} value="by_device">{{ __( 'By Devices' ) }}</option>
														<option {{ ((string) $wb_rb_invoice_type === 'by_items') ? 'selected' : '' }} value="by_items">{{ __( 'By Items' ) }}</option>
													</select>
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
														<input name="pickupdate" id="pickupdate" {{ $pickupdate_checked }} type="checkbox" value="show" />
														<label for="pickupdate">{{ sprintf( __( 'Show %s as created' ), $pickup_date_label_none ?? 'pickup_date' ) }} </label>
														<input name="deliverydate" id="deliverydate" {{ $deliverydate_checked }} type="checkbox" value="show" />
														<label for="deliverydate">{{ sprintf( __( 'Show %s' ), $delivery_date_label_none ?? 'delivery_date' ) }} </label>
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
													<select name="repair_order_type" id="repair_order_type" class="form-control">
														<option {{ ((string) $repair_order_type === 'pos_type') ? 'selected' : '' }} value="pos_type">{{ __( 'With Terms & Conditions QR code to sign by customer' ) }}</option>
														<option {{ ((string) $repair_order_type === 'invoice_type') ? 'selected' : '' }} value="invoice_type">{{ __( 'Invoice Type without amounts' ) }}</option>
													</select>
												</td>
											</tr>

											<tr>
												<th scope="row">
													<label for="business_terms">{{ __( 'Terms & Conditions for Repair Order' ) }}</label>
												</th>
												<td>
													<input 
														name="business_terms" 
														id="business_terms" 
														class="regular-text" 
														value="{{ $business_terms }}" 
														type="text" 
														placeholder="{{ __( 'On Repair Order QR Code would be generated with this link.' ) }}"/>
												</td>
											</tr>

											<tr>
												<th scope="row">
													<label for="repair_order_print_size">
														{{ __( 'Repair Order Print Size' ) }}
													</label>
												</th>
												<td>
													<select name="wc_repair_order_print_size" id="repair_order_print_size" class="form-control">
														<option {{ ((string) $wc_repair_order_print_size === 'default') ? 'selected' : '' }} value="default">{{ __( 'Default (POS Size)' ) }}</option>
														<option {{ ((string) $wc_repair_order_print_size === 'a4') ? 'selected' : '' }} value="a4">{{ __( 'A4' ) }}</option>
														<option {{ ((string) $wc_repair_order_print_size === 'a5') ? 'selected' : '' }} value="a5">{{ __( 'A5' ) }}</option>
													</select>
												</td>
											</tr>

											<tr>
												<th scope="row">
													<label for="wc_rb_cr_display_add_on_ro">{{ __( 'Display Business Address Details' ) }}</label>
												</th>
												<td>
													<input type="checkbox" {{ $wc_rb_cr_display_add_on_ro }} name="wc_rb_cr_display_add_on_ro" id="wc_rb_cr_display_add_on_ro" />
													<p class="description">{{ __( 'Show business address, email and phone details on repair order.' ) }}</p>
												</td>
											</tr>

											<tr>
												<th scope="row">
													<label for="wc_rb_cr_display_add_on_ro_cu">{{ __( 'Display Customer Email & Address Details' ) }}</label>
												</th>
												<td>
													<input type="checkbox" {{ $wc_rb_cr_display_add_on_ro_cu }} name="wc_rb_cr_display_add_on_ro_cu" id="wc_rb_cr_display_add_on_ro_cu" />
													<p class="description">{{ __( 'Show customer address, email and phone details on repair order.' ) }}</p>
												</td>
											</tr>

											<tr>
												<th scope="row">
													<label for="wc_rb_ro_thanks_msg">{{ __( 'Footer message on Repair Order' ) }}</label>
												</th>
												<td>
													<input 
														name="wc_rb_ro_thanks_msg" 
														id="wc_rb_ro_thanks_msg" 
														class="regular-text" 
														value="{{ $wc_rb_ro_thanks_msg }}" 
														type="text" 
														placeholder="{{ __( 'Thanks for your business!' ) }}"
														/>
												</td>
											</tr>

											<tr><td><input 
														class="button button-primary" 
														type="Submit"  
														value="{{ __( 'Save Changes' ) }}"/>
												</td>
												<td>
													<div class="report_setting_success_class"></div>
													{!! $nonce_report_setting_html !!}
													<input type="hidden" name="form_type" value="wcrb_report_setting_form" />
													<input type="hidden" name="wc_rep_labels_submit" value="1" />
												</td>
											</tr>
										</table>
									</form>
								</div>
							</div><!-- tab reportsAInvoices -->
							
							<div class="tabs-panel team-wrap{{ $class_status }}" id="panel3" role="tabpanel" aria-hidden="false" aria-labelledby="panel3-label">
								
								<p class="help-text">
									<a class="button button-primary button-small" data-open="statusFormReveal">
										{{ __( 'Add New Status' ) }}
									</a>
								</p>
								{!! $add_status_form_footer_html ?? '' !!}

								<div id="job_status_wrapper">
									<table id="status_poststuff" class="wp-list-table widefat fixed striped posts">
										<thead>
											<tr>
												<th  class="column-id">{{ __( 'ID' ) }}</th>
												<th>{{ __( 'Name' ) }}</th>
												<th>{{ __( 'Slug' ) }}</th>
												<th>{{ __( 'Description' ) }}</th>
												<th>{{ __( 'Invoice Label' ) }}</th>

												@if ($wc_inventory_management_status)
												<th>{{ __( 'Manage Woo Stock' ) }}</th>
												@endif
												<th class="column-id">{{ __( 'Status' ) }}</th>
												<th class="column-id">{{ __( 'Actions' ) }}</th>
											</tr>
										</thead>

										<tbody>
											{!! $job_status_rows_html !!}
										</tbody>
									</table>
									<!-- Let's produce the form for status to consider completed and cancelled /-->
								</div><!-- Post Stuff/-->

								<div class="wc-rb-grey-bg-box">
									<h2>{{ __( 'Status settings' ) }}</h2>
									<div class="job_status_settings_msg"></div>

									<form data-async data-abide class="needs-validation" novalidate method="post" data-success-class=".job_status_settings_msg">
										<table class="form-table border">
											<tbody>
												<tr>
													<th scope="row">
														<label for="wcrb_job_status_delivered">{{ __( 'Job status to consider job completed' ) }}</label>
													</th>
													<td>
														<select class="form-select" name="wcrb_job_status_delivered" id="wcrb_job_status_delivered">
														{!! $status_options_delivered_html !!}
														</select>
													</td>
												</tr>
												<tr>
													<th scope="row">
														<label for="wcrb_job_status_cancelled">{{ __( 'Job status to consider job cancelled' ) }}</label>
													</th>
													<td>
														<select class="form-select" name="wcrb_job_status_cancelled" id="wcrb_job_status_cancelled">
														{!! $status_options_cancelled_html !!}
														</select>
													</td>
												</tr>
											</tbody>
										</table>
										
										<input type="hidden" name="form_action" value="wcrb_update_job_status_consideration" />
										<input type="hidden" name="form_type" value="wcrb_update_job_status_consideration" />
										{!! $nonce_delivered_status_html !!}

										<button type="submit" class="button button-primary" data-type="rbssubmitdevices">Update</button>
									</form>
								</div><!-- wc rb Devices /-->

							</div><!-- tab 3 Ends -->

							{!! $settings_tab_body_html ?? '' !!}

							<div class="tabs-panel team-wrap{{ $class_activation }}" id="panel4" role="tabpanel" aria-hidden="false" 
							aria-labelledby="panel4-label">
								
								<div id="license_activation">
									{!! $activation_form_html !!}
								</div><!-- Post Stuff/-->

							</div><!-- tab 4 Ends -->

							<div class="tabs-panel team-wrap" id="documentation" role="tabpanel" aria-hidden="false" 
							aria-labelledby="documentation-label">
								<h1>Shortcodes</h1>
								<p>RepairBuddy WordPress Plugin provides various shortcodes to use in different pages. Just copy a shortcode you need and paste in a page to use it. Please check Page Setup for some default created pages.</p>
								
								<div class="documentation-section">
									<h2>Check Repair Status</h2>
								<p>To add check case status form create a page and insert shortcode</p>
								<pre>[wc_order_status_form]</pre></div>
									
								<div class="documentation-section">
									<h2>Book Device / Book Service</h2>
									<p>Book the service with brand, device, and service selection.</p>
									<p>Doesn't include device type or grouped services.</p>
									<pre>[wc_book_my_service]</pre>

									<p>Grouped services with device type, brands, devices.</p>
									<pre>[wc_book_type_grouped_service]</pre>

									<p>To add start new job by device on front end for loged in users only</p> 
									<pre>[wc_start_job_with_device]</pre></div>

								<div class="documentation-section">
									<h2>Get feedback on job page</h2>
									<p>Using this shortcode you can get the feedback from customers on jobs you performed for them. For auto feedback request check reviews settings. </p>
									<pre>[wc_get_order_feedback]</pre>
									<h2>Display Reviews on Page</h2>
									<p>Using the following shortcode you can display reviews in a page, widget or post. Columns 1, 2, 3 </p>
									<pre>[wcrb_display_reviews columns="2" hide_below_rating="3"]</pre>
								</div>

								<div class="documentation-section">
									<h2>My Account Page</h2>
									<p>Note: If you are using WooCommerce then WooCommerce My Account page can list Repair Orders and Request quote section, You do not need to add separate account page in that case.</p>
									<p>To add user account page into front end create a page and use</p>
									<pre>[wc_cr_my_account]</pre>
								</div>	

								<div class="documentation-section">
									<h2>For Warranty Claim</h2>
									<p>Warranty claim can be done for WooCommerce products or Devices.</p>
									<p>Following Shortcode let customers book their device for warranty claim. Doesn't require services to be included.</p>
									<pre>[wc_book_my_warranty]</pre>
								</div>	
								
								<div class="documentation-section">
									<h2>Simple Quote Form</h2>
								<p>To add simple request quote form into front end use</p> 
								<pre>[wc_request_quote_form]</pre></div>

								<div class="documentation-section">
									<h2>Services Page</h2>
								<p>To populate services create a page and insert shortcode</p>
								<pre>[wc_list_services]</pre></div>

								<div class="documentation-section"><h2>Parts Page</h2>
								<p>To populate parts/products create a page and insert shortcode</p> 
								<pre>[wc_list_products]</pre></div>
							</div><!-- tab Documentation Ends -->

							@if (! $repairbuddy_whitelabel)
							<div class="tabs-panel team-wrap" id="addons" role="tabpanel" aria-hidden="false" 
							aria-labelledby="addons-label">
								<h1>Addons</h1>
								<p>We have some addons which you can use to extend the features of your RepairBuddy WordPress Plugin.</p>
								
								<div class="theaddons-container grid-x grid-margin-x grid-container fluid">
									@if (! $rb_ms_version_defined)
									<div class="large-4 medium-4 medium-6 cell">
										<div class="documentation-section theaddon">
												<h2>MultiStore - RepairBuddy</h2>
											<p>Multistore RepairBuddy addon extends your CRM with features to have more than one stores, filter jobs based on stores. Technicians can access jobs only they have access to, Managers can access only store they have access to. Invoices can also have address of selected store on that job and much more ...</p>
											<a href="https://www.webfulcreations.com/products/multi-store-addon-repairbuddy/" class="button button-primary" target="_blank">Learn More</a>
										</div>
									</div> <!-- Column Ends /-->
									@endif

									@if (! $rb_qb_version_defined)
									<div class="large-4 medium-4 medium-6 cell">
										<div class="documentation-section theaddon">
												<h2>QuickBooks Addon – RepairBuddy</h2>
											<p>QuickBooks Addon – RepairBuddy is another great addon to expand features of your RepairBuddy supported website. Using QuickBooks addon you can easily fetch your customers from QuickBooks and also send invoices to QuickBooks from RepairBuddy. While you can manually send invoices to QuickBooks clicking button but also on status change automatically job can be sent to QuickBooks as invoice. </p>
											<a href="https://www.webfulcreations.com/products/quickbooks-addon-repairbuddy/" class="button button-primary" target="_blank">Learn More</a>
										</div>
									</div> <!-- Column Ends /-->
									@endif

								</div><!-- End container /-->
									
							</div><!-- Addons Ends -->
							@endif

						</div><!-- tabs content ends -->
					</div>

				</div>
			
			</div><!-- Main Content Div Ends /-->
		</div><!-- Row Ends /-->
	</div>
@endsection
