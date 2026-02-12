<div class="tabs-panel team-wrap{{ $class_currency_settings }}" id="currencyFormatting" 
role="tabpanel" aria-hidden="true" aria-labelledby="panel1-label">
		<div class="wrap">
			<h2>
				{{ __( 'Currency Settings' ) }}
			</h2>

		<form data-async data-abide class="needs-validation" novalidate method="post" action="{{ route('tenant.settings.currency.update', ['business' => $tenant->slug]) }}" data-success-class=".currency_setting_success_class">
			@csrf
			<table class="form-table">
			<tbody>
				<tr valign="top">
					<th scope="row" class="titledesc">
						<label for="wc_cr_selected_currency">{{ __( 'Currency' ) }}</label>
					</th>
					<td class="forminp forminp-select">
						<x-settings.select
							name="wc_cr_selected_currency"
							id="wc_cr_selected_currency"
							:options="($currencyOptions ?? [])"
							:value="(string) old('wc_cr_selected_currency', $wc_cr_selected_currency)"
						/>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row" class="titledesc">
						<label for="wc_cr_currency_position">
							{{ __( 'Currency position' ) }}
						</label>
					</th>
					<td class="forminp forminp-select">
						<x-settings.select
							name="wc_cr_currency_position"
							id="wc_cr_currency_position"
							:options="($currencyPositionOptions ?? [])"
							:value="(string) old('wc_cr_currency_position', $wc_cr_currency_position)"
						/>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row" class="titledesc">
						<label for="wc_cr_thousand_separator">
							{{ __( 'Thousand separator' ) }}
						</label>
					</th>
					<td class="forminp forminp-text">
						<x-settings.input
							name="wc_cr_thousand_separator"
							id="wc_cr_thousand_separator"
							type="text"
							style="width:50px;"
							:value="old('wc_cr_thousand_separator', $wc_cr_thousand_separator)"
						/>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row" class="titledesc">
						<label for="wc_cr_decimal_separator">
							{{ __( 'Decimal separator' ) }}
						</label>
					</th>
					<td class="forminp forminp-text">
						<x-settings.input
							name="wc_cr_decimal_separator"
							id="wc_cr_decimal_separator"
							type="text"
							style="width:50px;"
							:value="old('wc_cr_decimal_separator', $wc_cr_decimal_separator)"
						/>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row" class="titledesc">
						<label for="wc_cr_number_of_decimals">
							{{ __( 'Number of decimals' ) }}
						</label>
					</th>
					<td class="forminp forminp-number">
						<x-settings.input
							name="wc_cr_number_of_decimals"
							id="wc_cr_number_of_decimals"
							type="number"
							style="width:50px;"
							min="0"
							step="1"
							:value="old('wc_cr_number_of_decimals', $wc_cr_number_of_decimals)"
						/>
					</td>
				</tr>
				<tr>
					<x-settings.submit-row>
						<div class="currency_setting_success_class"></div>
						<input type="hidden" name="form_type" value="wcrb_currency_setting_form" />
						<input type="hidden" name="wc_rep_currency_submit" value="1" />
					</x-settings.submit-row>
				</tr>
			</tbody>
			</table>
		</form>
	</div>
</div><!-- tab CurrencyFormatting -->
