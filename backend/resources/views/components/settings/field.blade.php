@props([
	'label' => null,
	'for' => null,
	'help' => null,
	'errorKey' => null,
])

<div {{ $attributes }}>
	@if ($label !== null)
		<label @if($for) for="{{ $for }}" @endif>
			<span class="wcrb-settings-label-text">{{ $label }}</span>
			@if ($help)
				<small>{{ $help }}</small>
			@endif
		</label>
		{{ $slot }}
	@else
		{{ $slot }}
	@endif

	@if ($errorKey)
		@error($errorKey)
			<p class="description">{{ $message }}</p>
		@enderror
	@endif
</div>
