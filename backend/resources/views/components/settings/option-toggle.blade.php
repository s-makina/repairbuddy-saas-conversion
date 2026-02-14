@props([
	'name',
	'id' => null,
	'checked' => false,
	'value' => 1,
	'uncheckedValue' => 0,
	'label' => null,
	'description' => null,
])

<div {{ $attributes->class(['wcrb-settings-option']) }}>
	<div class="wcrb-settings-option-head">
		<div class="wcrb-settings-option-control">
			<x-settings.toggle
				name="{{ $name }}"
				id="{{ $id ?? $name }}"
				:checked="$checked"
				value="{{ $value }}"
				uncheckedValue="{{ $uncheckedValue }}"
			/>
		</div>
		@if ($label !== null)
			<label for="{{ $id ?? $name }}" class="wcrb-settings-option-title">{{ $label }}</label>
		@endif
	</div>
	@if ($description !== null)
		<div class="wcrb-settings-option-description">{{ $description }}</div>
	@endif
</div>
