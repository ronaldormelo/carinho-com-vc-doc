@props(['number'])

@php
    $href = \App\Support\EmergencyTelLink::href($number);
    $display = \App\Support\EmergencyTelLink::normalize($number) ?? trim((string) $number);
    $name = $display !== '' ? \App\Support\EmergencyTelLink::label($display) : '';
@endphp

@if($href)
    <a
        href="{{ $href }}"
        {{ $attributes->class('emergency-tel-link') }}
        aria-label="Ligar para {{ $name }}, {{ $display }}"
    >{{ $slot->isEmpty() ? $display : $slot }}</a>
@elseif($display !== '')
    {{ $display }}
@endif
