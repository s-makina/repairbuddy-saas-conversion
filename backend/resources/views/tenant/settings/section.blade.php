@extends('tenant.layouts.myaccount', ['title' => $pageTitle ?? 'Settings'])

@push('page-styles')
	<link rel="stylesheet" href="https://s.w.org/wp-includes/css/dashicons.css">
	<link rel="stylesheet" href="{{ asset('repairbuddy/plugin/css/foundation.min.css') }}">
	<link rel="stylesheet" href="{{ asset('repairbuddy/plugin/css/style.css') }}">
	<link rel="stylesheet" href="{{ asset('repairbuddy/plugin/css/admin-style.css') }}">
	<link rel="stylesheet" href="{{ asset('repairbuddy/plugin/css/wp-admin-shim.css') }}">
	<link rel="stylesheet" href="{{ asset('repairbuddy/plugin/css/switchcolorscheme.css') }}">
@endpush

@push('page-scripts')
	<script src="{{ asset('repairbuddy/plugin/js/foundation.min.js') }}"></script>
	<script>
		if (window.jQuery && window.jQuery.fn && window.jQuery.fn.foundation) {
			window.jQuery(document).foundation();
		}
	</script>
@endpush

@section('content')
	<div class="main-container computer-repair wcrbfd">
		<div class="grid-x grid-container grid-margin-x grid-padding-y fluid" style="width:100%;">
			<div class="small-12 cell">
				<div class="form-update-message">
					@if (session('status'))
						<div class="notice notice-success">
							<p>{{ (string) session('status') }}</p>
						</div>
					@endif

					@if ($errors->any())
						<div class="notice notice-error">
							<p>{{ __( 'Please fix the errors below.' ) }}</p>
							<ul style="margin: 6px 0 0 18px;">
								@foreach ($errors->all() as $error)
									<li>{{ $error }}</li>
								@endforeach
							</ul>
						</div>
					@endif
				</div>
			</div>

			<div class="large-12 medium-12 small-12 cell">
				<div class="team-wrap grid-x" data-equalizer data-equalize-on="medium">
					<div class="cell medium-12 thewhitebg contentsideb">
						<div class="tabs-content vertical">
							@includeIf($settingsSectionView)
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
@endsection
