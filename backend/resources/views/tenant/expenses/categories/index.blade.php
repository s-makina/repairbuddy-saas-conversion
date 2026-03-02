@extends('tenant.layouts.myaccount', ['title' => $pageTitle ?? __('Expense Categories')])

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

			<div class="mt-3">
				@if (empty($categories) || count($categories) === 0)
					<div class="text-center py-5">
						<i class="bi bi-tags fs-1 text-muted"></i>
						<p class="mt-3 text-muted">{{ __('No categories found') }}</p>
					</div>
				@else
					<div class="row g-3">
						@foreach ($categories as $category)
							<div class="col-md-6 col-lg-4 col-xl-3">
								<div class="card h-100">
									<div class="card-body">
										<div class="d-flex align-items-start">
											<div class="me-3">
													<div class="rounded-circle d-flex align-items-center justify-content-center"
														 style="background-color: {{ $category->color_code ?? '#3498db' }}; width: 48px; height: 48px;">
														<i class="bi bi-tag text-white"></i>
													</div>
											</div>
											<div class="flex-grow-1 min-w-0">
													<h6 class="card-title mb-1 text-truncate">{{ $category->category_name }}</h6>
													<!-- <small class="text-muted">#{{ $category->id }}</small> -->
													@if ($category->category_description)
														<p class="card-text text-muted small mb-2 text-truncate">{{ $category->category_description }}</p>
													@endif
													<div class="d-flex flex-wrap gap-1">
														<span class="badge {{ $category->is_active ? 'bg-success' : 'bg-secondary' }}">
															{{ $category->is_active ? __('Active') : __('Inactive') }}
														</span>
														@if ($category->taxable)
															<span class="badge bg-info">{{ $category->tax_rate }}%</span>
														@endif
													</div>
											</div>
											<div class="dropdown ms-auto">
												<button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
													<i class="bi bi-three-dots"></i>
												</button>
												<ul class="dropdown-menu dropdown-menu-end shadow-sm">
													<li>
														<a class="dropdown-item" href="{{ route('tenant.expense_categories.edit', ['business' => $tenant->slug, 'category' => $category->id]) }}">
															<i class="bi bi-pencil-square text-primary me-2"></i>
															{{ __('Edit') }}
														</a>
													</li>
													<li>
														<form method="post" action="{{ route('tenant.expense_categories.delete', ['business' => $tenant->slug, 'category' => $category->id]) }}">
															@csrf
															<button type="submit" class="dropdown-item text-danger" onclick="return confirm('{{ __('Are you sure you want to delete this category?') }}')">
																<i class="bi bi-trash text-danger me-2"></i>
																{{ __('Delete') }}
															</button>
														</form>
													</li>
												</ul>
											</div>
										</div>
									</div>
								</div>
							</div>
						@endforeach
					</div>
				@endif
			</div>
		</x-settings.card>
	</div>
@endsection
