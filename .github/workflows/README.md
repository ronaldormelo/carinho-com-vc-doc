# GitHub Actions - Deploy para KingHost

Este diretório contém os workflows de CI/CD para deploy automático dos sistemas Carinho para o KingHost.

## Workflow: deploy-testing.yml

### Descrição

Este workflow é acionado automaticamente quando há um **merge/push na branch `testing`**. Ele executa duas etapas principais:

1. **Build**: Executa `composer install` para cada sistema
2. **Deploy**: Faz upload dos arquivos via SFTP para o KingHost

### Sistemas Deployados

- `carinho-atendimento`
- `carinho-crm`
- `carinho-cuidadores`
- `carinho-documentos-lgpd`
- `carinho-financeiro`
- `carinho-integracoes`
- `carinho-marketing`
- `carinho-operacao`
- `carinho-site`

## Configuração de Secrets

As seguintes variáveis devem ser configuradas em **Settings > Secrets and variables > Actions** do repositório:

### Credenciais SFTP (Obrigatórias)

| Secret | Descrição | Exemplo |
|--------|-----------|---------|
| `SFTP_HOST` | Endereço do servidor KingHost | `ftp.seusite.kinghost.net` |
| `SFTP_USERNAME` | Usuário SFTP | `usuario@seusite.com.br` |
| `SFTP_PASSWORD` | Senha SFTP | `sua_senha_segura` |
| `SFTP_PORT` | Porta SFTP (opcional, padrão: 22) | `22` |

### Caminhos Remotos (Obrigatórios)

Cada sistema precisa de um secret com o caminho remoto no servidor:

| Secret | Descrição | Exemplo |
|--------|-----------|---------|
| `SFTP_PATH_ATENDIMENTO` | Caminho remoto para carinho-atendimento | `/home/usuario/public_html/atendimento` |
| `SFTP_PATH_CRM` | Caminho remoto para carinho-crm | `/home/usuario/public_html/crm` |
| `SFTP_PATH_CUIDADORES` | Caminho remoto para carinho-cuidadores | `/home/usuario/public_html/cuidadores` |
| `SFTP_PATH_DOCUMENTOS_LGPD` | Caminho remoto para carinho-documentos-lgpd | `/home/usuario/public_html/documentos` |
| `SFTP_PATH_FINANCEIRO` | Caminho remoto para carinho-financeiro | `/home/usuario/public_html/financeiro` |
| `SFTP_PATH_INTEGRACOES` | Caminho remoto para carinho-integracoes | `/home/usuario/public_html/integracoes` |
| `SFTP_PATH_MARKETING` | Caminho remoto para carinho-marketing | `/home/usuario/public_html/marketing` |
| `SFTP_PATH_OPERACAO` | Caminho remoto para carinho-operacao | `/home/usuario/public_html/operacao` |
| `SFTP_PATH_SITE` | Caminho remoto para carinho-site | `/home/usuario/public_html/site` |

## Como Configurar

1. Acesse o repositório no GitHub
2. Vá em **Settings** > **Secrets and variables** > **Actions**
3. Clique em **New repository secret**
4. Adicione cada secret listado acima com seu respectivo valor

## Arquivos Excluídos do Deploy

Os seguintes arquivos/diretórios são automaticamente excluídos do deploy:

- `.git/` - Diretório do Git
- `.github/` - Workflows e configurações do GitHub
- `.env` e `.env.*` - Arquivos de ambiente (devem ser configurados manualmente no servidor)
- `storage/logs/*` - Logs da aplicação
- `storage/framework/cache/*` - Cache
- `storage/framework/sessions/*` - Sessões
- `storage/framework/views/*` - Views compiladas

## Informações do Build

Cada deploy inclui um arquivo `.build-info` na raiz do sistema com:

- Data/hora do build
- SHA do commit
- Referência da branch
- Número do build

## Troubleshooting

### Deploy Falhou

1. Verifique se todas as variáveis de ambiente estão configuradas corretamente
2. Confirme se as credenciais SFTP estão válidas
3. Verifique se os caminhos remotos existem no servidor
4. Consulte os logs do workflow em **Actions** > **[nome do workflow]**

### Composer Install Falhou

1. Verifique se o `composer.json` está válido
2. Confirme se todas as extensões PHP necessárias estão especificadas
3. Verifique se não há conflitos de dependências

## Executando Manualmente

Para executar o workflow manualmente (se necessário), você pode adicionar o trigger `workflow_dispatch` ao arquivo YAML:

```yaml
on:
  push:
    branches:
      - testing
  workflow_dispatch:  # Permite execução manual
```
