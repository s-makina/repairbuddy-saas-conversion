@php
	/**
	 * Expects:
	 * - $section: 'type'|'brand'|'device'
	 * - $tenant, $part
	 * - $paginator (LengthAwarePaginator)
	 * - $partPriceOverridesIndex (array)
	 */
	$section = is_string($section ?? null) ? $section : '';
	$searchValue = '';
	$searchName = '';
	$pageName = '';
	$title = '';

	if ($section === 'type') {
		$searchName = 'types_q';
		$pageName = 'types_page';
		$title = __('Set price by Device Type');
		$searchValue = (string) request()->query('types_q', '');
	} elseif ($section === 'brand') {
		$searchName = 'brands_q';
		$pageName = 'brands_page';
		$title = __('Set price by Device Brand');
		$searchValue = (string) request()->query('brands_q', '');
	} elseif ($section === 'device') {
		$searchName = 'devices_q';
		$pageName = 'devices_page';
		$title = __('Set price by Device');
		$searchValue = (string) request()->query('devices_q', '');
	}
@endphp

@if ($section !== '')
	<div class="wcrb-price-override-section" data-section="{{ $section }}" data-page-name="{{ $pageName }}" data-search-name="{{ $searchName }}">
		<style>
			.wcrb-price-override-section .pagination { gap: .35rem; }
			.wcrb-price-override-section .pagination .page-item { margin: 0 !important; }
		</style>
		<form method="get" action="{{ url()->current() }}" class="mb-2 wcrb-price-override-search">
			<div class="input-group input-group-sm">
				<input type="text" class="form-control" name="{{ $searchName }}" value="{{ $searchValue }}" placeholder="{{ __('Search') }}" />
				<button class="btn btn-outline-secondary" type="submit">{{ __('Search') }}</button>
			</div>
		</form>

		<form method="post" action="{{ route('tenant.operations.parts.price_overrides.update', ['business' => $tenant->slug, 'part' => $part->id]) }}">
			@csrf
			<input type="hidden" name="scope_type" value="{{ $section }}" />

			<div class="table-responsive">
				<table class="table table-sm align-middle">
					<thead>
						<tr>
							<th style="width: 35%">{{ __('Name') }}</th>
							<th style="width: 20%">{{ __('Price') }}</th>
							<th style="width: 20%">{{ __('Manufacturing Code') }}</th>
							<th style="width: 20%">{{ __('Stock Code') }}</th>
							<th style="width: 5%">{{ __('Active') }}</th>
						</tr>
					</thead>
					<tbody>
						@foreach (($paginator ?? []) as $row)
							@php
								$rowId = (int) ($row->id ?? 0);
								$key = $section . ':' . $rowId;
								$override = ($partPriceOverridesIndex ?? [])[$key] ?? null;
								$priceUi = '';
								if ($override && is_numeric($override->price_amount_cents)) {
									$priceUi = number_format(((int) $override->price_amount_cents) / 100, 2, '.', '');
								}

								$mfgUi = $override ? (string) ($override->manufacturing_code ?? '') : '';
								$stockUi = $override ? (string) ($override->stock_code ?? '') : '';

								$name = '';
								if ($section === 'device') {
									$name = (string) ($row->model ?? '');
								} else {
									$name = (string) ($row->name ?? '');
								}
							@endphp
							<tr>
								<td>
									<input type="hidden" name="scope_ref_id[]" value="{{ $rowId }}" />
									{{ $name }}
								</td>
								<td>
									<input type="number" step="0.01" min="0" class="form-control form-control-sm" style="min-width: 140px" name="price[]" value="{{ $priceUi }}" placeholder="0.00" />
								</td>
								<td>
									<input type="text" class="form-control form-control-sm" style="min-width: 160px" name="manufacturing_code[]" value="{{ $mfgUi }}" />
								</td>
								<td>
									<input type="text" class="form-control form-control-sm" style="min-width: 160px" name="stock_code[]" value="{{ $stockUi }}" />
								</td>
								<td class="text-center">
									@php
										$toggleId = 'wcrb_override_' . $section . '_' . $rowId;
									@endphp
									<div class="form-check form-switch d-inline-flex align-items-center justify-content-center m-0">
										<input class="form-check-input m-0" type="checkbox" role="switch" id="{{ $toggleId }}" name="active_ref_id[]" value="{{ $rowId }}" @checked($override ? (bool) $override->is_active : false) />
									</div>
								</td>
							</tr>
						@endforeach
					</tbody>
				</table>
			</div>

			@if (($paginator ?? null) && method_exists($paginator, 'links'))
				<div class="d-flex justify-content-center mt-3 mb-2 wcrb-price-override-pagination">
					@php
						$paginator->appends([$searchName => $searchValue]);
					@endphp
					{{ $paginator->links() }}
				</div>
			@endif

			<div class="d-flex justify-content-end">
				<button type="submit" class="btn btn-sm btn-primary">{{ __('Update Prices') }}</button>
			</div>
		</form>
	</div>
@endif
