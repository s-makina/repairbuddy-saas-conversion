@props([
	'label' => null,
])

<tr>
	<td>
		<input class="button button-primary" type="Submit" value="{{ $label ?? __('Save Changes') }}"/>
	</td>
	<td>
		{{ $slot }}
	</td>
</tr>
