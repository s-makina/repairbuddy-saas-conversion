@props([
	'name',
	'id' => null,
	'value' => null,
	'rows' => 4,
	'errorKey' => null,
	'required' => false,
	'placeholder' => null,
])

<textarea
	name="{{ $name }}"
	id="{{ $id ?? $name }}"
	rows="{{ $rows }}"
	@if($required) required @endif
	@if($placeholder) placeholder="{{ $placeholder }}" @endif
	{{ $attributes }}
>{{ $value }}</textarea>
