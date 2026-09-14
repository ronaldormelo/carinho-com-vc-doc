<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">

    {{-- Google tag (gtag.js) — GA4 --}}
    @if(config('integrations.analytics.enabled') && config('integrations.analytics.ga4_id'))
    <script async src="https://www.googletagmanager.com/gtag/js?id={{ config('integrations.analytics.ga4_id') }}" data-cfasync="false"></script>
    <script data-cfasync="false">
      window.dataLayer = window.dataLayer || [];
      function gtag(){dataLayer.push(arguments);}
      gtag('js', new Date());

      gtag('config', '{{ config('integrations.analytics.ga4_id') }}');
    </script>
    @endif

    {{-- SEO Meta Tags --}}
    <title>{{ $seo['title'] ?? config('branding.seo.default_title') }}</title>
    <meta name="description" content="{{ $seo['description'] ?? config('branding.seo.default_description') }}">
    @if(isset($seo['keywords']))
    <meta name="keywords" content="{{ $seo['keywords'] }}">
    @endif

    {{-- Canonical URL --}}
    <link rel="canonical" href="{{ url()->current() }}">

    {{-- Open Graph --}}
    <meta property="og:title" content="{{ $seo['title'] ?? config('branding.seo.default_title') }}">
    <meta property="og:description" content="{{ $seo['description'] ?? config('branding.seo.default_description') }}">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:image" content="{{ asset(config('branding.assets.og_image')) }}">
    <meta property="og:site_name" content="{{ config('branding.name') }}">
    <meta property="og:locale" content="pt_BR">

    {{-- Twitter Card --}}
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $seo['title'] ?? config('branding.seo.default_title') }}">
    <meta name="twitter:description" content="{{ $seo['description'] ?? config('branding.seo.default_description') }}">

    {{-- Favicon --}}
    <link rel="icon" href="{{ asset(config('branding.assets.logo.favicon')) }}" type="image/x-icon">

    {{-- Fonts --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">

    {{-- Styles --}}
    <link rel="stylesheet" href="{{ asset('css/brand.css') }}">
    @stack('styles')

    {{-- CSRF Token --}}
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- Google Analytics / Tag Manager --}}
    @if(config('integrations.analytics.enabled') && config('integrations.analytics.gtm_id'))
    <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
    new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
    j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
    'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
    })(window,document,'script','dataLayer','{{ config('integrations.analytics.gtm_id') }}');</script>
    @endif

    {{-- reCAPTCHA (data-cfasync: Cloudflare Rocket Loader quebra grecaptcha.execute) --}}
    @if(config('integrations.recaptcha.enabled') && config('integrations.recaptcha.site_key'))
    <meta name="recaptcha-site-key" content="{{ config('integrations.recaptcha.site_key') }}">
    <script src="https://www.google.com/recaptcha/api.js?render={{ config('integrations.recaptcha.site_key') }}" data-cfasync="false"></script>
    @endif

    {{-- Schema.org JSON-LD --}}
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "LocalBusiness",
        "name": "{{ config('branding.name') }}",
        "description": "{{ config('branding.seo.default_description') }}",
        "url": "{{ config('app.url') }}",
        "telephone": "{{ config('branding.contact.whatsapp_display') }}",
        "email": "{{ config('branding.contact.email') }}",
        "priceRange": "$$",
        "areaServed": {
            "@type": "City",
            "name": "São Paulo"
        },
        "serviceType": ["Cuidador de Idosos", "Home Care", "Cuidado Domiciliar"]
    }
    </script>
</head>
<body>
    {{-- GTM noscript --}}
    @if(config('integrations.analytics.enabled') && config('integrations.analytics.gtm_id'))
    <noscript><iframe src="https://www.googletagmanager.com/ns.html?id={{ config('integrations.analytics.gtm_id') }}"
    height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
    @endif

    {{-- Header --}}
    @include('partials.header')

    {{-- Main Content --}}
    <main>
        @yield('content')
    </main>

    {{-- Footer --}}
    @include('partials.footer')

    {{-- WhatsApp Float Button --}}
    @include('partials.whatsapp-float')

    {{-- Scripts --}}
    @if(config('integrations.recaptcha.enabled') && config('integrations.recaptcha.site_key'))
    <script data-cfasync="false">
    window.carinhoGetRecaptchaToken = async function (action) {
        const siteKey = document.querySelector('meta[name="recaptcha-site-key"]')?.content;
        if (!siteKey) {
            return '';
        }
        if (typeof grecaptcha === 'undefined' || typeof grecaptcha.ready !== 'function') {
            throw new Error('recaptcha_unavailable');
        }
        await new Promise(function (resolve, reject) {
            const timeout = setTimeout(function () {
                reject(new Error('recaptcha_timeout'));
            }, 8000);
            grecaptcha.ready(function () {
                clearTimeout(timeout);
                resolve();
            });
        });
        return await grecaptcha.execute(siteKey, { action: action });
    };
    </script>
    @endif
    @stack('scripts')
</body>
</html>
