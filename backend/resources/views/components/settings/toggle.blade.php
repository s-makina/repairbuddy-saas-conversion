@props([
	'name',
	'id' => null,
	'checked' => false,
	'value' => 1,
	'errorKey' => null,
])

<input type="hidden" name="{{ $name }}" value="0" />
<input type="checkbox" name="{{ $name }}" id="{{ $id ?? $name }}" value="{{ $value }}" {{ $checked ? 'checked' : '' }} {{ $attributes->class(['wcrb-settings-toggle']) }} />
