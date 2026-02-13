<div class="tabs-panel team-wrap{{ $class_currency_settings }}" id="currencyFormatting" 
role="tabpanel" aria-hidden="true" aria-labelledby="panel1-label">
		<div class="wrap">
			<h2>
				{{ __( 'Currency Settings' ) }}
			</h2>

		<form data-abide class="needs-validation" novalidate method="post" action="{{ route('tenant.settings.currency.update', ['business' => $tenant->slug]) }}" data-success-class=".currency_setting_success_class">
			@csrf
			<div class="wcrb-settings-form">
			<div class="wcrb-settings-row">
				<label for="wc_cr_selected_currency" class="wcrb-settings-row-label">{{ __( 'Currency' ) }}</label>
				<div class="wcrb-settings-row-control">
					<x-settings.select
						name="wc_cr_selected_currency"
						id="wc_cr_selected_currency"
						:options="($currencyOptions ?? [])"
						:value="(string) old('wc_cr_selected_currency', $wc_cr_selected_currency)"
					/>
				</div>
			</div>

			<div class="wcrb-settings-row">
				<label for="wc_cr_currency_position" class="wcrb-settings-row-label">{{ __( 'Currency position' ) }}</label>
				<div class="wcrb-settings-row-control">
					<x-settings.select
						name="wc_cr_currency_position"
						id="wc_cr_currency_position"
						:options="($currencyPositionOptions ?? [])"
						:value="(string) old('wc_cr_currency_position', $wc_cr_currency_position)"
					/>
				</div>
			</div>

			<div class="wcrb-settings-row">
				<label for="wc_cr_thousand_separator" class="wcrb-settings-row-label">{{ __( 'Thousand separator' ) }}</label>
				<div class="wcrb-settings-row-control">
					<x-settings.input
						name="wc_cr_thousand_separator"
						id="wc_cr_thousand_separator"
						type="text"
						style="width:50px;"
						:value="old('wc_cr_thousand_separator', $wc_cr_thousand_separator)"
					/>
				</div>
			</div>

			<div class="wcrb-settings-row">
				<label for="wc_cr_decimal_separator" class="wcrb-settings-row-label">{{ __( 'Decimal separator' ) }}</label>
				<div class="wcrb-settings-row-control">
					<x-settings.input
						name="wc_cr_decimal_separator"
						id="wc_cr_decimal_separator"
						type="text"
						style="width:50px;"
						:value="old('wc_cr_decimal_separator', $wc_cr_decimal_separator)"
					/>
				</div>
			</div>

			<div class="wcrb-settings-row">
				<label for="wc_cr_number_of_decimals" class="wcrb-settings-row-label">{{ __( 'Number of decimals' ) }}</label>
				<div class="wcrb-settings-row-control">
					<x-settings.input
						name="wc_cr_number_of_decimals"
						id="wc_cr_number_of_decimals"
						type="number"
						style="width:50px;"
						min="0"
						step="1"
						:value="old('wc_cr_number_of_decimals', $wc_cr_number_of_decimals)"
					/>
				</div>
			</div>

			<div class="wcrb-settings-actions">
				<button type="submit" class="button button-primary">{{ __('Save Changes') }}</button>
				<div class="currency_setting_success_class"></div>
				<input type="hidden" name="form_type" value="wcrb_currency_setting_form" />
				<input type="hidden" name="wc_rep_currency_submit" value="1" />
			</div>
			</div>
		</form>
	</div>
</div><!-- tab CurrencyFormatting -->
