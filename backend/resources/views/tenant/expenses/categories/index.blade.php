@extends('tenant.layouts.myaccount', ['title' => $pageTitle ?? __('Expense Categories')])

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

    var $table = window.jQuery('#categoriesTable');
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
      ajax: "{{ route('tenant.expense_categories.datatable', ['business' => $tenant->slug]) }}",
      order: [[3, 'asc']],
      columns: [
        { data: 'id', name: 'id', width: '70px' },
        { data: 'color_display', name: 'color_code', width: '100px', orderable: false, searchable: false },
        { data: 'category_name', name: 'category_name' },
        { data: 'tax_display', name: 'taxable', width: '120px', orderable: false, searchable: false },
        { data: 'status_display', name: 'is_active', width: '120px', orderable: false, searchable: false },
        { data: 'actions_display', name: 'actions_display', orderable: false, searchable: false, className: 'text-end', width: '160px' }
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

		<x-settings.card :title="__('Expense Categories')">
			<div class="d-flex justify-content-end">
				<a class="btn btn-primary" href="{{ route('tenant.expense_categories.create', ['business' => $tenant->slug]) }}">
					<i class="bi bi-plus-circle me-1"></i>
					{{ __('Add Category') }}
				</a>
			</div>

			<div class="mt-3 table-responsive">
				<table class="table table-sm align-middle mb-0" id="categoriesTable">
					<thead class="bg-light">
						<tr>
							<th style="width: 70px;">{{ __('ID') }}</th>
							<th style="width: 100px;">{{ __('Color') }}</th>
							<th>{{ __('Name') }}</th>
							<th style="width: 120px;">{{ __('Tax') }}</th>
							<th style="width: 120px;">{{ __('Status') }}</th>
							<th class="text-end" style="width: 160px;">{{ __('Actions') }}</th>
						</tr>
					</thead>
					<tbody></tbody>
				</table>
			</div>
		</x-settings.card>
	</div>
@endsection
