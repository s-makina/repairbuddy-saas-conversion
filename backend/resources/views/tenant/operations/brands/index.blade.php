@extends('tenant.layouts.myaccount', ['title' => $pageTitle ?? __('Brands')])

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

    var $table = window.jQuery('#brandsTable');
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
      ajax: "{{ route('tenant.operations.brands.datatable', ['business' => $tenant->slug]) }}",
      order: [[1, 'asc']],
      columns: [
        { data: 'id', name: 'id', width: '90px' },
        { data: 'image_display', name: 'image_path', width: '60px', orderable: false, searchable: false },
        { data: 'name', name: 'name' },
        { data: 'status_display', name: 'is_active', width: '140px', orderable: false, searchable: false },
        { data: 'actions_display', name: 'actions_display', orderable: false, searchable: false, className: 'text-end', width: '280px' }
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

		<x-settings.card :title="__('Brands')">
			<div class="d-flex justify-content-end">
				<a class="btn btn-primary" href="{{ route('tenant.operations.brands.create', ['business' => $tenant->slug]) }}">{{ __('Add Brand') }}</a>
			</div>

			<div class="mt-3 table-responsive">
				<table class="table table-sm align-middle mb-0" id="brandsTable">
					<thead class="bg-light">
						<tr>
							<th style="width: 90px;">{{ __('ID') }}</th>
							<th style="width: 60px;">{{ __('Photo') }}</th>
							<th>{{ __('Name') }}</th>
							<th style="width: 140px;">{{ __('Status') }}</th>
							<th class="text-end" style="width: 280px;">{{ __('Actions') }}</th>
						</tr>
					</thead>
					<tbody></tbody>
				</table>
			</div>
		</x-settings.card>
	</div>
@endsection
