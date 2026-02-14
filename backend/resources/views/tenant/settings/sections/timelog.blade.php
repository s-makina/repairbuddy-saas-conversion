<div class="tabs-panel team-wrap" id="wcrb_timelog_tab" role="tabpanel" aria-hidden="true" aria-labelledby="wcrb_timelog_tab-label">
	<div class="wrap">
		<h2>{{ __('Time Log Settings') }}</h2>

		<div class="wc-rb-grey-bg-box">
			<form data-abide class="needs-validation" novalidate method="post" action="{{ route('tenant.settings.time_log.update', ['business' => $tenant->slug]) }}">
				@csrf

				<table class="form-table border">
					<tbody>
						<tr>
							<th scope="row"><label for="disable_timelog">{{ __('Disable Time Log Completely') }}</label></th>
							<td>
								@php
									$disabledChecked = old('disable_timelog') !== null
										? (old('disable_timelog') === 'on')
										: (bool) ($timeLogDisabledUi ?? false);
								@endphp
								<input type="checkbox" name="disable_timelog" id="disable_timelog" {{ $disabledChecked ? 'checked' : '' }} />
								<label for="disable_timelog">{{ __('Disable Time Log Completely') }}</label>
							</td>
						</tr>

						<tr>
							<th scope="row"><label for="default_tax_id">{{ __('Default tax for hours') }}</label></th>
							<td>
								@php
									$selectedTax = (string) old('default_tax_id', (string) ($timeLogDefaultTaxIdUi ?? ''));
								@endphp
								<select name="default_tax_id" id="default_tax_id" class="form-control">
									<option value="">{{ __('Select tax') }}</option>
									@foreach (($taxesForTimeLog ?? collect()) as $tax)
										<option value="{{ (string) $tax->id }}" {{ ($selectedTax !== '' && $selectedTax === (string) $tax->id) ? 'selected' : '' }}>{{ (string) $tax->name }} ({{ (string) $tax->rate }}%)</option>
									@endforeach
								</select>
							</td>
						</tr>

						<tr>
							<th scope="row">{{ __('Enable time log') }}</th>
							<td>
								@php
									$included = old('job_status_include', $timeLogIncludedStatusesUi ?? []);
									if (! is_array($included)) {
										$included = [];
									}
									$included = array_map('strval', $included);
								@endphp
								<fieldset class="fieldset">
									<legend>{{ __('Select job status to include') }}</legend>
									@foreach (($jobStatusesForTimeLog ?? collect()) as $st)
										@php
											$code = is_string($st->code) ? trim((string) $st->code) : '';
											if ($code === '') {
												continue;
											}
											$isChecked = in_array($code, $included, true);
										@endphp
										<label style="display:block" for="job_status_{{ $code }}">
											<input type="checkbox" id="job_status_{{ $code }}" name="job_status_include[]" value="{{ $code }}" {{ $isChecked ? 'checked' : '' }}> {{ (string) $st->label }}
										</label>
									@endforeach
									<p>{{ __('To make time log work make sure to create correct my account page in page settings.') }}</p>
								</fieldset>
							</td>
						</tr>

						<tr>
							<th scope="row"><label for="activities">{{ __('Time Log Activities') }}</label></th>
							<td>
								<fieldset class="fieldset">
									<legend>{{ __('Define activities for time log') }}</legend>
									<textarea name="activities" id="activities" rows="5" cols="50" class="large-text code">{{ old('activities', (string) ($timeLogActivitiesUi ?? '')) }}</textarea>
									<p>{{ __('Define activities for time log, one per line.') }}</p>
								</fieldset>
							</td>
						</tr>
					</tbody>
				</table>

				<button type="submit" class="button button-primary">{{ __('Update Options') }}</button>
			</form>
		</div>
	</div>
</div>
