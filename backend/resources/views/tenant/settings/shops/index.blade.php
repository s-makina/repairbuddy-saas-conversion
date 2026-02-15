@extends('tenant.layouts.myaccount', ['title' => $pageTitle ?? __('Shops')])

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

		<x-settings.card :title="__('Shops')">
			<div class="d-flex justify-content-end">
				<a class="btn btn-primary" href="{{ route('tenant.settings.shops.create', ['business' => $tenant->slug]) }}">{{ __('Add Shop') }}</a>
			</div>

			<div class="mt-3 table-responsive">
				<table class="table table-sm align-middle mb-0">
					<thead class="bg-light">
						<tr>
							<th style="width: 90px;">{{ __('ID') }}</th>
							<th>{{ __('Name') }}</th>
							<th style="width: 140px;">{{ __('Code') }}</th>
							<th style="width: 170px;">{{ __('Status') }}</th>
							<th style="width: 170px;">{{ __('Default') }}</th>
							<th class="text-end" style="width: 220px;">{{ __('Actions') }}</th>
						</tr>
					</thead>
					<tbody>
						@forelse(($branches ?? []) as $b)
							@php
								$editUrl = route('tenant.settings.shops.edit', ['business' => $tenant->slug, 'branch' => $b->id]);
								$activeUrl = route('tenant.settings.shops.active', ['business' => $tenant->slug, 'branch' => $b->id]);
								$defaultUrl = route('tenant.settings.shops.default', ['business' => $tenant->slug, 'branch' => $b->id]);
								$isDefault = (int) ($tenant->default_branch_id ?? 0) === (int) $b->id;
							@endphp
							<tr>
								<td>{{ (int) $b->id }}</td>
								<td class="fw-semibold">{{ (string) $b->name }}</td>
								<td>{{ (string) $b->code }}</td>
								<td>
									@if ($b->is_active)
										<span class="badge text-bg-success">{{ __('Active') }}</span>
									@else
										<span class="badge text-bg-secondary">{{ __('Inactive') }}</span>
									@endif
								</td>
								<td>
									@if ($isDefault)
										<span class="badge text-bg-primary">{{ __('Default') }}</span>
									@else
										<span class="text-muted">—</span>
									@endif
								</td>
								<td class="text-end">
									<div class="d-inline-flex gap-2">
										<a class="btn btn-sm btn-outline-primary" href="{{ $editUrl }}" title="{{ __('Edit') }}" aria-label="{{ __('Edit') }}">
											<i class="bi bi-pencil"></i>
										</a>

										@if (! $isDefault)
											<form method="post" action="{{ $defaultUrl }}">
												@csrf
												<button type="submit" class="btn btn-sm btn-outline-secondary" title="{{ __('Make default') }}" aria-label="{{ __('Make default') }}">
													<i class="bi bi-star"></i>
												</button>
											</form>
										@endif

										<form method="post" action="{{ $activeUrl }}">
											@csrf
											<input type="hidden" name="is_active" value="{{ $b->is_active ? 0 : 1 }}" />
											<button type="submit" class="btn btn-sm btn-outline-warning" title="{{ $b->is_active ? __('Deactivate') : __('Activate') }}" aria-label="{{ $b->is_active ? __('Deactivate') : __('Activate') }}">
												<i class="bi {{ $b->is_active ? 'bi-slash-circle' : 'bi-check-circle' }}"></i>
											</button>
										</form>
									</div>
								</td>
							</tr>
						@empty
							<tr>
								<td colspan="6">
									<div class="text-muted">{{ __('No shops yet.') }}</div>
								</td>
							</tr>
						@endforelse
					</tbody>
				</table>
			</div>
		</x-settings.card>
	</div>
@endsection
