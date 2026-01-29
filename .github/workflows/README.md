# GitHub Actions - Deploy para KingHost

Este diretório contém os workflows de CI/CD para deploy automático dos sistemas Carinho para o KingHost.

## Workflow: deploy-testing.yml

### Descrição

Este workflow é acionado automaticamente quando há um **merge/push na branch `testing`**. Ele executa três etapas principais:

1. **Clone Config**: Clona o repositório de configuração com os arquivos `.env`
2. **Build**: Copia o `.env` correspondente e executa `composer install` para cada sistema
3. **Deploy**: Faz upload dos arquivos via SFTP para o KingHost

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

## Repositório de Configuração

O workflow clona automaticamente o repositório de configuração:

```
https://github.com/ronaldormelo/carinho-com-vc-doc-config.git
```

### Estrutura Esperada do Repositório de Configuração

O repositório de configuração deve conter os arquivos `.env` para cada sistema. O workflow procura os arquivos na seguinte ordem de prioridade:

```
carinho-com-vc-doc-config/
├── carinho-atendimento/
│   └── .env
├── carinho-crm/
│   └── .env
├── carinho-cuidadores/
│   └── .env
├── carinho-documentos-lgpd/
│   └── .env
├── carinho-financeiro/
│   └── .env
├── carinho-integracoes/
│   └── .env
├── carinho-marketing/
│   └── .env
├── carinho-operacao/
│   └── .env
└── carinho-site/
    └── .env
```

**Estruturas alternativas suportadas:**

```
# Opção 1: Pasta por sistema
config/{sistema}/.env

# Opção 2: Pasta testing com subpastas
config/testing/{sistema}/.env

# Opção 3: Arquivo nomeado por sistema
config/{sistema}.env

# Opção 4: Pasta testing com arquivos nomeados
config/testing/{sistema}.env
```

## Configuração de Secrets

As seguintes variáveis devem ser configuradas em **Settings > Secrets and variables > Actions** do repositório:

### Token do Repositório de Configuração (Obrigatório)

| Secret | Descrição | Como Gerar |
|--------|-----------|------------|
| `CONFIG_REPO_TOKEN` | Token de acesso ao repositório de configuração | Ver instruções abaixo |

**Como gerar o CONFIG_REPO_TOKEN:**

1. Acesse GitHub > Settings > Developer settings > Personal access tokens > Tokens (classic)
2. Clique em "Generate new token (classic)"
3. Dê um nome descritivo (ex: "Deploy Config Access")
4. Selecione os escopos: `repo` (acesso completo a repositórios privados)
5. Clique em "Generate token"
6. Copie o token e adicione como secret no repositório

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

## Fluxo do Workflow

```
┌─────────────────────────────────────────────────────────────────┐
│                    TRIGGER: Push na branch testing               │
└─────────────────────────────────────────────────────────────────┘
                                  │
                                  ▼
┌─────────────────────────────────────────────────────────────────┐
│                         STAGE: BUILD                             │
│  ┌───────────────────────────────────────────────────────────┐  │
│  │  Para cada sistema (em paralelo):                         │  │
│  │  1. Checkout código principal                              │  │
│  │  2. Checkout repositório de configuração                   │  │
│  │  3. Copiar .env para raiz do sistema                       │  │
│  │  4. Configurar PHP 8.2                                     │  │
│  │  5. composer install --no-dev --optimize-autoloader        │  │
│  │  6. Criar .build-info                                      │  │
│  │  7. Upload artefato                                        │  │
│  └───────────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────────┘
                                  │
                                  ▼ (após todos builds OK)
┌─────────────────────────────────────────────────────────────────┐
│                        STAGE: DEPLOY                             │
│  ┌───────────────────────────────────────────────────────────┐  │
│  │  Para cada sistema (max 3 em paralelo):                   │  │
│  │  1. Download artefato do build                             │  │
│  │  2. Verificar presença do .env                             │  │
│  │  3. Upload via SFTP para KingHost                          │  │
│  │  4. Verificar deploy                                       │  │
│  └───────────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────────┘
                                  │
                                  ▼
┌─────────────────────────────────────────────────────────────────┐
│                       STAGE: NOTIFY                              │
│              Reportar status final do deploy                     │
└─────────────────────────────────────────────────────────────────┘
```

## Arquivos Excluídos do Deploy

Os seguintes arquivos/diretórios são automaticamente excluídos do deploy:

- `.git/` - Diretório do Git
- `.github/` - Workflows e configurações do GitHub
- `storage/logs/*` - Logs da aplicação
- `storage/framework/cache/*` - Cache
- `storage/framework/sessions/*` - Sessões
- `storage/framework/views/*` - Views compiladas

**Nota:** O arquivo `.env` agora É incluído no deploy (vem do repositório de configuração).

## Informações do Build

Cada deploy inclui um arquivo `.build-info` na raiz do sistema com:

- Data/hora do build
- SHA do commit
- Referência da branch
- Número do build

## Troubleshooting

### Arquivo .env não encontrado

1. Verifique se o repositório de configuração está acessível
2. Confirme se o `CONFIG_REPO_TOKEN` tem permissão para acessar o repositório
3. Verifique se a estrutura do repositório de configuração está correta
4. Confirme se existe um arquivo `.env` para o sistema que está falhando

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

Para executar o workflow manualmente, adicione o trigger `workflow_dispatch`:

```yaml
on:
  push:
    branches:
      - testing
  workflow_dispatch:  # Permite execução manual
```

## Checklist de Configuração

- [ ] `CONFIG_REPO_TOKEN` configurado
- [ ] `SFTP_HOST` configurado
- [ ] `SFTP_USERNAME` configurado
- [ ] `SFTP_PASSWORD` configurado
- [ ] `SFTP_PORT` configurado (opcional)
- [ ] `SFTP_PATH_ATENDIMENTO` configurado
- [ ] `SFTP_PATH_CRM` configurado
- [ ] `SFTP_PATH_CUIDADORES` configurado
- [ ] `SFTP_PATH_DOCUMENTOS_LGPD` configurado
- [ ] `SFTP_PATH_FINANCEIRO` configurado
- [ ] `SFTP_PATH_INTEGRACOES` configurado
- [ ] `SFTP_PATH_MARKETING` configurado
- [ ] `SFTP_PATH_OPERACAO` configurado
- [ ] `SFTP_PATH_SITE` configurado
- [ ] Repositório de configuração com arquivos `.env` para cada sistema
