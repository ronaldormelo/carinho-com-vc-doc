<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">

    
    <title><?php echo e($seo['title'] ?? config('branding.seo.default_title')); ?></title>
    <meta name="description" content="<?php echo e($seo['description'] ?? config('branding.seo.default_description')); ?>">
    <?php if(isset($seo['keywords'])): ?>
    <meta name="keywords" content="<?php echo e($seo['keywords']); ?>">
    <?php endif; ?>

    
    <link rel="canonical" href="<?php echo e(url()->current()); ?>">

    
    <meta property="og:title" content="<?php echo e($seo['title'] ?? config('branding.seo.default_title')); ?>">
    <meta property="og:description" content="<?php echo e($seo['description'] ?? config('branding.seo.default_description')); ?>">
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?php echo e(url()->current()); ?>">
    <meta property="og:image" content="<?php echo e(asset(config('branding.assets.og_image'))); ?>">
    <meta property="og:site_name" content="<?php echo e(config('branding.name')); ?>">
    <meta property="og:locale" content="pt_BR">

    
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?php echo e($seo['title'] ?? config('branding.seo.default_title')); ?>">
    <meta name="twitter:description" content="<?php echo e($seo['description'] ?? config('branding.seo.default_description')); ?>">

    
    <link rel="icon" href="<?php echo e(asset(config('branding.assets.logo.favicon'))); ?>" type="image/x-icon">

    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">

    
    <link rel="stylesheet" href="<?php echo e(asset('css/brand.css')); ?>">
    <?php echo $__env->yieldPushContent('styles'); ?>

    
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">

    
    <?php if(config('integrations.analytics.enabled') && config('integrations.analytics.gtm_id')): ?>
    <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
    new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
    j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
    'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
    })(window,document,'script','dataLayer','<?php echo e(config('integrations.analytics.gtm_id')); ?>');</script>
    <?php endif; ?>

    
    <?php if(config('integrations.recaptcha.enabled') && config('integrations.recaptcha.site_key')): ?>
    <script src="https://www.google.com/recaptcha/api.js?render=<?php echo e(config('integrations.recaptcha.site_key')); ?>"></script>
    <?php endif; ?>

    
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "LocalBusiness",
        "name": "<?php echo e(config('branding.name')); ?>",
        "description": "<?php echo e(config('branding.seo.default_description')); ?>",
        "url": "<?php echo e(config('app.url')); ?>",
        "telephone": "<?php echo e(config('branding.contact.whatsapp_display')); ?>",
        "email": "<?php echo e(config('branding.contact.email')); ?>",
        "priceRange": "$$",
        "areaServed": {
            "@type": "City",
            "name": "Sao Paulo"
        },
        "serviceType": ["Cuidador de Idosos", "Home Care", "Cuidado Domiciliar"]
    }
    </script>
</head>
<body>
    
    <?php if(config('integrations.analytics.enabled') && config('integrations.analytics.gtm_id')): ?>
    <noscript><iframe src="https://www.googletagmanager.com/ns.html?id=<?php echo e(config('integrations.analytics.gtm_id')); ?>"
    height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
    <?php endif; ?>

    
    <?php echo $__env->make('partials.header', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    
    <main>
        <?php echo $__env->yieldContent('content'); ?>
    </main>

    
    <?php echo $__env->make('partials.footer', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    
    <?php echo $__env->make('partials.whatsapp-float', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    
    <?php echo $__env->yieldPushContent('scripts'); ?>
</body>
</html>
<?php /**PATH /var/www/html/resources/views/layouts/app.blade.php ENDPATH**/ ?>