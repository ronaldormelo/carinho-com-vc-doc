# Requisitos não funcionais — CRM

Contrato de plataforma: [PERFORMANCE.md](../../../PERFORMANCE.md) e [SECURITY.md](../../../SECURITY.md).

## Usabilidade

- Operador comercial: kanban, tarefa, origem UTM, motivo de perda obrigatório.
- Família: aceite de contrato por link (fluxo com Documentos); não navega o painel.
- Gestão de depoimentos/FAQ do site: [modulo-gestao-conteudo.md](modulo-gestao-conteudo.md) — o dado canônico da página pública continua no Site.
- Guia: [guia-usuario-operacional.md](guia-usuario-operacional.md).

## Performance

| Item | Meta / comportamento |
|------|----------------------|
| Listas e dashboard | Cache Redis; paginação |
| Integrações internas | Timeout 10 s (Documentos 15 s) |
| Z-API | Timeout 30 s |
| Jobs | Contratos expirando (diário); tarefas atrasadas (4 h); sync (horário); relatórios (diário) |

Gargalos: fan-out para Operação/Financeiro/Documentos no fechamento do deal; export Excel em horário comercial. Relatórios pesados devem ir para job, não para o request do kanban.

## Integração

O CRM é a base de lead/cliente, não a agenda nem a fatura. Chamadas documentadas no README. Conteúdo do site via `CarinhoSiteService` + `X-API-Key`.

## Segurança e LGPD

- Sanctum + Spatie Permission (RBAC). Aliases de middleware (`verify.internal`, headers de segurança) em `bootstrap/app.php` — o `app/Http/Kernel.php` não é usado no Laravel 11.
- Campos sensíveis com criptografia AES-256-CBC (`HasEncryptedFields`).
- Activity log; consentimento; exportação/anonimização declaradas no README.
- Headers de segurança; rate limit; `WEBHOOK_SECRET` comparado com `hash_equals`.
- Exportação de relatório grava arquivo em `storage`; **não** envia e-mail com o link.
- PII de paciente/família não deve ir completa para log de integração.

`analise-praticas-tradicionais.md` é estudo, não backlog obrigatório.
