@php
	/**
	 * Expects:
	 * - $section: 'type'|'brand'|'device'
	 * - $tenant, $service
	 * - $paginator (LengthAwarePaginator)
	 * - $servicePriceOverridesIndex (array)
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
		<form method="get" action="{{ url()->current() }}" class="mb-2 wcrb-price-override-search">
			<div class="input-group input-group-sm">
				<input type="text" class="form-control" name="{{ $searchName }}" value="{{ $searchValue }}" placeholder="{{ __('Search') }}" />
				<button class="btn btn-outline-secondary" type="submit">{{ __('Search') }}</button>
			</div>
		</form>

		<form method="post" action="{{ route('tenant.operations.services.price_overrides.update', ['business' => $tenant->slug, 'service' => $service->id]) }}">
			@csrf
			<input type="hidden" name="scope_type" value="{{ $section }}" />

			<div class="table-responsive">
				<table class="table table-sm align-middle">
					<thead>
						<tr>
							<th style="width: 70%">{{ __('Name') }}</th>
							<th style="width: 30%">{{ __('Price') }}</th>
							<th style="width: 10%">{{ __('Active') }}</th>
						</tr>
					</thead>
					<tbody>
						@foreach (($paginator ?? []) as $row)
							@php
								$rowId = (int) ($row->id ?? 0);
								$key = $section . ':' . $rowId;
								$override = ($servicePriceOverridesIndex ?? [])[$key] ?? null;
								$priceUi = '';
								if ($override && is_numeric($override->price_amount_cents)) {
									$priceUi = number_format(((int) $override->price_amount_cents) / 100, 2, '.', '');
								}

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
				<div class="d-flex justify-content-center my-2 wcrb-price-override-pagination">
					{{ $paginator->withQueryString()->links() }}
				</div>
			@endif

			<div class="d-flex justify-content-end">
				<button type="submit" class="btn btn-sm btn-primary">{{ __('Update Prices') }}</button>
			</div>
		</form>
	</div>
@endif
