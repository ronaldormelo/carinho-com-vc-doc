# Arquitetura

## Visão Geral

Repositório central de documentos e conformidade LGPD (documentos.carinho.com.vc). Gerencia contratos, termos, consentimentos e auditoria de acesso com foco em segurança e conformidade legal.

## Stack Tecnológica

- **Linguagem:** PHP 8.2
- **Framework:** Laravel 11
- **Banco de dados:** MySQL 8.0
- **Cache e filas:** Redis 7
- **Storage:** AWS S3

## Componentes Principais

### Camada de Apresentação
- Controllers REST para APIs
- Views Blade para páginas públicas e assinatura
- Middleware de autenticação por token

### Camada de Serviços
- **DocumentService:** Gerenciamento de documentos
- **ContractService:** Criação e assinatura de contratos
- **ConsentService:** Gerenciamento de consentimentos LGPD
- **SignatureService:** Assinaturas digitais
- **LgpdService:** Solicitações de dados (exportação/exclusão)
- **NotificationService:** Envio de notificações

### Camada de Integração
- **S3StorageClient:** Upload/download seguro para AWS S3
- **ZApiClient:** Integração com WhatsApp via Z-API
- **CrmClient:** Sincronização com CRM
- **CuidadoresClient:** Integração com sistema de cuidadores
- **FinanceiroClient:** Integração com sistema financeiro
- **AtendimentoClient:** Integração com atendimento
- **IntegracoesClient:** Hub central de eventos

### Camada de Dados
- Models Eloquent com relacionamentos
- Tabelas de domínio para tipos/status
- Índices otimizados para consultas

## Diagrama de Componentes

```
┌─────────────────────────────────────────────────────────────────┐
│                        API Gateway                               │
├─────────────────────────────────────────────────────────────────┤
│                        Controllers                               │
│  Document │ Contract │ Consent │ Signature │ DataRequest │ etc  │
├─────────────────────────────────────────────────────────────────┤
│                         Services                                 │
│  DocumentService │ ContractService │ LgpdService │ etc          │
├─────────────────────────────────────────────────────────────────┤
│                       Integrations                               │
│  S3Storage │ Z-API │ CRM │ Cuidadores │ Financeiro │ etc        │
├─────────────────────────────────────────────────────────────────┤
│                     Models / Database                            │
│  Documents │ Signatures │ Consents │ AccessLogs │ etc           │
└─────────────────────────────────────────────────────────────────┘
```

## Integrações Externas

### AWS S3
- **Documentação:** https://docs.aws.amazon.com/sdk-for-php/
- **Uso:** Armazenamento de documentos com criptografia
- **Recursos:**
  - Upload com criptografia server-side (AES-256)
  - URLs pré-assinadas para downloads
  - Versionamento de objetos
  - Lifecycle policies

### Z-API (WhatsApp)
- **Documentação:** https://developer.z-api.io/
- **Uso:** Envio de notificações e códigos OTP
- **Endpoints:**
  - `POST /send-text` - Mensagens de texto
  - `POST /send-document` - Envio de documentos
  - `POST /send-link` - Links com preview

## Integrações Internas

### CRM
- Notificação de contratos criados/assinados
- Atualização de consentimentos
- Alertas de solicitações LGPD

### Cuidadores
- Gestão de documentos de cuidadores
- Notificação de contratos
- Status de documentos

### Financeiro
- Armazenamento de notas fiscais
- Comprovantes de pagamento

### Atendimento
- Envio de termos e política
- Notificações via canais de atendimento

### Integrações Hub
- Publicação de eventos
- Disparo de automações

## Dados e Armazenamento

### Banco de Dados
- Contratos, termos e políticas
- Consentimentos com data, hora e IP
- Logs de acesso e alteração
- Solicitações LGPD

### Storage S3
- Estrutura de pastas:
  - `clients/{client_id}/` - Documentos de clientes
  - `caregivers/{caregiver_id}/` - Documentos de cuidadores
  - `contracts/{year}/{month}/` - Contratos
  - `templates/` - Templates de documentos
  - `exports/` - Exportações LGPD

## Segurança e LGPD

### Criptografia
- Em repouso: AES-256 no S3
- Em trânsito: TLS 1.3

### Controle de Acesso
- Token interno entre sistemas
- URLs assinadas com expiração
- Logs de auditoria completos

### Conformidade LGPD
- Registro de base legal
- Consentimento com timestamp e fonte
- Direito de acesso, retificação e exclusão
- Prazo de 15 dias para solicitações

## Escalabilidade e Desempenho

### Storage
- S3 com escalabilidade automática
- Cache de metadados no Redis

### Cache
- Templates de documentos
- Consentimentos ativos
- Metadados de documentos

### Índices
- Por proprietário (owner_type_id, owner_id)
- Por documento e timestamp

## Observabilidade

### Logs
- Upload, download e assinatura
- Erros de integração
- Acessos a documentos

### Alertas
- Falhas de armazenamento
- Solicitações LGPD atrasadas
- Erros de integração

## Backup e Resiliência

### Backup
- Banco de dados: diário
- Storage S3: versionamento habilitado

### Recuperação
- Testes periódicos de restore
- Procedimento documentado
