@props([
	'title' => null,
	'bodyClass' => null,
])

<div {{ $attributes->class(['wcrb-settings-card']) }}>
	@if ($title !== null)
		<h3 class="wcrb-settings-card-title">{{ $title }}</h3>
	@endif
	<div class="wcrb-settings-card-body {{ $bodyClass }}">
		{{ $slot }}
	</div>
</div>
