@extends('tenant.layouts.myaccount', ['title' => $pageTitle ?? __('Managers')])

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

    var $table = window.jQuery('#managersTable');
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
      ajax: "{{ route('tenant.managers.datatable', ['business' => $tenant->slug]) }}",
      order: [[1, 'asc']],
      columns: [
        { data: 'id', name: 'id', width: '90px' },
        { data: 'name', name: 'name' },
        { data: 'email', name: 'email' },
        { data: 'phone', name: 'phone' },
        { data: 'address_display', name: 'address_display', orderable: false, searchable: false },
        { data: 'shops_display', name: 'shops_display', orderable: false, searchable: false }
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

		<x-settings.card :title="__('Managers')">
			<div class="mt-3 table-responsive">
				<table class="table table-sm align-middle mb-0" id="managersTable">
					<thead class="bg-light">
						<tr>
							<th style="width: 90px;">{{ __('ID') }}</th>
							<th>{{ __('Name') }}</th>
							<th>{{ __('Email') }}</th>
							<th>{{ __('Phone') }}</th>
							<th>{{ __('Address') }}</th>
							<th>{{ __('Shops') }}</th>
						</tr>
					</thead>
					<tbody></tbody>
				</table>
			</div>
		</x-settings.card>
	</div>
@endsection
