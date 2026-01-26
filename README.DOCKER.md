# Docker - Carinho com Você

## Estrutura de Containers

Este projeto utiliza uma arquitetura de containers onde:

- **MariaDB Compartilhado**: Um único container MariaDB é compartilhado por todos os sistemas
- **Redis por Sistema**: Cada sistema tem seu próprio container Redis (ou pode usar o compartilhado)
- **App por Sistema**: Cada sistema tem seu próprio container de aplicação

## Inicialização

### 1. Iniciar MariaDB Compartilhado

Primeiro, inicie o container MariaDB compartilhado na raiz do projeto:

```bash
cd /caminho/para/carinho-com-vc-doc
docker-compose -f docker-compose.mysql.yml up -d
```

Isso criará:
- Container `carinho-mariadb` na porta 3306
- Container `carinho-redis` (opcional, compartilhado) na porta 6379
- Network `carinho-network` para comunicação entre containers

### 2. Iniciar Sistemas Individuais

Para cada sistema, navegue até sua pasta e inicie os containers:

```bash
# Carinho Atendimento
cd sistemas/carinho-atendimento
docker-compose up -d

# Carinho Cuidadores
cd sistemas/carinho-cuidadores
docker-compose up -d

# Carinho Documentos LGPD
cd sistemas/carinho-documentos-lgpd
docker-compose up -d

# Carinho Operação
cd sistemas/carinho-operacao
docker-compose up -d

# Carinho Site
cd sistemas/carinho-site
docker-compose up -d

# Carinho CRM
cd sistemas/carinho-crm
docker-compose up -d

# Carinho Marketing
cd sistemas/carinho-marketing
docker-compose up -d

# Carinho Financeiro
cd sistemas/carinho-financeiro
docker-compose up -d

# Carinho Integrações
cd sistemas/carinho-integracoes
docker-compose up -d
```

## Portas Padrão

| Sistema | App Port | Redis Prefix |
|---------|----------|-------------|
| Atendimento | 8080 | carinho_atendimento: |
| Cuidadores | 8081 | carinho_cuidadores: |
| Documentos | 8082 | carinho_documentos_lgpd: |
| Operação | 8083 | carinho_operacao: |
| Site | 8084 | carinho_site: |
| CRM | 8085 | carinho_crm: |
| Marketing | 8086 | carinho_marketing: |
| Financeiro | 8087 | carinho_financeiro: |
| Integrações | 8088 | carinho_integracoes: |
| MariaDB (compartilhado) | 3306 | - |
| Redis (compartilhado) | 6379 | - |

## Configuração de Banco de Dados

### Variáveis de Ambiente

Cada sistema deve ter as seguintes variáveis no `.env`:

```env
DB_HOST=carinho-mariadb
DB_PORT=3306
DB_DATABASE=carinho_[nome_sistema]
DB_USERNAME=root
DB_PASSWORD=carinho

REDIS_HOST=carinho-redis
REDIS_PORT=6379
REDIS_PREFIX=carinho_[nome_sistema]:
```

### Criar Bancos de Dados

Os bancos de dados são criados automaticamente pelo script de inicialização em `mysql/init/init.sql` quando o container MariaDB é iniciado pela primeira vez.

Se precisar criar manualmente:

```bash
# Conectar ao MariaDB
docker exec -it carinho-mariadb mariadb -uroot -proot

# Criar bancos de dados
CREATE DATABASE carinho_atendimento;
CREATE DATABASE carinho_cuidadores;
CREATE DATABASE carinho_documentos_lgpd;
CREATE DATABASE carinho_operacao;
CREATE DATABASE carinho_site;
CREATE DATABASE carinho_crm;
CREATE DATABASE carinho_marketing;
CREATE DATABASE carinho_financeiro;
CREATE DATABASE carinho_integracoes;

# Criar usuário (se ainda não existir)
CREATE USER IF NOT EXISTS 'carinho'@'%' IDENTIFIED BY 'carinho';
GRANT ALL PRIVILEGES ON carinho_*.* TO 'carinho'@'%';
FLUSH PRIVILEGES;
```

Ou use um script de inicialização SQL em `mysql/init/init.sql`.

## Network

Todos os containers usam a network `carinho-network` para comunicação:

- Os containers de aplicação se conectam ao MariaDB via hostname `carinho-mariadb`
- Os containers podem se comunicar entre si usando os nomes dos containers

## Comandos Úteis

### Ver logs
```bash
docker-compose logs -f app
```

### Parar todos os containers
```bash
# Parar sistemas individuais
cd sistemas/[sistema]
docker-compose down

# Parar MariaDB compartilhado
docker-compose -f docker-compose.mysql.yml down
```

### Rebuild containers
```bash
docker-compose build --no-cache
docker-compose up -d
```

**Nota:** Os containers instalam automaticamente as dependências do Composer na primeira inicialização através do script `docker-entrypoint.sh`. Se precisar reinstalar as dependências, você pode:

```bash
# Entrar no container
docker exec -it carinho-[sistema]-app bash

# Reinstalar dependências
composer install
```

### Acessar container
```bash
docker exec -it carinho-[sistema]-app bash
```

### Verificar conexão com MariaDB
```bash
docker exec -it carinho-[sistema]-app php artisan migrate:status
```

### Instalar dependências do Composer

As dependências são instaladas automaticamente na primeira inicialização do container. Se precisar reinstalar:

```bash
# Opção 1: Dentro do container
docker exec -it carinho-[sistema]-app composer install

# Opção 2: Rebuild do container
docker-compose build --no-cache
docker-compose up -d
```

## Troubleshooting

### Container não consegue conectar ao MariaDB

1. Verifique se o MariaDB compartilhado está rodando:
   ```bash
   docker ps | grep carinho-mariadb
   ```

2. Verifique se a network está criada:
   ```bash
   docker network ls | grep carinho-network
   ```

3. Verifique se o container está na mesma network `carinho-network`

### Porta já em uso

Altere a porta no `.env` ou no `docker-compose.yml`:
```yaml
ports:
  - "8089:80"  # Altere 8089 para uma porta livre
```

### Redis não conecta

Todos os sistemas usam o Redis compartilhado (`carinho-redis`). Cada sistema tem um prefixo único para evitar conflito de chaves:

- `carinho_atendimento:`
- `carinho_cuidadores:`
- `carinho_documentos_lgpd:`
- `carinho_operacao:`
- `carinho_site:`
- `carinho_crm:`
- `carinho_marketing:`
- `carinho_financeiro:`
- `carinho_integracoes:`

O prefixo é configurado automaticamente via variável de ambiente `REDIS_PREFIX` no docker-compose.yml de cada sistema.
