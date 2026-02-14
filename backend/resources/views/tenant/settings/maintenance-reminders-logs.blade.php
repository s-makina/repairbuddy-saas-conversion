@extends('tenant.layouts.myaccount', ['title' => 'Reminders Log'])

@section('content')
	<main class="dashboard-content container-fluid py-4">
		<div class="d-flex justify-content-between align-items-center mb-3">
			<h3 class="mb-0">{{ __('Reminders Log') }}</h3>
			<a href="{{ route('tenant.settings', ['business' => $tenant->slug]) }}#wc_rb_maintenance_reminder" class="btn btn-outline-secondary">{{ __('Back to Device Reminders') }}</a>
		</div>

		<div class="card">
			<div class="card-body p-0">
				<div class="table-responsive">
					<table class="table table-striped table-hover align-middle mb-0">
						<thead class="table-light">
							<tr>
								<th>{{ __('Date') }}</th>
								<th>{{ __('Reminder') }}</th>
								<th>{{ __('Channel') }}</th>
								<th>{{ __('To') }}</th>
								<th>{{ __('Status') }}</th>
								<th>{{ __('Error') }}</th>
							</tr>
						</thead>
						<tbody>
							@if (!($logs ?? null) || $logs->count() === 0)
								<tr>
									<td colspan="6">{{ __('No logs yet.') }}</td>
								</tr>
							@else
								@foreach ($logs as $l)
									<tr>
										<td>{{ $l->created_at ? (string) $l->created_at : '-' }}</td>
										<td>{{ (string) ($l->reminder?->name ?? '-') }}</td>
										<td>{{ (string) ($l->channel ?? '-') }}</td>
										<td>{{ (string) ($l->to_address ?? '-') }}</td>
										<td>{{ (string) ($l->status ?? '-') }}</td>
										<td>{{ (string) ($l->error_message ?? '-') }}</td>
									</tr>
								@endforeach
							@endif
						</tbody>
					</table>
				</div>
			</div>
		</div>

		@if (($logs ?? null) && method_exists($logs, 'links'))
			<div class="mt-3">
				{{ $logs->links() }}
			</div>
		@endif
	</main>
@endsection
