# Módulo de Gestão de Conteúdo do Site

## Visão Geral

Este módulo permite gerenciar o conteúdo do site institucional (`carinho-site`) diretamente pelo painel administrativo do CRM. Os operadores podem criar, editar e excluir:

- **Depoimentos (Testimonials)**: Depoimentos de clientes exibidos na home
- **FAQ**: Categorias e itens de perguntas frequentes
- **Páginas Dinâmicas**: Páginas customizadas do site

## Arquitetura

### Fluxo de Dados

```
CRM (Interface Web) → API CRM → Service → API Site → Controller Site → Database Site
```

1. **Interface Web (CRM)**: Views Blade no `carinho-crm` para operadores
2. **Controllers Web (CRM)**: `ContentController` processa requisições e renderiza views
3. **Service (CRM)**: `CarinhoSiteService` faz chamadas HTTP para o site
4. **API Site**: Endpoints em `/api/content/*` que recebem requisições do CRM
5. **Controller API (Site)**: `ContentController` valida e persiste no banco

### Autenticação

- **CRM → Site**: Autenticação via API Key no header `X-API-Key`
- **Token**: Configurado em `INTERNAL_API_TOKEN` no `.env` do site
- **Validação**: Middleware no `ContentController` do site verifica o token

## Estrutura de Arquivos

### CRM (`carinho-crm`)

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── Api/
│   │   │   └── ContentController.php      # API REST para integração
│   │   └── ContentController.php          # Controller Web (views)
│   └── Services/
│       └── Integrations/
│           └── CarinhoSiteService.php     # Service expandido com métodos de conteúdo
resources/
└── views/
    └── content/
        ├── testimonials/
        │   ├── index.blade.php
        │   └── form.blade.php
        ├── faq/
        │   ├── categories/
        │   │   ├── index.blade.php
        │   │   └── form.blade.php
        │   └── items/
        │       ├── index.blade.php
        │       └── form.blade.php
        └── pages/
            ├── index.blade.php
            └── form.blade.php
routes/
├── api.php                                 # Rotas API /api/v1/content/*
└── web.php                                  # Rotas Web /content/*
```

### Site (`carinho-site`)

```
app/
└── Http/
    └── Controllers/
        └── Api/
            └── ContentController.php       # Endpoints API para receber do CRM
routes/
└── api.php                                  # Rotas /api/content/*
```

## Rotas

### Web (CRM)

| Método | Rota | Descrição |
|--------|------|-----------|
| GET | `/content/testimonials` | Lista depoimentos |
| GET | `/content/testimonials/create` | Formulário novo depoimento |
| GET | `/content/testimonials/{id}/edit` | Formulário editar depoimento |
| POST | `/content/testimonials` | Criar depoimento |
| PUT | `/content/testimonials/{id}` | Atualizar depoimento |
| DELETE | `/content/testimonials/{id}` | Excluir depoimento |
| GET | `/content/faq/categories` | Lista categorias FAQ |
| GET | `/content/faq/categories/{id}/items` | Lista itens de uma categoria |
| GET | `/content/pages` | Lista páginas |

### API (CRM → Site)

| Método | Endpoint | Descrição |
|--------|----------|-----------|
| GET | `/api/content/testimonials` | Lista depoimentos |
| POST | `/api/content/testimonials` | Cria depoimento |
| PUT | `/api/content/testimonials/{id}` | Atualiza depoimento |
| DELETE | `/api/content/testimonials/{id}` | Exclui depoimento |
| GET | `/api/content/faq/categories` | Lista categorias |
| POST | `/api/content/faq/categories` | Cria categoria |
| GET | `/api/content/faq/categories/{id}/items` | Lista itens |
| POST | `/api/content/faq/categories/{id}/items` | Cria item |
| GET | `/api/content/pages` | Lista páginas |
| POST | `/api/content/pages` | Cria página |

## Configuração

### 1. Variáveis de Ambiente

**No `carinho-site/.env`:**
```env
INTERNAL_API_TOKEN=seu-token-seguro-aqui
```

**No `carinho-crm/.env`:**
```env
CARINHO_SITE_URL=https://site.carinho.com.vc
CARINHO_SITE_API_KEY=seu-token-seguro-aqui  # Mesmo valor do INTERNAL_API_TOKEN
```

### 2. Service Provider

O `CarinhoSiteService` já está registrado no `IntegrationServiceProvider` do CRM.

## Uso

### Acessar o Módulo

1. Faça login no CRM
2. No menu lateral, clique em **"Conteúdo do Site"**
3. Escolha o tipo de conteúdo:
   - **Depoimentos**: Gerenciar depoimentos de clientes
   - **FAQ**: Gerenciar categorias e perguntas frequentes
   - **Páginas**: Gerenciar páginas dinâmicas

### Criar um Depoimento

1. Acesse `/content/testimonials`
2. Clique em "Novo Depoimento"
3. Preencha:
   - Nome do autor
   - Função/Cargo (opcional)
   - Depoimento (texto completo)
   - Avaliação (1-5 estrelas)
   - URL do avatar (opcional)
   - Marque "Destacar no site" se quiser exibir na home
   - Marque "Ativo" para exibir no site
4. Clique em "Salvar"

### Gerenciar FAQ

1. **Criar Categoria**:
   - Acesse `/content/faq/categories`
   - Clique em "Nova Categoria"
   - Preencha nome, slug e ordem
   - Salve

2. **Adicionar Itens**:
   - Clique em "Ver Itens" na categoria
   - Clique em "Novo Item"
   - Preencha pergunta e resposta
   - Defina ordem e status
   - Salve

### Criar Página Dinâmica

1. Acesse `/content/pages`
2. Clique em "Nova Página"
3. Preencha:
   - Slug (URL amigável)
   - Título
   - Status (Rascunho/Publicada/Arquivada)
   - SEO (título, descrição, palavras-chave)
   - Conteúdo em JSON
4. Salve

## Validações

### Depoimentos
- Nome: obrigatório, máximo 255 caracteres
- Conteúdo: obrigatório
- Avaliação: obrigatório, entre 1 e 5
- Avatar URL: opcional, deve ser URL válida

### FAQ
- Nome da categoria: obrigatório, máximo 255 caracteres
- Slug: obrigatório, apenas letras minúsculas, números e hífens
- Pergunta: obrigatório, máximo 500 caracteres
- Resposta: obrigatório

### Páginas
- Slug: obrigatório, único, apenas letras minúsculas, números e hífens
- Título: obrigatório, máximo 255 caracteres
- Status: obrigatório, deve existir em `domain_page_status`
- Conteúdo JSON: obrigatório, JSON válido

## Segurança

- **Autenticação**: Todas as requisições do CRM para o site requerem API Key válida
- **Validação**: Inputs são validados tanto no CRM quanto no site
- **Rate Limiting**: Endpoints API têm rate limiting (60 req/min)
- **Sanitização**: Dados são sanitizados antes de persistir

## Cache

- **Páginas**: Cache é limpo automaticamente ao atualizar/excluir uma página
- **FAQ/Testimonials**: Cache pode ser limpo manualmente via webhook

## Troubleshooting

### Erro 401 Unauthorized
- Verifique se `INTERNAL_API_TOKEN` no site está configurado
- Verifique se `CARINHO_SITE_API_KEY` no CRM está configurado
- Ambos devem ter o mesmo valor

### Erro 500 ao salvar
- Verifique logs do site: `storage/logs/laravel.log`
- Verifique se o banco de dados do site está acessível
- Verifique se as migrations foram executadas

### Dados não aparecem no site
- Verifique se o campo `active` está marcado como `true`
- Para depoimentos, verifique se `featured` está marcado para aparecer na home
- Limpe o cache do site se necessário

## Próximos Passos

- [ ] Adicionar upload de imagens para avatares
- [ ] Editor WYSIWYG para conteúdo de páginas
- [ ] Preview de páginas antes de publicar
- [ ] Histórico de versões
- [ ] Exportação/importação de conteúdo
- [ ] Integração com clientes para criar depoimentos automaticamente
