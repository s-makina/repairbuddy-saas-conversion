@props([
	'label' => null,
	'for' => null,
	'help' => null,
	'errorKey' => null,
])

<div {{ $attributes }}>
	@if ($label !== null)
		<label @if($for) for="{{ $for }}" @endif>
			{{ $label }}
			@if ($help)
				<small>{{ $help }}</small>
			@endif
			{{ $slot }}
		</label>
	@else
		{{ $slot }}
	@endif

	@if ($errorKey)
		@error($errorKey)
			<p class="description">{{ $message }}</p>
		@enderror
	@endif
</div>
