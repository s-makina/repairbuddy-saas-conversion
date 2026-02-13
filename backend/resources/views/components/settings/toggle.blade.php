@props([
	'name',
	'id' => null,
	'checked' => false,
	'uncheckedValue' => 0,
	'value' => 1,
	'errorKey' => null,
])

<input type="hidden" name="{{ $name }}" value="{{ $uncheckedValue }}" />
<input type="checkbox" name="{{ $name }}" id="{{ $id ?? $name }}" value="{{ $value }}" {{ $checked ? 'checked' : '' }} {{ $attributes->class(['wcrb-settings-toggle']) }} />
