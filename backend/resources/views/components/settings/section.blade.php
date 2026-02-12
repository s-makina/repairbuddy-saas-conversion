@props([
	'title' => null,
	'id' => null,
])

<div {{ $attributes }}>
	@if ($title !== null)
		<h3>{{ $title }}</h3>
	@endif
	{{ $slot }}
</div>
