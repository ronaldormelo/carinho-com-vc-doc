<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro de cuidador — Carinho com Você</title>
    <style>
        :root { --primary:#5BBFAD; --bg:#F4F7F9; --text:#1a2b32; --muted:#616E7C; --accent:#F5C6AA; }
        * { box-sizing: border-box; }
        body { margin:0; font-family: Nunito, Inter, Arial, sans-serif; background: var(--bg); color: var(--text); }
        .wrap { max-width: 560px; margin: 2.5rem auto; background:#fff; border-radius:12px; padding:2rem; box-shadow:0 4px 16px rgb(0 0 0 / 0.06); }
        h1 { margin:0 0 .25rem; font-size:1.4rem; }
        p.lead { color: var(--muted); margin:0 0 1.5rem; }
        label { display:block; font-weight:600; margin:0 0 .35rem; }
        input, textarea { width:100%; padding:.6rem .75rem; border:1px solid #e5e7eb; border-radius:8px; font: inherit; }
        input:focus, textarea:focus { outline:none; border-color: var(--primary); box-shadow:0 0 0 3px rgba(91,191,173,.25); }
        .field { margin-bottom:1rem; }
        .btn { width:100%; border:0; background: var(--primary); color:#fff; padding:.75rem; border-radius:8px; font-weight:700; cursor:pointer; }
        .btn:hover { background:#4AA99A; }
        .errors { background:#FEE2E2; color:#B91C1C; padding:.75rem 1rem; border-radius:8px; margin-bottom:1rem; }
        .errors li { margin:.25rem 0; }
        .brand { color: var(--primary); font-weight:800; letter-spacing:.02em; margin-bottom:.75rem; }
    </style>
</head>
<body>
    <div class="wrap">
        <div class="brand">Carinho com Você</div>
        <h1>Cadastro de cuidador</h1>
        <p class="lead">Preencha seus dados. A equipe entra em contato para a triagem.</p>

        <?php if($errors->any()): ?>
            <div class="errors">
                <ul>
                    <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <li><?php echo e($error); ?></li>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" action="<?php echo e(route('cadastro.store')); ?>">
            <?php echo csrf_field(); ?>
            <div class="field">
                <label for="name">Nome completo</label>
                <input id="name" name="name" value="<?php echo e(old('name')); ?>" required maxlength="255">
            </div>
            <div class="field">
                <label for="phone">WhatsApp</label>
                <input id="phone" name="phone" value="<?php echo e(old('phone')); ?>" required maxlength="32" placeholder="86999990000">
            </div>
            <div class="field">
                <label for="email">E-mail (opcional)</label>
                <input id="email" type="email" name="email" value="<?php echo e(old('email')); ?>" maxlength="255">
            </div>
            <div class="field">
                <label for="city">Cidade</label>
                <input id="city" name="city" value="<?php echo e(old('city')); ?>" required maxlength="128">
            </div>
            <div class="field">
                <label for="experience_years">Anos de experiência</label>
                <input id="experience_years" type="number" min="0" name="experience_years" value="<?php echo e(old('experience_years', 0)); ?>">
            </div>
            <div class="field">
                <label for="profile_summary">Resumo do perfil (opcional)</label>
                <textarea id="profile_summary" name="profile_summary" rows="4" maxlength="2000"><?php echo e(old('profile_summary')); ?></textarea>
            </div>
            <button class="btn" type="submit">Enviar cadastro</button>
        </form>
    </div>
</body>
</html>
<?php /**PATH /var/www/html/resources/views/cadastro.blade.php ENDPATH**/ ?>