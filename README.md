# Feira Esquerda Livre

Plataforma de marketplace, agenda de feiras, comunidade e AVA para lojistas, expositores populares, clientes e equipe interna. O projeto combina CMS, painel administrativo, área do lojista, área do cliente, catálogo por eixos, checkout, comunicação pós-venda, email marketing e cursos digitais.

A experiência foi desenhada para funcionar bem em redes lentas (3G/4G), com navegação mobile first e foco em público 40+.

---

## MVP Demonstrável

O projeto já possui um fluxo mínimo viável para apresentação a clientes, diretores e sócios da feira:

- Home pública com identidade visual aplicada, banners responsivos, próximas feiras, expositores, marketplace, blog, chamada para expositores e newsletter.
- Navbar, footer, botão de voltar ao topo e páginas institucionais padronizadas.
- Página de contato com canais oficiais, formulário e resposta automática em HTML para o usuário.
- Política de Privacidade e Termos de Uso com contatos oficiais e link para o formulário.
- Painel administrativo com dashboard, configurações do site, logo/favicon, banners, conteúdos, eventos, expositores, visibilidade e checkout.
- Marketplace com produtos, serviços, cuidados, carrinho, checkout autenticado e integração inicial com Mercado Pago.
- Dados demonstrativos para produtos, expositores, posts de comunidade e curso online com certificado.

---

## Stack Técnica

| Camada | Tecnologia |
|---|---|
| Backend | PHP 8.2 · Laravel 12 |
| Frontend reativo | Livewire 4 · AlpineJS 3 |
| Estilização | TailwindCSS 4 |
| Build | Vite 7 |
| Banco de dados | MySQL em produção · SQLite suportado em desenvolvimento/testes |
| Permissões | spatie/laravel-permission |
| API mobile | Laravel Sanctum (tokens Bearer, consumidos pelo app Flutter) |
| Processamento de imagens | Intervention/Image 3 |
| PDF | barryvdh/laravel-dompdf |
| Filas | Laravel Queue |
| Inteligência de Cliente | JMF Customer Intelligence SDK 1.0.0 |

---

## Inteligência de Cliente (JMF CI)

A plataforma integra o SDK JMF Customer Intelligence para rastreamento e análise comportamental de visitantes e clientes.

### Funcionalidades
- **Dashboard em tempo real:** métricas de visitas, conversões, eventos
- **Rastreamento automático:** eventos de produtos, carrinho, pedidos
- **Gestão de contatos:** CRM integrado com histórico de interações
- **Relatórios:** visualização de padrões de comportamento

### Configuração

Variáveis de ambiente obrigatórias:
```env
JMF_CI_BASE_URL=http://179.198.115.221
JMF_CI_TOKEN=<token-gerado-no-painel-admin>
JMF_CI_QUEUE_CONNECTION=sync
JMF_CI_TIMEOUT=2
```

### Componentes Livewire Disponíveis
- `<livewire:jmf-ci-dashboard />` — Dashboard com métricas
- `<livewire:jmf-ci-configuration />` — Validação de conexão
- `<livewire:jmf-ci-contact-index />` — Lista de contatos
- `<livewire:jmf-ci-contact-show />` — Detalhe de contato
- `<livewire:jmf-ci-event-index />` — Tabela de eventos

### Rastreamento de Eventos

```php
use JmfSystem\CustomerIntelligence\Facades\CustomerIntelligence;

// Rastrear eventos principais
CustomerIntelligence::track('produto.visualizado', [
    'produto_id' => $produto->id,
    'nome' => $produto->nome,
    'preco' => $produto->preco,
]);

CustomerIntelligence::track('carrinho.adicionado', [
    'item_id' => $cartItem->id,
    'quantidade' => $cartItem->quantity,
]);

CustomerIntelligence::track('pedido.criado', [
    'pedido_id' => $order->id,
    'total' => $order->total,
]);
```

Consulte [`docs/JMF_CI_INTEGRATION.md`](docs/JMF_CI_INTEGRATION.md) para documentação completa.

---

## Visão do Produto

A Feira Esquerda Livre está organizada em seis grandes áreas funcionais:

| Área | Descrição |
|---|---|
| CMS e conteúdo | Banners, páginas, menus, posts/notícias, mídia, eventos e configurações globais |
| Marketplace | Catálogo público, lojas, produtos, serviços, cuidados, carrinho, checkout e pedidos multilojas |
| Lojistas | Solicitação pública, aprovação administrativa, painel da loja, produtos, cursos, pedidos, perguntas, feed e relatório de exposição |
| Clientes | Cadastro, pedidos, endereços, checkout autenticado, chats por pedido, aprendizado e certificados |
| Comunidade e marketing | Feed social, moderação, compartilhamento, campanhas de email marketing e descadastro LGPD |
| AVA | Cursos digitais, módulos, aulas, materiais protegidos, progresso do aluno e certificado PDF |

---

## Desenvolvimento com Docker (recomendado)

O ambiente local roda inteiramente em containers (PHP 8.3, Nginx, MySQL 8.4,
phpMyAdmin, Redis 7, Node 22 + Vite, queue worker e Mailpit). Não é necessário
Laragon, XAMPP, PHP, MySQL, Composer ou Node instalados no Windows.

```bash
docker compose up -d
```

| | |
|---|---|
| Aplicação | http://localhost |
| phpMyAdmin | http://localhost:8081 |
| Vite | http://localhost:5173 |
| Mailpit | http://localhost:8025 |

Instalação inicial, comandos do dia a dia e troubleshooting em
**[docs/DOCKER_DEVELOPMENT.md](docs/DOCKER_DEVELOPMENT.md)**.

---

## Requisitos

- PHP >= 8.2 com extensões: `pdo`, `pdo_mysql` ou `pdo_sqlite`, `mbstring`, `fileinfo`, `gd` ou `imagick`
- Composer >= 2
- Node.js >= 20 e npm
- MySQL 8+ para ambiente persistente de desenvolvimento/produção
- SQLite para testes ou desenvolvimento local simplificado

---

## Instalação

```bash
git clone <url-do-repositorio> feira-esquerda-livre
cd feira-esquerda-livre
composer run setup
```

O script `composer run setup` executa:

- `composer install`
- cópia de `.env.example` para `.env`
- `php artisan key:generate`
- `php artisan migrate --force`
- `npm install`
- `npm run build`

### Configuração manual do `.env`

```env
APP_NAME="Feira Esquerda Livre"
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=feira_esquerda_livre
DB_USERNAME=root
DB_PASSWORD=

QUEUE_CONNECTION=database
```

Integrações opcionais usadas por módulos específicos:

- Melhor Envio: cotação de frete e rastreio.
- Mercado Pago: fluxo de pagamento iniciado por `/pedido/{reference}/pagar`.
- Email SMTP: notificações, campanhas, AVA e rastreio de entrega.

Consulte também [`docs/INTEGRACAO_FRETE_MELHOR_ENVIO.md`](docs/INTEGRACAO_FRETE_MELHOR_ENVIO.md).

### Seeders

```bash
php artisan db:seed
```

Os seeders incluem dados de base para configurações, usuários, permissões, categorias, banners, eventos, expositores, produtos, serviços, cuidados, posts de comunidade, imagens demonstrativas e um curso online de demonstração no AVA.

### Usuários demonstrativos

| Perfil | E-mail | Senha | Uso sugerido |
|---|---|---|---|
| Administrador | `admin@feiraesquerdalivre.com.br` | `password` | Painel administrativo |
| Lojista demo | `tech@teste.com` | `password` | Loja Tecnologia Solidária e curso digital |
| Cliente curso demo | `cliente.curso@teste.com` | `password` | Acessar `/minha-conta/aprendizado`, concluir curso e baixar certificado |

O seeder `DemoAvaCourseSeeder` cria o produto digital `Curso Online de Informática Popular`, com valor `R$ 0,01`, quatro aulas, matrícula ativa para o cliente demo e certificado habilitado.

---

## Desenvolvimento

```bash
composer run dev
```

Esse comando sobe em paralelo:

- servidor Laravel
- worker de filas
- stream de logs com Pail
- Vite HMR

Acesse `http://localhost:8000`.

Para compilar assets de produção:

```bash
npm run build
```

---

## Os Três Eixos do Marketplace

O catálogo é unificado na tabela `products`, mas separado por `item_type` para navegação pública, filtros e campos específicos.

| Eixo | Rota pública | Campos exclusivos |
|---|---|---|
| Produtos | `/produtos` | Estoque, quantidade, peso e dimensões de frete |
| Serviços | `/servicos` | Modalidade, tipo de preço e duração |
| Cuidados & Bem Viver | `/cuidados` | Modalidade, tipo de preço e duração |

Um mesmo lojista pode atuar em todos os eixos. O carrinho e o checkout agrupam itens por loja para gerar splits de pedido.

---

## Rotas Principais

### Público

| Rota | Descrição |
|---|---|
| `GET /` | Homepage com banners, eventos, expositores rotacionados, produtos, serviços, cuidados e posts |
| `GET /produtos` | Catálogo de produtos físicos |
| `GET /servicos` | Catálogo de serviços |
| `GET /cuidados` | Catálogo de cuidados e bem viver |
| `GET /feed` | Feed social público |
| `GET /contato` | Página de contato com telefones, e-mail, endereço e formulário |
| `POST /contato` | Envio do formulário de contato e confirmação automática por e-mail |
| `GET /politica-de-privacidade` | Política de Privacidade |
| `GET /termos-de-uso` | Termos de Uso |
| `POST /newsletter` | Inscrição na newsletter |
| `GET /newsletter/descadastro/{token}` | Página de descadastro de campanhas |
| `GET /seja-um-expositor` | Formulário público de solicitação para novos lojistas |
| `GET /agenda` | Agenda de feiras com filtros |
| `GET /agenda/{slug}` | Detalhe de evento/feira |
| `GET /blog/{slug}` | Detalhe de post/notícia |
| `GET /loja/{slug}` | Página pública de uma loja |
| `GET /loja/{slug}/{productSlug}` | Página pública de produto, serviço ou cuidado |
| `GET /loja/{slug}/{productSlug}/compartilhar.png` | Imagem dinâmica para compartilhamento |
| `GET /checkout` | Finalização de compra |
| `POST /shipping/quote` | Cotação de frete via Melhor Envio |
| `GET /pedido/{reference}` | Confirmação e acompanhamento do pedido |
| `GET /pedido/{reference}/pagar` | Início do pagamento Mercado Pago |
| `GET /rastreio/{trackingCode}` | Página pública de rastreio de entrega |

### Painel Administrativo (`/admin`)

Requer autenticação, papel interno e permissões via `spatie/laravel-permission`.

| Rota | Descrição |
|---|---|
| `/admin` | Dashboard administrativo |
| `/admin/settings` | Configurações gerais |
| `/admin/settings/mail` | Configurações de email |
| `/admin/settings/checkout` | Frete, checkout e credenciais de pagamento/frete |
| `/admin/usuarios` | Gestão de usuários internos |
| `/admin/perfis-acesso` | Perfis e permissões |
| `/admin/pages` | Páginas estáticas |
| `/admin/banners` | Banners da home |
| `/admin/menus` | Menus de navegação |
| `/admin/media` | Biblioteca de mídia |
| `/admin/posts` | Posts, notícias e campanhas editoriais |
| `/admin/events` | Eventos e feiras |
| `/admin/expositores` | Gestão de expositores |
| `/admin/expositores/visibilidade` | Rotação, pesos, slots e visibilidade de expositores |
| `/admin/categorias` | Categorias por eixo |
| `/admin/lojistas/solicitacoes` | Aprovação de lojistas |
| `/admin/pedidos` | Pedidos e acompanhamento operacional |
| `/admin/clientes` | Gestão de clientes |
| `/admin/feed/reportes` | Moderação de denúncias do feed |
| `/admin/email-marketing` | Campanhas de email marketing |

### Painel do Lojista (`/minha-loja`)

Requer autenticação com papel `lojista` e expositor ativo.

| Rota | Descrição |
|---|---|
| `/minha-loja` | Dashboard do lojista |
| `/minha-loja/loja` | Perfil público da loja |
| `/minha-loja/produtos` | Produtos, serviços e cuidados |
| `/minha-loja/produtos/novo` | Cadastro de item |
| `/minha-loja/produtos/{product}/editar` | Edição de item |
| `/minha-loja/produtos/{product}/imagem-compartilhamento` | Imagem dinâmica para divulgação |
| `/minha-loja/pedidos` | Pedidos recebidos, pagamento, envio e rastreio |
| `/minha-loja/pedidos/{split}/chat` | Chat pós-pedido por loja |
| `/minha-loja/perguntas` | Perguntas públicas de produtos |
| `/minha-loja/feed` | Publicações do lojista no feed |
| `/minha-loja/exposicao` | Relatório de visibilidade na home |
| `/minha-loja/cursos` | Cursos digitais vinculados aos produtos |
| `/minha-loja/cursos/{course}/builder` | Course builder do AVA |

### Área do Cliente (`/minha-conta`)

Requer autenticação.

| Rota | Descrição |
|---|---|
| `/minha-conta/pedidos` | Pedidos do cliente |
| `/minha-conta/enderecos` | Endereços salvos |
| `/minha-conta/aprendizado` | Cursos em que o cliente está matriculado |
| `/minha-conta/aprendizado/{enrollment}/player` | Player do curso |
| `/minha-conta/aprendizado/{enrollment}/certificado` | Download do certificado |
| `/ava/materiais/{material}/download` | Download protegido de materiais por URL assinada |

---

## API Mobile (Flutter)

API REST versionada em `/api/v1`, autenticada via Laravel Sanctum (tokens Bearer), consumida pelo app mobile em Flutter para clientes compradores e lojistas. Reaproveita os mesmos Services do site (`CartService`, `OrderService`, `MercadoPagoService`, `Shipping\MelhorEnvioService`, `AvaEnrollmentService`) — mesmo comportamento de negócio, exposto em JSON.

Cobre: cadastro/login, catálogo público, perguntas de produto, carrinho (exige login), cotação de frete, checkout, pedidos, chat pós-pedido, endereços, AVA (Meu Aprendizado, progresso e certificado) e, para o lojista, perfil da loja, CRUD de produtos, gestão de pedidos recebidos, perguntas, exposição na home e publicação de cursos.

Documentação completa de rotas, formatos de resposta e exemplos: [`docs/API.md`](docs/API.md).

---

## Perfis de Usuário

| Papel | Contexto | Acesso |
|---|---|---|
| `admin` | Equipe interna | Administração completa |
| `gerente` | Equipe interna | Gestão operacional conforme permissões |
| `supervisor` | Equipe interna | Rotinas operacionais específicas |
| `editor` | Equipe interna | CMS, mídia e conteúdo |
| `lojista` | Expositor | Painel da própria loja, produtos, cursos e pedidos |
| `user` | Cliente | Minha conta, pedidos, endereços e aprendizado |

A autorização administrativa combina o enum `UserRole` com roles e permissions do pacote Spatie. O campo `users.role` permanece como compatibilidade e separação de contexto.

---

## Modelos Principais

```text
User                     -> autenticação, papel base e roles Spatie
CustomerProfile          -> perfil comprador e opt-in de marketing
CustomerAddress          -> endereços do cliente
SiteSetting              -> configurações globais
Banner                   -> banners da homepage
Menu / MenuItem          -> navegação dinâmica
Page / PageSection       -> páginas estáticas
Post                     -> posts, notícias e campanhas editoriais
Event                    -> feiras/eventos; pivot event_expositores
Expositor                -> loja pública vinculada ao lojista
ExpositorVisibilitySlot  -> slots e prioridades de visibilidade
ExpositorImpression      -> registros assíncronos de impressão
Product                  -> catálogo unificado dos três eixos
ProductFaq               -> FAQ estático por produto
ProductQuestion          -> Q&A público por produto
CartItem                 -> carrinho por sessão/usuário
Order                    -> pedido principal
OrderItem                -> snapshot dos itens comprados
OrderSplit               -> divisão por loja
OrderShipping            -> envio/rastreio por split
OrderTrackingEvent       -> linha do tempo de entrega
OrderMessage             -> chat pós-pedido
NewsletterSubscriber     -> base de newsletter
EmailCampaign            -> campanhas de email marketing
EmailCampaignSend        -> envios, abertura, clique e descadastro
LojistasSolicitacao      -> entrada e aprovação de lojistas
Media                    -> biblioteca de mídia
ContentCategory          -> categorias filtradas por eixo
FeedPost / FeedComment   -> feed social
FeedLike / FeedReport    -> curtidas, denúncias e moderação
AvaCourse                -> curso digital vinculado ao produto
AvaModule / AvaLesson    -> estrutura do curso
AvaLessonMaterial        -> materiais complementares protegidos
AvaEnrollment            -> matrícula do aluno
AvaLessonProgress        -> progresso por aula
```

---

## Enums

| Enum | Valores principais |
|---|---|
| `ItemType` | `produto`, `servico`, `cuidado` |
| `PriceType` | `fixo`, `por_hora`, `por_sessao`, `sob_consulta` |
| `Modality` | `presencial`, `online`, `ambos` |
| `UserRole` | `admin`, `gerente`, `supervisor`, `editor`, `lojista`, `user` |
| `SolicitacaoStatus` | `pendente`, `aprovado`, `bloqueado` |
| `OrderStatus` | `aguardando_pagamento`, `pagamento_confirmado`, `concluido`, `cancelado` |
| `OrderSplitStatus` | `pendente`, `confirmado` |
| `ShippingStatus` | ciclo de envio e entrega |
| `TrackingEventSource` | origem manual ou integração |
| `DeliveryType` | tipos de entrega/retirada |
| `CampaignStatus` | rascunho, agendada, enviada e estados relacionados |
| `RecipientType` | públicos-alvo de campanhas |
| `MarketplaceStatus` | status do perfil de cliente |
| `AvaEnrollmentStatus` | matrícula ativa, expirada, cancelada ou reembolsada |
| `FeedPostType` | tipos de publicação do feed |
| `VisibilitySlotType` | tipos de slot de exposição |

---

## Status do Projeto

Consulte [`docs/ROADMAP.md`](docs/ROADMAP.md) para o planejamento detalhado. O código atual representa um MVP demonstrável, com os principais fluxos públicos, administrativos, de marketplace, comunicação, marketing e AVA já implementados.

| Fase | Status no código | Entregável |
|---|---|---|
| Fase 1 — CMS, Admin & Home | Concluída | Plataforma funcional com conteúdo dinâmico |
| Fase 2 — Lojistas & Agenda | Concluída | Entrada de lojistas, painel da loja e agenda pública |
| Fase 3 — Catálogo & Três Eixos | Concluída | Produtos, serviços, cuidados, loja pública e carrinho multilojas |
| Fase 4 — Checkout, Frete & Pagamentos | MVP avançado | Checkout, pedidos, cotação Melhor Envio, Mercado Pago, envio e rastreio por split |
| Fase 5 — Comunidade & Marketing | Implementada no código | Feed social, moderação, email marketing e visibilidade de expositores |
| Fase 6 — Governança Admin | Concluída | Usuários internos, perfis, permissões e proteção de rotas/ações |
| Fase 7 — Comunicação Loja-Cliente | Concluída | FAQ, Q&A público e chat pós-pedido |
| Fase 8 — AVA | Concluída | Course builder, player, materiais protegidos, progresso e certificado PDF |
| Fase 9 — API Mobile (Flutter) | Concluída (v1) | API `/api/v1` com Sanctum, catálogo, carrinho, checkout, pedidos, chat, endereços, AVA e endpoints de lojista |
| Fase 10 — Inteligência de Cliente (JMF CI) | Concluída | Dashboard admin, rastreamento de eventos (produtos, carrinho, pedidos) e testes E2E |

### Cenário demo do AVA

O MVP inclui um curso online demonstrativo para validar a jornada de produto digital:

1. O expositor `Tecnologia Solidária` possui o produto `Curso Online de Informática Popular`.
2. O produto é digital, custa `R$ 0,01` e pode ser usado em testes de checkout.
3. O cliente `cliente.curso@teste.com` já possui matrícula ativa.
4. Em `/minha-conta/aprendizado`, o cliente pode abrir o player, marcar as quatro aulas como concluídas e baixar o certificado em PDF.

### Pós-MVP planejado

- Split automático completo via Mercado Pago.
- OAuth por lojista para Melhor Envio.
- Compra e geração de etiquetas físicas.
- Auditoria administrativa ampliada.
- Recuperação de carrinho abandonado.
- Integração SendGrid/SES para campanhas em larga escala.
- API: construtor de curso AVA, feed/comunidade e recuperação de senha para o app Flutter.

---

## Testes

```bash
composer run test
```

A suíte cobre, entre outros módulos:

- carrinho e checkout autenticado
- cotação de frete
- Mercado Pago
- governança administrativa
- email marketing
- visibilidade de expositores
- rastreio de pedidos
- FAQ, Q&A e chat
- AVA, materiais e certificados
- API mobile `/api/v1` (autenticação, catálogo, carrinho, checkout, pedidos, chat, endereços, AVA e lojista)
- Inteligência de Cliente (dashboard admin, rastreamento de eventos e resiliência a falhas do SDK)

---

## Princípios Transversais

- **Público 40+ first:** fonte mínima de 16px, botões com área de toque generosa e fluxos sem gestos complexos.
- **Performance em redes lentas:** compressão de imagens, WebP, lazy loading e dependências JavaScript enxutas.
- **Mobile first:** layout pensado a partir de 360px e escalado para tablets/desktops.
- **LGPD:** CPF/CNPJ e dados pessoais tratados com cuidado, consentimento explícito para marketing e descadastro disponível.
- **Autorização real:** menus podem esconder opções, mas a proteção precisa estar nas rotas, policies, middlewares e ações Livewire.
