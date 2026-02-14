<div class="tabs-panel team-wrap" id="wcrb_estimates_tab" role="tabpanel" aria-hidden="true" aria-labelledby="wcrb_estimates_tab-label">
	<div class="wrap">
		<h2>{{ __('Estimates') }}</h2>
		<p>{{ __('Estimates settings allow you to configure how estimates and quotes are managed in your repair shop.') }}</p>

		<div class="wc-rb-grey-bg-box">
			<h3>{{ __('Estimate Settings') }}</h3>
			<form data-abide class="needs-validation" novalidate method="post" action="{{ route('tenant.settings.estimates.update', ['business' => $tenant->slug]) }}">
				@csrf

				<table class="form-table border">
					<tbody>
						<tr>
							<th scope="row">
								<label for="estimates_enabled">{{ __('Enable Estimates') }}</label>
							</th>
							<td>
								<input type="checkbox" name="estimates_enabled" id="estimates_enabled" {{ ($estimatesEnabledUi ?? false) ? 'checked' : '' }} />
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="estimate_valid_days">{{ __('Default validity (days)') }}</label>
							</th>
							<td>
								<input type="number" min="1" max="365" name="estimate_valid_days" id="estimate_valid_days" value="{{ old('estimate_valid_days', (string) ($estimatesValidDaysUi ?? 30)) }}" />
							</td>
						</tr>
					</tbody>
				</table>

				<button type="submit" class="button button-primary">{{ __('Update Options') }}</button>
			</form>
		</div>
	</div>
</div>
