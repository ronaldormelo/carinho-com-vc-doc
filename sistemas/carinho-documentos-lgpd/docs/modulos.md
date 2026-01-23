# Módulos

Documentação dos módulos implementados no sistema Carinho Documentos e LGPD.

## 1. Módulo de Documentos

### Responsabilidade
Gerenciamento de documentos com upload, download, versionamento e exclusão.

### Componentes
- `DocumentController` - API REST
- `DocumentService` - Lógica de negócio
- `Document` - Model
- `DocumentVersion` - Model de versões

### Funcionalidades
- Upload de arquivos (PDF, JPG, PNG, WEBP)
- Download via URLs pré-assinadas
- Versionamento automático
- Checksums para integridade
- Busca por proprietário (cliente/cuidador)

### Endpoints
```
POST   /api/documents/upload
GET    /api/documents/{id}
GET    /api/documents/{id}/signed-url
GET    /api/documents/{id}/versions
POST   /api/documents/{id}/versions
GET    /api/documents/owner/{type}/{id}
DELETE /api/documents/{id}
```

## 2. Módulo de Contratos

### Responsabilidade
Criação e gerenciamento de contratos com assinatura digital.

### Componentes
- `ContractController` - API REST
- `ContractService` - Lógica de negócio
- `DocumentTemplate` - Templates de contrato
- `Signature` - Assinaturas

### Funcionalidades
- Criação de contratos a partir de templates
- Assinatura digital via OTP ou clique
- Envio de link de assinatura via WhatsApp
- Download do contrato assinado

### Tipos de Contrato
- `contrato_cliente` - Contrato com cliente
- `contrato_cuidador` - Contrato com cuidador

### Endpoints
```
POST   /api/contracts
GET    /api/contracts/{id}
POST   /api/contracts/{id}/sign
GET    /api/contracts/{id}/status
GET    /api/contracts/{id}/signature-url
GET    /api/contracts/client/{id}
GET    /api/contracts/caregiver/{id}
```

## 3. Módulo de Assinaturas

### Responsabilidade
Gerenciamento de assinaturas digitais com validação.

### Componentes
- `SignatureController` - API REST
- `SignatureService` - Lógica de negócio
- `Signature` - Model

### Funcionalidades
- Envio de código OTP via WhatsApp
- Verificação de OTP
- Assinatura por clique
- Verificação de assinatura via hash
- Certificado de assinatura

### Métodos de Assinatura
- `otp` - Código via WhatsApp
- `click` - Clique para aceitar
- `certificate` - Certificado digital (futuro)

### Endpoints
```
GET    /api/signatures/{id}
GET    /api/signatures/document/{id}
GET    /api/signatures/verify/{id}
POST   /api/signatures/send-otp
POST   /api/signatures/verify-otp
```

## 4. Módulo de Consentimentos

### Responsabilidade
Gerenciamento de consentimentos LGPD.

### Componentes
- `ConsentController` - API REST
- `ConsentService` - Lógica de negócio
- `Consent` - Model

### Funcionalidades
- Registro de consentimento
- Revogação de consentimento
- Verificação de consentimento ativo
- Histórico completo

### Tipos de Consentimento
- `data_processing` - Tratamento de dados
- `marketing` - Comunicações de marketing
- `sharing` - Compartilhamento com terceiros
- `profiling` - Perfilamento automatizado
- `cookies` - Uso de cookies

### Endpoints
```
POST   /api/consents
GET    /api/consents/{id}
DELETE /api/consents/{id}
GET    /api/consents/subject/{type}/{id}
GET    /api/consents/check/{type}/{id}/{consent}
GET    /api/consents/history/{type}/{id}
```

## 5. Módulo LGPD

### Responsabilidade
Solicitações de dados conforme LGPD.

### Componentes
- `DataRequestController` - API REST
- `LgpdService` - Lógica de negócio
- `DataRequest` - Model

### Funcionalidades
- Solicitação de exportação de dados
- Solicitação de exclusão de dados
- Processamento assíncrono
- Prazo de 15 dias
- Notificação de status

### Tipos de Solicitação
- `export` - Exportação de dados
- `delete` - Exclusão de dados
- `update` - Atualização de dados

### Endpoints
```
POST   /api/data-requests
GET    /api/data-requests/{id}
PUT    /api/data-requests/{id}
POST   /api/data-requests/{id}/process
POST   /api/data-requests/export
GET    /api/data-requests/export/{id}/download
POST   /api/data-requests/delete
```

## 6. Módulo de Templates

### Responsabilidade
Gerenciamento de templates de documentos.

### Componentes
- `DocumentTemplateController` - API REST
- `DocumentTemplate` - Model

### Funcionalidades
- CRUD de templates
- Versionamento de templates
- Renderização com variáveis
- Ativação/desativação

### Endpoints
```
GET    /api/templates
POST   /api/templates
GET    /api/templates/{id}
PUT    /api/templates/{id}
DELETE /api/templates/{id}
GET    /api/templates/type/{type}
POST   /api/templates/{id}/render
```

## 7. Módulo de Auditoria

### Responsabilidade
Registro e consulta de logs de acesso.

### Componentes
- `AccessLogController` - API REST
- `AccessLog` - Model

### Funcionalidades
- Registro automático de acessos
- Consulta por documento
- Consulta por ator
- Relatórios de acesso

### Ações Registradas
- `view` - Visualização
- `download` - Download
- `sign` - Assinatura
- `delete` - Exclusão

### Endpoints
```
GET    /api/access-logs
GET    /api/access-logs/document/{id}
GET    /api/access-logs/actor/{id}
GET    /api/access-logs/report
```

## 8. Módulo de Retenção

### Responsabilidade
Políticas de retenção e arquivamento.

### Componentes
- `RetentionPolicyController` - API REST
- `RetentionPolicy` - Model
- `ProcessRetentionPolicies` - Job

### Funcionalidades
- CRUD de políticas
- Execução automática (cron)
- Arquivamento de documentos
- Listagem de pendentes

### Políticas Padrão
| Tipo | Retenção |
|------|----------|
| contrato_cliente | 10 anos |
| contrato_cuidador | 10 anos |
| termos | 5 anos |
| privacidade | 5 anos |

### Endpoints
```
GET    /api/retention-policies
POST   /api/retention-policies
GET    /api/retention-policies/{id}
PUT    /api/retention-policies/{id}
DELETE /api/retention-policies/{id}
POST   /api/retention-policies/execute
GET    /api/retention-policies/pending
```

## 9. Módulo de Notificações

### Responsabilidade
Envio de notificações por email e WhatsApp.

### Componentes
- `NotificationService` - Serviço de envio
- `ZApiClient` - Integração WhatsApp
- Views de email

### Funcionalidades
- Email de contrato pronto
- Email de contrato assinado
- WhatsApp com link
- WhatsApp com OTP

### Templates de Email
- `contrato_pronto.blade.php`
- `contrato_assinado.blade.php`
- `otp.blade.php`

## 10. Módulo de Storage

### Responsabilidade
Integração com AWS S3.

### Componentes
- `S3StorageClient` - Cliente S3
- Configuração em `config/filesystems.php`

### Funcionalidades
- Upload com criptografia
- Download seguro
- URLs pré-assinadas
- Listagem de objetos
- Metadados
