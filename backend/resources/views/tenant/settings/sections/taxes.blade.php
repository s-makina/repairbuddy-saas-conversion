<div class="tabs-panel team-wrap" id="wc_rb_manage_taxes" role="tabpanel" aria-hidden="true" aria-labelledby="wc_rb_manage_taxes-label">
	<div class="wrap">
		<h2>{{ __('Tax Settings') }}</h2>

		<div class="wc-rb-grey-bg-box">
			<h3>{{ __('Add new Tax') }}</h3>
			<form data-abide class="needs-validation" novalidate method="post" action="{{ route('tenant.settings.taxes.store', ['business' => $tenant->slug]) }}">
				@csrf
				<label>
					{{ __('Tax Name') }}
					<input type="text" name="tax_name" required value="{{ old('tax_name', '') }}" />
					<span class="form-error">{{ __('Tax name is required.') }}</span>
				</label>

				<label>
					{{ __('Tax Rate') }}
					<input type="text" name="tax_rate" required value="{{ old('tax_rate', '') }}" />
					<span class="form-error">{{ __('Tax rate is required.') }}</span>
				</label>

				<label>
					{{ __('Status') }}
					<select name="tax_status">
						@php
							$taxStatus = (string) old('tax_status', 'active');
						@endphp
						<option value="active" {{ $taxStatus === 'active' ? 'selected' : '' }}>{{ __('Active') }}</option>
						<option value="inactive" {{ $taxStatus === 'inactive' ? 'selected' : '' }}>{{ __('Inactive') }}</option>
					</select>
				</label>

				<label>
					<input type="checkbox" name="tax_is_default" value="on" {{ old('tax_is_default') ? 'checked' : '' }}>
					{{ __('Default') }}
				</label>

				<button type="submit" class="button button-primary">{{ __('Add') }}</button>
			</form>
		</div>

		<div class="wc-rb-grey-bg-box">
			<h3>{{ __('Taxes') }}</h3>
			<table class="wp-list-table widefat fixed striped posts">
				<thead>
					<tr>
						<th class="column-id">{{ __('ID') }}</th>
						<th>{{ __('Name') }}</th>
						<th>{{ __('Rate') }}</th>
						<th>{{ __('Status') }}</th>
						<th>{{ __('Default') }}</th>
						<th>{{ __('Actions') }}</th>
					</tr>
				</thead>
				<tbody>
					@if (($taxes ?? collect())->count() === 0)
						<tr>
							<td colspan="6">{{ __('No taxes found.') }}</td>
						</tr>
					@else
						@foreach ($taxes as $t)
							<tr>
								<td>{{ (string) $t->id }}</td>
								<td><strong>{{ (string) $t->name }}</strong></td>
								<td>{{ (string) $t->rate }}%</td>
								<td>{{ $t->is_active ? __('Active') : __('Inactive') }}</td>
								<td>{{ $t->is_default ? __('Yes') : __('No') }}</td>
								<td>
									<form method="post" style="display:inline" action="{{ route('tenant.settings.taxes.active', ['business' => $tenant->slug, 'tax' => $t->id]) }}">
										@csrf
										<input type="hidden" name="is_active" value="{{ $t->is_active ? '0' : '1' }}">
										<button type="submit" class="button button-small">{{ __('Toggle Active') }}</button>
									</form>
									<form method="post" style="display:inline" action="{{ route('tenant.settings.taxes.default', ['business' => $tenant->slug, 'tax' => $t->id]) }}">
										@csrf
										<button type="submit" class="button button-small">{{ __('Make Default') }}</button>
									</form>
								</td>
							</tr>
						@endforeach
					@endif
				</tbody>
			</table>
		</div>

		<div class="wc-rb-grey-bg-box">
			<h3>{{ __('Tax Settings') }}</h3>
			<form data-abide class="needs-validation" novalidate method="post" action="{{ route('tenant.settings.taxes.settings', ['business' => $tenant->slug]) }}">
				@csrf
				<table class="form-table border">
					<tbody>
						<tr>
							<th scope="row">{{ __('Enable Taxes') }}</th>
							<td>
								<label>
									<input type="checkbox" name="wc_use_taxes" {{ ($taxEnable ?? false) ? 'checked' : '' }}>
									{{ __('Yes') }}
								</label>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="wc_primary_tax">{{ __('Default Tax') }}</label></th>
							<td>
								<select name="wc_primary_tax" id="wc_primary_tax" class="regular-text">
									<option value="">{{ __('Select tax') }}</option>
									@foreach (($taxes ?? collect()) as $t)
										<option value="{{ (string) $t->id }}" {{ ($taxDefaultId !== null && (string) $taxDefaultId === (string) $t->id) ? 'selected' : '' }}>{{ (string) $t->name }}</option>
									@endforeach
								</select>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="wc_prices_inclu_exclu">{{ __('Invoice amount') }}</label></th>
							<td>
								@php
									$amounts = (string) ($taxInvoiceAmounts ?? 'exclusive');
								@endphp
								<select name="wc_prices_inclu_exclu" id="wc_prices_inclu_exclu" class="regular-text">
									<option value="exclusive" {{ $amounts === 'exclusive' ? 'selected' : '' }}>{{ __('Exclusive') }}</option>
									<option value="inclusive" {{ $amounts === 'inclusive' ? 'selected' : '' }}>{{ __('Inclusive') }}</option>
								</select>
							</td>
						</tr>
					</tbody>
				</table>
				<button type="submit" class="button button-primary">{{ __('Update Options') }}</button>
			</form>
		</div>
	</div>
</div>
