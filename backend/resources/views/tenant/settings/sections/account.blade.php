<div class="tabs-panel team-wrap" id="wc_rb_manage_account" role="tabpanel" aria-hidden="true" aria-labelledby="wc_rb_manage_account-label">
	<div class="wrap">
		<h2>{{ __('My Account Settings') }}</h2>
		<p>{{ __('Configure account settings for customer registration and access control.') }}</p>

		@php
			$customerRegistrationChecked = (string) old('customer_registration', ($customerRegistrationUi ?? false) ? 'on' : 'off') === 'on';
			$accountApprovalRequiredChecked = (string) old('account_approval_required', ($accountApprovalRequiredUi ?? false) ? 'on' : 'off') === 'on';
			$role = (string) old('default_customer_role', (string) ($defaultCustomerRoleUi ?? 'customer'));
			$roleOptions = is_array($customerRoleOptionsUi ?? null)
				? $customerRoleOptionsUi
				: [
					'customer' => __('Customer'),
					'vip_customer' => __('VIP Customer'),
				];
		@endphp

		<form data-abide class="needs-validation" novalidate method="post" action="{{ route('tenant.settings.account.update', ['business' => $tenant->slug]) }}">
			@csrf
			<div class="wcrb-settings-form">
				<div class="wcrb-settings-card">
					<h3 class="wcrb-settings-card-title">{{ __('Account Settings') }}</h3>
					<div class="wcrb-settings-card-body">
						<div class="row g-3">
							<div class="col-12">
								<div class="wcrb-settings-option" style="border-bottom: 0; padding-bottom: 6px; margin-bottom: 6px;">
									<div class="wcrb-settings-option-head">
										<div class="wcrb-settings-option-control">
											<x-settings.toggle
												name="customer_registration"
												id="customer_registration"
												:checked="$customerRegistrationChecked"
												value="on"
												uncheckedValue="off"
											/>
										</div>
										<label for="customer_registration" class="wcrb-settings-option-title">{{ __('Customer Registration') }}</label>
									</div>
									<div class="wcrb-settings-option-description">{{ __('Allow customers to register accounts') }}</div>
								</div>
							</div>

							<div class="col-12">
								<div class="wcrb-settings-option" style="border-bottom: 0; padding-bottom: 6px; margin-bottom: 6px;">
									<div class="wcrb-settings-option-head">
										<div class="wcrb-settings-option-control">
											<x-settings.toggle
												name="account_approval_required"
												id="account_approval_required"
												:checked="$accountApprovalRequiredChecked"
												value="on"
												uncheckedValue="off"
											/>
										</div>
										<label for="account_approval_required" class="wcrb-settings-option-title">{{ __('Account Approval Required') }}</label>
									</div>
									<div class="wcrb-settings-option-description">{{ __('Require admin approval for new accounts') }}</div>
								</div>
							</div>

							<div class="col-md-6">
								<x-settings.field for="default_customer_role" :label="__('Default Customer Role')" errorKey="default_customer_role" class="wcrb-settings-field">
									<x-settings.select
										name="default_customer_role"
										id="default_customer_role"
										:options="$roleOptions"
										:value="$role"
									/>
								</x-settings.field>
							</div>
						</div>
						<div class="wcrb-settings-actions" style="justify-content: flex-end; padding-top: 8px;">
							<button type="submit" class="button button-primary">{{ __('Update Options') }}</button>
						</div>
					</div>
				</div>
			</div>
		</form>
	</div>
</div>
