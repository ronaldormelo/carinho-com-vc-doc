# Requisitos não funcionais — Documentos e LGPD

Contrato de plataforma: [PERFORMANCE.md](../../../PERFORMANCE.md) e [SECURITY.md](../../../SECURITY.md).  
Auditoria: [procedimentos-auditoria.md](procedimentos-auditoria.md).

## Usabilidade

- Família e cuidador assinam por link (OTP WhatsApp, clique ou certificado), sem conta neste painel.
- DPO/operador: upload, retenção, exportação/exclusão em 15 dias.
- Textos públicos de privacidade continuam no **Site**; este módulo guarda versão, evidência e arquivo.

## Performance

| Item | Meta / comportamento |
|------|----------------------|
| Download | URL pré-assinada S3 (expira) |
| Upload | Cliente Cuidadores até 60 s |
| APIs internas | Timeout 8 s (token interno 5 s) |
| `ProcessDataExport` | Timeout de job 10 min |
| Jobs | Retenção 03:00; limpeza 04:00; sync de metadados horário |

Gargalos: S3 na região `sa-east-1`; OTP se Z-API cair; export LGPD cruzando sistemas (orquestração manual/hub — exclusão não pode ficar só neste schema).

## Integração

Dono do arquivo e do consentimento. Notifica CRM, Cuidadores, Financeiro, Atendimento e o hub. [integracoes.md](integracoes.md).

## Segurança e LGPD

- AES-256 em repouso (S3); TLS em trânsito.
- HMAC-SHA256 na verificação de assinatura (README).
- Log de visualização, download, assinatura e exclusão (IP, ator, timestamp).
- Token entre sistemas; sem bucket público.
- Health: `GET /up` (Laravel), `GET /api/health` e `GET /api/up` (módulo).
- Prazo legal de 15 dias para o titular.
