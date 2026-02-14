<div class="tabs-panel team-wrap" id="wc_rb_page_sms_IDENTIFIER" role="tabpanel" aria-hidden="true" aria-labelledby="wc_rb_page_sms_IDENTIFIER-label">
	<div class="wrap">
		<h2>{{ __('SMS') }}</h2>
		<p>{{ __('Configure SMS notifications for your repair shop. You will need to configure an SMS gateway service.') }}</p>

		<div class="wc-rb-grey-bg-box">
			<h3>{{ __('SMS Settings') }}</h3>
			<style>
				#wc_rb_page_sms_IDENTIFIER .wcrb-chip-group{display:flex;flex-wrap:wrap;gap:8px;max-width:560px}
				#wc_rb_page_sms_IDENTIFIER .wcrb-chip{display:inline-flex;align-items:center;flex:0 0 auto;width:auto;max-width:100%;border:1px solid #d1d5db;border-radius:9999px;padding:6px 10px;background:#fff;cursor:pointer;user-select:none;line-height:1}
				#wc_rb_page_sms_IDENTIFIER .wcrb-chip input{position:absolute;opacity:0;pointer-events:none}
				#wc_rb_page_sms_IDENTIFIER .wcrb-chip.is-active{background:#eff6ff;border-color:#93c5fd;color:#1e3a8a}
				#wc_rb_page_sms_IDENTIFIER .wcrb-chip:focus-within{outline:2px solid rgba(59,130,246,.6);outline-offset:2px}
				#wc_rb_page_sms_IDENTIFIER .wcrb-inline-errors{margin:0 0 14px 0;padding:10px 12px;border:1px solid #fecaca;background:#fff1f2;border-radius:10px}
				#wc_rb_page_sms_IDENTIFIER .wcrb-inline-errors ul{margin:6px 0 0 18px}
				#wc_rb_page_sms_IDENTIFIER .wcrb-muted{color:#6b7280}
			</style>

			<div class="grid-x grid-margin-x" style="align-items: flex-start;">
				<div class="cell large-7 medium-12 small-12">
					<form data-abide class="needs-validation" novalidate method="post" action="{{ route('tenant.settings.sms.update', ['business' => $tenant->slug]) }}">
						@csrf
						<input type="hidden" name="sms_settings_form" value="1">
						<input type="hidden" name="wc_rb_job_status_include_present" value="1">

						@if ($errors->any())
							<div class="wcrb-inline-errors" role="alert" aria-live="polite">
								<strong>{{ __('Please fix the errors below and try again.') }}</strong>
								<ul>
									@foreach ($errors->all() as $error)
										<li>{{ $error }}</li>
									@endforeach
								</ul>
							</div>
						@endif

						<div class="wcrb-settings-form">
							<div class="wcrb-settings-card">
								<h3 class="wcrb-settings-card-title">{{ __('Activation') }}</h3>
								<div class="wcrb-settings-card-body">
									<div class="wcrb-settings-option">
										<div class="wcrb-settings-option-head">
											<div class="wcrb-settings-option-control">
												<input type="checkbox" name="wc_rb_sms_active" id="wc_rb_sms_active" value="YES" {{ $smsActiveUi ? 'checked' : '' }} class="wcrb-settings-toggle" />
											</div>
											<label for="wc_rb_sms_active" class="wcrb-settings-option-title">{{ __('Activate SMS for selective statuses') }}</label>
										</div>
										<p class="description">{{ __('When enabled, SMS will be sent only for the statuses selected below.') }}</p>
									</div>
								</div>
							</div>

							<div class="wcrb-settings-card">
								<h3 class="wcrb-settings-card-title">{{ __('Gateway') }}</h3>
								<div class="wcrb-settings-card-body">
									<div class="grid-x grid-margin-x">
										<div class="cell medium-8 small-12">
											<x-settings.field for="wc_rb_sms_gateway" :label="__('Select SMS Gateway')" class="wcrb-settings-field">
												<x-settings.select
													name="wc_rb_sms_gateway"
													id="wc_rb_sms_gateway"
													:options="($smsGatewayOptions ?? [])"
													:value="(string) old('wc_rb_sms_gateway', $smsGatewayUi)"
												/>
											</x-settings.field>
											<p class="description wcrb-muted" style="margin-top:6px;">
												{{ __('Choose a provider, then add its credentials below. These are stored in your business settings.') }}
											</p>
										</div>
									</div>

									@php
										$twilioStyle = ((string) ($smsGatewayUi ?? '') === 'twilio') ? '' : 'display:none;';
									@endphp
									<div class="grid-x grid-margin-x sms-gateway-fields" data-gateway="twilio" style="{{ $twilioStyle }}">
										<div class="cell medium-6 small-12">
											<x-settings.field for="sms_gateway_account_sid" :label="__('Account SID')" class="wcrb-settings-field">
												<x-settings.input name="sms_gateway_account_sid" id="sms_gateway_account_sid" :value="old('sms_gateway_account_sid', $smsGatewayAccountSidUi)" type="text" />
											</x-settings.field>
										</div>
										<div class="cell medium-6 small-12">
											<x-settings.field for="sms_gateway_auth_token" :label="__('Auth Token')" class="wcrb-settings-field">
												<x-settings.input name="sms_gateway_auth_token" id="sms_gateway_auth_token" :value="old('sms_gateway_auth_token', $smsGatewayAuthTokenUi)" type="password" />
											</x-settings.field>
										</div>
										<div class="cell medium-6 small-12">
											<x-settings.field for="sms_gateway_from_number" :label="__('From Number')" class="wcrb-settings-field">
												<x-settings.input name="sms_gateway_from_number" id="sms_gateway_from_number" :value="old('sms_gateway_from_number', $smsGatewayFromNumberUi)" type="text" />
											</x-settings.field>
										</div>
										<div class="cell small-12">
											<p class="description wcrb-muted" style="margin-top:2px;">
												{{ __('Tip: Use E.164 format for phone numbers, e.g. +15551234567') }}
											</p>
										</div>
									</div>
								</div>
							</div>

							<div class="wcrb-settings-card">
								<h3 class="wcrb-settings-card-title">{{ __('Statuses') }}</h3>
								<div class="wcrb-settings-card-body">
									<x-settings.field for="wc_rb_job_status_include" :label="__('Send message when status changed to')" class="wcrb-settings-field" />
									<div class="wcrb-settings-option">
										<div class="wcrb-chip-group" role="group" aria-label="{{ __('Included statuses') }}">
										@foreach ($allJobStatuses as $s)
											@php
												$code = (string) ($s->code ?? '');
												$label = (string) ($s->label ?? $code);
												$checked = is_string($code) && $code !== '' && in_array($code, $smsSendWhenStatusChangedToIdsUi ?? [], true);
											@endphp
											@if ($code !== '')
												<label class="wcrb-chip {{ $checked ? 'is-active' : '' }}">
													<input type="checkbox" class="wcrb-sms-status" name="wc_rb_job_status_include[]" value="{{ $code }}" {{ $checked ? 'checked' : '' }}>
													<span>{{ $label }}</span>
												</label>
											@endif
										@endforeach
										</div>
									</div>

									<p class="description">{{ __('To make SMS working do not forget to add message in status message field by editing the status.') }}</p>
									<p class="description wcrb-muted" style="margin-top:6px;">{{ __('If you select no statuses, no automatic SMS will be sent even if SMS is enabled.') }}</p>
								</div>
							</div>

							<div class="wcrb-settings-actions">
								<button type="submit" class="button button-primary">{{ __('Save SMS Settings') }}</button>
							</div>
						</div>
					</form>

					@php
						$gw = (string) ($smsGatewayUi ?? '');
						$testDisabled = ($gw === '');
					@endphp
					<div class="wcrb-settings-card" style="margin-top: 18px;">
						<h3 class="wcrb-settings-card-title">{{ __('Test SMS') }}</h3>
						<div class="wcrb-settings-card-body">
							<form data-abide class="needs-validation" novalidate method="post" action="{{ route('tenant.settings.sms.update', ['business' => $tenant->slug]) }}">
								@csrf
								<input type="hidden" name="sms_test" value="1">
								@if ($testDisabled)
									<div class="notice notice-warning" style="margin-bottom: 12px;">
										<p>{{ __('Select and save an SMS gateway first to enable test sending.') }}</p>
									</div>
								@endif

								<div class="grid-x grid-margin-x">
									<div class="cell small-12">
										<x-settings.field for="sms_test_number" :label="__('Phone number')" class="wcrb-settings-field">
											<x-settings.input name="sms_test_number" id="sms_test_number" :value="old('sms_test_number', $smsTestNumberUi)" type="text" {{ $testDisabled ? 'disabled' : '' }} />
										</x-settings.field>
									</div>
								</div>
								<div class="grid-x grid-margin-x">
									<div class="cell small-12">
										<x-settings.field for="sms_test_message" :label="__('Message')" class="wcrb-settings-field">
											<x-settings.textarea name="sms_test_message" id="sms_test_message" rows="4" :value="old('sms_test_message', $smsTestMessageUi)" {{ $testDisabled ? 'disabled' : '' }} />
										</x-settings.field>
									</div>
								</div>

								<div class="wcrb-settings-actions">
									<button type="submit" class="button button-primary" {{ $testDisabled ? 'disabled' : '' }}>{{ __('Send Message') }}</button>
								</div>
							</form>
						</div>
					</div>
				</div>

				<div class="cell large-5 medium-12 small-12">
					<div class="wcrb-settings-card" style="position: sticky; top: 16px;">
						<h3 class="wcrb-settings-card-title">{{ __('Help & Preview') }}</h3>
						<div class="wcrb-settings-card-body">
							<div class="wcrb-settings-option">
								<strong>{{ __('Selected gateway') }}:</strong>
								<span id="sms-preview-gateway" data-placeholder="{{ __('Not set') }}">{{ (string) ($smsGatewayUi ?? '') ?: __('Not set') }}</span>
							</div>

							<div class="wcrb-settings-option" style="margin-top: 10px;">
								<strong>{{ __('Included statuses') }}:</strong>
								<span id="sms-preview-status-count">{{ is_array($smsSendWhenStatusChangedToIdsUi ?? null) ? count($smsSendWhenStatusChangedToIdsUi) : 0 }}</span>
							</div>

							<div class="wcrb-settings-option" style="margin-top: 10px;">
								<strong>{{ __('Sample SMS') }}:</strong>
								<div id="sms-preview-message" style="white-space: pre-wrap; margin-top: 6px; padding: 10px; border: 1px solid #e5e7eb; border-radius: 8px; background: #fff;">
									{{ (string) ($smsTestMessageUi ?? '') ?: __('Type a test message on the left to preview it here.') }}
								</div>
							</div>

							<hr>
							<p class="description">{{ __('Tip: If you enabled SMS for a status but customers are not receiving messages, verify that the status has an SMS message template configured.') }}</p>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

<script>
	(function () {
		try {
			var sel = document.getElementById('wc_rb_sms_gateway');
			if (!sel) {
				return;
			}
			var toggle = function () {
				var gw = sel.value || '';
				var rows = document.querySelectorAll('#wc_rb_page_sms_IDENTIFIER .sms-gateway-fields');
				rows.forEach(function (r) {
					var show = (r.getAttribute('data-gateway') === gw);
					r.style.display = show ? '' : 'none';
				});
				var gatewayPreview = document.getElementById('sms-preview-gateway');
				if (gatewayPreview) {
					var placeholder = gatewayPreview.getAttribute('data-placeholder') || 'Not set';
					var selectedText = '';
					try {
						selectedText = sel.options && sel.selectedIndex >= 0 ? (sel.options[sel.selectedIndex].text || '') : '';
					} catch (e) {
						selectedText = '';
					}
					gatewayPreview.textContent = (gw && selectedText) ? selectedText : placeholder;
				}
			};
			sel.addEventListener('change', toggle);

			var updateStatusCount = function () {
				var countEl = document.getElementById('sms-preview-status-count');
				if (!countEl) {
					return;
				}
				var checked = document.querySelectorAll('#wc_rb_page_sms_IDENTIFIER input.wcrb-sms-status:checked');
				countEl.textContent = String(checked ? checked.length : 0);
			};
			var statusInputs = document.querySelectorAll('#wc_rb_page_sms_IDENTIFIER input.wcrb-sms-status');
			statusInputs.forEach(function (el) {
				var chip = el.closest ? el.closest('.wcrb-chip') : null;
				var syncChip = function () {
					if (!chip) {
						return;
					}
					if (el.checked) {
						chip.classList.add('is-active');
					} else {
						chip.classList.remove('is-active');
					}
				};
				syncChip();
				el.addEventListener('change', updateStatusCount);
				el.addEventListener('change', syncChip);
			});

			var msg = document.getElementById('sms_test_message');
			var msgPreview = document.getElementById('sms-preview-message');
			if (msg && msgPreview) {
				var syncMessage = function () {
					var v = msg.value || '';
					msgPreview.textContent = v || 'Type a test message on the left to preview it here.';
				};
				msg.addEventListener('input', syncMessage);
				syncMessage();
			}

			toggle();
			updateStatusCount();
		} catch (e) {
		}
	})();
</script>
