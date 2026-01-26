<footer class="footer" role="contentinfo">
    <div class="container">
        <div class="footer-grid">
            
            <div class="footer-brand">
                <a href="<?php echo e(route('home')); ?>" class="logo logo-white" aria-label="Página inicial - Carinho com Você">
                    <img src="<?php echo e(asset(config('branding.assets.logo.white'))); ?>" alt="<?php echo e(config('branding.name')); ?>" />
                </a>
                <p><?php echo e(config('branding.value_proposition')); ?></p>
                <p>
                    <strong>WhatsApp:</strong> <?php echo e(config('branding.contact.whatsapp_display')); ?><br>
                    <strong>E-mail:</strong> <?php echo e(config('branding.contact.email')); ?>

                </p>
                <p style="margin-top: var(--spacing-4);">
                    <strong>Horário de Atendimento:</strong><br>
                    Seg a Sex: 08h às 20h<br>
                    Sáb: 09h às 18h
                </p>
            </div>

            
            <div>
                <h4 class="footer-title">Institucional</h4>
                <ul class="footer-links">
                    <li><a href="<?php echo e(route('about')); ?>">Quem Somos</a></li>
                    <li><a href="<?php echo e(route('services')); ?>">Nossos Serviços</a></li>
                    <li><a href="<?php echo e(route('how-it-works')); ?>">Como Funciona</a></li>
                    <li><a href="<?php echo e(route('faq')); ?>">Perguntas Frequentes</a></li>
                    <li><a href="<?php echo e(route('investors')); ?>">Investidores</a></li>
                    <li><a href="<?php echo e(route('contact')); ?>">Contato</a></li>
                </ul>
            </div>

            
            <div>
                <h4 class="footer-title">Para Você</h4>
                <ul class="footer-links">
                    <li><a href="<?php echo e(route('clients')); ?>">Preciso de um Cuidador</a></li>
                    <li><a href="<?php echo e(route('caregivers')); ?>">Quero ser Cuidador</a></li>
                    <li><a href="<?php echo e(route('legal.payment')); ?>">Preços e Pagamento</a></li>
                    <li><a href="<?php echo e(route('legal.cancellation')); ?>">Cancelamento</a></li>
                </ul>
            </div>

            
            <div>
                <h4 class="footer-title">Legal</h4>
                <ul class="footer-links">
                    <li><a href="<?php echo e(route('legal.privacy')); ?>">Política de Privacidade</a></li>
                    <li><a href="<?php echo e(route('legal.terms')); ?>">Termos de Uso</a></li>
                    <li><a href="<?php echo e(route('legal.cancellation')); ?>">Política de Cancelamento</a></li>
                    <li><a href="<?php echo e(route('legal.emergency')); ?>">Política de Emergências</a></li>
                    <li><a href="<?php echo e(route('legal.caregiver-terms')); ?>">Termos para Cuidadores</a></li>
                </ul>
            </div>
        </div>

        
        <div style="text-align: center; padding: var(--spacing-6) 0; border-top: 1px solid rgba(255, 255, 255, 0.1); margin-top: var(--spacing-6);">
            <p style="color: var(--color-text-muted); font-size: var(--font-size-sm); margin-bottom: var(--spacing-2);">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display: inline; vertical-align: middle; margin-right: 4px;" aria-hidden="true">
                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                </svg>
                Site seguro com certificado SSL | Seus dados estão protegidos
            </p>
        </div>

        
        <div class="footer-bottom">
            <p>&copy; <?php echo e(date('Y')); ?> <?php echo e(config('branding.name')); ?>. Todos os direitos reservados.</p>
            <p style="margin-top: var(--spacing-2);">
                <?php echo e(config('branding.domain')); ?>

            </p>
        </div>
    </div>
</footer>
<?php /**PATH /var/www/html/resources/views/partials/footer.blade.php ENDPATH**/ ?>