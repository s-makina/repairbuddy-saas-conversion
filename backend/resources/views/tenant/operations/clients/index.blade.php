@extends('tenant.layouts.myaccount', ['title' => $pageTitle ?? __('Clients')])

@push('page-styles')
<link rel="stylesheet" href="https://cdn.datatables.net/2.1.8/css/dataTables.bootstrap5.min.css" />
@endpush

@push('page-scripts')
<script src="https://cdn.datatables.net/2.1.8/js/dataTables.min.js"></script>
<script src="https://cdn.datatables.net/2.1.8/js/dataTables.bootstrap5.min.js"></script>
<script>
  (function () {
    if (!window.jQuery || !window.jQuery.fn || !window.jQuery.fn.DataTable) {
      return;
    }

    var $table = window.jQuery('#clientsTable');
    if ($table.length === 0) {
      return;
    }

    if (window.jQuery.fn.DataTable.isDataTable($table)) {
      return;
    }

    $table.DataTable({
      processing: true,
      serverSide: true,
      pageLength: 25,
      ajax: "{{ route('tenant.operations.clients.datatable', ['business' => $tenant->slug]) }}",
      order: [[0, 'desc']],
      columns: [
        { data: 'id', name: 'id', width: '90px' },
        { data: 'first_name', name: 'first_name', width: '160px' },
        { data: 'last_name', name: 'last_name', width: '180px' },
        { data: 'email', name: 'email' },
        { data: 'phone_display', name: 'phone', width: '160px', orderable: false },
        { data: 'address_display', name: 'address_line1', orderable: false, searchable: false },
        { data: 'company_display', name: 'company', width: '180px', orderable: false },
        { data: 'tax_id_display', name: 'tax_id', width: '150px', orderable: false },
        { data: 'actions_display', name: 'actions_display', orderable: false, searchable: false, className: 'text-end', width: '180px' }
      ]
    });
  })();
</script>
@endpush

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

		<x-settings.card :title="__('Clients')">
			<div class="d-flex justify-content-end">
				<a class="btn btn-primary" href="{{ route('tenant.operations.clients.create', ['business' => $tenant->slug]) }}">{{ __('Add Client') }}</a>
			</div>

			<div class="mt-3 table-responsive">
				<table class="table table-sm align-middle mb-0" id="clientsTable">
					<thead class="bg-light">
						<tr>
							<th>{{ __('ID') }}</th>
							<th>{{ __('First name') }}</th>
							<th>{{ __('Last name') }}</th>
							<th>{{ __('Email') }}</th>
							<th>{{ __('Phone') }}</th>
							<th>{{ __('Address') }}</th>
							<th>{{ __('Company') }}</th>
							<th>{{ __('Tax ID') }}</th>
							<th class="text-end">{{ __('Actions') }}</th>
						</tr>
					</thead>
					<tbody></tbody>
				</table>
			</div>
		</x-settings.card>
	</div>
@endsection
