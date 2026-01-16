@php
    $url = config('app.url');
    $logoUrl = (string) config('brand.email.logo_url');
    $logoAlt = (string) config('brand.email.logo_alt', config('app.name'));
@endphp

<tr>
<td class="header">
<a href="{{ $url }}" style="display:inline-block; text-decoration:none;">
@if (! empty($logoUrl))
<img src="{{ $logoUrl }}" alt="{{ $logoAlt }}" style="height:42px; max-height:42px; width:auto; display:block; margin:0 auto;" />
@else
<span style="font-size:18px; font-weight:700; color: {{ config('brand.email.primary_color', '#063e70') }};">
{{ $slot }}
</span>
@endif
</a>
</td>
</tr>
