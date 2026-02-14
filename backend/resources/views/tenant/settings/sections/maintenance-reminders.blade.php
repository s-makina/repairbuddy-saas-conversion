<div class="tabs-panel team-wrap" id="wc_rb_maintenance_reminder" role="tabpanel" aria-hidden="true" aria-labelledby="wc_rb_maintenance_reminder-label">
	<div class="wrap">
		<h2>{{ __('Device Reminders') }}</h2>
		<p>{{ __('Jobs should have delivery date set for reminders to work') }}</p>

		<div class="wcrb-settings-form" style="max-width: none; width: 100%;">
			<div class="wcrb-settings-card">
				<div class="wcrb-settings-card-body">
					<div class="grid-x grid-margin-x align-middle">
						<div class="cell auto">
							<h3 style="margin: 0;">{{ __('Existing Reminders') }}</h3>
						</div>
						<div class="cell shrink" style="text-align: right;">
							<div class="d-inline-flex align-items-center gap-2">
								<a href="{{ route('tenant.settings.maintenance_reminders.logs', ['business' => $tenant->slug]) }}" class="button button-secondary">{{ __('Reminders Log') }}</a>
								<button type="button" class="button button-primary" data-bs-toggle="modal" data-bs-target="#wcrbAddMaintenanceReminderModal" data-wcrb-reminder-mode="add">{{ __('Add Reminder') }}</button>
							</div>
						</div>
					</div>

					<div style="margin-top: 12px;">
						<div class="table-responsive">
							<table class="table table-striped table-hover align-middle mb-0">
								<thead>
									<tr>
										<th>{{ __('ID') }}</th>
										<th>{{ __('Name') }}</th>
										<th>{{ __('Interval') }}</th>
										<th>{{ __('Device Type') }}</th>
										<th>{{ __('Brand') }}</th>
										<th>{{ __('Email') }}</th>
										<th>{{ __('SMS') }}</th>
										<th>{{ __('Reminder') }}</th>
										<th>{{ __('Last Run') }}</th>
										<th class="text-end">{{ __('Actions') }}</th>
									</tr>
								</thead>
								<tbody>
									@if (($maintenanceReminders ?? collect())->count() === 0)
										<tr><td colspan="10">{{ __('No reminders yet.') }}</td></tr>
									@else
										@foreach ($maintenanceReminders as $r)
											<tr>
												<td>{{ (string) $r->id }}</td>
												<td><strong>{{ (string) $r->name }}</strong></td>
												<td>{{ (string) $r->interval_days }} {{ __('days') }}</td>
												<td>{{ (string) ($r->deviceType?->name ?? __('All')) }}</td>
												<td>{{ (string) ($r->deviceBrand?->name ?? __('All')) }}</td>
												<td>{{ $r->email_enabled ? __('Active') : __('Inactive') }}</td>
												<td>{{ $r->sms_enabled ? __('Active') : __('Inactive') }}</td>
												<td>{{ $r->reminder_enabled ? __('Active') : __('Inactive') }}</td>
												<td>{{ $r->last_executed_at ? (string) $r->last_executed_at : '-' }}</td>
												<td class="text-end">
													<div class="d-inline-flex align-items-center gap-2">
														<button
															type="button"
															class="btn btn-sm btn-outline-primary"
															data-bs-toggle="modal"
															data-bs-target="#wcrbAddMaintenanceReminderModal"
															data-wcrb-reminder-mode="edit"
															data-wcrb-reminder-id="{{ (string) $r->id }}"
															data-wcrb-update-action="{{ route('tenant.settings.maintenance_reminders.update', ['business' => $tenant->slug, 'reminder' => $r->id]) }}"
															data-wcrb-name="{{ (string) $r->name }}"
															data-wcrb-interval-days="{{ (string) $r->interval_days }}"
															data-wcrb-description="{{ (string) ($r->description ?? '') }}"
															data-wcrb-email-body="{{ (string) ($r->email_body ?? '') }}"
															data-wcrb-sms-body="{{ (string) ($r->sms_body ?? '') }}"
															data-wcrb-device-type-id="{{ (string) ($r->device_type_id ?? '') }}"
															data-wcrb-device-brand-id="{{ (string) ($r->device_brand_id ?? '') }}"
															data-wcrb-email-enabled="{{ $r->email_enabled ? 'active' : 'inactive' }}"
															data-wcrb-sms-enabled="{{ $r->sms_enabled ? 'active' : 'inactive' }}"
															data-wcrb-reminder-enabled="{{ $r->reminder_enabled ? 'active' : 'inactive' }}"
														>{{ __('Edit') }}</button>
														<form method="post" action="{{ route('tenant.settings.maintenance_reminders.delete', ['business' => $tenant->slug, 'reminder' => $r->id]) }}" onsubmit="return confirm('{{ __('Delete this reminder?') }}');">
															@csrf
															<button type="submit" class="btn btn-sm btn-outline-danger">{{ __('Delete') }}</button>
														</form>
													</div>
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
		</div>

		@php
			$isMaintenanceReminderEditMode = (string) old('form_mode', '') === 'edit' && (string) old('reminder_id', '') !== '';
			$maintenanceReminderFormAction = $isMaintenanceReminderEditMode
				? route('tenant.settings.maintenance_reminders.update', ['business' => $tenant->slug, 'reminder' => (string) old('reminder_id')])
				: route('tenant.settings.maintenance_reminders.store', ['business' => $tenant->slug]);
			$maintenanceReminderModalTitle = $isMaintenanceReminderEditMode ? __('Edit Maintenance Reminder') : __('Add New Maintenance Reminder');
			$maintenanceReminderSubmitLabel = $isMaintenanceReminderEditMode ? __('Save Reminder') : __('Add Reminder');
		@endphp
		<div class="modal fade" id="wcrbAddMaintenanceReminderModal" tabindex="-1" aria-hidden="true">
			<div class="modal-dialog modal-lg">
				<div class="modal-content">
					<div class="modal-header py-2 px-3">
						<h5 class="modal-title" id="wcrbMaintenanceReminderModalTitle">{{ $maintenanceReminderModalTitle }}</h5>
						<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
					</div>
					<div class="modal-body p-3">
						@php
							$maintenanceReminderErrorKeys = [
								'name',
								'interval_days',
								'description',
								'device_type_id',
								'device_brand_id',
								'email_body',
								'sms_body',
								'email_enabled',
								'sms_enabled',
								'reminder_enabled',
							];
							$maintenanceReminderErrors = collect($maintenanceReminderErrorKeys)
								->flatMap(fn ($k) => $errors->get($k))
								->filter(fn ($m) => is_string($m) && trim($m) !== '')
								->values();
						@endphp

						@if ($maintenanceReminderErrors->isNotEmpty())
							<div class="notice notice-error" style="margin-bottom: 12px;">
								<p>{{ __( 'Please fix the errors below.' ) }}</p>
								<ul style="margin: 6px 0 0 18px;">
									@foreach ($maintenanceReminderErrors as $msg)
										<li>{{ $msg }}</li>
									@endforeach
								</ul>
							</div>
						@endif

						<form class="needs-validation" style="width: 100%;" novalidate method="post" action="{{ $maintenanceReminderFormAction }}" id="wcrbMaintenanceReminderForm">
							@csrf
							<input type="hidden" name="form_mode" id="wcrb_mr_form_mode" value="{{ (string) old('form_mode', 'add') }}">
							<input type="hidden" name="reminder_id" id="wcrb_mr_reminder_id" value="{{ (string) old('reminder_id', '') }}">
							<div class="wcrb-settings-form" style="padding: 0; width: 100%;">
								<div class="wcrb-settings-card" style="margin-bottom: 0;">
									<div class="wcrb-settings-card-body" style="padding: 12px;">
										<div class="row g-2">
											<div class="col-md-6">
												<x-settings.field for="mr_name" :label="__('Reminder Name')" errorKey="name" class="wcrb-settings-field" style="margin-bottom: 6px;">
													<x-settings.input name="name" id="mr_name" :required="true" :value="old('name', '')" />
												</x-settings.field>
											</div>
											<div class="col-md-6">
												<x-settings.field for="mr_interval_days" :label="__('Run After')" errorKey="interval_days" class="wcrb-settings-field" style="margin-bottom: 6px;">
													<x-settings.select
														name="interval_days"
														id="mr_interval_days"
														:options="[ '7' => __('7 Days'), '30' => __('30 Days'), '90' => __('90 Days'), '365' => __('365 Days') ]"
														:value="(string) old('interval_days', '30')"
													/>
												</x-settings.field>
											</div>
											<div class="col-12">
												<x-settings.field for="mr_description" :label="__('Description')" errorKey="description" class="wcrb-settings-field" style="margin-bottom: 6px;">
													<x-settings.input name="description" id="mr_description" :value="old('description', '')" />
												</x-settings.field>
											</div>
											<div class="col-12">
												<x-settings.field for="mr_email_body" :label="__('Email Message')" :help="__('Keywords') . ': ' . '{' . '{device_name}' . '}' . ' ' . '{' . '{customer_name}' . '}' . ' ' . '{' . '{unsubscribe_device}' . '}'" errorKey="email_body" class="wcrb-settings-field" style="margin-bottom: 6px;">
													<x-settings.textarea name="email_body" id="mr_email_body" :rows="5" :value="old('email_body', '')" />
												</x-settings.field>
											</div>
											<div class="col-12">
												<x-settings.field for="mr_sms_body" :label="__('SMS Message')" :help="__('Keywords available to use') . ' ' . '{' . '{device_name}' . '}' . ' ' . '{' . '{customer_name}' . '}'" errorKey="sms_body" class="wcrb-settings-field" style="margin-bottom: 6px;">
													<x-settings.textarea name="sms_body" id="mr_sms_body" :rows="3" :value="old('sms_body', '')" />
												</x-settings.field>
											</div>
											<div class="col-md-6">
												<x-settings.field for="mr_device_type_id" :label="__('Device Type')" errorKey="device_type_id" class="wcrb-settings-field" style="margin-bottom: 6px;">
													<x-settings.select
														name="device_type_id"
														id="mr_device_type_id"
														:options="collect(($deviceTypesForMaintenance ?? collect()))->mapWithKeys(fn ($dt) => [(string) $dt->id => (string) $dt->name])->prepend(__('All'), '')->all()"
														:value="(string) old('device_type_id', '')"
													/>
												</x-settings.field>
											</div>
											<div class="col-md-6">
												<x-settings.field for="mr_device_brand_id" :label="__('Brand')" errorKey="device_brand_id" class="wcrb-settings-field" style="margin-bottom: 6px;">
													<x-settings.select
														name="device_brand_id"
														id="mr_device_brand_id"
														:options="collect(($deviceBrandsForMaintenance ?? collect()))->mapWithKeys(fn ($db) => [(string) $db->id => (string) $db->name])->prepend(__('All'), '')->all()"
														:value="(string) old('device_brand_id', '')"
													/>
												</x-settings.field>
											</div>
											<div class="col-md-4">
												<x-settings.field for="mr_email_enabled" :label="__('Activate Email Reminder*')" errorKey="email_enabled" class="wcrb-settings-field" style="margin-bottom: 6px;">
													<x-settings.select
														name="email_enabled"
														id="mr_email_enabled"
														required
														:options="[ 'active' => __('Active'), 'inactive' => __('Inactive') ]"
														:value="(string) old('email_enabled', 'active')"
													/>
												</x-settings.field>
											</div>
											<div class="col-md-4">
												<x-settings.field for="mr_sms_enabled" :label="__('Activate SMS Reminder*')" errorKey="sms_enabled" class="wcrb-settings-field" style="margin-bottom: 6px;">
													<x-settings.select
														name="sms_enabled"
														id="mr_sms_enabled"
														required
														:options="[ 'active' => __('Active'), 'inactive' => __('Inactive') ]"
														:value="(string) old('sms_enabled', 'inactive')"
													/>
												</x-settings.field>
											</div>
											<div class="col-md-4">
												<x-settings.field for="mr_reminder_enabled" :label="__('Reminder Status*')" errorKey="reminder_enabled" class="wcrb-settings-field" style="margin-bottom: 6px;">
													<x-settings.select
														name="reminder_enabled"
														id="mr_reminder_enabled"
														required
														:options="[ 'active' => __('Active'), 'inactive' => __('Inactive') ]"
														:value="(string) old('reminder_enabled', 'active')"
													/>
												</x-settings.field>
											</div>
										</div>
									</div>
								</div>
							</div>
							<div class="mt-2 d-flex justify-content-end gap-2">
								<button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
								<button type="submit" class="btn btn-primary" id="wcrbMaintenanceReminderSubmit">{{ $maintenanceReminderSubmitLabel }}</button>
							</div>
						</form>
					</div>
				</div>
			</div>
		</div>

		<script>
			(function(){
				var modal = document.getElementById('wcrbAddMaintenanceReminderModal');
				var form = document.getElementById('wcrbMaintenanceReminderForm');
				var titleEl = document.getElementById('wcrbMaintenanceReminderModalTitle');
				var submitEl = document.getElementById('wcrbMaintenanceReminderSubmit');
				var modeEl = document.getElementById('wcrb_mr_form_mode');
				var reminderIdEl = document.getElementById('wcrb_mr_reminder_id');
				if (!modal || !form) {
					return;
				}

				var setFieldValue = function (id, value) {
					var el = document.getElementById(id);
					if (!el) return;
					el.value = (value === null || value === undefined) ? '' : String(value);
				};

				var setAddMode = function () {
					if (modeEl) modeEl.value = 'add';
					if (reminderIdEl) reminderIdEl.value = '';
					form.setAttribute('action', @json(route('tenant.settings.maintenance_reminders.store', ['business' => $tenant->slug])));
					if (titleEl) titleEl.textContent = @json(__('Add New Maintenance Reminder'));
					if (submitEl) submitEl.textContent = @json(__('Add Reminder'));

					setFieldValue('mr_name', '');
					setFieldValue('mr_interval_days', '30');
					setFieldValue('mr_description', '');
					setFieldValue('mr_email_body', '');
					setFieldValue('mr_sms_body', '');
					setFieldValue('mr_device_type_id', '');
					setFieldValue('mr_device_brand_id', '');
					setFieldValue('mr_email_enabled', 'active');
					setFieldValue('mr_sms_enabled', 'inactive');
					setFieldValue('mr_reminder_enabled', 'active');
				};

				var setEditMode = function (btn) {
					if (!btn) return;
					var reminderId = btn.getAttribute('data-wcrb-reminder-id') || '';
					var action = btn.getAttribute('data-wcrb-update-action') || '';
					if (!reminderId || !action) {
						return;
					}

					if (modeEl) modeEl.value = 'edit';
					if (reminderIdEl) reminderIdEl.value = reminderId;
					form.setAttribute('action', action);
					if (titleEl) titleEl.textContent = @json(__('Edit Maintenance Reminder'));
					if (submitEl) submitEl.textContent = @json(__('Save Reminder'));

					setFieldValue('mr_name', btn.getAttribute('data-wcrb-name') || '');
					setFieldValue('mr_interval_days', btn.getAttribute('data-wcrb-interval-days') || '30');
					setFieldValue('mr_description', btn.getAttribute('data-wcrb-description') || '');
					setFieldValue('mr_email_body', btn.getAttribute('data-wcrb-email-body') || '');
					setFieldValue('mr_sms_body', btn.getAttribute('data-wcrb-sms-body') || '');
					setFieldValue('mr_device_type_id', btn.getAttribute('data-wcrb-device-type-id') || '');
					setFieldValue('mr_device_brand_id', btn.getAttribute('data-wcrb-device-brand-id') || '');
					setFieldValue('mr_email_enabled', btn.getAttribute('data-wcrb-email-enabled') || 'inactive');
					setFieldValue('mr_sms_enabled', btn.getAttribute('data-wcrb-sms-enabled') || 'inactive');
					setFieldValue('mr_reminder_enabled', btn.getAttribute('data-wcrb-reminder-enabled') || 'active');
				};

				modal.addEventListener('show.bs.modal', function (event) {
					var btn = event.relatedTarget;
					if (!btn) return;
					var mode = btn.getAttribute('data-wcrb-reminder-mode') || 'add';
					if (mode === 'edit') {
						setEditMode(btn);
					} else {
						setAddMode();
					}
				});

				var hasErrors = {{ $errors->has('name') || $errors->has('interval_days') || $errors->has('description') || $errors->has('device_type_id') || $errors->has('device_brand_id') || $errors->has('email_body') || $errors->has('sms_body') || $errors->has('email_enabled') || $errors->has('sms_enabled') || $errors->has('reminder_enabled') ? 'true' : 'false' }};
				if (!hasErrors) { return; }
				try {
					if (window.bootstrap && bootstrap.Modal) {
						bootstrap.Modal.getOrCreateInstance(modal).show();
					}
				} catch (e) {
				}
			})();
		</script>
	</div>
</div>
