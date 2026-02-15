@extends('tenant.layouts.myaccount', ['title' => $pageTitle ?? __('Manage Hourly Rates')])

@section('content')
	<div class="container-fluid p-3">
		@if (session('status'))
			<div class="notice notice-success">
				<p>{{ (string) session('status') }}</p>
			</div>
		@endif

		@if ($errors->any())
			<div class="notice notice-error">
				<p>{{ __( 'Please fix the errors below.' ) }}</p>
			</div>
		@endif

		<x-settings.card :title="__('Manage Hourly Rates')">
			<div class="mt-3 table-responsive">
				<table class="table table-sm align-middle mb-0">
					<thead class="bg-light">
						<tr>
							<th style="width: 90px;">{{ __('ID') }}</th>
							<th>{{ __('Name') }}</th>
							<th style="width: 180px;">{{ __('Role') }}</th>
							<th>{{ __('Email') }}</th>
							<th style="width: 160px;">{{ __('Tech Rate') }}</th>
							<th style="width: 160px;">{{ __('Client Rate') }}</th>
							<th class="text-end" style="width: 120px;">{{ __('Actions') }}</th>
						</tr>
					</thead>
					<tbody>
						@foreach (($staff ?? []) as $staffUser)
							@php
								$currencyCode = is_string($tenant->currency ?? null) && (string) $tenant->currency !== '' ? (string) $tenant->currency : 'USD';
								$techRate = is_numeric($staffUser->tech_hourly_rate_cents) ? number_format(((int) $staffUser->tech_hourly_rate_cents) / 100, 2, '.', '') : '';
								$clientRate = is_numeric($staffUser->client_hourly_rate_cents) ? number_format(((int) $staffUser->client_hourly_rate_cents) / 100, 2, '.', '') : '';
								$isTechnician = method_exists($staffUser, 'hasRole') ? (bool) $staffUser->hasRole('Technician') : false;
								$roleLabel = $isTechnician ? __('Technician') : (string) ($staffUser->role ?? '');
							@endphp

							<tr>
								<td>{{ (int) $staffUser->id }}</td>
								<td>{{ (string) ($staffUser->name ?? '') }}</td>
								<td>{{ $roleLabel }}</td>
								<td>{{ (string) ($staffUser->email ?? '') }}</td>
								<td colspan="3">
									<form method="post" action="{{ route('tenant.settings.hourly_rates.update', ['business' => $tenant->slug, 'user' => $staffUser->id]) }}">
										@csrf
										<div class="d-flex align-items-center gap-2 justify-content-end">
											<div style="width: 160px;">
												<div class="input-group input-group-sm">
													<span class="input-group-text">{{ $currencyCode }}</span>
													<input type="number" step="0.01" min="0" inputmode="decimal" name="tech_rate" value="{{ $techRate }}" class="form-control" placeholder="0.00" />
												</div>
											</div>
											<div style="width: 160px;">
												<div class="input-group input-group-sm">
													<span class="input-group-text">{{ $currencyCode }}</span>
													<input type="number" step="0.01" min="0" inputmode="decimal" name="client_rate" value="{{ $clientRate }}" class="form-control" placeholder="0.00" />
												</div>
											</div>
											<div class="text-end" style="width: 120px;">
												<button type="submit" class="btn btn-sm btn-outline-primary" title="{{ __('Update') }}" aria-label="{{ __('Update') }}">
													<i class="bi bi-save"></i>
												</button>
											</div>
										</div>
									</form>
								</td>
							</tr>
						@endforeach
					</tbody>
				</table>
			</div>
		</x-settings.card>
	</div>
@endsection
