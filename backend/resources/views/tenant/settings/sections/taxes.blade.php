<div class="tabs-panel team-wrap" id="wc_rb_manage_taxes" role="tabpanel" aria-hidden="true" aria-labelledby="wc_rb_manage_taxes-label">
	<div class="wrap">
		<!-- <h2>{{ __('Tax Settings') }}</h2> -->

		@php
			$shouldOpenTaxModal = $errors->has('tax_name') || $errors->has('tax_description') || $errors->has('tax_rate') || $errors->has('tax_status');
			$shouldOpenEditTaxModal = $errors->has('edit_tax_name') || $errors->has('edit_tax_description') || $errors->has('edit_tax_rate') || $errors->has('edit_tax_status');
		@endphp

		

		<div class="modal fade" id="wcrbAddTaxModal" tabindex="-1" aria-hidden="true">
			<div class="modal-dialog modal-lg">
				<div class="modal-content">
					<div class="modal-header">
						<h5 class="modal-title">{{ __('Add new Tax') }}</h5>
						<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
					</div>
					<div class="modal-body">
						@if ($shouldOpenTaxModal)
							<div class="notice notice-error">
								<p>{{ __( 'Please fix the errors below.' ) }}</p>
							</div>
						@endif

						<form class="needs-validation" novalidate method="post" action="{{ route('tenant.settings.taxes.store', ['business' => $tenant->slug]) }}">
							@csrf
							<div class="row g-3">
								<div class="col-md-6">
									<x-settings.field for="tax_name" :label="__('Tax Name')" class="wcrb-settings-field">
										<x-settings.input name="tax_name" id="tax_name" :required="true" :value="old('tax_name', '')" />
									</x-settings.field>
								</div>
								<div class="col-md-6">
									<x-settings.field for="tax_description" :label="__('Tax Description')" class="wcrb-settings-field">
										<x-settings.input name="tax_description" id="tax_description" :value="old('tax_description', '')" />
									</x-settings.field>
								</div>
								<div class="col-md-6">
									<x-settings.field for="tax_rate" :label="__('Tax Rate')" class="wcrb-settings-field">
										<x-settings.input name="tax_rate" id="tax_rate" type="number" step="0.001" min="0" max="100" :required="true" :value="old('tax_rate', '')" />
									</x-settings.field>
								</div>
								<div class="col-md-6">
									<x-settings.field for="tax_status" :label="__('Status')" class="wcrb-settings-field">
										<x-settings.select
											name="tax_status"
											id="tax_status"
											:options="['active' => __('Active'), 'inactive' => __('Inactive')]"
											:value="(string) old('tax_status', 'active')"
										/>
									</x-settings.field>
								</div>
							</div>
							<div class="mt-3 d-flex justify-content-end gap-2">
								<button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __( 'Cancel' ) }}</button>
								<button type="submit" class="btn btn-primary">{{ __( 'Add' ) }}</button>
							</div>
						</form>
					</div>
				</div>
			</div>
		</div>

		<div class="modal fade" id="wcrbEditTaxModal" tabindex="-1" aria-hidden="true">
			<div class="modal-dialog modal-lg">
				<div class="modal-content">
					<div class="modal-header">
						<h5 class="modal-title">{{ __('Edit Tax') }}</h5>
						<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
					</div>
					<div class="modal-body">
						@if ($shouldOpenEditTaxModal)
							<div class="notice notice-error">
								<p>{{ __( 'Please fix the errors below.' ) }}</p>
							</div>
						@endif

						<form
							class="needs-validation"
							novalidate
							method="post"
							id="wcrbEditTaxForm"
							data-action-template="{{ route('tenant.settings.taxes.update', ['business' => $tenant->slug, 'tax' => 999999]) }}"
							action="{{ route('tenant.settings.taxes.update', ['business' => $tenant->slug, 'tax' => 999999]) }}"
						>
							@csrf
							<input type="hidden" name="edit_tax_id" id="edit_tax_id" value="{{ old('edit_tax_id', '') }}" />
							<div class="row g-3">
								<div class="col-md-6">
									<x-settings.field for="edit_tax_name" :label="__('Tax Name')" class="wcrb-settings-field">
										<x-settings.input name="edit_tax_name" id="edit_tax_name" :required="true" :value="old('edit_tax_name', '')" />
									</x-settings.field>
								</div>
								<div class="col-md-6">
									<x-settings.field for="edit_tax_description" :label="__('Tax Description')" class="wcrb-settings-field">
										<x-settings.input name="edit_tax_description" id="edit_tax_description" :value="old('edit_tax_description', '')" />
									</x-settings.field>
								</div>
								<div class="col-md-6">
									<x-settings.field for="edit_tax_rate" :label="__('Tax Rate')" class="wcrb-settings-field">
										<x-settings.input name="edit_tax_rate" id="edit_tax_rate" type="number" step="0.001" min="0" max="100" :required="true" :value="old('edit_tax_rate', '')" />
									</x-settings.field>
								</div>
								<div class="col-md-6">
									<x-settings.field for="edit_tax_status" :label="__('Status')" class="wcrb-settings-field">
										<x-settings.select
											name="edit_tax_status"
											id="edit_tax_status"
											:options="['active' => __('Active'), 'inactive' => __('Inactive')]"
											:value="(string) old('edit_tax_status', 'active')"
										/>
									</x-settings.field>
								</div>
							</div>
							<div class="mt-3 d-flex justify-content-end gap-2">
								<button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __( 'Cancel' ) }}</button>
								<button type="submit" class="btn btn-primary">{{ __( 'Save' ) }}</button>
							</div>
						</form>
					</div>
				</div>
			</div>
		</div>

		<div class="wcrb-settings-form" style="max-width: none; width: 100%;">
			<div class="wcrb-settings-card">
				<p>
					<a class="button button-primary button-small" data-bs-toggle="modal" data-bs-target="#wcrbAddTaxModal">
					{{ __('Add Tax') }}
					</a>
				</p>

				<div class="wcrb-settings-card-body">
					<div class="grid-x grid-margin-x align-middle">
						<div class="cell auto">
							<h3 style="margin: 0;">{{ __('Taxes') }}</h3>
						</div>
					</div>

					<div style="margin-top: 0px;">
						<div class="table-responsive">
							<table class="table table-sm align-middle mb-0" style="--bs-table-cell-padding-y: .25rem; --bs-table-cell-padding-x: .5rem;">
								<thead class="bg-light">
									<tr>
										<th class="text-muted fw-semibold" style="width: 90px; text-transform: none;">{{ __('ID') }}</th>
										<th style="text-transform: none;">{{ __('Name') }}</th>
										<th style="width: 120px; text-transform: none;">{{ __('Rate') }}</th>
										<th style="width: 140px; text-transform: none;">{{ __('Status') }}</th>
										<th style="width: 140px; text-transform: none;">{{ __('Default') }}</th>
										<th class="text-end" style="width: 160px; text-transform: none;">{{ __('Actions') }}</th>
									</tr>
								</thead>
								<tbody>
									@if (($taxes ?? collect())->count() === 0)
										<tr class="border-top">
											<td colspan="6">{{ __('No taxes found.') }}</td>
										</tr>
									@else
										@foreach ($taxes as $t)
										<tr>
											<td>{{ (string) $t->id }}</td>
											<td><strong>{{ (string) $t->name }}</strong></td>
											@php
												$rateUi = rtrim(rtrim((string) $t->rate, '0'), '.');
											@endphp
											<td>{{ $rateUi }}%</td>
											<td>{{ $t->is_active ? __('Active') : __('Inactive') }}</td>
											<td>{{ $t->is_default ? __('Yes') : __('No') }}</td>
											<td class="text-end">
												<button
													type="button"
													class="btn btn-sm btn-outline-primary"
													data-bs-toggle="modal"
													data-bs-target="#wcrbEditTaxModal"
													data-tax-id="{{ (string) $t->id }}"
													data-tax-name="{{ (string) $t->name }}"
													data-tax-description="{{ (string) ($t->description ?? '') }}"
													data-tax-rate="{{ $rateUi }}"
													data-tax-status="{{ $t->is_active ? 'active' : 'inactive' }}"
												>
													{{ __('Edit') }}
												</button>
											</td>
										</tr>
									@endforeach
									@endif
								</tbody>
							</table>
						</div>
					</div>
				</div>
			</div>

			<div class="wcrb-settings-card">
				<div class="wcrb-settings-card-body">
					<h3 class="wcrb-settings-card-title">{{ __('Tax Settings') }}</h3>
					<form class="needs-validation" novalidate method="post" action="{{ route('tenant.settings.taxes.settings', ['business' => $tenant->slug]) }}">
						@csrf
						<div class="row g-3">
							<div class="col-12">
								<div class="wcrb-settings-option">
									<div class="wcrb-settings-option-head">
										<div class="wcrb-settings-option-control">
											<x-settings.toggle
												name="wc_use_taxes"
												id="wc_use_taxes"
												:checked="(bool) ($taxEnable ?? false)"
												value="on"
												uncheckedValue=""
											/>
										</div>
										<label for="wc_use_taxes" class="wcrb-settings-option-title">{{ __('Enable Taxes') }}</label>
									</div>
									<div class="wcrb-settings-option-description"></div>
								</div>
							</div>
							<div class="col-md-6">
								<x-settings.field for="wc_primary_tax" :label="__('Default Tax')" class="wcrb-settings-field">
									<x-settings.select
										name="wc_primary_tax"
										id="wc_primary_tax"
										:options="collect(($taxes ?? collect()))->mapWithKeys(fn ($t) => [(string) $t->id => (string) $t->name])->prepend(__('Select tax'), '')->all()"
										:value="$taxDefaultId !== null ? (string) $taxDefaultId : ''"
									/>
								</x-settings.field>
							</div>
							<div class="col-md-6">
								<x-settings.field for="wc_prices_inclu_exclu" :label="__('Invoice amount')" class="wcrb-settings-field">
									@php
										$amounts = (string) ($taxInvoiceAmounts ?? 'exclusive');
									@endphp
									<x-settings.select
										name="wc_prices_inclu_exclu"
										id="wc_prices_inclu_exclu"
										:options="['exclusive' => __('Exclusive'), 'inclusive' => __('Inclusive')]"
										:value="$amounts"
									/>
								</x-settings.field>
							</div>
						</div>
						<div class="wcrb-settings-actions" style="justify-content: flex-end; padding-top: 8px;">
							<button type="submit" class="button button-primary">{{ __('Update Options') }}</button>
						</div>
					</form>
				</div>
			</div>
		</div>

	</div>
</div>

@if ($shouldOpenTaxModal)
	<script>
		(function () {
			var el = document.getElementById('wcrbAddTaxModal');
			if (!el || typeof bootstrap === 'undefined' || !bootstrap.Modal) {
				return;
			}
			try {
				(new bootstrap.Modal(el)).show();
			} catch (e) {
				// no-op
			}
		})();
	</script>
@endif

@if ($shouldOpenEditTaxModal)
	<script>
		(function () {
			var el = document.getElementById('wcrbEditTaxModal');
			var form = document.getElementById('wcrbEditTaxForm');
			if (!el || !form || typeof bootstrap === 'undefined' || !bootstrap.Modal) {
				return;
			}

			var template = form.getAttribute('data-action-template') || form.getAttribute('action');
			var taxId = String(@json(old('edit_tax_id', '')) || '');
			if (template && taxId) {
				form.setAttribute('action', template.replace('999999', taxId));
			}

			try {
				(new bootstrap.Modal(el)).show();
			} catch (e) {
				// no-op
			}
		})();
	</script>
@endif

	<script>
		(function () {
			var editModalEl = document.getElementById('wcrbEditTaxModal');
			var editForm = document.getElementById('wcrbEditTaxForm');
			if (!editModalEl || !editForm) {
				return;
			}

			editModalEl.addEventListener('show.bs.modal', function (event) {
				var button = event.relatedTarget;
				if (!button) {
					return;
				}

				var taxId = button.getAttribute('data-tax-id') || '';
				var taxName = button.getAttribute('data-tax-name') || '';
				var taxDescription = button.getAttribute('data-tax-description') || '';
				var taxRate = button.getAttribute('data-tax-rate') || '';
				var taxStatus = button.getAttribute('data-tax-status') || 'active';

				var template = editForm.getAttribute('data-action-template') || editForm.getAttribute('action');
				if (template && taxId) {
					editForm.setAttribute('action', template.replace('999999', taxId));
				}

				var idEl = document.getElementById('edit_tax_id');
				var nameEl = document.getElementById('edit_tax_name');
				var descEl = document.getElementById('edit_tax_description');
				var rateEl = document.getElementById('edit_tax_rate');
				var statusEl = document.getElementById('edit_tax_status');

				if (idEl) idEl.value = taxId;
				if (nameEl) nameEl.value = taxName;
				if (descEl) descEl.value = taxDescription;
				if (rateEl) rateEl.value = taxRate;
				if (statusEl) statusEl.value = taxStatus;
			});
		})();
	</script>
