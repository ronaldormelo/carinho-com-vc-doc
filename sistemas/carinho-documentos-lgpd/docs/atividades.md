# Atividades

Lista de atividades implementadas no sistema Carinho Documentos e LGPD.

## Organização de Documentos

### Implementado
- [x] Estrutura de pastas por cliente e cuidador no S3
- [x] Padrões de nomenclatura com timestamp e UUID
- [x] Versionamento de documentos
- [x] Checksums SHA-256 para integridade

### Componentes
- `S3StorageClient` - Upload/download para AWS S3
- `DocumentVersion` - Modelo de versões
- `DocumentService` - Gerenciamento de documentos

## Contratos e Termos

### Implementado
- [x] Contratos padrão com clientes e cuidadores
- [x] Sistema de templates com variáveis dinâmicas
- [x] Assinatura digital com OTP via WhatsApp
- [x] Assinatura por clique
- [x] Registro de aceite com IP e timestamp
- [x] Política de privacidade e termos publicados

### Componentes
- `ContractService` - Criação e assinatura
- `DocumentTemplate` - Templates de contratos
- `Signature` - Registro de assinaturas
- Views: `contracts/sign.blade.php`, `public/terms.blade.php`, `public/privacy.blade.php`

## LGPD e Consentimento

### Implementado
- [x] Definição de base legal para tratamento
- [x] Registro de consentimento com data/hora
- [x] Tipos de consentimento (dados, marketing, compartilhamento, etc.)
- [x] Revogação de consentimento
- [x] Histórico completo de consentimentos
- [x] Processo de exportação de dados
- [x] Processo de exclusão de dados
- [x] Prazo de 15 dias para solicitações

### Componentes
- `ConsentService` - Gerenciamento de consentimentos
- `LgpdService` - Solicitações LGPD
- `DataRequest` - Modelo de solicitações
- `Consent` - Modelo de consentimentos

## Segurança e Acesso

### Implementado
- [x] Níveis de acesso por token interno
- [x] URLs assinadas com expiração
- [x] Registro de logs de acesso
- [x] Auditoria completa de ações
- [x] Criptografia de documentos no S3

### Componentes
- `VerifyInternalToken` - Middleware de autenticação
- `AccessLog` - Modelo de logs
- `AccessLogController` - Relatórios de acesso

## Integrações

### Implementado
- [x] AWS S3 - Armazenamento de documentos
- [x] Z-API - WhatsApp para OTP e notificações
- [x] CRM - Sincronização de eventos
- [x] Cuidadores - Documentos de cuidadores
- [x] Financeiro - Notas e comprovantes
- [x] Atendimento - Envio de termos
- [x] Hub de Integrações - Eventos

### Componentes
- `app/Integrations/` - Clientes de integração

## Notificações

### Implementado
- [x] Email de contrato pronto
- [x] Email de contrato assinado
- [x] WhatsApp com link de assinatura
- [x] WhatsApp com código OTP
- [x] Confirmação de assinatura

### Componentes
- `NotificationService` - Envio de notificações
- `SendContractNotification` - Job de notificação
- Views: `emails/contrato_pronto.blade.php`, `emails/contrato_assinado.blade.php`

## Jobs Assíncronos

### Implementado
- [x] Processamento de assinatura de contrato
- [x] Exportação de dados LGPD
- [x] Exclusão de dados LGPD
- [x] Políticas de retenção
- [x] Limpeza de documentos expirados
- [x] Sincronização com storage
- [x] Sincronização com CRM

### Componentes
- `ProcessContractSignature`
- `ProcessDataExport`
- `ProcessDataDeletion`
- `ProcessRetentionPolicies`
- `CleanExpiredDocuments`
- `SyncDocumentsWithStorage`
- `SyncWithCrm`

## Páginas Públicas

### Implementado
- [x] Página de assinatura de contrato
- [x] Página de termos de uso
- [x] Página de política de privacidade
- [x] CSS com identidade visual da marca

### Componentes
- Views em `resources/views/`
- CSS em `public/css/brand.css`

## APIs REST

### Documentos
- `POST /api/documents/upload`
- `GET /api/documents/{id}`
- `GET /api/documents/{id}/signed-url`
- `GET /api/documents/owner/{type}/{id}`

### Contratos
- `POST /api/contracts`
- `POST /api/contracts/{id}/sign`
- `GET /api/contracts/{id}/status`
- `GET /api/contracts/client/{id}`
- `GET /api/contracts/caregiver/{id}`

### Consentimentos
- `POST /api/consents`
- `GET /api/consents/{id}`
- `DELETE /api/consents/{id}`
- `GET /api/consents/check/{type}/{id}/{consent}`
- `GET /api/consents/history/{type}/{id}`

### Solicitações LGPD
- `POST /api/data-requests`
- `GET /api/data-requests/{id}`
- `POST /api/data-requests/{id}/process`
- `POST /api/data-requests/export`
- `POST /api/data-requests/delete`

### Assinaturas
- `POST /api/signatures/send-otp`
- `GET /api/signatures/verify/{id}`
- `GET /api/signatures/document/{id}`

### Templates
- `GET /api/templates`
- `GET /api/templates/type/{type}`
- `POST /api/templates/{id}/render`

### Logs
- `GET /api/access-logs`
- `GET /api/access-logs/document/{id}`
- `GET /api/access-logs/report`

### Políticas de Retenção
- `GET /api/retention-policies`
- `POST /api/retention-policies/execute`
- `GET /api/retention-policies/pending`
