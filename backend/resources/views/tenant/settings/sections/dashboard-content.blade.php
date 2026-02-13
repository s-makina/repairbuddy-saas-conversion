<div class="wrap">
	<h2>{{ __('Dashboard') }}</h2>

	@php
		$jobStatusOptions = $jobStatusOptions ?? [];
		$jobStatusCounts = $jobStatusCounts ?? [];
		$estimateCounts = $estimateCounts ?? ['pending' => 0, 'approved' => 0, 'rejected' => 0];
		$dashboardBaseUrl = $dashboardBaseUrl ?? '#';
	@endphp

	@php
		$navItems = [
			['label' => __('Tickets'), 'image' => 'jobs.png', 'screen' => 'jobs'],
			['label' => __('Estimates'), 'image' => 'estimate.png', 'screen' => 'estimates'],
			['label' => __('Reviews'), 'image' => 'reviews.png', 'screen' => 'reviews'],
			['label' => __('Payments'), 'image' => 'payments.png', 'screen' => 'payments'],
			['label' => __('Services'), 'image' => 'services.png', 'screen' => 'services'],
			['label' => __('Parts'), 'image' => 'parts.png', 'screen' => 'parts'],
			['label' => __('Devices'), 'image' => 'devices.png', 'screen' => 'devices'],
			['label' => __('Device Brands'), 'image' => 'manufacture.png', 'screen' => 'device_brands'],
			['label' => __('Device Type'), 'image' => 'types.png', 'screen' => 'device_types'],
			['label' => __('Customers'), 'image' => 'clients.png', 'screen' => 'customers'],
			['label' => __('Technicians'), 'image' => 'technicians.png', 'screen' => 'technicians'],
			['label' => __('Managers'), 'image' => 'manager.png', 'screen' => 'managers'],
			['label' => __('Reports'), 'image' => 'report.png', 'screen' => 'reports'],
		];
	@endphp

	<div class="wcrb_dashboard_nav wcrb_dashboard_section">
		@foreach ($navItems as $item)
			@php
				$img = asset('repairbuddy/plugin/assets/admin/images/icons/' . ($item['image'] ?? 'jobs.png'));
				$screen = (string) ($item['screen'] ?? '');
				$link = $dashboardBaseUrl !== '#'
					? ($dashboardBaseUrl . '?screen=' . urlencode($screen))
					: '#';
			@endphp
			<div class="wcrb_dan_item">
				<a href="{{ $link }}">
					<img src="{{ $img }}" alt="" />
					<h3>{{ $item['label'] ?? '' }}</h3>
				</a>
			</div>
		@endforeach
	</div>

	<div class="wcrb_dashboard_jobs_status wcrb_dashboard_section grid-x grid-margin-x grid-container fluid">
		@foreach ($jobStatusOptions as $slug => $label)
			@php
				$count = (int) ($jobStatusCounts[$slug] ?? 0);
				$link = $dashboardBaseUrl !== '#'
					? ($dashboardBaseUrl . '?screen=jobs&job_status=' . urlencode((string) $slug))
					: '#';
				$icon = asset('repairbuddy/plugin/assets/admin/images/icons/jobs.png');
			@endphp
			<div class="large-3 medium-4 small-6 cell">
				<div class="wcrb_widget wcrb_widget-12 wcrb_has-shadow">
					<a href="{{ $link }}">
						<div class="wcrb_widget-body">
							<div class="wcrb_media">
								<div class="wcrb_align-self-center wcrb_ml-5 wcrb_mr-5">
									<img src="{{ $icon }}" alt="" />
								</div>
								<div class="wcrb_media-body wcrb_align-self-center">
									<div class="wcrb_title">{{ $label }}</div>
									<div class="wcrb_number">{{ $count }} {{ __('Jobs') }}</div>
								</div>
							</div>
						</div>
					</a>
				</div>
			</div>
		@endforeach
	</div>

	<br>
	<div class="wcrb_dashboard_jobs_status wcrb_dashboard_section grid-x grid-margin-x grid-container fluid">
		@php
			$estimateWidgets = [
				['key' => 'pending', 'label' => __('Pending')],
				['key' => 'approved', 'label' => __('Approved')],
				['key' => 'rejected', 'label' => __('Rejected')],
			];
		@endphp

		@foreach ($estimateWidgets as $w)
			@php
				$key = (string) ($w['key'] ?? '');
				$label = (string) ($w['label'] ?? $key);
				$count = (int) ($estimateCounts[$key] ?? 0);
				$link = $dashboardBaseUrl !== '#'
					? ($dashboardBaseUrl . '?screen=estimates&estimate_status=' . urlencode($key))
					: '#';
				$icon = asset('repairbuddy/plugin/assets/admin/images/icons/estimate.png');
			@endphp
			<div class="large-3 medium-4 small-6 cell">
				<div class="wcrb_widget wcrb_widget-12 wcrb_has-shadow">
					<a href="{{ $link }}">
						<div class="wcrb_widget-body">
							<div class="wcrb_media">
								<div class="wcrb_align-self-center wcrb_ml-5 wcrb_mr-5">
									<img src="{{ $icon }}" alt="" />
								</div>
								<div class="wcrb_media-body wcrb_align-self-center">
									<div class="wcrb_title">{{ $label }}</div>
									<div class="wcrb_number">{{ $count }} {{ __('Estimates') }}</div>
								</div>
							</div>
						</div>
					</a>
				</div>
			</div>
		@endforeach
	</div>
</div>
