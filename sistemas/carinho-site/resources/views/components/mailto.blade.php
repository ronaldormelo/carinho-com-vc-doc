@props(['address'])

@php
    $email = \App\Support\MailtoLink::normalize($address);
    $href = \App\Support\MailtoLink::href($address);
@endphp

@if($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => 'mailto-link']) }}>{{ $slot->isEmpty() ? $email : $slot }}</a>
@elseif(trim((string) $address) !== '')
    {{ trim((string) $address) }}
@endif
