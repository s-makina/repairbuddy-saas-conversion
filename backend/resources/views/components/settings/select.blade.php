@props([
	'name',
	'id' => null,
	'value' => null,
	'options' => [],
	'errorKey' => null,
])

<select name="{{ $name }}" id="{{ $id ?? $name }}" {{ $attributes }}>
	@foreach ($options as $optValue => $optLabel)
		<option value="{{ $optValue }}" {{ (string) $value === (string) $optValue ? 'selected' : '' }}>{{ $optLabel }}</option>
	@endforeach
</select>
