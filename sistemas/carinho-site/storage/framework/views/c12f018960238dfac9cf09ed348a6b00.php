

<?php $__env->startSection('content'); ?>

<nav aria-label="Breadcrumb" style="background: var(--bg-secondary); padding: var(--spacing-3) 0;">
    <div class="container">
        <ol style="list-style: none; padding: 0; margin: 0; display: flex; gap: var(--spacing-2); font-size: var(--font-size-sm); color: var(--color-text-muted);">
            <li><a href="<?php echo e(route('home')); ?>" style="color: var(--color-text-muted);">Início</a></li>
            <li aria-hidden="true">/</li>
            <li aria-current="page" style="color: var(--color-primary);">Quem Somos</li>
        </ol>
    </div>
</nav>


<section class="section" style="background: linear-gradient(135deg, var(--bg-secondary) 0%, var(--bg-primary) 100%); padding-bottom: var(--spacing-8);">
    <div class="container">
        <h1>Quem Somos</h1>
        <p class="text-light" style="font-size: var(--font-size-xl); max-width: 600px;">
            Somos a Carinho com Você, uma empresa dedicada a tornar o cuidado domiciliar simples, humano e confiável.
        </p>
    </div>
</section>


<section class="section">
    <div class="container">
        <div class="grid grid-2" style="align-items: center; gap: var(--spacing-12);">
            <div>
                <h2>Nossa Missão</h2>
                <p class="text-light" style="font-size: var(--font-size-lg);">
                    Tornar o cuidado domiciliar simples, humano e confiável, reduzindo o esforço
                    das famílias para encontrar cuidadores qualificados.
                </p>
                <p class="text-light">
                    Sabemos como é difícil encontrar um profissional de confiança quando alguém
                    que amamos precisa de cuidado. Por isso, criamos uma solução que conecta famílias
                    a cuidadores qualificados de forma rápida, segura e sem complicação.
                </p>
            </div>
            <div class="text-center">
                <div style="background: var(--color-primary-light); padding: var(--spacing-8); border-radius: var(--border-radius-xl);">
                    <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100" viewBox="0 0 24 24" fill="none" stroke="var(--color-primary-dark)" stroke-width="1.5" aria-hidden="true">
                        <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
                    </svg>
                </div>
            </div>
        </div>
    </div>
</section>


<section class="section section-alt">
    <div class="container">
        <h2 class="text-center mb-8">Nossos Valores</h2>

        <div class="grid grid-4">
            <div class="card text-center">
                <div class="feature-icon" style="margin: 0 auto var(--spacing-4);">
                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
                    </svg>
                </div>
                <h4>Humana e Acolhedora</h4>
                <p class="text-muted">Tratamos cada família com empatia e respeito.</p>
            </div>

            <div class="card text-center">
                <div class="feature-icon" style="margin: 0 auto var(--spacing-4);">
                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                    </svg>
                </div>
                <h4>Profissional e Segura</h4>
                <p class="text-muted">Rigor na seleção e verificação de cuidadores.</p>
            </div>

            <div class="card text-center">
                <div class="feature-icon" style="margin: 0 auto var(--spacing-4);">
                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <polyline points="20 6 9 17 4 12"></polyline>
                    </svg>
                </div>
                <h4>Simples e Direta</h4>
                <p class="text-muted">Sem burocracia, sem complicação.</p>
            </div>

            <div class="card text-center">
                <div class="feature-icon" style="margin: 0 auto var(--spacing-4);">
                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                        <polyline points="22 4 12 14.01 9 11.01"></polyline>
                    </svg>
                </div>
                <h4>Confiável e Responsável</h4>
                <p class="text-muted">Compromisso com a continuidade do cuidado.</p>
            </div>
        </div>
    </div>
</section>


<section class="section">
    <div class="container">
        <div class="highlight-box" style="max-width: 800px; margin: 0 auto; padding: var(--spacing-8); text-align: center;">
            <h2 style="color: var(--color-primary); margin-bottom: var(--spacing-4);">Nossa Promessa</h2>
            <p style="font-size: var(--font-size-xl); margin-bottom: 0;">
                "Atendimento rápido, transparente e com continuidade."
            </p>
        </div>
    </div>
</section>


<section class="section section-alt">
    <div class="container">
        <h2 class="text-center mb-8">O que nos diferencia</h2>

        <div class="grid grid-2" style="gap: var(--spacing-8);">
            <div class="card">
                <h4>Processo 100% Digital</h4>
                <p class="text-muted">
                    Eliminamos processos manuais, ligações infinitas e visitas presenciais desnecessárias.
                    Tudo pode ser resolvido pelo WhatsApp de forma rápida e prática.
                </p>
            </div>

            <div class="card">
                <h4>Cuidadores Verificados</h4>
                <p class="text-muted">
                    Todos os cuidadores passam por um processo de verificação que inclui
                    documentos, experiência e avaliação de perfil.
                </p>
            </div>

            <div class="card">
                <h4>Resposta em Minutos</h4>
                <p class="text-muted">
                    Nosso SLA é de 5 minutos para primeira resposta no horário comercial.
                    Valorizamos seu tempo e sua urgência.
                </p>
            </div>

            <div class="card">
                <h4>Substituição Garantida</h4>
                <p class="text-muted">
                    Se o cuidador não puder comparecer, nós encontramos um substituto
                    para que você não fique sem suporte.
                </p>
            </div>
        </div>
    </div>
</section>


<section class="cta-section">
    <div class="container">
        <h2>Conheça nossos serviços</h2>
        <p>Encontre o modelo ideal de cuidado para sua família.</p>
        <a href="<?php echo e(route('services')); ?>" class="btn btn-secondary btn-lg">Ver serviços</a>
    </div>
</section>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/html/resources/views/pages/about.blade.php ENDPATH**/ ?>