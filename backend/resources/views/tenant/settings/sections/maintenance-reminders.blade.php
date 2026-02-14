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
							<button type="button" class="button button-primary" data-bs-toggle="modal" data-bs-target="#wcrbAddMaintenanceReminderModal">{{ __('Add Reminder') }}</button>
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
														<button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#wcrbEditMaintenanceReminderModal_{{ (string) $r->id }}">{{ __('Edit') }}</button>
														<form method="post" action="{{ route('tenant.settings.maintenance_reminders.delete', ['business' => $tenant->slug, 'reminder' => $r->id]) }}" onsubmit="return confirm('{{ __('Delete this reminder?') }}');">
															@csrf
															<button type="submit" class="btn btn-sm btn-outline-danger">{{ __('Delete') }}</button>
														</form>
													</div>

													<div class="modal fade" id="wcrbEditMaintenanceReminderModal_{{ (string) $r->id }}" tabindex="-1" aria-hidden="true">
														<div class="modal-dialog modal-lg">
															<div class="modal-content">
																<div class="modal-header py-2 px-3">
																	<h5 class="modal-title">{{ __('Edit Reminder') }}</h5>
																	<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
																</div>
																<div class="modal-body p-3">
																	<form method="post" action="{{ route('tenant.settings.maintenance_reminders.update', ['business' => $tenant->slug, 'reminder' => $r->id]) }}">
																		@csrf
																		<p><label>{{ __('Name') }}<br><input type="text" name="name" value="{{ old('name', (string) $r->name) }}" class="regular-text"></label></p>
																		<p><label>{{ __('Interval days') }}<br><input type="number" name="interval_days" min="1" max="3650" value="{{ old('interval_days', (string) $r->interval_days) }}"></label></p>
																		<p><label>{{ __('Description') }}<br><input type="text" name="description" value="{{ old('description', (string) ($r->description ?? '')) }}" class="regular-text"></label></p>
																		<p><label>{{ __('Device Type') }}<br>
																			<select name="device_type_id" class="regular-text">
																				<option value="">{{ __('All') }}</option>
																				@foreach (($deviceTypesForMaintenance ?? collect()) as $dt)
																					<option value="{{ (string) $dt->id }}" {{ (string) old('device_type_id', (string) ($r->device_type_id ?? '')) === (string) $dt->id ? 'selected' : '' }}>{{ (string) $dt->name }}</option>
																				@endforeach
																			</select>
																		</label></p>
																		<p><label>{{ __('Brand') }}<br>
																			<select name="device_brand_id" class="regular-text">
																				<option value="">{{ __('All') }}</option>
																				@foreach (($deviceBrandsForMaintenance ?? collect()) as $db)
																					<option value="{{ (string) $db->id }}" {{ (string) old('device_brand_id', (string) ($r->device_brand_id ?? '')) === (string) $db->id ? 'selected' : '' }}>{{ (string) $db->name }}</option>
																				@endforeach
																			</select>
																		</label></p>
																		<p><label><input type="checkbox" name="email_enabled" {{ old('email_enabled') !== null ? 'checked' : ($r->email_enabled ? 'checked' : '') }}> {{ __('Email enabled') }}</label></p>
																		<p><label>{{ __('Email body') }}<br><textarea name="email_body" rows="4" class="large-text">{{ old('email_body', (string) ($r->email_body ?? '')) }}</textarea></label></p>
																		<p><label><input type="checkbox" name="sms_enabled" {{ old('sms_enabled') !== null ? 'checked' : ($r->sms_enabled ? 'checked' : '') }}> {{ __('SMS enabled') }}</label></p>
																		<p><label>{{ __('SMS body') }}<br><textarea name="sms_body" rows="3" class="large-text">{{ old('sms_body', (string) ($r->sms_body ?? '')) }}</textarea></label></p>
																		<p><label><input type="checkbox" name="reminder_enabled" {{ old('reminder_enabled') !== null ? 'checked' : ($r->reminder_enabled ? 'checked' : '') }}> {{ __('Reminder enabled') }}</label></p>
																		<div class="mt-2 d-flex justify-content-end gap-2">
																			<button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
																			<button type="submit" class="btn btn-primary">{{ __('Save') }}</button>
																		</div>
																	</form>
																</div>
															</div>
														</div>
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

		<div class="modal fade" id="wcrbAddMaintenanceReminderModal" tabindex="-1" aria-hidden="true">
			<div class="modal-dialog modal-lg">
				<div class="modal-content">
					<div class="modal-header py-2 px-3">
						<h5 class="modal-title">{{ __('Add New Maintenance Reminder') }}</h5>
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

						<form class="needs-validation" style="width: 100%;" novalidate method="post" action="{{ route('tenant.settings.maintenance_reminders.store', ['business' => $tenant->slug]) }}">
							@csrf
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
								<button type="submit" class="btn btn-primary">{{ __('Add Reminder') }}</button>
							</div>
						</form>
					</div>
				</div>
			</div>
		</div>

		<script>
			(function(){
				var hasErrors = {{ $errors->has('name') || $errors->has('interval_days') || $errors->has('description') || $errors->has('device_type_id') || $errors->has('device_brand_id') || $errors->has('email_body') || $errors->has('sms_body') || $errors->has('email_enabled') || $errors->has('sms_enabled') || $errors->has('reminder_enabled') ? 'true' : 'false' }};
				if (!hasErrors) { return; }
				try {
					if (window.bootstrap && bootstrap.Modal) {
						var modal = document.getElementById('wcrbAddMaintenanceReminderModal');
						if (modal) {
							bootstrap.Modal.getOrCreateInstance(modal).show();
						}
					}
				} catch (e) {
				}
			})();
		</script>
	</div>
</div>
