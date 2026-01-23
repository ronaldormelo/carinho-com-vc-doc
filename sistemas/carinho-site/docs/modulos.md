# Modulos do Sistema Carinho Site

## Visao Geral

O Carinho Site e o portal institucional do ecossistema Carinho com Voce. Sua funcao principal e apresentar a proposta de valor, informar sobre os servicos e captar leads que serao encaminhados para o WhatsApp e CRM.

## Modulos Implementados

### 1. Paginas Institucionais

#### 1.1 Home
- Hero com proposta de valor e CTA
- Features (beneficios da plataforma)
- Servicos resumidos
- Como funciona (passo a passo)
- Depoimentos de clientes
- CTA para WhatsApp

#### 1.2 Quem Somos
- Missao e proposito
- Valores da empresa
- Promessa central
- Diferenciais competitivos

#### 1.3 Servicos
- Detalhamento dos tipos de servico (horista, diario, mensal)
- Tipos de cuidado oferecidos
- O que esta incluso
- Informacoes sobre politicas

#### 1.4 Como Funciona
- Passo a passo do processo
- Diferenciais do modelo digital
- O que acontece durante o atendimento

#### 1.5 Contato
- Canais de contato (WhatsApp, e-mail, emergencias)
- Horarios de atendimento
- SLA de resposta

#### 1.6 FAQ
- Perguntas frequentes organizadas por categoria
- Categorias: Servicos, Pagamento, Para Cuidadores

### 2. Paginas por Publico

#### 2.1 Para Clientes
- Formulario de solicitacao de cuidador
- Campos: nome, telefone, cidade, urgencia, tipo de servico
- Campos opcionais: e-mail, condicao do paciente, observacoes
- Validacao de dados
- Integracao com reCAPTCHA
- Envio automatico para CRM

#### 2.2 Para Cuidadores
- Formulario de cadastro para trabalhar na plataforma
- Campos: nome, telefone, e-mail, cidade, experiencia
- Especialidades e disponibilidade
- Informacoes sobre comissoes e beneficios
- Integracao com CRM

### 3. Paginas Legais

#### 3.1 Politica de Privacidade
- Conformidade com LGPD (Lei 13.709/2018)
- Dados coletados e finalidades
- Bases legais para tratamento
- Compartilhamento de dados
- Direitos do titular
- Seguranca e retencao
- Contato do DPO

#### 3.2 Termos de Uso
- Descricao dos servicos
- Obrigacoes do cliente
- Relacao com cuidadores
- Pagamento e cancelamento
- Limitacao de responsabilidade
- Propriedade intelectual

#### 3.3 Politica de Cancelamento
- Regras de reembolso por prazo
- Como solicitar cancelamento
- Cancelamento pelo cuidador
- Forca maior
- Reagendamento

#### 3.4 Politica de Pagamento
- Pagamento adiantado
- Formas de pagamento aceitas
- Atraso e encargos
- Comissoes dos cuidadores
- Politica de repasses
- Notas fiscais

#### 3.5 Politica de Emergencias
- Canais de emergencia
- Tempo de resposta por nivel
- Tipos de emergencia e acoes
- Procedimentos em emergencia medica
- Escalonamento
- Numeros uteis

#### 3.6 Termos para Cuidadores
- Natureza da relacao
- Requisitos para cadastro
- Obrigacoes e proibicoes
- Comissoes e pagamentos
- Cancelamento e faltas
- Avaliacoes

### 4. Sistema de Formularios e Leads

#### 4.1 Captacao
- Formularios com validacao frontend e backend
- Normalizacao de telefone
- Registro de consentimento LGPD
- Captura de IP e user agent

#### 4.2 UTM Tracking
- Captura de parametros UTM na URL
- Armazenamento em sessao
- Vinculacao ao lead
- Passagem para CRM

#### 4.3 Sincronizacao com CRM
- Job assincrono para envio
- Retry automatico em falhas
- Backoff exponencial
- Dead letter para falhas persistentes

### 5. Integracoes

#### 5.1 CRM (carinho-crm)
- Criacao/atualizacao de leads
- Registro de origem e UTM
- Verificacao de duplicidade por telefone

#### 5.2 WhatsApp (Z-API)
- Geracao de links de CTA
- Notificacao de novos leads
- Mensagem de boas-vindas para urgentes

#### 5.3 Analytics
- Google Analytics 4
- Google Tag Manager
- Eventos de conversao

#### 5.4 reCAPTCHA v3
- Validacao de formularios
- Score minimo configuravel
- Fallback para desenvolvimento

### 6. SEO e Performance

#### 6.1 SEO
- Meta tags otimizadas por pagina
- Open Graph e Twitter Cards
- Schema.org JSON-LD
- URLs amigaveis
- Canonical URLs

#### 6.2 Performance
- Cache de paginas no Redis
- Compressao de assets
- Fontes web otimizadas
- Lazy loading de imagens

### 7. Identidade Visual

#### 7.1 CSS de Marca
- Variaveis CSS customizadas
- Paleta de cores definida
- Tipografia padronizada
- Componentes reutilizaveis
- Layout responsivo

#### 7.2 Componentes
- Header com navegacao
- Footer com links e contato
- Botao flutuante WhatsApp
- Cards de servico
- Formularios estilizados

## Fluxos Principais

### Fluxo de Lead Cliente
1. Usuario acessa pagina de clientes
2. Preenche formulario com dados
3. Valida reCAPTCHA
4. Cria submissao no banco
5. Dispara job para CRM
6. Dispara notificacao WhatsApp
7. Redireciona para WhatsApp

### Fluxo de Lead Cuidador
1. Usuario acessa pagina de cuidadores
2. Preenche formulario de cadastro
3. Valida reCAPTCHA
4. Cria submissao no banco
5. Dispara job para CRM (tipo cuidador)
6. Exibe mensagem de sucesso

### Fluxo de UTM
1. Usuario acessa URL com parametros UTM
2. Middleware captura e armazena em sessao
3. Ao submeter formulario, busca UTM da sessao
4. Cria ou encontra registro de campanha
5. Vincula campanha a submissao
6. Passa informacoes para CRM

## Proximos Passos

- [ ] Implementar paginas por cidade (SEO local)
- [ ] Adicionar blog/conteudo educativo
- [ ] Implementar chat ao vivo
- [ ] A/B testing em CTAs
- [ ] Integracao com Google Meu Negocio
