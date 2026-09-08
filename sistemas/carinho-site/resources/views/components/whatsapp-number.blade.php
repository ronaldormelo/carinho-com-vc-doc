@props(['msg' => 'contact'])

<a
    href="{{ route('whatsapp.cta', ['msg' => $msg]) }}"
    target="_blank"
    rel="noopener"
    {{ $attributes->class('whatsapp-number-link') }}
    aria-label="Abrir WhatsApp {{ config('branding.contact.whatsapp_display') }}"
>{{ config('branding.contact.whatsapp_display') }}</a>
