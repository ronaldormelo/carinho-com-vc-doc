@extends('layouts.app')

@section('content')
{{-- Breadcrumb --}}
<nav aria-label="Breadcrumb" style="background: var(--bg-secondary); padding: var(--spacing-3) 0;">
    <div class="container">
        <ol style="list-style: none; padding: 0; margin: 0; display: flex; gap: var(--spacing-2); font-size: var(--font-size-sm); color: var(--color-text-muted);">
            <li><a href="{{ route('home') }}" style="color: var(--color-text-muted);">Início</a></li>
            <li aria-hidden="true">/</li>
            <li aria-current="page" style="color: var(--color-primary);">Investidores</li>
        </ol>
    </div>
</nav>

{{-- Hero Section --}}
<section class="section" style="background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-primary-dark) 100%); color: white; padding: var(--spacing-16) 0;">
    <div class="container">
        <div style="max-width: 800px;">
            <span style="background: rgba(255,255,255,0.2); padding: 4px 12px; border-radius: var(--border-radius); font-size: var(--font-size-sm); display: inline-block; margin-bottom: var(--spacing-4);">Plano de Negócios</span>
            <h1 style="color: white; font-size: var(--font-size-5xl); margin-bottom: var(--spacing-4);">Carinho com Você</h1>
            <p style="font-size: var(--font-size-xl); opacity: 0.9; margin-bottom: var(--spacing-6);">
                Plataforma digital de cuidadores domiciliares que conecta famílias a profissionais qualificados de forma rápida, segura e humanizada.
            </p>
            <div style="display: flex; gap: var(--spacing-8); flex-wrap: wrap;">
                <div>
                    <p style="font-size: var(--font-size-3xl); font-weight: 700; margin: 0;">R$ 30bi+</p>
                    <p style="opacity: 0.8; font-size: var(--font-size-sm);">Mercado de Home Care no Brasil</p>
                </div>
                <div>
                    <p style="font-size: var(--font-size-3xl); font-weight: 700; margin: 0;">15%</p>
                    <p style="opacity: 0.8; font-size: var(--font-size-sm);">Crescimento anual do setor</p>
                </div>
                <div>
                    <p style="font-size: var(--font-size-3xl); font-weight: 700; margin: 0;">30M+</p>
                    <p style="opacity: 0.8; font-size: var(--font-size-sm);">Idosos no Brasil em 2025</p>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- Executive Summary --}}
<section class="section">
    <div class="container">
        <div class="grid grid-2" style="gap: var(--spacing-12); align-items: center;">
            <div>
                <h2>Sumário Executivo</h2>
                <p class="text-light" style="font-size: var(--font-size-lg);">
                    A <strong>Carinho com Você</strong> é uma plataforma digital que resolve uma das maiores dores das famílias brasileiras: encontrar cuidadores domiciliares confiáveis de forma rápida e sem burocracia.
                </p>
                <p class="text-light">
                    Combinamos tecnologia com atendimento humanizado para entregar uma experiência superior tanto para famílias quanto para profissionais cuidadores.
                </p>
                
                <div class="highlight-box" style="margin-top: var(--spacing-6);">
                    <p style="margin: 0; font-style: italic; font-size: var(--font-size-lg);">
                        "Contratação rápida e sem complicação de cuidadores qualificados, com atendimento humanizado e gestão digital."
                    </p>
                    <p style="margin: var(--spacing-2) 0 0 0; font-size: var(--font-size-sm); color: var(--color-text-muted);">— Proposta de Valor</p>
                </div>
            </div>
            <div>
                <div class="card" style="background: var(--bg-secondary);">
                    <h4 style="color: var(--color-primary);">Destaques do Negócio</h4>
                    <ul style="color: var(--color-text-light); padding-left: 20px; margin: 0;">
                        <li style="margin-bottom: var(--spacing-3);">Modelo 100% digital e escalável</li>
                        <li style="margin-bottom: var(--spacing-3);">Margem operacional de 25-30% por serviço</li>
                        <li style="margin-bottom: var(--spacing-3);">Receita recorrente (contratos mensais)</li>
                        <li style="margin-bottom: var(--spacing-3);">Baixo investimento inicial em infraestrutura</li>
                        <li style="margin-bottom: var(--spacing-3);">Mercado em forte expansão (envelhecimento populacional)</li>
                        <li>CAC baixo via indicações e SEO local</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- O Problema --}}
<section class="section section-alt">
    <div class="container">
        <h2 class="text-center mb-8">O Problema que Resolvemos</h2>
        
        <div class="grid grid-2" style="gap: var(--spacing-8);">
            <div>
                <h3 style="color: var(--color-danger);">Dores das Famílias</h3>
                <div class="card">
                    <ul style="color: var(--color-text-light); padding-left: 20px; margin: 0;">
                        <li style="margin-bottom: var(--spacing-3);"><strong>Urgência:</strong> Necessidade imediata de iniciar o cuidado</li>
                        <li style="margin-bottom: var(--spacing-3);"><strong>Insegurança:</strong> Dúvidas sobre confiança e qualidade do profissional</li>
                        <li style="margin-bottom: var(--spacing-3);"><strong>Falta de continuidade:</strong> Dificuldade em substituições de última hora</li>
                        <li style="margin-bottom: var(--spacing-3);"><strong>Burocracia:</strong> Processos longos e falta de transparência</li>
                        <li><strong>Falta de padrão:</strong> Cada cuidador trabalha de um jeito</li>
                    </ul>
                </div>
            </div>
            <div>
                <h3 style="color: var(--color-primary);">Nossa Solução</h3>
                <div class="card" style="border: 2px solid var(--color-primary);">
                    <ul style="color: var(--color-text-light); padding-left: 20px; margin: 0;">
                        <li style="margin-bottom: var(--spacing-3);"><strong>Resposta rápida:</strong> SLA de 5 minutos no horário comercial</li>
                        <li style="margin-bottom: var(--spacing-3);"><strong>Cuidadores verificados:</strong> Documentação e curadoria de perfil</li>
                        <li style="margin-bottom: var(--spacing-3);"><strong>Substituição garantida:</strong> Backup automático em ausências</li>
                        <li style="margin-bottom: var(--spacing-3);"><strong>100% digital:</strong> WhatsApp + contratos online</li>
                        <li><strong>Padronização:</strong> Check-in/out e relatórios de atendimento</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- Modelo de Negócio --}}
<section class="section">
    <div class="container">
        <h2 class="text-center mb-8">Modelo de Negócio</h2>
        
        <div class="grid grid-3" style="margin-bottom: var(--spacing-12);">
            <div class="card text-center" style="border-top: 4px solid var(--color-primary);">
                <h3 style="color: var(--color-primary); font-size: var(--font-size-4xl); margin-bottom: 0;">70-75%</h3>
                <p class="text-muted" style="margin-bottom: var(--spacing-4);">Repasse ao Cuidador</p>
                <hr style="border: none; border-top: 1px solid var(--border-color); margin: var(--spacing-4) 0;">
                <h3 style="color: var(--color-text); font-size: var(--font-size-4xl); margin-bottom: 0;">25-30%</h3>
                <p class="text-muted">Margem Operacional</p>
            </div>
            
            <div class="card">
                <h4>Tipos de Serviço</h4>
                <table style="width: 100%; font-size: var(--font-size-sm);">
                    <tr style="border-bottom: 1px solid var(--border-color);">
                        <td style="padding: var(--spacing-2) 0;"><strong>Horista</strong></td>
                        <td style="text-align: right;">Mínimo 2h</td>
                    </tr>
                    <tr style="border-bottom: 1px solid var(--border-color);">
                        <td style="padding: var(--spacing-2) 0;"><strong>Diário</strong></td>
                        <td style="text-align: right;">Turnos 6-12h</td>
                    </tr>
                    <tr>
                        <td style="padding: var(--spacing-2) 0;"><strong>Mensal</strong></td>
                        <td style="text-align: right;">Escala fixa</td>
                    </tr>
                </table>
                <p class="text-muted mt-4" style="font-size: var(--font-size-sm);">Ticket médio maior em contratos mensais com receita recorrente.</p>
            </div>
            
            <div class="card">
                <h4>Fontes de Receita</h4>
                <ul style="color: var(--color-text-light); padding-left: 20px; margin: 0;">
                    <li style="margin-bottom: var(--spacing-2);">Margem sobre horas/diárias</li>
                    <li style="margin-bottom: var(--spacing-2);">Mensalidades recorrentes</li>
                    <li style="margin-bottom: var(--spacing-2);">Taxa de ativação (opcional)</li>
                    <li>Comissão sobre indicações</li>
                </ul>
            </div>
        </div>

        {{-- Fluxo Operacional --}}
        <h3 class="text-center mb-8">Fluxo Operacional</h3>
        <div style="display: flex; justify-content: center; flex-wrap: wrap; gap: var(--spacing-2); margin-bottom: var(--spacing-8);">
            <div style="background: var(--color-primary); color: white; padding: var(--spacing-3) var(--spacing-4); border-radius: var(--border-radius);">Lead</div>
            <div style="padding: var(--spacing-3); color: var(--color-text-muted);">→</div>
            <div style="background: var(--bg-secondary); padding: var(--spacing-3) var(--spacing-4); border-radius: var(--border-radius);">Triagem</div>
            <div style="padding: var(--spacing-3); color: var(--color-text-muted);">→</div>
            <div style="background: var(--bg-secondary); padding: var(--spacing-3) var(--spacing-4); border-radius: var(--border-radius);">Proposta</div>
            <div style="padding: var(--spacing-3); color: var(--color-text-muted);">→</div>
            <div style="background: var(--bg-secondary); padding: var(--spacing-3) var(--spacing-4); border-radius: var(--border-radius);">Contrato</div>
            <div style="padding: var(--spacing-3); color: var(--color-text-muted);">→</div>
            <div style="background: var(--bg-secondary); padding: var(--spacing-3) var(--spacing-4); border-radius: var(--border-radius);">Alocação</div>
            <div style="padding: var(--spacing-3); color: var(--color-text-muted);">→</div>
            <div style="background: var(--bg-secondary); padding: var(--spacing-3) var(--spacing-4); border-radius: var(--border-radius);">Execução</div>
            <div style="padding: var(--spacing-3); color: var(--color-text-muted);">→</div>
            <div style="background: var(--color-success); color: white; padding: var(--spacing-3) var(--spacing-4); border-radius: var(--border-radius);">Renovação</div>
        </div>
    </div>
</section>

{{-- Mercado --}}
<section class="section section-alt">
    <div class="container">
        <h2 class="text-center mb-8">Oportunidade de Mercado</h2>
        
        <div class="grid grid-3" style="margin-bottom: var(--spacing-8);">
            <div class="card text-center">
                <div class="feature-icon" style="margin: 0 auto var(--spacing-4); background: var(--color-primary-light);">
                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="var(--color-primary-dark)" stroke-width="2" aria-hidden="true">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                        <circle cx="9" cy="7" r="4"></circle>
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                    </svg>
                </div>
                <h3 style="color: var(--color-primary);">30+ milhões</h3>
                <p class="text-muted">de idosos no Brasil (14% da população)</p>
            </div>
            
            <div class="card text-center">
                <div class="feature-icon" style="margin: 0 auto var(--spacing-4); background: var(--color-primary-light);">
                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="var(--color-primary-dark)" stroke-width="2" aria-hidden="true">
                        <line x1="12" y1="1" x2="12" y2="23"></line>
                        <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                    </svg>
                </div>
                <h3 style="color: var(--color-primary);">R$ 30+ bi</h3>
                <p class="text-muted">mercado de home care no Brasil</p>
            </div>
            
            <div class="card text-center">
                <div class="feature-icon" style="margin: 0 auto var(--spacing-4); background: var(--color-primary-light);">
                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="var(--color-primary-dark)" stroke-width="2" aria-hidden="true">
                        <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"></polyline>
                        <polyline points="17 6 23 6 23 12"></polyline>
                    </svg>
                </div>
                <h3 style="color: var(--color-primary);">15% a.a.</h3>
                <p class="text-muted">crescimento esperado do setor</p>
            </div>
        </div>

        <div class="grid grid-2" style="gap: var(--spacing-8);">
            <div class="card">
                <h4>Tendências Favoráveis</h4>
                <ul style="color: var(--color-text-light); padding-left: 20px; margin: 0;">
                    <li style="margin-bottom: var(--spacing-2);">Envelhecimento acelerado da população brasileira</li>
                    <li style="margin-bottom: var(--spacing-2);">Preferência por cuidado domiciliar vs. institucionalização</li>
                    <li style="margin-bottom: var(--spacing-2);">Aumento da renda média das famílias</li>
                    <li style="margin-bottom: var(--spacing-2);">Digitalização dos serviços de saúde</li>
                    <li>Escassez de mão de obra qualificada (oportunidade de formação)</li>
                </ul>
            </div>
            
            <div class="card">
                <h4>Perfil do Cliente Ideal (ICP)</h4>
                <ul style="color: var(--color-text-light); padding-left: 20px; margin: 0;">
                    <li style="margin-bottom: var(--spacing-2);"><strong>Renda familiar:</strong> Acima de R$ 10.000/mês</li>
                    <li style="margin-bottom: var(--spacing-2);"><strong>Perfil:</strong> Idoso, PCD, TEA, pós-operatório</li>
                    <li style="margin-bottom: var(--spacing-2);"><strong>Urgência:</strong> Demanda imediata ou recorrente</li>
                    <li style="margin-bottom: var(--spacing-2);"><strong>Comportamento:</strong> Prefere pagar mais por conveniência</li>
                    <li><strong>Canal:</strong> WhatsApp como preferência</li>
                </ul>
            </div>
        </div>
    </div>
</section>

{{-- Diferenciais Competitivos --}}
<section class="section">
    <div class="container">
        <h2 class="text-center mb-8">Diferenciais Competitivos</h2>
        
        <div class="grid grid-4">
            <div class="card text-center">
                <div class="feature-icon" style="margin: 0 auto var(--spacing-4);">
                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <circle cx="12" cy="12" r="10"></circle>
                        <polyline points="12 6 12 12 16 14"></polyline>
                    </svg>
                </div>
                <h4>Velocidade</h4>
                <p class="text-muted">SLA de 5 minutos para primeira resposta. Contratação em horas, não dias.</p>
            </div>
            
            <div class="card text-center">
                <div class="feature-icon" style="margin: 0 auto var(--spacing-4);">
                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect>
                        <line x1="8" y1="21" x2="16" y2="21"></line>
                        <line x1="12" y1="17" x2="12" y2="21"></line>
                    </svg>
                </div>
                <h4>100% Digital</h4>
                <p class="text-muted">Sem visitas presenciais desnecessárias. Tudo via WhatsApp e contratos online.</p>
            </div>
            
            <div class="card text-center">
                <div class="feature-icon" style="margin: 0 auto var(--spacing-4);">
                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                    </svg>
                </div>
                <h4>Confiabilidade</h4>
                <p class="text-muted">Cuidadores verificados, check-in/out digital e substituição garantida.</p>
            </div>
            
            <div class="card text-center">
                <div class="feature-icon" style="margin: 0 auto var(--spacing-4);">
                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
                    </svg>
                </div>
                <h4>Humanização</h4>
                <p class="text-muted">Tecnologia com toque humano. Atendimento empático e personalizado.</p>
            </div>
        </div>
    </div>
</section>

{{-- Estratégia de Crescimento --}}
<section class="section section-alt">
    <div class="container">
        <h2 class="text-center mb-8">Estratégia de Crescimento</h2>
        
        <div class="grid grid-2" style="gap: var(--spacing-8);">
            <div>
                <h3>Aquisição de Clientes</h3>
                <div class="card">
                    <h4 style="color: var(--color-primary);">Canais Prioritários</h4>
                    <ul style="color: var(--color-text-light); padding-left: 20px;">
                        <li style="margin-bottom: var(--spacing-2);"><strong>Google Meu Negócio:</strong> SEO local para buscas "home care + cidade"</li>
                        <li style="margin-bottom: var(--spacing-2);"><strong>Google Ads:</strong> Campanhas de busca com intenção de compra</li>
                        <li style="margin-bottom: var(--spacing-2);"><strong>Meta Ads:</strong> Segmentação local no Facebook/Instagram</li>
                        <li style="margin-bottom: var(--spacing-2);"><strong>Indicações:</strong> Programa de referral com benefícios</li>
                        <li><strong>Parcerias:</strong> Clínicas, hospitais, condomínios</li>
                    </ul>
                </div>
                
                <div class="card mt-4">
                    <h4 style="color: var(--color-primary);">Funil de Aquisição</h4>
                    <ol style="color: var(--color-text-light); padding-left: 20px;">
                        <li style="margin-bottom: var(--spacing-2);">Atração: SEO, anúncios, conteúdo</li>
                        <li style="margin-bottom: var(--spacing-2);">Conversão: Landing page + WhatsApp</li>
                        <li style="margin-bottom: var(--spacing-2);">Qualificação: Triagem no atendimento</li>
                        <li style="margin-bottom: var(--spacing-2);">Fechamento: Proposta e contrato digital</li>
                        <li>Retenção: Feedback e plano recorrente</li>
                    </ol>
                </div>
            </div>
            
            <div>
                <h3>Expansão Geográfica</h3>
                <div class="card">
                    <h4 style="color: var(--color-primary);">Roadmap de Expansão</h4>
                    <div style="margin-bottom: var(--spacing-4);">
                        <div style="display: flex; justify-content: space-between; margin-bottom: var(--spacing-2);">
                            <span><strong>Fase 1:</strong> São Paulo Capital</span>
                            <span style="color: var(--color-success);">✓ Atual</span>
                        </div>
                        <div style="height: 8px; background: var(--color-success); border-radius: var(--border-radius);"></div>
                    </div>
                    <div style="margin-bottom: var(--spacing-4);">
                        <div style="display: flex; justify-content: space-between; margin-bottom: var(--spacing-2);">
                            <span><strong>Fase 2:</strong> Grande São Paulo</span>
                            <span style="color: var(--color-text-muted);">Próxima</span>
                        </div>
                        <div style="height: 8px; background: var(--bg-tertiary); border-radius: var(--border-radius);"></div>
                    </div>
                    <div style="margin-bottom: var(--spacing-4);">
                        <div style="display: flex; justify-content: space-between; margin-bottom: var(--spacing-2);">
                            <span><strong>Fase 3:</strong> Capitais do Sudeste</span>
                            <span style="color: var(--color-text-muted);">Planejada</span>
                        </div>
                        <div style="height: 8px; background: var(--bg-tertiary); border-radius: var(--border-radius);"></div>
                    </div>
                    <div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: var(--spacing-2);">
                            <span><strong>Fase 4:</strong> Nacional</span>
                            <span style="color: var(--color-text-muted);">Futuro</span>
                        </div>
                        <div style="height: 8px; background: var(--bg-tertiary); border-radius: var(--border-radius);"></div>
                    </div>
                </div>
                
                <div class="card mt-4">
                    <h4 style="color: var(--color-primary);">Escalabilidade</h4>
                    <p class="text-muted">
                        O modelo digital permite expansão com baixo investimento incremental. Cada nova cidade requer apenas:
                    </p>
                    <ul style="color: var(--color-text-light); padding-left: 20px; margin: 0;">
                        <li>Captação de base inicial de cuidadores</li>
                        <li>Campanhas de marketing local</li>
                        <li>Ajustes de precificação regional</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- Tecnologia e Operação --}}
<section class="section">
    <div class="container">
        <h2 class="text-center mb-8">Plataforma Tecnológica</h2>
        
        <div class="grid grid-2" style="gap: var(--spacing-8); margin-bottom: var(--spacing-8);">
            <div class="card">
                <h4>Stack Tecnológico</h4>
                <table style="width: 100%;">
                    <tr style="border-bottom: 1px solid var(--border-color);">
                        <td style="padding: var(--spacing-2) 0;"><strong>Backend</strong></td>
                        <td style="text-align: right;">PHP/Laravel</td>
                    </tr>
                    <tr style="border-bottom: 1px solid var(--border-color);">
                        <td style="padding: var(--spacing-2) 0;"><strong>Banco de Dados</strong></td>
                        <td style="text-align: right;">MySQL</td>
                    </tr>
                    <tr style="border-bottom: 1px solid var(--border-color);">
                        <td style="padding: var(--spacing-2) 0;"><strong>Cache/Filas</strong></td>
                        <td style="text-align: right;">Redis</td>
                    </tr>
                    <tr style="border-bottom: 1px solid var(--border-color);">
                        <td style="padding: var(--spacing-2) 0;"><strong>Infraestrutura</strong></td>
                        <td style="text-align: right;">Cloud (AWS/GCP)</td>
                    </tr>
                    <tr>
                        <td style="padding: var(--spacing-2) 0;"><strong>Integrações</strong></td>
                        <td style="text-align: right;">WhatsApp API, Analytics</td>
                    </tr>
                </table>
            </div>
            
            <div class="card">
                <h4>Módulos do Sistema</h4>
                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: var(--spacing-2);">
                    <div style="background: var(--bg-secondary); padding: var(--spacing-3); border-radius: var(--border-radius); text-align: center;">
                        <strong>Site</strong>
                        <p class="text-muted" style="font-size: var(--font-size-xs); margin: 0;">Captação</p>
                    </div>
                    <div style="background: var(--bg-secondary); padding: var(--spacing-3); border-radius: var(--border-radius); text-align: center;">
                        <strong>CRM</strong>
                        <p class="text-muted" style="font-size: var(--font-size-xs); margin: 0;">Leads</p>
                    </div>
                    <div style="background: var(--bg-secondary); padding: var(--spacing-3); border-radius: var(--border-radius); text-align: center;">
                        <strong>Atendimento</strong>
                        <p class="text-muted" style="font-size: var(--font-size-xs); margin: 0;">WhatsApp</p>
                    </div>
                    <div style="background: var(--bg-secondary); padding: var(--spacing-3); border-radius: var(--border-radius); text-align: center;">
                        <strong>Operação</strong>
                        <p class="text-muted" style="font-size: var(--font-size-xs); margin: 0;">Alocação</p>
                    </div>
                    <div style="background: var(--bg-secondary); padding: var(--spacing-3); border-radius: var(--border-radius); text-align: center;">
                        <strong>Cuidadores</strong>
                        <p class="text-muted" style="font-size: var(--font-size-xs); margin: 0;">Base</p>
                    </div>
                    <div style="background: var(--bg-secondary); padding: var(--spacing-3); border-radius: var(--border-radius); text-align: center;">
                        <strong>Financeiro</strong>
                        <p class="text-muted" style="font-size: var(--font-size-xs); margin: 0;">Pagamentos</p>
                    </div>
                    <div style="background: var(--bg-secondary); padding: var(--spacing-3); border-radius: var(--border-radius); text-align: center;">
                        <strong>Marketing</strong>
                        <p class="text-muted" style="font-size: var(--font-size-xs); margin: 0;">Campanhas</p>
                    </div>
                    <div style="background: var(--bg-secondary); padding: var(--spacing-3); border-radius: var(--border-radius); text-align: center;">
                        <strong>LGPD</strong>
                        <p class="text-muted" style="font-size: var(--font-size-xs); margin: 0;">Compliance</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="card" style="background: var(--bg-secondary);">
            <h4>Automações Implementadas</h4>
            <div class="grid grid-4" style="gap: var(--spacing-4);">
                <div style="text-align: center;">
                    <p style="font-size: var(--font-size-2xl); margin: 0;">📱</p>
                    <p class="text-muted" style="font-size: var(--font-size-sm);">WhatsApp → CRM automático</p>
                </div>
                <div style="text-align: center;">
                    <p style="font-size: var(--font-size-2xl); margin: 0;">📄</p>
                    <p class="text-muted" style="font-size: var(--font-size-sm);">Contratos digitais com assinatura</p>
                </div>
                <div style="text-align: center;">
                    <p style="font-size: var(--font-size-2xl); margin: 0;">🔔</p>
                    <p class="text-muted" style="font-size: var(--font-size-sm);">Notificações de check-in/out</p>
                </div>
                <div style="text-align: center;">
                    <p style="font-size: var(--font-size-2xl); margin: 0;">⭐</p>
                    <p class="text-muted" style="font-size: var(--font-size-sm);">Feedback pós-serviço</p>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- KPIs --}}
<section class="section section-alt">
    <div class="container">
        <h2 class="text-center mb-8">Indicadores-Chave (KPIs)</h2>
        
        <div class="grid grid-4">
            <div class="card text-center">
                <h4 style="color: var(--color-text-muted); font-size: var(--font-size-sm); text-transform: uppercase;">Tempo de Resposta</h4>
                <p style="font-size: var(--font-size-3xl); color: var(--color-primary); font-weight: 700; margin: var(--spacing-2) 0;">< 5min</p>
                <p class="text-muted" style="font-size: var(--font-size-sm);">SLA horário comercial</p>
            </div>
            <div class="card text-center">
                <h4 style="color: var(--color-text-muted); font-size: var(--font-size-sm); text-transform: uppercase;">Conversão Lead → Cliente</h4>
                <p style="font-size: var(--font-size-3xl); color: var(--color-primary); font-weight: 700; margin: var(--spacing-2) 0;">25-30%</p>
                <p class="text-muted" style="font-size: var(--font-size-sm);">Meta de conversão</p>
            </div>
            <div class="card text-center">
                <h4 style="color: var(--color-text-muted); font-size: var(--font-size-sm); text-transform: uppercase;">Taxa de Renovação</h4>
                <p style="font-size: var(--font-size-3xl); color: var(--color-primary); font-weight: 700; margin: var(--spacing-2) 0;">70%+</p>
                <p class="text-muted" style="font-size: var(--font-size-sm);">Contratos mensais</p>
            </div>
            <div class="card text-center">
                <h4 style="color: var(--color-text-muted); font-size: var(--font-size-sm); text-transform: uppercase;">NPS</h4>
                <p style="font-size: var(--font-size-3xl); color: var(--color-primary); font-weight: 700; margin: var(--spacing-2) 0;">50+</p>
                <p class="text-muted" style="font-size: var(--font-size-sm);">Satisfação do cliente</p>
            </div>
        </div>
    </div>
</section>

{{-- Compliance e Segurança --}}
<section class="section">
    <div class="container">
        <h2 class="text-center mb-8">Compliance e Segurança</h2>
        
        <div class="grid grid-3">
            <div class="card">
                <h4>LGPD</h4>
                <ul style="color: var(--color-text-light); padding-left: 20px; margin: 0;">
                    <li style="margin-bottom: var(--spacing-2);">Política de privacidade publicada</li>
                    <li style="margin-bottom: var(--spacing-2);">Consentimento registrado com timestamp</li>
                    <li style="margin-bottom: var(--spacing-2);">Processo de exclusão de dados</li>
                    <li>DPO designado</li>
                </ul>
            </div>
            
            <div class="card">
                <h4>Jurídico</h4>
                <ul style="color: var(--color-text-light); padding-left: 20px; margin: 0;">
                    <li style="margin-bottom: var(--spacing-2);">CNPJ e CNAE corretos</li>
                    <li style="margin-bottom: var(--spacing-2);">Contratos digitais padronizados</li>
                    <li style="margin-bottom: var(--spacing-2);">Termos de uso e responsabilidade</li>
                    <li>Aceite digital com log</li>
                </ul>
            </div>
            
            <div class="card">
                <h4>Segurança</h4>
                <ul style="color: var(--color-text-light); padding-left: 20px; margin: 0;">
                    <li style="margin-bottom: var(--spacing-2);">HTTPS em todas as páginas</li>
                    <li style="margin-bottom: var(--spacing-2);">Criptografia de dados sensíveis</li>
                    <li style="margin-bottom: var(--spacing-2);">Backups diários</li>
                    <li>Rate limiting e proteção CSRF</li>
                </ul>
            </div>
        </div>
    </div>
</section>

{{-- Equipe --}}
<section class="section section-alt">
    <div class="container">
        <h2 class="text-center mb-8">Estrutura Organizacional</h2>
        
        <div class="grid grid-2" style="gap: var(--spacing-8);">
            <div class="card">
                <h4>Áreas Funcionais</h4>
                <table style="width: 100%;">
                    <tr style="border-bottom: 1px solid var(--border-color);">
                        <td style="padding: var(--spacing-2) 0;"><strong>Atendimento</strong></td>
                        <td style="text-align: right; color: var(--color-text-muted);">Captação e triagem de leads</td>
                    </tr>
                    <tr style="border-bottom: 1px solid var(--border-color);">
                        <td style="padding: var(--spacing-2) 0;"><strong>Operação</strong></td>
                        <td style="text-align: right; color: var(--color-text-muted);">Match e confirmação de agenda</td>
                    </tr>
                    <tr style="border-bottom: 1px solid var(--border-color);">
                        <td style="padding: var(--spacing-2) 0;"><strong>Financeiro</strong></td>
                        <td style="text-align: right; color: var(--color-text-muted);">Pagamentos e repasses</td>
                    </tr>
                    <tr>
                        <td style="padding: var(--spacing-2) 0;"><strong>Suporte</strong></td>
                        <td style="text-align: right; color: var(--color-text-muted);">Emergências e substituições</td>
                    </tr>
                </table>
            </div>
            
            <div class="card">
                <h4>Modelo Enxuto</h4>
                <p class="text-muted">
                    Operação inicial com equipe reduzida, alavancada por tecnologia e automações:
                </p>
                <ul style="color: var(--color-text-light); padding-left: 20px; margin: 0;">
                    <li style="margin-bottom: var(--spacing-2);">Atendimento centralizado via WhatsApp</li>
                    <li style="margin-bottom: var(--spacing-2);">CRM automatizado para gestão de leads</li>
                    <li style="margin-bottom: var(--spacing-2);">Contratos e pagamentos digitais</li>
                    <li>Escala conforme demanda validada</li>
                </ul>
            </div>
        </div>
    </div>
</section>

{{-- CTA Contato --}}
<section class="section" style="background: var(--color-primary); color: white; padding: var(--spacing-16) 0;">
    <div class="container">
        <div style="max-width: 800px; margin: 0 auto; text-align: center;">
            <h2 style="color: white; font-size: var(--font-size-3xl); margin-bottom: var(--spacing-4);">Interessado em Investir?</h2>
            <p style="font-size: var(--font-size-lg); opacity: 0.9; margin-bottom: var(--spacing-8);">
                Entre em contato para receber o material completo com projeções financeiras, 
                valuation e oportunidades de participação.
            </p>
            
            <div class="card" style="background: white; color: var(--color-text); text-align: left; max-width: 600px; margin: 0 auto;">
                <h3 style="text-align: center; margin-bottom: var(--spacing-6);">Solicite Mais Informações</h3>
                
                <form id="investorContactForm" method="POST" action="{{ route('lead.investor.submit') }}" aria-label="Formulário de contato para investidores">
                    @csrf
                    
                    <div class="form-group">
                        <label class="form-label" for="investor_name">Nome completo *</label>
                        <input type="text" id="investor_name" name="name" class="form-input" required placeholder="Seu nome" autocomplete="name">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="investor_email">E-mail corporativo *</label>
                        <input type="email" id="investor_email" name="email" class="form-input" required placeholder="seu@empresa.com" autocomplete="email">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="investor_phone">Telefone *</label>
                        <input type="tel" id="investor_phone" name="phone" class="form-input" required placeholder="(11) 99999-9999" autocomplete="tel">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="investor_company">Empresa/Fundo</label>
                        <input type="text" id="investor_company" name="company" class="form-input" placeholder="Nome da empresa ou fundo">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="investor_interest">Interesse *</label>
                        <select id="investor_interest" name="interest" class="form-select" required>
                            <option value="">Selecione...</option>
                            <option value="investimento">Investimento financeiro</option>
                            <option value="parceria">Parceria estratégica</option>
                            <option value="aquisicao">Aquisição</option>
                            <option value="informacoes">Apenas informações</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="investor_message">Mensagem (opcional)</label>
                        <textarea id="investor_message" name="message" class="form-textarea" placeholder="Conte-nos mais sobre seu interesse..."></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-checkbox">
                            <input type="checkbox" name="consent" required>
                            <span>Concordo em receber informações sobre investimento e aceito a <a href="{{ route('legal.privacy') }}" target="_blank" style="color: var(--color-primary);">Política de Privacidade</a>. *</span>
                        </label>
                    </div>
                    
                    <input type="hidden" name="recaptcha_token" id="recaptcha_token_investor">
                    <input type="hidden" name="type" value="investor">
                    
                    <button type="submit" class="btn btn-primary btn-lg btn-block" id="submitBtnInvestor">
                        Enviar Solicitação
                    </button>
                    
                    <div id="formMessageInvestor" class="mt-4" style="display: none;" role="alert" aria-live="polite"></div>
                </form>
            </div>
            
            <div style="margin-top: var(--spacing-8);">
                <p style="opacity: 0.8; margin-bottom: var(--spacing-4);">Ou entre em contato diretamente:</p>
                <p style="font-size: var(--font-size-lg);">
                    <strong>E-mail:</strong> <x-mailto :address="config('branding.contact.email_investors')" /><br>
                    <strong>WhatsApp:</strong> {{ config('branding.contact.whatsapp_display') }}
                </p>
            </div>
        </div>
    </div>
</section>

{{-- Footer adicional --}}
<section class="section" style="background: var(--bg-secondary); padding: var(--spacing-8) 0;">
    <div class="container text-center">
        <p class="text-muted" style="font-size: var(--font-size-sm);">
            Este material é informativo e não constitui oferta pública de valores mobiliários. 
            Investimentos envolvem riscos. Consulte seus assessores antes de tomar decisões de investimento.
        </p>
    </div>
</section>
@endsection

@push('scripts')
<script>
document.getElementById('investorContactForm').addEventListener('submit', async function(e) {
    e.preventDefault();

    const form = e.target;
    const submitBtn = document.getElementById('submitBtnInvestor');
    const messageDiv = document.getElementById('formMessageInvestor');

    submitBtn.disabled = true;
    submitBtn.textContent = 'Enviando...';
    submitBtn.setAttribute('aria-busy', 'true');

    try {
        @if(config('integrations.recaptcha.enabled') && config('integrations.recaptcha.site_key'))
        const token = await grecaptcha.execute('{{ config('integrations.recaptcha.site_key') }}', {action: 'submit_investor'});
        document.getElementById('recaptcha_token_investor').value = token;
        @endif

        const formData = new FormData(form);
        const response = await fetch(form.action, {
            method: 'POST',
            body: formData,
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        });

        const data = await response.json();

        if (data.success) {
            messageDiv.innerHTML = '<div class="card" style="background: #d4edda; border-color: #c3e6cb; color: #155724;" role="status">' + data.message + '</div>';
            messageDiv.style.display = 'block';
            form.reset();
        } else {
            messageDiv.innerHTML = '<div class="card" style="background: #f8d7da; border-color: #f5c6cb; color: #721c24;" role="alert">' + (data.message || 'Erro ao enviar. Tente novamente.') + '</div>';
            messageDiv.style.display = 'block';
        }
    } catch (error) {
        messageDiv.innerHTML = '<div class="card" style="background: #f8d7da; border-color: #f5c6cb; color: #721c24;" role="alert">Erro ao enviar. Por favor, tente novamente ou entre em contato por e-mail.</div>';
        messageDiv.style.display = 'block';
    }

    submitBtn.disabled = false;
    submitBtn.textContent = 'Enviar Solicitação';
    submitBtn.setAttribute('aria-busy', 'false');
});
</script>
@endpush
