<div class="tabs-panel team-wrap" id="wcrb_timelog_tab" role="tabpanel" aria-hidden="true" aria-labelledby="wcrb_timelog_tab-label">
	<div class="wrap">
		<h2>{{ __('Time Log Settings') }}</h2>
		<style>
			#wcrb_timelog_tab .wcrb-chip-group{display:flex;flex-wrap:wrap;gap:8px;max-width:560px}
			#wcrb_timelog_tab .wcrb-chip{display:inline-flex;align-items:center;flex:0 0 auto;width:auto;max-width:100%;border:1px solid #d1d5db;border-radius:9999px;padding:6px 10px;background:#fff;cursor:pointer;user-select:none;line-height:1}
			#wcrb_timelog_tab .wcrb-chip input{position:absolute;opacity:0;pointer-events:none}
			#wcrb_timelog_tab .wcrb-chip.is-active{background:#eff6ff;border-color:#93c5fd;color:#1e3a8a}
			#wcrb_timelog_tab .wcrb-chip:focus-within{outline:2px solid rgba(59,130,246,.6);outline-offset:2px}
		</style>

		@php
			$timeLogDisabledChecked = (bool) old('disable_timelog', (bool) ($timeLogDisabledUi ?? false));
			$selectedTax = (string) old('default_tax_id', (string) ($timeLogDefaultTaxIdUi ?? ''));

			$included = old('job_status_include', $timeLogIncludedStatusesUi ?? []);
			if (! is_array($included)) {
				$included = [];
			}
			$included = array_values(array_filter(array_map('strval', $included), fn ($v) => trim((string) $v) !== ''));
		@endphp

		<form data-abide class="needs-validation" novalidate method="post" action="{{ route('tenant.settings.time_log.update', ['business' => $tenant->slug]) }}">
			@csrf
			<div class="wcrb-settings-form">
				<div class="wcrb-settings-card">
					<h3 class="wcrb-settings-card-title">{{ __('Time Log') }}</h3>
					<div class="wcrb-settings-card-body">
						<div class="row g-3">
							<div class="col-12">
								<div class="wcrb-settings-option">
									<div class="wcrb-settings-option-head">
										<div class="wcrb-settings-option-control">
											<x-settings.toggle
												name="disable_timelog"
												id="disable_timelog"
												:checked="$timeLogDisabledChecked"
												value="1"
												uncheckedValue="0"
											/>
										</div>
										<label for="disable_timelog" class="wcrb-settings-option-title">{{ __('Disable Time Log Completely') }}</label>
									</div>
									<div class="wcrb-settings-option-description"></div>
								</div>
							</div>

							<div class="col-md-6">
								<x-settings.field for="default_tax_id" :label="__('Default tax for hours')" errorKey="default_tax_id" class="wcrb-settings-field">
									<x-settings.select
										name="default_tax_id"
										id="default_tax_id"
										:options="collect(($taxesForTimeLog ?? collect()))
											->mapWithKeys(fn ($t) => [(string) $t->id => (string) $t->name . ' (' . (string) $t->rate . '%)'])
											->prepend(__('Select tax'), '')
											->all()"
										:value="$selectedTax"
									/>
								</x-settings.field>
							</div>

							<div class="col-12">
								<x-settings.field :label="__('Enable time log')" :help="__('Select job status to include')" class="wcrb-settings-field">
									@php
										$availableStatuses = collect(($jobStatusesForTimeLog ?? collect()))
											->filter(fn ($st) => is_string($st->code) && trim((string) $st->code) !== '')
											->values();
									@endphp

									@if ($availableStatuses->isEmpty())
										<p class="description">{{ __('No job statuses are available yet. Create job statuses first, then come back to enable Time Log for specific statuses.') }}</p>
									@else
										<div class="wcrb-settings-option">
											<div class="wcrb-chip-group" role="group" aria-label="{{ __('Included statuses') }}">
												@foreach ($availableStatuses as $st)
													@php
														$code = trim((string) $st->code);
														$label = (string) ($st->label ?? $code);
														$isChecked = ($code !== '') && in_array($code, $included, true);
													@endphp
													@if ($code !== '')
														<label class="wcrb-chip {{ $isChecked ? 'is-active' : '' }}">
															<input type="checkbox" class="wcrb-timelog-status" name="job_status_include[]" value="{{ $code }}" {{ $isChecked ? 'checked' : '' }}>
															<span>{{ $label }}</span>
														</label>
													@endif
												@endforeach
											</div>
										</div>
									@endif
									<p class="description" style="margin-top: 6px;">{{ __('To make time log work make sure to create correct my account page in page settings.') }}</p>
								</x-settings.field>
							</div>

							<div class="col-12">
								<x-settings.field for="activities" :label="__('Time Log Activities')" :help="__('Define activities for time log, one per line.')" errorKey="activities" class="wcrb-settings-field">
									<x-settings.textarea
										name="activities"
										id="activities"
										:rows="6"
										:value="old('activities', (string) ($timeLogActivitiesUi ?? ''))"
									/>
								</x-settings.field>
							</div>
						</div>
					</div>
				</div>

				<div class="grid-x grid-margin-x">
					<div class="cell small-12">
						<div class="wcrb-settings-actions">
							<button type="submit" class="button button-primary">{{ __('Update Options') }}</button>
						</div>
					</div>
				</div>
			</div>
		</form>
	</div>
</div>

<script>
	(function () {
		try {
			var inputs = document.querySelectorAll('#wcrb_timelog_tab input.wcrb-timelog-status');
			inputs.forEach(function (el) {
				var chip = el.closest ? el.closest('.wcrb-chip') : null;
				var syncChip = function () {
					if (!chip) return;
					if (el.checked) {
						chip.classList.add('is-active');
					} else {
						chip.classList.remove('is-active');
					}
				};
				syncChip();
				el.addEventListener('change', syncChip);
			});
		} catch (e) {
		}
	})();
</script>
