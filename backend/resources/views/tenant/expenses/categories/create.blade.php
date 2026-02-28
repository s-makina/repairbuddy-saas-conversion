@extends('tenant.layouts.myaccount', ['title' => $pageTitle ?? __('Add Expense Category')])

@section('content')
	<div class="container-fluid p-3">
		@if ($errors->any())
			<div class="alert alert-danger">
				<div class="fw-semibold mb-1">{{ __( 'Please fix the errors below.' ) }}</div>
				<ul class="mb-0">
					@foreach ($errors->all() as $error)
						<li>{{ $error }}</li>
					@endforeach
				</ul>
			</div>
		@endif

		<div class="row justify-content-center">
			<div class="col-12 col-lg-8">
				<div class="card">
					<div class="card-header">
						<h5 class="card-title mb-0">{{ __('Add Expense Category') }}</h5>
					</div>
					<div class="card-body">
						<form method="post" action="{{ route('tenant.expense_categories.store', ['business' => $tenant->slug]) }}">
							@csrf
							<div class="row g-3">
								<div class="col-12">
									<div class="row align-items-start">
										<label for="category_name" class="col-sm-3 col-form-label">{{ __('Name') }} *</label>
										<div class="col-sm-9">
											<input
												type="text"
												name="category_name"
												id="category_name"
												class="form-control @error('category_name') is-invalid @enderror"
												value="{{ old('category_name', '') }}"
												required
											>
											@error('category_name')
												<div class="invalid-feedback">{{ $message }}</div>
											@enderror
										</div>
									</div>
								</div>
								<div class="col-12">
									<div class="row align-items-start">
										<label for="category_description" class="col-sm-3 col-form-label">{{ __('Description') }}</label>
										<div class="col-sm-9">
											<textarea
												name="category_description"
												id="category_description"
												rows="3"
												class="form-control @error('category_description') is-invalid @enderror"
											>{{ old('category_description', '') }}</textarea>
											@error('category_description')
												<div class="invalid-feedback">{{ $message }}</div>
											@enderror
										</div>
									</div>
								</div>
								<div class="col-12">
									<div class="row align-items-start">
										<label for="color_code" class="col-sm-3 col-form-label">{{ __('Color') }}</label>
										<div class="col-sm-9">
											<div class="d-flex align-items-center gap-2">
												<input
													type="color"
													name="color_code"
													id="color_code"
													class="form-control form-control-color"
													value="{{ old('color_code', '#3498db') }}"
													style="width: 60px; height: 38px;"
												>
												<input
													type="text"
													id="color_code_text"
													class="form-control"
													value="{{ old('color_code', '#3498db') }}"
													style="width: 120px;"
													pattern="^#[0-9A-Fa-f]{6}$"
												>
											</div>
											@error('color_code')
												<div class="invalid-feedback d-block">{{ $message }}</div>
											@enderror
										</div>
									</div>
								</div>
								<div class="col-12">
									<div class="row align-items-start">
										<label for="sort_order" class="col-sm-3 col-form-label">{{ __('Sort Order') }}</label>
										<div class="col-sm-9">
											<input
												type="number"
												name="sort_order"
												id="sort_order"
												class="form-control @error('sort_order') is-invalid @enderror"
												value="{{ old('sort_order', '0') }}"
												min="0"
											>
											@error('sort_order')
												<div class="invalid-feedback">{{ $message }}</div>
											@enderror
											<div class="form-text">{{ __('Higher numbers appear first.') }}</div>
										</div>
									</div>
								</div>
								<div class="col-12">
									<div class="row align-items-start">
										<label for="parent_category_id" class="col-sm-3 col-form-label">{{ __('Parent Category') }}</label>
										<div class="col-sm-9">
											<select name="parent_category_id" id="parent_category_id" class="form-select @error('parent_category_id') is-invalid @enderror">
												@foreach ($parentOptions as $value => $label)
													<option value="{{ $value }}" {{ old('parent_category_id') == $value ? 'selected' : '' }}>{{ $label }}</option>
												@endforeach
											</select>
											@error('parent_category_id')
												<div class="invalid-feedback">{{ $message }}</div>
											@enderror
										</div>
									</div>
								</div>
								<div class="col-12">
									<div class="row">
										<div class="col-sm-9 offset-sm-3">
											<div class="form-check form-check-inline">
												<input
													class="form-check-input"
													type="checkbox"
													name="is_active"
													id="is_active"
													value="1"
													{{ old('is_active', '1') ? 'checked' : '' }}
												>
												<label class="form-check-label" for="is_active">{{ __('Active') }}</label>
											</div>
											<div class="form-check form-check-inline">
												<input
													class="form-check-input"
													type="checkbox"
													name="taxable"
													id="taxable"
													value="1"
													{{ old('taxable') ? 'checked' : '' }}
												>
												<label class="form-check-label" for="taxable">{{ __('Taxable') }}</label>
											</div>
										</div>
									</div>
								</div>
								<div class="col-12" id="tax_rate_row">
									<div class="row align-items-start">
										<label for="tax_rate" class="col-sm-3 col-form-label">{{ __('Tax Rate') }}</label>
										<div class="col-sm-9">
											<div class="input-group" style="max-width: 150px;">
												<input
													type="number"
													name="tax_rate"
													id="tax_rate"
													class="form-control @error('tax_rate') is-invalid @enderror"
													value="{{ old('tax_rate', '0') }}"
													min="0"
													max="100"
													step="0.01"
												>
												<span class="input-group-text">%</span>
											</div>
											@error('tax_rate')
												<div class="invalid-feedback">{{ $message }}</div>
											@enderror
										</div>
									</div>
								</div>
								<div class="col-12">
									<div class="d-flex justify-content-end gap-2 mt-3">
										<a class="btn btn-outline-secondary" href="{{ route('tenant.expense_categories.index', ['business' => $tenant->slug]) }}">{{ __('Cancel') }}</a>
										<button type="submit" class="btn btn-primary">{{ __('Save') }}</button>
									</div>
								</div>
							</div>
						</form>
					</div>
				</div>
			</div>
		</div>
	</div>
@endsection

@push('page-scripts')
<script>
  (function () {
    var $taxable = document.getElementById('taxable');
    var $taxRateRow = document.getElementById('tax_rate_row');

    function toggleTaxRate() {
      if ($taxable && $taxRateRow) {
        $taxRateRow.style.display = $taxable.checked ? '' : 'none';
      }
    }

    if ($taxable) {
      $taxable.addEventListener('change', toggleTaxRate);
      toggleTaxRate();
    }

    // Sync color picker with text input
    var $colorPicker = document.getElementById('color_code');
    var $colorText = document.getElementById('color_code_text');

    if ($colorPicker && $colorText) {
      $colorPicker.addEventListener('input', function () {
        $colorText.value = $colorPicker.value;
      });
      $colorText.addEventListener('input', function () {
        if (/^#[0-9A-Fa-f]{6}$/.test($colorText.value)) {
          $colorPicker.value = $colorText.value;
        }
      });
    }
  })();
</script>
@endpush
