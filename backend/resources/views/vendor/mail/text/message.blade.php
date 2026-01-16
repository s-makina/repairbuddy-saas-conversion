@component('mail::layout')
    @slot('header')
        @component('mail::header')
            {{ config('app.name') }}
        @endcomponent
    @endslot

{{ $slot }}

    @isset($subcopy)
        @slot('subcopy')
            @component('mail::subcopy')
{{ $subcopy }}
            @endcomponent
        @endslot
    @endisset

    @slot('footer')
        @component('mail::footer')
            {{ config('app.name') }}
        @endcomponent
    @endslot
@endcomponent
