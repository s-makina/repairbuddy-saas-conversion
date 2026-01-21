@php
    $appName = (string) config('app.name', '99smartx');
    $supportEmail = (string) config('mail.from.address');
@endphp

<tr>
<td>
<table class="footer" align="center" width="570" cellpadding="0" cellspacing="0" role="presentation">
<tr>
<td class="content-cell" align="center">
<p style="margin:0; font-size:12px; line-height:18px; color: {{ config('brand.email.muted_text_color', '#667085') }};">
{{ $appName }}
@if (! empty($supportEmail))
&nbsp;&middot;&nbsp;<a href="mailto:{{ $supportEmail }}" style="color: {{ config('brand.email.primary_color', '#063e70') }}; text-decoration:none;">{{ $supportEmail }}</a>
@endif
</p>
</td>
</tr>
</table>
</td>
</tr>
