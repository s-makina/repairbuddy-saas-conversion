@props([
	'name',
	'id' => null,
	'value' => null,
	'type' => 'text',
	'errorKey' => null,
	'required' => false,
	'placeholder' => null,
])

<input
	name="{{ $name }}"
	id="{{ $id ?? $name }}"
	type="{{ $type }}"
	value="{{ $value }}"
	@if($required) required @endif
	@if($placeholder) placeholder="{{ $placeholder }}" @endif
	{{ $attributes->class(['wcrb-settings-input']) }}
/>
