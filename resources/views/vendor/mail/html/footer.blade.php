@component('mail::layout')
    {{-- Footer --}}
    @slot('footer')
        @component('mail::footer')
            © {{ date('Y') }} Trivex Trade. All rights reserved.
        @endcomponent
    @slot('footer')
@endcomponent