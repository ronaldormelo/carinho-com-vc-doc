

<?php $__env->startSection('content'); ?>

<section class="section" style="background: var(--color-primary); color: white; padding: var(--spacing-12) 0;">
    <div class="container">
        <h1 style="color: white;">Termos para Cuidadores</h1>
        <p style="opacity: 0.9;">Carinho com Voce</p>
    </div>
</section>


<section class="section">
    <div class="container">
        <div class="legal-content">
            <p class="text-muted">Ultima atualizacao: <?php echo e(date('d/m/Y')); ?></p>

            <h2>1. ACEITACAO DOS TERMOS</h2>
            <p>
                Ao se cadastrar como cuidador parceiro da Carinho com Voce, voce concorda com estes Termos.
                Eles regem a relacao entre voce e a plataforma, estabelecendo direitos e obrigacoes de ambas as partes.
            </p>

            <h2>2. NATUREZA DA RELACAO</h2>
            <p>
                A Carinho com Voce e uma plataforma de intermediacao. Os cuidadores parceiros sao profissionais
                autonomos ou microempreendedores individuais, nao havendo vinculo empregaticio com a plataforma.
            </p>
            <p>A relacao envolve:</p>
            <ul>
                <li>Cadastro e verificacao de perfil</li>
                <li>Indicacao de oportunidades de trabalho</li>
                <li>Gestao de agenda e comunicacao</li>
                <li>Processamento de pagamentos</li>
                <li>Suporte operacional</li>
            </ul>

            <h2>3. REQUISITOS PARA CADASTRO</h2>
            <p>Para ser um cuidador parceiro, voce deve:</p>
            <ul>
                <li>Ter no minimo 18 anos de idade</li>
                <li>Possuir documentos de identificacao validos (RG, CPF)</li>
                <li>Comprovar experiencia minima de 1 ano como cuidador</li>
                <li>Possuir certificado de curso de cuidador (desejavel)</li>
                <li>Nao possuir antecedentes criminais graves</li>
                <li>Manter disponibilidade atualizada na plataforma</li>
            </ul>

            <h2>4. PROCESSO DE VERIFICACAO</h2>
            <p>Apos o cadastro, seu perfil passara por verificacao que inclui:</p>
            <ul>
                <li>Validacao de documentos de identidade</li>
                <li>Verificacao de experiencia e referencias</li>
                <li>Avaliacao de perfil por nossa equipe</li>
                <li>Assinatura de contrato digital</li>
            </ul>
            <p>
                A Carinho com Voce se reserva o direito de recusar ou desativar cadastros que nao atendam aos criterios.
            </p>

            <h2>5. OBRIGACOES DO CUIDADOR</h2>
            <p>Ao aceitar um atendimento, voce se compromete a:</p>
            <ul>
                <li>Comparecer pontualmente no local e horario combinados</li>
                <li>Realizar check-in e check-out pelo sistema</li>
                <li>Prestar o servico com zelo, dedicacao e respeito</li>
                <li>Seguir as orientacoes da familia e do plano de cuidado</li>
                <li>Registrar atividades realizadas durante o atendimento</li>
                <li>Comunicar imediatamente qualquer intercorrencia</li>
                <li>Manter sigilo sobre informacoes dos clientes</li>
                <li>Zelar pela seguranca do paciente</li>
            </ul>

            <h2>6. PROIBICOES</h2>
            <p>E expressamente proibido:</p>
            <ul>
                <li>Captar clientes diretamente, fora da plataforma</li>
                <li>Receber pagamentos diretamente dos clientes</li>
                <li>Deixar o paciente sem supervisao durante o atendimento</li>
                <li>Realizar procedimentos medicos sem habilitacao</li>
                <li>Compartilhar dados de clientes com terceiros</li>
                <li>Utilizar celular excessivamente durante o atendimento</li>
                <li>Consumir bebidas alcoolicas ou substancias ilicitas no trabalho</li>
            </ul>
            <p>
                Violacoes podem resultar em suspensao ou desligamento da plataforma.
            </p>

            <h2>7. COMISSOES E PAGAMENTOS</h2>
            <p>Voce recebera percentual do valor do servico conforme o tipo:</p>

            <table class="policy-table">
                <thead>
                    <tr>
                        <th>Tipo de Servico</th>
                        <th>Seu Percentual</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Horista</td>
                        <td><strong><?php echo e($commission['horista']); ?>%</strong></td>
                    </tr>
                    <tr>
                        <td>Diario</td>
                        <td><strong><?php echo e($commission['diario']); ?>%</strong></td>
                    </tr>
                    <tr>
                        <td>Mensal</td>
                        <td><strong><?php echo e($commission['mensal']); ?>%</strong></td>
                    </tr>
                </tbody>
            </table>

            <p><strong>Bonus:</strong></p>
            <ul>
                <li>Ate +<?php echo e($commission['bonus']['rating']); ?>% por avaliacao alta (media acima de 4.5)</li>
                <li>Ate +<?php echo e($commission['bonus']['tenure']); ?>% por tempo de casa (apos 1 ano)</li>
            </ul>

            <h2>8. REPASSES</h2>
            <table class="policy-table">
                <tbody>
                    <tr>
                        <td><strong>Frequencia</strong></td>
                        <td>Semanal (sextas-feiras)</td>
                    </tr>
                    <tr>
                        <td><strong>Valor minimo</strong></td>
                        <td>R$ <?php echo e(number_format($payoutPolicy['min_value'], 2, ',', '.')); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Liberacao</strong></td>
                        <td><?php echo e($payoutPolicy['release_days']); ?> dias apos conclusao do servico</td>
                    </tr>
                </tbody>
            </table>

            <div class="highlight-box">
                <p style="margin: 0;">
                    <strong>Importante:</strong> Para receber os repasses, voce deve manter seus dados bancarios
                    atualizados na plataforma. Repasses sao feitos exclusivamente via PIX.
                </p>
            </div>

            <h2>9. CANCELAMENTO E FALTAS</h2>
            <p>Se voce precisar cancelar um atendimento:</p>
            <ul>
                <li><strong>Mais de 24h antes:</strong> Comunique pelo sistema, sem penalidade</li>
                <li><strong>Menos de 24h:</strong> Comunique imediatamente e justifique</li>
                <li><strong>Nao comparecimento (no-show):</strong> Pode resultar em suspensao</li>
            </ul>
            <p>
                Cancelamentos frequentes ou injustificados podem levar a suspensao ou desligamento da plataforma.
            </p>

            <h2>10. AVALIACOES</h2>
            <p>
                Apos cada atendimento, o cliente podera avaliar seu servico. As avaliacoes:
            </p>
            <ul>
                <li>Sao confidenciais (cliente nao e identificado)</li>
                <li>Influenciam seu ranking na plataforma</li>
                <li>Podem impactar os bonus recebidos</li>
                <li>Sao utilizadas para melhoria do servico</li>
            </ul>
            <p>
                Avaliacoes consistentemente baixas podem resultar em revisao da parceria.
            </p>

            <h2>11. CONFIDENCIALIDADE</h2>
            <p>
                Voce se compromete a manter sigilo sobre todas as informacoes dos clientes,
                incluindo dados pessoais, condicoes de saude e rotinas familiares.
            </p>
            <p>
                A violacao de confidencialidade constitui falta grave e pode resultar em
                desligamento imediato e medidas legais cabiveis.
            </p>

            <h2>12. ENCERRAMENTO DA PARCERIA</h2>
            <p>A parceria pode ser encerrada:</p>
            <ul>
                <li><strong>Por voce:</strong> a qualquer momento, comunicando com 15 dias de antecedencia</li>
                <li><strong>Pela plataforma:</strong> por descumprimento destes termos ou avaliacoes insatisfatorias</li>
            </ul>
            <p>
                Atendimentos ja agendados devem ser concluidos ou transferidos antes do encerramento.
            </p>

            <h2>13. CONTATO</h2>
            <p>Para duvidas sobre estes termos ou a parceria:</p>
            <p>
                <strong>WhatsApp Cuidadores:</strong> <?php echo e(config('branding.contact.whatsapp_display')); ?><br>
                <strong>E-mail:</strong> <a href="mailto:<?php echo e(config('branding.contact.email')); ?>"><?php echo e(config('branding.contact.email')); ?></a>
            </p>
        </div>
    </div>
</section>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/html/resources/views/legal/caregiver-terms.blade.php ENDPATH**/ ?>