# Feira Esquerda Livre

Plataforma de marketplace e agenda de feiras para lojistas e expositores populares, com CMS integrado, painel administrativo e área restrita para lojistas. Desenvolvida para funcionar bem em redes lentas (3G/4G) e para o público 40+.

---

## Stack Técnica

| Camada | Tecnologia |
|---|---|
| Backend | PHP 8.2 · Laravel 12 |
| Frontend reativo | Livewire 4 · AlpineJS 3 |
| Estilização | TailwindCSS 4 |
| Build | Vite 7 |
| Banco de dados | MySQL (produção) · SQLite (desenvolvimento) |
| Processamento de imagens | Intervention/Image 3 |

---

## Requisitos

- PHP >= 8.2 com extensões: `pdo`, `pdo_mysql` (ou `pdo_sqlite`), `mbstring`, `fileinfo`, `gd` ou `imagick`
- Composer >= 2
- Node.js >= 20 e npm
- MySQL 8+ (produção) ou SQLite (desenvolvimento local)

---

## Instalação

```bash
# 1. Clonar o repositório
git clone <url-do-repositorio> feira-esquerda-livre
cd feira-esquerda-livre

# 2. Instalar dependências PHP e JS, gerar chave e rodar migrations
composer run setup
```

O script `composer run setup` executa automaticamente:
- `composer install`
- Cópia de `.env.example` para `.env`
- `php artisan key:generate`
- `php artisan migrate`
- `npm install`
- `npm run build`

### Configuração manual do `.env`

```env
APP_NAME="Feira Esquerda Livre"
APP_URL=http://localhost

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=feira_esquerda_livre
DB_USERNAME=root
DB_PASSWORD=
```

### Seeders (dados de demonstração)

```bash
php artisan db:seed
```

---

## Rodando em desenvolvimento

```bash
composer run dev
```

Sobe em paralelo: servidor Laravel, queue worker, log stream (Pail) e Vite HMR.

Acesse `http://localhost:8000`.

---

## Os Três Eixos do Marketplace

O catálogo está estruturado em três eixos, cada um com rotas e campos específicos:

| Eixo | Rota pública | Campos exclusivos |
|---|---|---|
| 🛍️ Produtos | `/produtos` | Estoque, quantidade |
| 🎯 Serviços | `/servicos` | Modalidade, tipo de preço, duração |
| 🌿 Cuidados & Bem Viver | `/cuidados` | Modalidade, tipo de preço, duração |

Um mesmo lojista pode atuar em todos os eixos simultaneamente. O carrinho é unificado para os três tipos.

---

## Estrutura de Rotas

### Público

| Rota | Descrição |
|---|---|
| `GET /` | Homepage com banners, eventos, expositores e posts |
| `GET /produtos` | Catálogo de produtos físicos |
| `GET /servicos` | Catálogo de serviços |
| `GET /cuidados` | Catálogo de cuidados e bem viver |
| `POST /newsletter` | Inscrição na newsletter |
| `GET /seja-um-expositor` | Formulário de solicitação para novos lojistas |
| `GET /agenda` | Agenda de feiras com filtro por estado e mês |
| `GET /agenda/{slug}` | Detalhe de um evento/feira |
| `GET /blog/{slug}` | Detalhe de post/notícia |
| `GET /loja/{slug}` | Página pública de uma loja (agrupa por eixo) |
| `GET /loja/{slug}/{produto-slug}` | Página pública de um item |
| `GET /checkout` | Finalização de compra (dados do cliente + endereço) |
| `GET /pedido/{reference}` | Confirmação do pedido com instruções de pagamento manual por loja |

### Painel Administrativo (`/admin`)

Requer autenticação com role `admin`.

| Rota | Descrição |
|---|---|
| `/admin` | Dashboard com resumo do sistema |
| `/admin/settings` | Configurações gerais do site |
| `/admin/settings/mail` | Configurações de e-mail |
| `/admin/settings/checkout` | Configuração de frete e pagamento (modo manual + credenciais para integração futura) |
| `/admin/banners` | Gestão de banners do carousel |
| `/admin/menus` | Gestão de menus de navegação |
| `/admin/pages` | Gestão de páginas estáticas |
| `/admin/posts` | Gestão de posts e notícias |
| `/admin/events` | Gestão de eventos/feiras |
| `/admin/expositores` | Gestão de expositores |
| `/admin/categorias` | Gestão de categorias de conteúdo (vinculadas a um eixo) |
| `/admin/media` | Biblioteca de mídia |
| `/admin/lojistas/solicitacoes` | Aprovação de novos lojistas |
| `/admin/pedidos` | Visão geral de todos os pedidos |

### Painel do Lojista (`/minha-loja`)

Requer autenticação com role `lojista`.

| Rota | Descrição |
|---|---|
| `/minha-loja` | Dashboard do lojista |
| `/minha-loja/loja` | Configuração do perfil da loja |
| `/minha-loja/produtos` | Listagem dos produtos |
| `/minha-loja/produtos/novo` | Cadastro de novo produto |
| `/minha-loja/produtos/{id}/editar` | Edição de produto |
| `/minha-loja/pedidos` | Pedidos recebidos, com confirmação manual de pagamento |

---

## Roles de Usuário

| Role | Acesso |
|---|---|
| `admin` | Painel completo de administração |
| `lojista` | Painel restrito da própria loja e produtos |
| _(sem role)_ | Apenas área pública e autenticação |

---

## Modelos Principais

```
User               → roles: admin, lojista
SiteSetting        → configurações globais (singleton)
Banner             → carousel da homepage
Menu / MenuItem    → navegação dinâmica
Page / PageSection → páginas estáticas com seções
Post               → posts, notícias, blog
Event              → feiras/eventos com agenda; pivot event_expositores
Expositor          → perfil público da loja (vinculado ao User lojista); campo eixos (JSON)
Product            → catálogo unificado dos três eixos; discriminado por item_type
CartItem           → carrinho multilojas (sessão + banco para usuários logados)
NewsletterSubscriber
LojistasSolicitacao → solicitações de novos lojistas; campo eixos declarados
Media              → biblioteca de mídia
ContentCategory    → categorias de conteúdo; campo eixo para filtro por catálogo
Order              → pedido (frete e pagamento manuais nesta fase); referência pública única
OrderItem          → itens do pedido (snapshot de nome/preço no momento da compra)
OrderSplit         → valor por loja dentro do pedido; confirmação manual de pagamento pelo lojista
```

### Enums

| Enum | Valores |
|---|---|
| `ItemType` | `produto` · `servico` · `cuidado` |
| `PriceType` | `fixo` · `por_hora` · `por_sessao` · `sob_consulta` |
| `Modality` | `presencial` · `online` · `ambos` |
| `UserRole` | `admin` · `lojista` |
| `SolicitacaoStatus` | `pendente` · `aprovado` · `bloqueado` |
| `OrderStatus` | `aguardando_pagamento` · `pagamento_confirmado` · `concluido` · `cancelado` |
| `OrderSplitStatus` | `pendente` · `confirmado` |

---

## Testes

```bash
composer run test
```

---

## Status do Projeto

Consulte [`docs/ROADMAP.md`](docs/ROADMAP.md) para o planejamento completo.

| Fase | Status | Entregável |
|---|---|---|
| Fase 1 — CMS, Admin & Home | ✅ Concluída | Plataforma funcional com conteúdo dinâmico |
| Fase 2 — Lojistas & Agenda | ✅ Concluída | Cadastro de lojistas, agenda pública, painel do lojista |
| Fase 3 — Catálogo & Loja | ✅ Concluída | Três eixos, CRUD de produtos do lojista, loja pública e carrinho multilojas |
| Fase 4 — Checkout & Pagamento | ✅ MVP manual concluído | Checkout, frete e pagamento manuais; telas de configuração prontas para integrar Melhor Envio e Mercado Pago depois |
| Fase 5 — Comunidade | ⏳ Pendente | Feed social e ferramentas de compartilhamento |

---

## Princípios de Design

- **Público 40+ First** — fonte mínima 16px, botões de no mínimo 48×48px, sem gestos complexos
- **Performance em redes lentas** — imagens comprimidas (WebP), lazy loading, sem libs JS acima de 30 KB gzipped
- **Mobile First** — layout parte de 360px; breakpoints 390, 414, 768, 1024, 1280
- **LGPD** — CPF/CNPJ armazenado encriptado, dados pessoais não repassados sem consentimento
