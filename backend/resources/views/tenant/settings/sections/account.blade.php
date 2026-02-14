<div class="tabs-panel team-wrap" id="wc_rb_manage_account" role="tabpanel" aria-hidden="true" aria-labelledby="wc_rb_manage_account-label">
	<div class="wrap">
		<h2>{{ __('My Account Settings') }}</h2>
		<p>{{ __('Configure account settings for customer registration and access control.') }}</p>

		<div class="wc-rb-grey-bg-box">
			<h3>{{ __('Account Settings') }}</h3>
			<form data-abide class="needs-validation" novalidate method="post" action="{{ route('tenant.settings.account.update', ['business' => $tenant->slug]) }}">
				@csrf
				<table class="form-table border">
					<tbody>
						<tr>
							<th scope="row"><label for="customer_registration">{{ __('Customer Registration') }}</label></th>
							<td>
								<input type="checkbox" name="customer_registration" id="customer_registration" {{ ($customerRegistrationUi ?? false) ? 'checked' : '' }} />
								{{ __('Allow customers to register accounts') }}
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="account_approval_required">{{ __('Account Approval Required') }}</label></th>
							<td>
								<input type="checkbox" name="account_approval_required" id="account_approval_required" {{ ($accountApprovalRequiredUi ?? false) ? 'checked' : '' }} />
								{{ __('Require admin approval for new accounts') }}
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="default_customer_role">{{ __('Default Customer Role') }}</label></th>
							<td>
								@php
									$role = (string) old('default_customer_role', (string) ($defaultCustomerRoleUi ?? 'customer'));
								@endphp
								<select name="default_customer_role" id="default_customer_role" class="form-control">
									<option value="customer" {{ $role === 'customer' ? 'selected' : '' }}>{{ __('Customer') }}</option>
									<option value="vip_customer" {{ $role === 'vip_customer' ? 'selected' : '' }}>{{ __('VIP Customer') }}</option>
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
