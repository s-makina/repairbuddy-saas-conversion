<div class="tabs-panel team-wrap" id="wc_rb_maintenance_reminder" role="tabpanel" aria-hidden="true" aria-labelledby="wc_rb_maintenance_reminder-label">
	<div class="wrap">
		<h2>{{ __('Maintenance Reminders') }}</h2>
		<p>{{ __('Jobs should have delivery date set for reminders to work') }}</p>

		<div class="wc-rb-grey-bg-box">
			<h3>{{ __('Add New Maintenance Reminder') }}</h3>
			<form method="post" action="{{ route('tenant.settings.maintenance_reminders.store', ['business' => $tenant->slug]) }}">
				@csrf
				<table class="form-table border">
					<tbody>
						<tr>
							<th scope="row"><label for="mr_name">{{ __('Reminder Name') }}</label></th>
							<td><input id="mr_name" type="text" name="name" class="regular-text" value="{{ old('name', '') }}"></td>
						</tr>
						<tr>
							<th scope="row"><label for="mr_interval_days">{{ __('Run After (days)') }}</label></th>
							<td><input id="mr_interval_days" type="number" name="interval_days" min="1" max="3650" value="{{ old('interval_days', '30') }}"></td>
						</tr>
						<tr>
							<th scope="row"><label for="mr_description">{{ __('Description') }}</label></th>
							<td><input id="mr_description" type="text" name="description" class="regular-text" value="{{ old('description', '') }}"></td>
						</tr>
						<tr>
							<th scope="row"><label for="mr_device_type_id">{{ __('Device Type') }}</label></th>
							<td>
								<select id="mr_device_type_id" name="device_type_id" class="regular-text">
									<option value="">{{ __('All') }}</option>
									@foreach (($deviceTypesForMaintenance ?? collect()) as $dt)
										<option value="{{ (string) $dt->id }}" {{ (string) old('device_type_id', '') === (string) $dt->id ? 'selected' : '' }}>{{ (string) $dt->name }}</option>
									@endforeach
								</select>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="mr_device_brand_id">{{ __('Brand') }}</label></th>
							<td>
								<select id="mr_device_brand_id" name="device_brand_id" class="regular-text">
									<option value="">{{ __('All') }}</option>
									@foreach (($deviceBrandsForMaintenance ?? collect()) as $db)
										<option value="{{ (string) $db->id }}" {{ (string) old('device_brand_id', '') === (string) $db->id ? 'selected' : '' }}>{{ (string) $db->name }}</option>
									@endforeach
								</select>
							</td>
						</tr>
						<tr>
							<th scope="row">{{ __('Email') }}</th>
							<td><label><input type="checkbox" name="email_enabled" {{ old('email_enabled') ? 'checked' : '' }}> {{ __('Enable') }}</label></td>
						</tr>
						<tr>
							<th scope="row"><label for="mr_email_body">{{ __('Email Message') }}</label></th>
							<td>
								<textarea id="mr_email_body" name="email_body" rows="5" class="large-text">{{ old('email_body', '') }}</textarea>
								<p class="description">{{ __('Keywords') }}: {{ '{' . '{device_name}' . '}' }} {{ '{' . '{customer_name}' . '}' }} {{ '{' . '{unsubscribe_device}' . '}' }}</p>
							</td>
						</tr>
						<tr>
							<th scope="row">{{ __('SMS') }}</th>
							<td><label><input type="checkbox" name="sms_enabled" {{ old('sms_enabled') ? 'checked' : '' }}> {{ __('Enable') }}</label></td>
						</tr>
						<tr>
							<th scope="row"><label for="mr_sms_body">{{ __('SMS Message') }}</label></th>
							<td>
								<textarea id="mr_sms_body" name="sms_body" rows="3" class="large-text">{{ old('sms_body', '') }}</textarea>
								<p class="description">{{ __('Keywords') }}: {{ '{' . '{device_name}' . '}' }} {{ '{' . '{customer_name}' . '}' }} {{ '{' . '{unsubscribe_device}' . '}' }}</p>
							</td>
						</tr>
						<tr>
							<th scope="row">{{ __('Reminder') }}</th>
							<td><label><input type="checkbox" name="reminder_enabled" {{ old('reminder_enabled', 'on') ? 'checked' : '' }}> {{ __('Active') }}</label></td>
						</tr>
					</tbody>
				</table>
				<button type="submit" class="button button-primary">{{ __('Add Reminder') }}</button>
			</form>
		</div>

		<div class="wc-rb-grey-bg-box">
			<h3>{{ __('Existing Reminders') }}</h3>
			<table class="wp-list-table widefat fixed striped posts">
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
						<th>{{ __('Actions') }}</th>
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
								<td>
									<details>
										<summary>{{ __('Edit') }}</summary>
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
											<p><button type="submit" class="button button-primary button-small">{{ __('Save') }}</button></p>
										</form>
										<form method="post" action="{{ route('tenant.settings.maintenance_reminders.delete', ['business' => $tenant->slug, 'reminder' => $r->id]) }}" onsubmit="return confirm('{{ __('Delete this reminder?') }}');">
											@csrf
											<button type="submit" class="button button-secondary button-small">{{ __('Delete') }}</button>
										</form>
									</details>
								</td>
							</tr>
						@endforeach
					@endif
				</tbody>
			</table>
		</div>
	</div>
</div>
