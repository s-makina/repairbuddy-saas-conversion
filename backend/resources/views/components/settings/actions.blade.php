@props([
	'align' => 'end',
	'paddingTop' => '8px',
])

@php
	$justify = 'flex-end';
	if ($align === 'start') {
		$justify = 'flex-start';
	} elseif ($align === 'between') {
		$justify = 'space-between';
	} elseif ($align === 'center') {
		$justify = 'center';
	}
@endphp

<div class="wcrb-settings-actions" style="justify-content: {{ $justify }}; padding-top: {{ $paddingTop }};">
	{{ $slot }}
</div>
