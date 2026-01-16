{{ config('app.name') }}
@if (! empty(config('mail.from.address')))
{{ config('mail.from.address') }}
@endif
