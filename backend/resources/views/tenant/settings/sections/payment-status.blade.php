<div class="tabs-panel team-wrap" id="wc_rb_payment_status" role="tabpanel" aria-hidden="true" aria-labelledby="wc_rb_payment_status-label">
	<div class="wrap">
		<h2>{{ __( 'Payment Status' ) }}</h2>

		<p class="help-text">
			<a href="#" class="button button-primary button-small" data-bs-toggle="modal" data-bs-target="#paymentStatusFormModal" data-payment-status-mode="add">
				{{ __( 'Add New Payment Status' ) }}
			</a>
		</p>

		<div id="payment_status_wrapper">
			<table id="paymentStatus_poststuff" class="wp-list-table widefat fixed striped posts">
				<thead>
					<tr>
						<th class="column-id">{{ __( 'ID' ) }}</th>
						<th>{{ __( 'Name' ) }}</th>
						<th class="column-id">{{ __( 'Status' ) }}</th>
						<th class="column-id">{{ __( 'Actions' ) }}</th>
					</tr>
				</thead>
				<tbody>
					@forelse (($paymentStatuses ?? collect()) as $ps)
						<tr>
							<td class="column-id">{{ $ps->id }}</td>
							<td><strong>{{ $ps->label }}</strong></td>
							<td class="column-id">
								<form method="post" action="{{ route('tenant.settings.payment_status.toggle', ['business' => $tenant->slug, 'status' => $ps->id]) }}" style="display:inline;">
									@csrf
									<button type="submit" class="btn btn-link p-0 align-baseline" title="{{ __( 'Change Status' ) }}">
										{{ $ps->is_active ? __( 'Active' ) : __( 'Inactive' ) }}
									</button>
								</form>
							</td>
							<td class="column-id">
								<a href="#" class="text-decoration-none" data-bs-toggle="modal" data-bs-target="#paymentStatusFormModal" data-payment-status-mode="update" data-payment-status-id="{{ $ps->id }}" data-payment-status-name="{{ $ps->label }}" data-payment-status-active="{{ $ps->is_active ? 'active' : 'inactive' }}">
									{{ __( 'Edit' ) }}
								</a>
							</td>
						</tr>
					@empty
						<tr>
							<td colspan="4" class="text-center text-muted">{{ __( 'No payment statuses found.' ) }}</td>
						</tr>
					@endforelse
				</tbody>
			</table>
		</div>

		<div class="wc-rb-payment-methods" style="margin-top: 20px;">
			<h2>{{ __( 'Payment Methods' ) }}</h2>
			<div class="methods_success_msg"></div>
			<form data-abide class="needs-validation" novalidate method="post" action="{{ route('tenant.settings.payment_methods.update', ['business' => $tenant->slug]) }}">
				@csrf
				<fieldset class="fieldset">
					<legend>{{ __( 'Select Payment Methods' ) }}</legend>

					@php
						$defaultMethods = old('wc_rb_payment_method', is_array($paymentMethodsActive ?? null) ? $paymentMethodsActive : []);
						if (!is_array($defaultMethods)) {
							$defaultMethods = [];
						}
						$defaultMethods = collect($defaultMethods)
							->filter(fn ($v) => is_string($v) && trim($v) !== '')
							->map(fn ($v) => trim($v))
							->unique()
							->values()
							->all();
						$receiveArray = [
							['name' => 'cash', 'label' => __( 'Cash' ), 'description' => ''],
							['name' => 'card', 'label' => __( 'Card' ), 'description' => ''],
							['name' => 'bank', 'label' => __( 'Bank Transfer' ), 'description' => ''],
							['name' => 'woocommerce', 'label' => __( 'WooCommerce' ), 'description' => ''],
						];
					@endphp

					@foreach ($receiveArray as $m)
						@php
							$theName = (string) ($m['name'] ?? '');
							$theLabel = (string) ($m['label'] ?? '');
							$theDescription = (string) ($m['description'] ?? '');
							$checked = in_array($theName, $defaultMethods, true);
						@endphp
						@if ($theName !== '' && $theLabel !== '')
							<div class="wcrb-settings-option" style="padding: 10px 0; border-bottom: 0;">
								<div class="wcrb-settings-option-head" style="gap: 18px;">
									<div class="wcrb-settings-option-control" style="gap: 12px;">
										{!! view('components.settings.toggle', [
											'name' => 'wc_rb_payment_method[]',
											'id' => $theName,
											'checked' => $checked,
											'uncheckedValue' => '',
											'value' => $theName,
										])->render() !!}
									</div>
									<label for="{{ $theName }}" class="wcrb-settings-option-title">{{ $theLabel }}</label>
								</div>
								@if ($theDescription !== '')
									<p class="description">{{ $theDescription }}</p>
								@endif
							</div>
						@endif
					@endforeach
				</fieldset>

				<input type="hidden" name="form_type" value="wc_rb_update_methods_ac" />
				<button type="submit" class="button button-primary" data-type="rbsubmitmethods">{{ __( 'Update Methods' ) }}</button>
			</form>
		</div>

		<div class="modal fade" id="paymentStatusFormModal" tabindex="-1" aria-hidden="true">
			<div class="modal-dialog modal-md">
				<div class="modal-content">
					<div class="modal-header py-2 px-3">
						<h5 class="modal-title">{{ __( 'Add new' ) }} {{ __( 'Payment status' ) }}</h5>
						<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
					</div>
					<div class="modal-body p-3">
						<div class="form-message"></div>

						@if ($errors->any())
							<div class="notice notice-error">
								<p>{{ __( 'Please fix the errors below.' ) }}</p>
							</div>
						@endif

						<form class="needs-validation" novalidate method="post" action="{{ route('tenant.settings.payment_status.save', ['business' => $tenant->slug]) }}">
							@csrf

							<div class="row g-2">
								<div class="col-md-6">
									<x-settings.field for="payment_status_name" :label="__( 'Status Name' )" class="wcrb-settings-field">
										<x-settings.input name="payment_status_name" id="payment_status_name" :required="true" :value="old('payment_status_name', '')" />
									</x-settings.field>
								</div>

								<div class="col-md-6">
									<x-settings.field for="payment_status_status" :label="__( 'Status' )" class="wcrb-settings-field">
										<x-settings.select
											name="payment_status_status"
											id="payment_status_status"
											:options="['active' => __( 'Active' ), 'inactive' => __( 'Inactive' )]"
											:value="(string) old('payment_status_status', 'active')"
										/>
									</x-settings.field>
								</div>
							</div>

							<input name="form_type" type="hidden" value="payment_status_form" />
							<input id="payment_status_form_mode" name="form_type_status_payment" type="hidden" value="add" />
							<input id="payment_status_form_id" name="status_id" type="hidden" value="" />

							<div class="mt-2 d-flex justify-content-end gap-2">
								<button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __( 'Cancel' ) }}</button>
								<button class="btn btn-primary" type="submit" id="paymentStatusSubmitBtn">{{ __( 'Add new' ) }}</button>
							</div>
						</form>
					</div>
				</div>
			</div>
		</div>

		<script>
			(function(){
				var modal = document.getElementById('paymentStatusFormModal');
				if (!modal) { return; }

				var title = modal.querySelector('.modal-title');
				var btnPrimary = modal.querySelector('#paymentStatusSubmitBtn');
				var inputName = document.getElementById('payment_status_name');
				var selectStatus = document.getElementById('payment_status_status');
				var modeInput = document.getElementById('payment_status_form_mode');
				var idInput = document.getElementById('payment_status_form_id');

				var setMode = function(mode, data){
					var isUpdate = mode === 'update';
					if (title) {
						title.textContent = (isUpdate ? @json(__( 'Update' )) : @json(__( 'Add new' ))) + ' ' + @json(__( 'Payment status' ));
					}
					if (btnPrimary) {
						btnPrimary.textContent = isUpdate ? @json(__( 'Update' )) : @json(__( 'Add new' ));
					}
					if (modeInput) {
						modeInput.value = isUpdate ? 'update' : 'add';
					}
					if (idInput) {
						idInput.value = isUpdate ? (data.id || '') : '';
					}
					if (inputName) {
						inputName.value = isUpdate ? (data.name || '') : '';
					}
					if (selectStatus) {
						selectStatus.value = isUpdate ? (data.active || 'active') : 'active';
					}
				};

				modal.addEventListener('show.bs.modal', function (event) {
					var trigger = event && event.relatedTarget ? event.relatedTarget : null;
					if (!(trigger instanceof Element)) {
						setMode('add', {});
						return;
					}

					var mode = trigger.getAttribute('data-payment-status-mode') || 'add';
					setMode(mode, {
						id: trigger.getAttribute('data-payment-status-id'),
						name: trigger.getAttribute('data-payment-status-name'),
						active: trigger.getAttribute('data-payment-status-active')
					});
				});

				var hasErrors = {{ $errors->has('payment_status_name') || $errors->has('payment_status_status') || $errors->has('status_id') ? 'true' : 'false' }};
				if (hasErrors) {
					var mode = @json(old('form_type_status_payment', 'add'));
					if (mode !== 'update' && mode !== 'add') {
						mode = 'add';
					}
					setMode(mode, {
						id: @json(old('status_id', '')),
						name: @json(old('payment_status_name', '')),
						active: @json(old('payment_status_status', 'active')),
					});
					try {
						if (window.bootstrap && bootstrap.Modal) {
							bootstrap.Modal.getOrCreateInstance(modal).show();
						}
					} catch (e) {
					}
				}
			})();
		</script>
	</div>
</div>
