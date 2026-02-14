@extends('tenant.layouts.myaccount', ['title' => 'Settings'])

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

			(function ($) {
				var $tabs = $('#example-tabs');
				if (!($tabs.length && $tabs.foundation)) {
					return;
				}

				var storageKey = 'repairbuddy_settings_active_tab';
				var restoreTab = function () {
					var hash = window.location.hash;
					var target = (hash && $(hash).length) ? hash : null;
					if (!target) {
						var stored = null;
						try {
							stored = window.localStorage ? window.localStorage.getItem(storageKey) : null;
						} catch (e) {
							stored = null;
						}
						target = (stored && $(stored).length) ? stored : null;
					}
					if (target && $tabs.foundation) {
						try {
							$tabs.foundation('selectTab', target);
						} catch (e) {
						}
					}
				};

				restoreTab();

				$tabs.on('change.zf.tabs', function (event, $tab) {
					var $a = ($tab && $tab.find) ? $tab.find('a').first() : null;
					var href = $a && $a.length ? $a.attr('href') : null;
					if (!href || href.charAt(0) !== '#') {
						return;
					}

					try {
						window.history.replaceState(null, '', href);
					} catch (e) {
						window.location.hash = href;
					}

					try {
						if (window.localStorage) {
							window.localStorage.setItem(storageKey, href);
						}
					} catch (e) {
					}
				});

				$(window).on('hashchange', function () {
					restoreTab();
				});
			})(window.jQuery);
		}
	</script>
@endpush

@section('content')
	<div class="main-container computer-repair wcrbfd">
		<div class="grid-x grid-container grid-margin-x grid-padding-y fluid" style="width:100%;">
			<div class="small-12 cell">
				<div class="form-update-message"></div>
			</div>
			<div class="large-12 medium-12 small-12 cell">
				
				<div class="team-wrap grid-x" data-equalizer data-equalize-on="medium">
					<div class="cell medium-2 thebluebg sidebarmenu">
						<div class="the-brand-logo">
							<a href="{{ $logoURL }}" target="_blank">
								<img src="{{ $logolink }}" alt="RepairBuddy CRM Logo" />
							</a>
						</div>
						<ul class="vertical tabs thebluebg" data-tabs="82ulyt-tabs" id="example-tabs">
							<li class="tabs-title{{ $class_general_settings }}" role="presentation">
								<a href="#panel1" role="tab" aria-controls="panel1" aria-selected="false" id="panel1-label">
									<h2>{{ __('General Settings') }}</h2>
								</a>
							</li>
							<li class="tabs-title{{ $class_currency_settings }}" role="presentation">
								<a href="#currencyFormatting" role="tab" aria-controls="currencyFormatting" aria-selected="true" id="currencyFormatting-label">
									<h2>{{ __('Currency') }}</h2>
								</a>
							</li>
							<li class="tabs-title{{ $class_invoices_settings }}" role="presentation">
								<a href="#reportsAInvoices" role="tab" aria-controls="reportsAInvoices" aria-selected="true" id="reportsAInvoices-label">
									<h2>{{ __('Reports & Invoices') }}</h2>
								</a>
							</li>
							<li class="tabs-title{{ $class_status }}" role="presentation">
								<a href="#panel3" role="tab" aria-controls="panel3" aria-selected="true" id="panel3-label">
									<h2>{{ __('Job Status') }}</h2>
								</a>
							</li>
							<li class="tabs-title" role="presentation">
								<a href="#wc_rb_payment_status" role="tab" aria-controls="wc_rb_payment_status" aria-selected="true" id="wc_rb_payment_status-label">
									<h2>{{ __('Payment Status') }}</h2>
								</a>
							</li>
							<li class="tabs-title" role="presentation">
								<a href="#wc_rb_page_sms_IDENTIFIER" role="tab" aria-controls="wc_rb_page_sms_IDENTIFIER" aria-selected="true" id="wc_rb_page_sms_IDENTIFIER-label">
									<h2>{{ __('SMS') }}</h2>
								</a>
							</li>
							@foreach (($extraTabs ?? []) as $tab)
								@php
									$tabId = (string) ($tab['id'] ?? '');
									$tabLabel = (string) ($tab['label'] ?? '');
								@endphp
								@if ($tabId !== '' && $tabLabel !== '')
									<li class="tabs-title" role="presentation">
										<a href="#{{ $tabId }}" role="tab" aria-controls="{{ $tabId }}" aria-selected="true" id="{{ $tabId }}-label">
											<h2>{{ $tabLabel }}</h2>
										</a>
									</li>
								@endif
							@endforeach
							<li class="tabs-title{{ $class_activation }}" role="presentation">
								<a href="#panel4" role="tab" aria-controls="panel4" aria-selected="true" id="panel4-label">
									<h2>{{ __('Activation') }}</h2>
								</a>
							</li>
							<li class="thespacer"><hr></li>
							<li class="tabs-title" role="presentation">
								<a href="#documentation" role="tab" aria-controls="documentation" aria-selected="true" id="documentation-label">
									<h2>{{ __('Shortcodes & Support') }}</h2>
								</a>
							</li>
							@if (! $repairbuddy_whitelabel)
							<li class="tabs-title" role="presentation">
								<a href="#addons" role="tab" aria-controls="addons" aria-selected="true" id="addons-label">
									<h2>{{ __('Addons') }}</h2>
								</a>
							</li>
							@endif
							<li class="thespacer"><hr></li>
							<li class="external-title">
								<a href="{{ $contactURL }}" target="_blank">
									<h2><span class="dashicons dashicons-buddicons-pm"></span> {{ __('Contact Us') }}</h2>
								</a>
							</li>
							@if (! $repairbuddy_whitelabel)
							<li class="external-title">
								<a href="https://www.facebook.com/WebfulCreations" target="_blank">
									<h2><span class="dashicons dashicons-facebook"></span> {{ __('Chat With Us') }}</h2>
								</a>
							</li>
							@endif
						</ul>
					</div>
					
					<div class="cell medium-10 thewhitebg contentsideb">
						<div class="tabs-content vertical" data-tabs-content="example-tabs">
							@include('tenant.settings.sections.general')
							@include('tenant.settings.sections.currency')
							@include('tenant.settings.sections.invoices')
							@include('tenant.settings.sections.job-status')
							@include('tenant.settings.sections.sms')
							@include('tenant.settings.sections.payment-status')
							@foreach (($extraTabs ?? []) as $tab)
								@php
									$tabView = (string) ($tab['view'] ?? '');
								@endphp
								@if ($tabView !== '')
									@includeIf($tabView)
								@endif
							@endforeach
							@include('tenant.settings.sections.activation')
							@include('tenant.settings.sections.documentation')
							@if (! $repairbuddy_whitelabel)
								@include('tenant.settings.sections.addons')
							@endif
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
@endsection
