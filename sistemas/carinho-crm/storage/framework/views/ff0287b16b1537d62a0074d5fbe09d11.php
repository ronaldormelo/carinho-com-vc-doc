<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <title>Entrar | <?php echo e(config('branding.name')); ?></title>
    <link rel="icon" href="<?php echo e(asset(config('branding.assets.logo.favicon', '/images/favicon.ico'))); ?>" type="image/x-icon">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo e(asset('css/app.css')); ?>">
    
    <style>
        html, body { font-family: 'Nunito', 'Inter', Arial, sans-serif; margin: 0; -webkit-font-smoothing: antialiased; -moz-osx-font-smoothing: grayscale; }
        .login-page { min-height: 100vh; display: flex; align-items: center; justify-content: center; background: #F4F7F9; padding: 1rem; font-family: 'Nunito', 'Inter', Arial, sans-serif; }
        .login-card { width: 100%; max-width: 400px; background: #fff; border-radius: 12px; box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1); padding: 2rem; }
        .login-logo { text-align: center; margin-bottom: 1.5rem; }
        .login-logo img { height: 44px; }
        .login-title { font-family: 'Nunito', 'Inter', Arial, sans-serif; font-size: 1.25rem; font-weight: 700; color: #1a2b32; margin-bottom: 0.25rem; }
        .login-subtitle { font-size: 0.875rem; color: #9AA5B1; margin-bottom: 1.5rem; font-family: 'Nunito', 'Inter', Arial, sans-serif; }
        .login-form .form-group { margin-bottom: 1rem; }
        .login-form label { display: block; font-size: 0.875rem; font-weight: 500; color: #1a2b32; margin-bottom: 0.375rem; font-family: 'Nunito', 'Inter', Arial, sans-serif; }
        .login-form input[type="email"], .login-form input[type="password"] { width: 100%; padding: 0.5rem 0.75rem; border: 1px solid #e5e7eb; border-radius: 8px; font-size: 1rem; font-family: 'Nunito', 'Inter', Arial, sans-serif; }
        .login-form input:focus { outline: none; border-color: #5BBFAD; box-shadow: 0 0 0 3px rgba(91, 191, 173, 0.2); }
        .login-form .form-check { display: flex; align-items: center; gap: 0.5rem; margin-bottom: 1rem; font-family: 'Nunito', 'Inter', Arial, sans-serif; }
        .login-form .form-check input { width: auto; }
        .login-form .btn-submit { width: 100%; padding: 0.625rem 1rem; background: #5BBFAD; color: #fff; border: none; border-radius: 8px; font-size: 1rem; font-weight: 500; cursor: pointer; font-family: 'Nunito', 'Inter', Arial, sans-serif; }
        .login-form .btn-submit:hover { background: #4AA99A; }
        .login-errors { background: #FEE2E2; color: #B91C1C; padding: 0.75rem; border-radius: 8px; margin-bottom: 1rem; font-size: 0.875rem; font-family: 'Nunito', 'Inter', Arial, sans-serif; }
    </style>
</head>
<body>
    <div class="login-page">
        <div class="login-card">
            <div class="login-logo">
                <img src="<?php echo e(asset(config('branding.assets.logo.primary', '/images/logo-transparente.webp'))); ?>" alt="<?php echo e(config('branding.name')); ?>">
            </div>
            <h1 class="login-title">Entrar no CRM</h1>
            <p class="login-subtitle">Use seu e-mail e senha para acessar o painel.</p>

            <?php if($errors->any()): ?>
                <div class="login-errors">
                    <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <div><?php echo e($error); ?></div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
            <?php endif; ?>

            <form class="login-form" method="POST" action="<?php echo e(route('login')); ?>">
                <?php echo csrf_field(); ?>
                <div class="form-group">
                    <label for="email">E-mail</label>
                    <input type="email" id="email" name="email" value="<?php echo e(old('email')); ?>" required autofocus autocomplete="username">
                </div>
                <div class="form-group">
                    <label for="password">Senha</label>
                    <input type="password" id="password" name="password" required autocomplete="current-password">
                </div>
                <div class="form-check">
                    <input type="checkbox" id="remember" name="remember">
                    <label for="remember">Lembrar de mim</label>
                </div>
                <button type="submit" class="btn-submit">Entrar</button>
            </form>
        </div>
    </div>
</body>
</html>
<?php /**PATH /var/www/html/resources/views/auth/login.blade.php ENDPATH**/ ?>