# Arquitetura — Feira Esquerda Livre

> **Como e por que o sistema foi construído.** Domínios, invariantes, decisões e
> Decision Log. Este documento **não controla andamento** — o estado de cada fase
> está em [`ROADMAP.md`](../ROADMAP.md). Instalação e operação estão em
> [`README.md`](../README.md).

## Índice

1. [Objetivo e governança](#1-objetivo-e-governança)
2. [Stack](#2-stack)
3. [Arquitetura da aplicação](#3-arquitetura-da-aplicação)
4. [Domínios](#4-domínios)
5. [Product × ProductOffer](#5-product--productoffer)
6. [Isolamento entre vendedores (multi-tenant)](#6-isolamento-entre-vendedores-multi-tenant)
7. [Identidade e autorização](#7-identidade-e-autorização)
8. [Checkout e pedidos](#8-checkout-e-pedidos)
9. [Pagamentos](#9-pagamentos)
10. [Estoque](#10-estoque)
11. [Frete e rastreio](#11-frete-e-rastreio)
12. [AVA](#12-ava)
13. [Customer Intelligence](#13-customer-intelligence)
14. [Catalog Intelligence](#14-catalog-intelligence)
15. [API mobile](#15-api-mobile)
16. [Filas](#16-filas)
17. [Scheduler](#17-scheduler)
18. [Docker e infraestrutura](#18-docker-e-infraestrutura)
19. [Segurança](#19-segurança)
20. [LGPD](#20-lgpd)
21. [Testes e validação](#21-testes-e-validação)
22. [Invariantes arquiteturais](#22-invariantes-arquiteturais)
23. [Decision Log](#23-decision-log)
24. [Dívidas técnicas arquiteturais](#24-dívidas-técnicas-arquiteturais)

---

## 1. Objetivo e governança

A Feira Esquerda Livre é uma plataforma de marketplace, agenda de feiras,
comunidade e cursos digitais para lojistas e expositores populares, clientes e
equipe interna. O produto combina CMS, painel administrativo, área do lojista,
área do cliente, catálogo em três eixos, checkout multilojas, comunicação
pós-venda, email marketing e AVA.

### Princípios de produto

- **Público 40+ primeiro:** fonte mínima de 16px, área de toque generosa, fluxos
  sem gestos complexos, feedback imediato.
- **Redes lentas (3G/4G):** imagens comprimidas no backend, WebP, lazy loading,
  JavaScript enxuto.
- **Mobile first:** layout a partir de 360px.
- **Autorização real:** esconder item de menu é conveniência; a proteção vive em
  rotas, policies, middlewares e ações Livewire.
- **LGPD:** minimização, consentimento explícito para marketing e analytics,
  descadastro disponível.

### Governança deste documento

- Toda **decisão arquitetural nova** entra no [Decision Log](#23-decision-log)
  com ID novo. **IDs nunca são renumerados nem reaproveitados.**
- Decisão superada **não é apagada**: muda de estado e aponta para a que a
  substituiu.
- Documentos por fase não são mais criados. Detalhe histórico — relatórios,
  controles negativos, diffs — fica no Git.
- Duas exceções técnicas convivem com este documento: [`docs/API.md`](API.md)
  (contrato público da API) e
  [`docs/CUSTOMER_INTELLIGENCE_INTERNAL.md`](CUSTOMER_INTELLIGENCE_INTERNAL.md)
  (renderizado dentro do painel admin). Em conflito, **este documento prevalece**
  sobre regras; o `API.md` prevalece sobre formato de payload.

### Convenção de IDs

Cada fase cunhou seus próprios prefixos, e alguns códigos se repetem entre fases
com significados diferentes. Neste documento, **a fase de origem vai entre
parênteses** sempre que há risco de ambiguidade:

| Código | Aparece em | Não confundir com |
|---|---|---|
| `D-1`, `D-2`, `D-3` | CAT-DOM-01 (dívidas de domínio) | `D-1`…`D-4` da CAT-05H (achados de validação) |
| `B-1`…`B-6` | CAT-05A (blockers) | `B-1`…`B-6` da CAT-06A (blockers diferentes) |
| `G-1` | CAT-DOM-02B (sem superfície de curadoria) | `G-1` da FIN-SEC-01G (repasse confirmado sem pagamento) |
| `G-1`…`G-11` | CAT-DOM-02B (gates de multi-oferta) | `G-C*`, `G-D*`, `G-F*` (gates de fase), `I-*` (02I) |
| `R-1`…`R-n` | achados de revisão pré-commit, numerados por fase | — |

---

## 2. Stack

| Camada | Tecnologia |
|---|---|
| Backend | PHP `^8.2` (container em 8.3) · Laravel `^12.0` (12.65) |
| Frontend reativo | Livewire `^4.3` · AlpineJS `^3.15` |
| Estilo e build | TailwindCSS `^4.3` · Vite `^7` |
| Banco | MySQL 8.4 no Docker · MySQL 8 em produção · **SQLite em memória nos testes** |
| Cache, sessão e filas | Driver `database` (Redis disponível no Docker e **não usado**) |
| Permissões | `spatie/laravel-permission` `^6.25` |
| API | Laravel Sanctum `^4.3`, token pessoal Bearer |
| Imagens | `intervention/image` `^3.11` |
| PDF | `barryvdh/laravel-dompdf` `^3.1` |
| Markdown | `league/commonmark` `^2.8` (2.9.0 instalado — ver [§19](#19-segurança)) |
| Pagamento | Mercado Pago |
| Frete | Melhor Envio e Frenet |
| IA externa (opcional) | OpenAI, Responses API — desligada por padrão; o módulo depende só de `CatalogAiProvider` ([§14.5](#145-ia-externa-cat-06)) |
| E-mail em desenvolvimento | Mailpit |
| App mobile | Flutter (Riverpod, dio, go_router) em `feira_esquerda_livre_app/` |

---

## 3. Arquitetura da aplicação

### Superfícies

```text
Web público (Blade + Livewire)     /  /produtos  /loja/{loja}/{item}  /checkout …
Painel admin (Livewire)             /admin/*          App\Livewire\Admin
Painel do lojista (Livewire)        /minha-loja/*     App\Livewire\Lojista
Área do cliente (Livewire)          /minha-conta/*    App\Livewire\Cliente
API mobile (JSON)                   /api/v1/*         App\Http\Controllers\Api\V1
Console e scheduler                 routes/console.php, comandos artisan
```

### Camadas

| Camada | Papel | Onde |
|---|---|---|
| **Actions** | Transições de domínio com invariante: uma porta por operação, transacional quando escreve | `app/Actions/{Catalog,Orders,Payments,Stock}` |
| **Services** | Integração e orquestração reutilizada por web e API | `app/Services` (`CartService`, `OrderService`, `MercadoPagoService`, `Shipping\*`, `AvaEnrollmentService`, `ImageService`, `CatalogAi\*` …) |
| **Policies** | Autoridade canônica (onde o override de admin é desejado) | `app/Policies` (`ProductPolicy`, `FeedPostPolicy`, `FeedCommentPolicy`) |
| **Predicados de domínio** | Ownership comercial, onde o override de admin **não** é desejado | `ProductOffer::pertenceAoExpositorDe()`, `ProductQuestion::podeSerRespondidaPor()` |
| **Módulos internos** | Namespaces autocontidos com ServiceProvider, config e tabelas próprias | `app/CustomerIntelligence`, `app/CatalogIntelligence` |
| **Eventos e listeners** | Desacoplamento marketplace → AVA e marketplace → tracking | `OrderSplitConfirmed`, `OrderSplitReverted` |

Regras transversais:

- **Web e API compartilham a mesma regra.** Nenhuma regra econômica ou de
  autorização existe em duas cópias: `SaveProductWithOffer` (cadastro),
  `CartShippingQuoter` (frete), `ResolveProductOffer` (seleção de oferta),
  `ConfirmOrderPayment` (pagamento).
- **Componentes Livewire ficam em `App\Livewire`**, inclusive os dos módulos
  internos — `config/livewire.php` só descobre esse namespace.
- **Método público de Livewire é endpoint.** Cada um se autoriza por conta
  própria; proteger só o `mount()` é proteção de fachada (SEC-02C, SEC-03).

### Estrutura dos módulos internos

```text
app/CustomerIntelligence/          app/CatalogIntelligence/
├── Actions/     5                 ├── Actions/     6
├── Console/     4                 ├── Console/     1
├── Enums/       4                 ├── Contracts/   1   CatalogAiProvider
├── Facades/     1                 ├── DTOs/        6
├── Http/        Controller+MW     ├── Enums/      10
├── Jobs/        1                 ├── Models/      3
├── Models/      5                 ├── Providers/   2   Null, Fake
├── Queries/     4                 ├── Queries/     1
├── Services/    1                 ├── Support/     6
├── Support/     5                 └── CatalogIntelligenceServiceProvider
└── CustomerIntelligenceServiceProvider
```

Convenções herdadas do Customer Intelligence e seguidas pela Catalog
Intelligence: provider registrado em `bootstrap/providers.php`, config própria,
prefixo de tabela próprio (`ci_`, `catalog_`), bindings `scoped()` e não
`singleton()` (Octane), decisão de política centralizada numa classe, pastas só
quando têm conteúdo.

O que é específico de fornecedor fica **fora** do módulo: desde a CAT-10A, o adaptador
do provider real e o seletor que decide entre ele e o `Null` moram em
`app/Services/CatalogAi` (D-CAT-10A-1, D-CAT-10A-2); desde a CAT-10A.1, também a leitura da
configuração que o painel grava no banco (`CatalogAiSettings`, D-CAT-10A1-4).

---

## 4. Domínios

| Domínio | Responsabilidade | Entidades principais |
|---|---|---|
| **CMS e conteúdo** | Banners, páginas, menus, posts, mídia, configurações globais | `Banner`, `Page`, `Menu`, `MenuItem`, `Post`, `Media`, `SiteSetting` |
| **Eventos / agenda** | Feiras e expositores confirmados | `Event`, pivot `event_expositores` |
| **Lojistas** | Solicitação, aprovação, loja pública | `LojistasSolicitacao`, `Expositor` |
| **Catálogo** | Identidade do item e condição de venda — ver [§5](#5-product--productoffer) | `Product`, `ProductOffer`, `ContentCategory`, `ProductFaq`, `ProductOfferFaq`, `ProductQuestion` |
| **Marketplace** | Carrinho, checkout, pedido, split, envio | `CartItem`, `Order`, `OrderItem`, `OrderSplit`, `OrderShipping`, `OrderTrackingEvent`, `PaymentConflict` |
| **Clientes** | Perfil comprador, endereços | `User`, `CustomerProfile`, `CustomerAddress` |
| **Comunicação** | FAQ, Q&A público, chat pós-pedido | `ProductOfferFaq`, `ProductQuestion`, `OrderMessage` |
| **Social** | Feed da comunidade e moderação | `FeedPost`, `FeedComment`, `FeedLike`, `FeedReport` |
| **Marketing** | Newsletter e campanhas | `NewsletterSubscriber`, `EmailCampaign`, `EmailCampaignSend` |
| **Visibilidade** | Exposição de expositores na home | `ExpositorVisibilitySlot`, `ExpositorImpression` |
| **AVA** | Cursos digitais, matrícula, progresso, certificado — ver [§12](#12-ava) | `AvaCourse`, `AvaModule`, `AvaLesson`, `AvaLessonMaterial`, `AvaEnrollment`, `AvaLessonProgress` |
| **Customer Intelligence** | Telemetria comportamental — ver [§13](#13-customer-intelligence) | `Visitor`, `VisitorSession`, `TrackedEvent`, `DailyMetric`, `AuditLog` |
| **Catalog Intelligence** | Assistência ao cadastro — ver [§14](#14-catalog-intelligence) | `KnowledgeEntry`, `KnowledgeTerm`, `KnowledgeRelation` |

### Enums centrais

| Enum | Valores |
|---|---|
| `ItemType` | `produto`, `servico`, `cuidado` |
| `PriceType` | `fixo`, `por_hora`, `por_sessao`, `sob_consulta` |
| `Modality` | `presencial`, `online`, `ambos` |
| `UserRole` | `admin`, `gerente`, `supervisor`, `editor`, `lojista`, `user` |
| `OrderStatus` | aguardando pagamento, pagamento confirmado, concluído, cancelado, expirado, estornado |
| `OrderSplitStatus` | pendente, confirmado, revertido |
| `AvaEnrollmentStatus` | `Active`, `Expired`, `Cancelled`, `Refunded` |

### Três eixos do catálogo

Um catálogo único separado por `item_type`. Produtos têm estoque e dimensões de
frete; serviços e cuidados têm modalidade, tipo de preço e duração — **todos
esses campos vivem na oferta**. Um lojista pode atuar nos três eixos; carrinho e
checkout agrupam por loja.

---

## 5. Product × ProductOffer

> **A fronteira mais importante do sistema.** Construída pela CAT-DOM-01 e pela
> CAT-DOM-02 (02A→02I), e travada por `CatalogoHardeningFinalTest`.

### 5.1 As duas verdades

```text
Product  = identidade / autoridade canônica global      "o que este item é"
ProductOffer = verdade comercial de um vendedor          "quem vende, por quanto, como"
```

Um item continua existindo quando o expositor que o cadastrou deixa a Feira:
some das vitrines, mas identidade, imagens canônicas e conhecimento acumulado
permanecem, prontos para quando outro expositor oferecê-lo.

```text
Product                              ProductOffer
├── identidade / curadoria           ├── vendedor (expositor_id)
├── slug, categoria, descrição       ├── preço, tipo de preço, modalidade, duração
├── imagem canônica                  ├── estoque físico e reservado
├── is_active (validade canônica)    ├── peso e dimensões de frete
├── expositor_id (proveniência)      ├── is_active (disponibilidade comercial)
└── delegação canônica               ├── destaque e ordem de vitrine
                                     ├── imagem comercial
                                     └── FAQ comercial

ProductQuestion  → Product (agrupamento) + ProductOffer (destinatário)
OrderItem        → ProductOffer histórica (SET NULL) + snapshots
AvaCourse        → Product canônico
AvaEnrollment    → oferta de origem via order_split → order_item
```

### 5.2 Schema

`products` — **17 colunas**, todas de identidade, governança, proveniência ou
imagem canônica:

```text
id · item_type · expositor_id · canonical_delegate_expositor_id
canonical_delegated_at · canonical_delegation_revoked_at · category_id
name · slug · short_description · description · image_path · images
is_active · is_digital · created_at · updated_at
```

`product_offers` — 20 colunas:

```text
id · product_id · expositor_id · images · price · price_type · modality
duration_min · weight · height · width · length · has_stock · stock_quantity
reserved_quantity · is_active · is_featured · sort_order · created_at · updated_at
```

`UNIQUE(product_id, expositor_id)`: um expositor tem no máximo uma oferta por
item.

**Os doze espelhos comerciais saíram de `products` na CAT-DOM-02H** e **não
podem voltar** sem decisão arquitetural explícita (D-02I-9):

```text
price  price_type  modality  duration_min  weight  height  width  length
has_stock  stock_quantity  is_featured  sort_order
```

A lista canônica vive em código: `SaveProductWithOffer::ESPELHOS_COMERCIAIS_LEGADOS`.
A `ProductFactory` e o trait de seed `SincronizaOfertaDoItem` aceitam essas
chaves como açúcar de entrada e as roteiam para a oferta. Nenhum writer — de
runtime, fixture ou seeder — grava esses campos em `products`.

### 5.3 `is_active` existe duas vezes, com significados diferentes

| Campo | Significa | Quem altera |
|---|---|---|
| **`products.is_active`** | **Validade canônica** — a plataforma considera o item válido e publicável | **Somente curadoria** (`ProductPolicy::updateStatus`). Nem o dono da oferta, nem o delegado canônico |
| **`product_offers.is_active`** | **Disponibilidade comercial** — aquele expositor está vendendo agora | O expositor dono da oferta |

`products.is_active` **não** representa estoque, pausa comercial, expositor
ativo nem oferta ativa (D-CAT-10). As implicações valem nas duas direções:

| Afirmação | Vale? |
|---|:--:|
| zero ofertas ⇒ `products.is_active = false` | Não |
| `products.is_active = true` ⇒ há algo à venda | Não |
| `products.is_active = false` ⇒ nenhuma oferta é vigente | **Sim** |
| todas as ofertas inativas ⇒ o `Product` deixa de existir | Não |

`ProdutoIndex::toggleActive` escreve **só** a oferta desde a CAT-DOM-02C.

### 5.4 Vigência — o que o público vê

```text
oferta vigente  ⇔  product_offers.is_active ∧ expositores.is_active ∧ products.is_active
produto visível ⇔  existe ao menos uma oferta vigente
```

A regra vive **só** em `ProductOffer::scopeVigente()` e `isVigente()`, e
`Product::scopeComOfertaVigente()` delega a ela. Reescrever a condição em outro
lugar recria a divergência que a CAT-DOM-01 eliminou — foi o que fazia item de
loja inativa aparecer na listagem e dar 404 ao ser clicado.

Um `Product` sem oferta vigente é preservado, continua ativo no catálogo interno
e na Catalog Intelligence, e não aparece nem é indexável (D-CAT-21).

### 5.5 Proveniência, delegação e autoridade canônica

| Coluna | Significa | Autoriza? |
|---|---|:--:|
| `products.expositor_id` | **Proveniência** — quem trouxe o item ao catálogo. Escrito uma vez, na criação | **Nunca** (D-CAT-11) |
| `canonical_delegate_expositor_id` + `canonical_delegated_at` + `canonical_delegation_revoked_at` | **Delegação canônica** — quem a plataforma autorizou a editar a identidade | Sim, enquanto ativa |

**Delegação ativa** = delegado não nulo **e** `revoked_at` nulo. Revogar preserva
quem teve e desde quando. FK `SET NULL`: sem expositor não há delegado, e o item
fica sob curadoria.

A autoridade sobre `Product` é **da plataforma** (D-CAT-09):

1. Curadoria (portadores de `produtos.moderar` — administrador, gerente,
   supervisor) edita campos canônicos sempre.
2. O expositor que origina um item **recebe delegação no mesmo ato do cadastro**
   (`SaveProductWithOffer`, `ProductFactory`, seeders).
3. A delegação **não** é ownership, **não** decorre da quantidade de ofertas,
   **não** é inferida de `products.expositor_id`, é **revogável**, **termina**
   quando o item é formalmente compartilhado e **não volta** se o número de
   ofertas cair para um.
4. Compartilhar um `Product` é ato exclusivo de curadoria.

> **Autoridade canônica ≠ quantidade de ofertas.** Quantidade de ofertas é estado
> comercial; autoridade é estado de governança. Nenhuma regra pode ser escrita
> como "se o produto tem uma oferta, o expositor edita".

`ProductPolicy`:

| Ability | Quem passa |
|---|---|
| `updateCanonical` | curadoria **ou** delegação ativa para o expositor do usuário |
| `updateStatus` | **somente** curadoria |

Campos sob autoridade canônica: `Product::CAMPOS_CANONICOS` = `name`,
`short_description`, `description`, `item_type`, `category_id`, `is_digital`.
A verificação é sobre **mudança**, não presença: o formulário reenvia tudo, e o
lojista sem delegação continua podendo alterar a própria oferta. Mudar canônico
sem autoridade lança `SemAutoridadeCanonica` (tela: mensagem; API: 403).

### 5.6 Ownership comercial

**Uma definição, um lugar** (D-02F-1):

```php
ProductOffer::pertenceAoExpositorDe(?User $user): bool   // product_offers.expositor_id
ProductQuestion::podeSerRespondidaPor(?User $user): bool
ProductQuestion::scopeDirigidaAoExpositor(?int $expositorId)
```

Deriva **exclusivamente** de `product_offers.expositor_id` — nunca de
`products.expositor_id`, nunca de delegação canônica, nunca de cardinalidade.

**Deliberadamente não é uma Policy.** `Gate::before` concede tudo a admin antes
de qualquer Policy, e admin não tem expositor: uma Policy responderia "pode" e o
código seguinte quebraria no expositor nulo.

| Eixo | Mecanismo | Admin passa por cima? |
|---|---|---|
| Autoridade **canônica** (`Product`, curso) | `ProductPolicy` + `Gate` | **sim**, e é desejado |
| Ownership **comercial** (`ProductOffer`) | predicado + escopo explícitos | **não**, e é desejado |

`expositor_id` e `product_id` estão **fora** das allowlists
(`CAMPOS_DA_OFERTA`, `CAMPOS_DO_PRODUTO`): transferir oferta ou revinculá-la a
outro item é impossível por construção, em formulário e em payload de API.

### 5.7 Conteúdo por oferta

| Conteúdo | Canônico (item) | Comercial (oferta) | Regra de leitura |
|---|---|---|---|
| **Imagem** | `products.images`, `products.image_path` | `product_offers.images` (JSON, até 4) | Fallback **de leitura**: oferta → canônica → `image_path` → placeholder, em `ProductOffer::imagensParaExibicao()` e `urlDaImagemPrincipal()` |
| **FAQ** | `product_faqs` (curadoria; hoje vazia) | `product_offer_faqs` (`UNIQUE(product_offer_id, sort_order)`, CASCADE) | **Sem fallback e sem concatenação** (D-02E-1). Se um dia a canônica aparecer em página comercial, em seção própria e rotulada |
| **Pergunta** | `product_questions.product_id` (agrupamento, `NOT NULL`) | `product_questions.product_offer_id` (destinatário, nullable, SET NULL) | Só o dono da oferta responde e oculta (D-02F-4). Pergunta sem oferta não tem destinatário e ninguém a assume (D-02F-5) |

Invariantes de conteúdo:

- **Imagem canônica e imagem da oferta nunca compartilham arquivo físico.**
  `ImageService::delete()` apaga por caminho sem contar referências; path
  compartilhado faria o lojista apagar a foto do catálogo. Backfill **copia bytes**
  e preserva a extensão de origem.
- **Fallback é leitura, nunca persistência** — copiar o caminho canônico para a
  oferta recriaria o compartilhamento proibido.
- `removeImage()` só apaga do disco o arquivo que nada mais referencia.
- `product_offers.image_path` **não existe** e não deve ser criado.
- Resposta de expositor só vira FAQ canônica ou conhecimento por **ato de
  curadoria** (D-CAT-18).
- `answered_by` guarda a pessoa (`users.id`); a loja respondente é derivável por
  `question.productOffer.expositor`.

### 5.8 Seleção de oferta

`App\Actions\Catalog\ResolveProductOffer` + `Contexto`:

```text
id informado            → valida que a oferta é DESTE produto; senão, null
id ausente + 1 oferta   → resolve pela cardinalidade determinística
id ausente + 0 ou >1    → null   (a superfície recusa: 422 ou 403)
```

| Contexto | Regra |
|---|---|
| `Contexto::Compra` | exige oferta **vigente** |
| `Contexto::Historico` | aceita oferta **inativa** — pedido e matrícula apontam para o que foi vendido |

Proibido para **decidir de quem se compra, de quem se comprou ou quem responde**:
`first()`, `latest()`, `oldest()`, `orderBy('id')`, `ofertaVigente`,
`products.expositor_id`, delegação canônica (D-02G-2).

`Product::ofertaVigente()` (menor preço, desempate por id) é permitido **apenas
em apresentação** — cards de vitrine, home, catálogo, `ProductResource`. No dia
em que houver multi-oferta, *qual oferta o card destaca* vira decisão de
produto.

A API aceita `product_offer_id` **opcional** em perguntas e no carrinho; ausente
e ambíguo, recusa com 422. Opcional por compatibilidade enquanto multi-oferta
não existir.

### 5.9 URL e slug

- A única URL comercial é **`/loja/{expositor}/{produto}`**, que resolve loja e
  item — exatamente uma oferta. **Não existe `/produto/{slug}`**, e há teste que
  falha se alguém criar (D-02G-7).
- `products.slug` é `UNIQUE` global e **desambiguado na criação**
  (`SaveProductWithOffer::slugUnico()`); o update não altera slug, então permalink
  publicado não muda.
- `product_offers` não tem slug.

### 5.10 Escrita

`SaveProductWithOffer` é o **único** ponto que transforma um cadastro em produto
+ oferta, usado pelo Livewire e pela API. Existe **um único
`ProductOffer::create` em `app/`**, dentro do ramo que cria o `Product` na mesma
transação; nenhuma assinatura aceita um `product_id` existente. Excluir um item no
painel ou na API remove a **oferta** (`DeleteProductOffer`), nunca o produto.

### 5.11 Multi-oferta

> **Preparada arquiteturalmente. NÃO habilitada.**

Está desabilitada **por construção**, não por convenção: não há rota, componente
Livewire nem endpoint que anexe oferta a item existente; o cadastro sempre cria
item novo (dois vendedores com o mesmo nome geram dois itens); `UNIQUE(product_id,
expositor_id)` é a segunda barreira. Cenários A × B nos testes são montados por
factory — fixture estrutural, não ativação (D-02I-8).

Pré-requisitos antes de habilitar (gates G-1…G-11 da CAT-DOM-02B, estado em
`ROADMAP.md` §13): superfície de curadoria (G-1), criação de oferta sobre item
existente só por curadoria (G-2), caminho de proposta (G-3), regra de
apresentação, SEO canônico. **Semelhança nunca funde identidade** (D-CAT-20): não
existe GTIN, SKU nem marca no catálogo, então o único critério admissível é
curadoria humana com evidência. Em artesanato, na dúvida, itens distintos.

### 5.12 Chaves estrangeiras que contam decisões

| Tabela · coluna | Regra | Motivo |
|---|---|---|
| `product_offers.product_id` / `expositor_id` | CASCADE | a oferta morre com o item ou com a loja; o item sobrevive à loja |
| `products.expositor_id` | SET NULL | proveniência sobrevive à saída |
| `products.canonical_delegate_expositor_id` | SET NULL | delegação some, item fica |
| `product_offer_faqs.product_offer_id` | CASCADE | FAQ é composição da oferta |
| `product_questions.product_offer_id` | SET NULL | conteúdo do cliente sobrevive à loja |
| `order_items.product_offer_id` / `product_id` | SET NULL | pedido é fato histórico |
| `ava_enrollments.order_split_id` | SET NULL | origem comercial preservada |

---

## 6. Isolamento entre vendedores (multi-tenant)

O sistema **não** é multi-tenant de banco: há uma base, um catálogo e uma equipe
interna. O isolamento que importa é **entre expositores** dentro do marketplace.

| Recurso | Isolado por | Prova |
|---|---|---|
| Oferta — preço, estoque, status, imagem, FAQ, exclusão | `ProductOffer::pertenceAoExpositorDe()`, escopos `ProductOffer::where('expositor_id')` | `CatalogoIsolamentoTest`, `OfertaIsolamentoComercialTest` |
| Pergunta e resposta | `product_offer_id` da pergunta | `PerguntaAutoridadeDeRespostaTest` |
| Split, chat e envio | `order_splits.expositor_id` + identidade válida | SEC-03 |
| Relatório de exposição | expositor autenticado | Fase 5.4 |
| Identidade do item | autoridade canônica, não ownership | `AutoridadeCanonicaTest` |

Regras que não se relaxam:

- Conhecer id, slug ou URL de recurso alheio não dá leitura de gestão, edição,
  exclusão, remoção de imagem, alteração de FAQ nem posse — no painel e na API.
- Recurso alheio responde **403** na área do lojista (padrão de `CursoBuilder`,
  `ProductShareImageController` e da API); a API de perguntas usa escopo e
  devolve 404.
- `expositor_id` nunca é recalculado a partir de quem salva.
- **Histórico preservado não é permissão preservada** (SEC-03): split órfão
  sobrevive e ninguém herda o papel do vendedor excluído.
- A similaridade da Catalog Intelligence atravessa lojas **por decisão**, mas só
  lê campos públicos de itens vigentes e nunca escreve em produto.

---

## 7. Identidade e autorização

### 7.1 Modelo de usuário

`users.role` guarda **um único papel base** (`UserRole`), que decide o contexto
de acesso. `CustomerProfile` é complementar e declara "este usuário também é
cliente do marketplace", independentemente do papel.

| Universo | `role` | Acessa | Pode ter `CustomerProfile` |
|---|---|---|---|
| Cliente | `user` | `/minha-conta` | sim, criado no registro |
| Lojista | `lojista` | `/minha-loja` | sim, se também comprar |
| Equipe interna | `admin`, `gerente`, `supervisor`, `editor` | `/admin` | sim |

- `users.is_active` controla acesso global; `customer_profiles.marketplace_status`
  controla só a atuação como comprador. Inativar um cliente não tira acesso
  administrativo de quem também é equipe.
- Lojistas não se cadastram pela API nem pelo site: nascem da aprovação de
  `lojista_solicitacoes`.

### 7.2 Camadas de autorização

| Camada | Mecanismo |
|---|---|
| Papéis e permissões | `spatie/laravel-permission`, versionados em `RolePermissionSeeder` (idempotente) |
| Admin total | `Gate::before(fn ($user) => $user->isAdmin() ? true : null)` |
| Painel admin | `AdminMiddleware` (`isInternalUser()`) + `can:*` por rota + `authorize()` no `mount()` |
| Painel do lojista | `LojistaMiddleware` (papel `lojista` e expositor ativo; 403 em JSON na API) |
| API | `auth:sanctum` |
| Autoridade canônica sobre `Product` e curso | `ProductPolicy` |
| Ownership comercial | predicados de `ProductOffer` e `ProductQuestion` — fora de Policy ([§5.6](#56-ownership-comercial)) |

**Curadoria = permissão `produtos.moderar`** (administrador, gerente,
supervisor). `editor` não a possui. Não existe permissão nova de curadoria, e
não existe tela de curadoria (G-1).

Permissões de módulos internos: `customer_intelligence.visualizar` (painel) e
`customer_intelligence.auditoria` (trilha — só administrador).

### 7.3 Regras de autorização que não se relaxam

- **Todo método público Livewire se autoriza.** `mount()` protegido não protege
  `save()`, `removeImage()`, `delete()` (SEC-02C).
- **Autorizar antes de I/O destrutivo.** Em `removeImage()` o guard vem antes do
  `Storage::delete`.
- **Identidade válida antes de comparar propriedade** — `null === null` não é
  correspondência (SEC-03).
- **Ownership nunca vem do payload.** `expositor_id` entra só na criação, a partir
  do expositor autenticado.
- **SEC-02 escolheu guard escopado e não `ProductPolicy`** para ownership, pelo
  motivo de `Gate::before`; a CAT-DOM-02C criou `ProductPolicy` só para o eixo
  canônico, onde o override de admin é desejado.

---

## 8. Checkout e pedidos

### 8.1 Princípio

> **O relacionamento com o vendedor é temporal. O pedido é histórico.**
> `DELETE` de cadastro operacional ≠ `DELETE` de fato comercial.

### 8.2 Estrutura

```text
Order (referência pública não sequencial, total, status, pagamento, prazos)
├── OrderItem   (product_id, product_offer_id, expositor_id — todos SET NULL)
│               product_name, expositor_name, unit_price, quantity, total_price
└── OrderSplit  (um por loja; expositor_id SET NULL; expositor_name)
    ├── gross_amount · commission_percent · commission_amount · net_amount
    ├── shipping_amount (nullable: divisão desconhecida é NULL, nunca rateio)
    ├── OrderShipping → OrderTrackingEvent
    └── OrderMessage (chat)
```

- `OrderService::createFromCart()` cria pedido, itens e splits numa transação,
  reserva estoque e grava os snapshots.
- `OrderItem` responde sozinho *o quê, por quanto, quantos, total e de quem*,
  sem produto, oferta ou expositor vivos.
- Snapshots são escritos **uma vez**, no servidor, na criação; nenhuma rotina os
  sincroniza com o cadastro vivo (D-FIN-06 a D-FIN-08).
- `OrderSplit` é **cálculo comercial histórico**, não recebível financeiro
  (D-FIN-10). Comissão incide sobre a mercadoria, nunca sobre o frete.
- `CASCADE` só onde há composição real: apagar o pedido leva itens e splits.
  `payment_conflicts.order_id` é `RESTRICT`: pedido com conflito não é apagável.
- Nenhuma superfície da aplicação apaga `Order`, `Expositor`, `Product` ou
  `User`.

### 8.3 Estados

`OrderStatus` tem uma matriz de transições; toda escrita de status passa por
`CancelOrder`, `CompleteOrder`, `ExpireOrder`, `ConfirmOrderPayment` ou
`ReverseOrderPayment`, que travam o pedido (`lockForUpdate`) e relêem o estado.

| Estado | Chega por | Terminal |
|---|---|:--:|
| Aguardando pagamento | criação | não |
| Pagamento confirmado | `ConfirmOrderPayment` | não |
| Concluído | `TrackShipmentsJob`, quando todos os envios estão entregues — **projeção logística** (D-FIN-36) | não |
| Cancelado | `CancelOrder` — decisão de alguém | sim |
| Expirado | `ExpireOrder` — o relógio | sim |
| Estornado | `ReverseOrderPayment` — refund comprovado | sim |

- `temPagamentoConfirmado()` = só `PagamentoConfirmado` e `Concluido`.
  `Estornado` tem `paid_at` e **não** conta: o dinheiro voltou (D-FIN-45).
- `OrderSplit::confirmar()` exige pedido com pagamento confirmado — split de
  pedido não pago não é confirmável por painel nem API (G-1 da FIN-SEC-01G).
- `Concluido → Estornado` é permitido: a evidência de entrega vive em
  `order_shippings.delivered_at` e sobrevive ao estorno.

### 8.4 Dois relógios

| Coluna | Significa | Autoridade |
|---|---|---|
| `payment_expires_at` | prazo objetivo de uma intenção de pagamento que **existe** | o gateway (`date_of_expiration`) |
| `checkout_expires_at` | quanto tempo a reserva vale enquanto **não existe** intenção | a plataforma (`CHECKOUT_RESERVATION_MINUTES`, padrão 30) |

- `payment_expires_at` nulo significa **sem evidência**, nunca "expirado"
  (D-FIN-26). Ele nunca recebe estimativa da aplicação (D-FIN-29).
- Quando os dois existem, o do gateway prevalece (D-FIN-30).
- `orders:expire-payments` roda a cada 5 minutos, em duas consultas separadas
  (uma por índice), até 200 pedidos por execução, uma transação por pedido; o
  comando escolhe quais olhar e `ExpireOrder` decide, sob lock.
- A liberação de estoque vive **dentro** da transação de expiração.
- Criar intenção de pagamento revalida o estado **sob lock na escrita**, depois
  da chamada HTTP — nunca com lock segurado durante I/O externo.

---

## 9. Pagamentos

### 9.1 Confirmação

`App\Actions\Payments\ConfirmOrderPayment` é a única transição de confirmação e
**não conhece gateway**: recebe um `PaymentConfirmation` (provedor, id externo,
valor, momento, payload).

```text
gateway afirma "aprovado"
  → MercadoPagoService traduz → PaymentConfirmation
  → ConfirmOrderPayment  [DB::transaction + lockForUpdate]
       ├── já pago? devolve sem efeito
       ├── valor == pedido, em centavos inteiros? senão recusa
       ├── ConsumeOrderStock
       ├── Order → pago; paid_at só na primeira confirmação
       └── cada split pendente → confirmar()
                                   ↓ DB::afterCommit
                          OrderSplitConfirmed → matrícula AVA · tracking
```

Regras:

- **Idempotente**: receber duas vezes produz o mesmo estado e os mesmos efeitos
  (D-FIN-13).
- **Igualdade**, não suficiência: `(int) round($valor * 100)` dos dois lados. Valor
  ausente ou ilegível **não confirma**.
- **Estado terminal não ressuscita**: `approved` tardio sobre pedido cancelado,
  expirado ou estornado vira conflito.
- Segundo `payment_id` para pedido já quitado não reescreve o rastro do
  pagamento que quitou.
- Efeitos externos só depois do commit; falha de listener **não** desfaz o
  pagamento.

### 9.2 Webhook

O webhook **não confia no corpo**: extrai `data.id` e consulta
`getPayment($id)` server-to-server com o token da loja; status e
`external_reference` vêm dessa resposta. A exceção é o tópico de chargeback, que
confia em `data.payment_id` e **não transiciona nada** — só grava conflito. Não
há verificação de assinatura (**F-06**, security debt).

### 9.3 Reversão e conflitos

`ReverseOrderPayment` espelha a confirmação: atômica, idempotente, sem gateway.
Leva o pedido a `Estornado`, splits a `Revertido` e, depois do commit, a matrícula
a `Refunded`.

| Preserva | Porque responde |
|---|---|
| `paid_at` | "quando foi pago?" (D-FIN-32) |
| `mercado_pago_payment_id` | identidade do pagamento, não do estorno (D-FIN-35) |
| `stock_consumed_at` e o estoque físico | reversão financeira não é devolução física (D-FIN-31) |
| `splits[].confirmed_at` | quando o repasse passou a ser devido |

Novas colunas: `reversed_at`, `reverted_at`.

`payment_conflicts` registra o que o domínio não sabe representar, **fora da
transação que falhou** (D-FIN-33), com chave única por evento:

| Tipo | Nasce quando |
|---|---|
| `insufficient_stock` | aprovado sem unidades |
| `payment_after_expiration` | aprovado sobre pedido expirado — o estoque já voltou |
| `payment_after_terminal` | aprovado sobre cancelado ou estornado |
| `amount_mismatch` | valor aprovado ≠ pedido |
| `unmatched_reversal` | reversão de outro pagamento ou de pedido nunca pago |
| `partial_refund_unsupported` | devolução parcial (D-FIN-34) |
| `chargeback_unverified` | contestação aberta, desfecho desconhecido (D-FIN-37) |
| `unexpected_cancellation_after_payment` | `cancelled` sobre pedido pago (D-FIN-39) |

Resolver é marcar `resolved_at`. Não há tela; a consulta de reconciliação é SQL
direto sobre `payment_conflicts JOIN orders WHERE resolved_at IS NULL`.

Recuperação de colisão acontece **fora** da transação, porque um deadlock (1213)
desfaz a transação inteira (D-FIN-43). Releitura após violação de unicidade usa
`lockForUpdate()` — em `REPEATABLE READ` a leitura simples via snapshot anterior
ao commit do vencedor.

---

## 10. Estoque

Estoque **pertence à oferta** (D-FIN-17).

```text
stock_quantity     físico — o que existe, o que o lojista edita
reserved_quantity  comprometido por pedidos ainda não pagos
disponível         stock_quantity − reserved_quantity      (ProductOffer::disponivel())
```

`has_stock = false` ou `stock_quantity` nulo = ilimitado. Produto digital não
disputa unidade.

| Momento | Operação | Action |
|---|---|---|
| Carrinho | nada — carrinho não reserva (D-FIN-18) | — |
| Checkout | `reserved += qty` | `ReserveOrderStock`, na transação do pedido |
| Pagamento | `stock -= qty`, `reserved -= qty` | `ConsumeOrderStock`, na transação da confirmação |
| Cancelamento / expiração | `reserved -= qty` | `ReleaseOrderStock`, na transação da transição |
| Estorno | **nada** | — |

Regras:

- Locks de oferta sempre em **ordem crescente de id**; ordem de lock
  `Order → ProductOffers`.
- `stock_reserved_at`, `stock_consumed_at`, `stock_released_at` em `orders`
  garantem cada transição no máximo uma vez; a **ausência** da marca identifica
  pedido anterior à reserva, que disputa o estoque no pagamento e falha fechado.
- Oferta com reserva ativa **não pode ser excluída** (D-FIN-24; lock em
  `DeleteProductOffer` + guarda `deleting` no model) nem ter o controle de estoque
  desligado ou reduzido abaixo do comprometido (D-FIN-28). Desativar continua
  permitido.
- `INT UNSIGNED` + `STRICT_TRANS_TABLES`: estoque negativo é **impossível no
  schema**, não improvável no código.
- Concorrência resolvida **no banco**, não por validação prévia de UI (D-FIN-22).

---

## 11. Frete e rastreio

- **Provedor único por vez**, escolhido em `SiteSetting.frete_provedor`: Melhor
  Envio (padrão) ou Frenet.
- Credenciais no painel `/admin/settings/checkout`, criptografadas no banco; o
  `.env` é fallback **só** para Melhor Envio e Frenet. Mercado Pago não tem
  fallback por `.env`.
- Conexão OAuth da **conta da plataforma** com o Melhor Envio em
  `/admin/melhor-envio/conectar` (callback em `/admin/melhor-envio/callback`).
  OAuth por lojista não existe.
- Cotação por loja: CEP de origem em `expositores.zipcode`; peso, dimensões e
  valor vêm **da oferta**. Item sem dados logísticos não quebra o checkout.
- `CartShippingQuoter` é compartilhado por web e API. **O cliente escolhe o
  serviço; o servidor conhece o preço** (D-FIN-11). Na API, `shipping_options`
  informa `{expositor_id, service_id}` por loja; `shipping_total` enviado e
  divergente do cotado recusa o pedido.
- **Fail closed**: timeout, 500, resposta vazia, preço ausente, não numérico,
  negativo ou zero do provedor — o pedido não é criado. Escolha duplicada para a
  mesma loja é recusada. Toda loja com item físico precisa de exatamente uma
  escolha. HTTP acontece antes da transação do pedido.
- Rastreio: lojista marca envio (`OrderShipping`), `TrackShipmentsJob` atualiza
  3×/dia, eventos normalizados em `order_tracking_events` (`source`: integração ou
  manual), página pública `/rastreio/{trackingCode}`, e-mail ao cliente. O
  rastreio é por loja.

---

## 12. AVA

- `ava_courses.product_id` é **UNIQUE**: o curso é **canônico do `Product`**; o
  que é comercial é a compra (D-02G-5).
- **Autoridade sobre o curso = autoridade canônica** (`ProductPolicy::updateCanonical`):
  curadoria ou delegação ativa. Ter oferta sobre o item **não** concede acesso ao
  builder nem à publicação (D-02G-6, revisada).
- Matrícula nasce do evento `OrderSplitConfirmed` →
  `HandleAvaEnrollmentOnSplitConfirmed` → `AvaEnrollmentService::createFromOrderSplit()`.
  O marketplace nunca importa código AVA.
- **Oferta de origem** da matrícula: `AvaEnrollment::ofertaDeOrigem()` percorre
  `order_split → order_item.product_offer_id` e devolve a oferta **registrada no
  item**, nunca `ofertaVigente`. Matrícula de cortesia devolve `null`.
- Reversão financeira → `Refunded` → `isAccessible() === false` em player,
  materiais e API. Progresso, conclusão e certificado emitido são preservados
  (D-FIN-38).
- Materiais por URL assinada de 15 minutos, conferindo matrícula ativa.
- Certificado PDF A4 (dompdf) gerado na primeira vez em que o progresso chega a
  100%, idempotente.
- **F-07 aberto:** `ava_courses → products` e `ava_enrollments → ava_courses` são
  CASCADE; apagar um `Product` por SQL apagaria curso, matrículas e progresso.

---

## 13. Customer Intelligence

> **Telemetria e comportamento** — visitas, sessões, carrinho, pedido,
> agregações, consentimento, LGPD e retenção. Módulo `app/CustomerIntelligence`.
> **Não é a Catalog Intelligence** ([§14](#14-catalog-intelligence)): não gera
> texto, não conhece conhecimento de catálogo e não participa do cadastro.

Documentação operacional detalhada, exibida dentro do painel em
`/admin/customer-intelligence/documentacao`:
[`CUSTOMER_INTELLIGENCE_INTERNAL.md`](CUSTOMER_INTELLIGENCE_INTERNAL.md).

### 13.1 Decisão de internalização

**Comportamento gerado na Feira pertence à Feira.** O módulo nasceu como cliente
HTTP de um SDK externo (Fase 10) e foi internalizado pela Trilha CI-01…CI-09:
grava no MySQL local, o painel lê do próprio banco, nenhuma chamada de rede, um
único `git clone`. Não existe credencial de plataforma externa a configurar.

### 13.2 Fluxo

```text
Requisição web
  └── TrackVisitorSession (último middleware do grupo web)  → ci_visitors · ci_sessions
Ação de negócio (7 pontos)
  └── CustomerIntelligence::track(EventName, props, entidade)
        └── TrackingPolicy::allowsAnalytics()?  não → nada
        └── captura sessão, usuário, instante e event_uuid no despacho
        └── TrackCustomerEventJob  (fila customer-intelligence, 3 tentativas)
              └── record(): PropertySanitizer → ci_events + ci_daily_metrics (mesma transação)
```

Os sete eventos (`EventName`): `produto.visualizado`, `produto.adicionado_carrinho`,
`produto.removido_carrinho`, `carrinho.checkout_iniciado`, `pedido.criado`,
`pedido.pagamento_confirmado`, `pedido.enviado`. Chamadas de rastreamento ficam em
`try/catch`: **analytics nunca derruba compra**.

### 13.3 Dados

| Tabela | Papel | Notas |
|---|---|---|
| `ci_visitors` | identidade pseudônima persistente | `visitor_uuid` único; `user_id` `nullOnDelete`, gravado uma vez |
| `ci_sessions` | janela de 30 minutos | landing só com caminho, referrer reduzido, UTMs; nome evita colidir com `sessions` |
| `ci_events` | fato comportamental **append-only** | sem `updated_at`; `occurred_at` × `created_at`; entidade real em `entity_type`/`entity_id` |
| `ci_daily_metrics` | agregado diário **permanente** | chave única com dimensões `NOT NULL DEFAULT ''` (NULL não é único no MySQL); `metric_date` sempre `Y-m-d` |
| `ci_audit_logs` | trilha administrativa append-only | sem metadata livre |

- UUID ordenado público + `id` bigint interno (alvo das FKs).
- Métricas: `eventos` (total e por tipo), `sessoes`, `visitantes` (primeira sessão
  do dia), `conversoes` (= `pedido.criado`, `MetricName::conversionEvent()`).
- Incremento atômico no banco (`metric_value = metric_value + ?`), insert com
  fallback para incremento em colisão.
- **Idempotência**: `event_uuid` nasce no despacho; retentativa colide na chave
  única e devolve o existente. Só essa colisão é tratada como retentativa.
- `customer-intelligence:rebuild-daily-metrics` nunca vai além do evento bruto
  mais antigo disponível — reconstruir período expurgado zeraria a série.
- Painel: camada `Queries/` fora dos componentes; contagem de consultas constante
  com o volume; nenhum cache.

### 13.4 Consentimento (GOV-01)

| Estado | Coleta | Cookies de analytics | Banner |
|---|---|---|---|
| `unknown` | não | não emitidos | aparece |
| `accepted` | sim | emitidos | não |
| `rejected` | não | expirados | não |

- **Opt-in.** Três estados num enum, não booleano.
- Decisão **em um lugar só**: `TrackingPolicy`, consultado apenas por
  `TrackVisitorSession` e `track()`. Os sete pontos de negócio **não conhecem
  consentimento** (há teste). `record()` não consulta, porque roda no worker.
- `fel_privacy_consent`, cookie essencial de 12 meses; valor inválido degrada para
  `unknown`.
- Eventos transacionais seguem a mesma regra: os fatos de negócio vivem em
  `orders`; `ci_events` não alimenta funcionalidade operacional.
- Recusar para a coleta e expira os cookies, **sem apagar histórico**; aceitar de
  novo gera identidade nova.
- `CI_ENABLED=false` desliga tudo, independentemente da escolha.

### 13.5 Auditoria administrativa (GOV-01)

- `ci_audit_logs`: `user_id`, `action` (`AuditAction`), `resource_type`,
  `resource_id`, `created_at`. **Sem IP, user-agent, UUIDs, payload ou metadata
  livre.**
- Gravada no `mount()` dos componentes (uma linha por abertura) e depois da
  execução dos comandos; síncrona; **nunca passa por `track()`** e **não consulta
  consentimento**.
- Permissão própria `customer_intelligence.auditoria`, só administrador.
- Retenção de 730 dias com expurgo próprio, sem acoplamento ao de eventos; o
  expurgo não se audita.

### 13.6 Retenção

| Dado | Prazo |
|---|---|
| `ci_events` | 180 dias (`CI_RETENTION_DAYS`), por `occurred_at`, expurgo 03:20 |
| `ci_audit_logs` | 730 dias (`CI_AUDIT_RETENTION_DAYS`), expurgo 03:40 |
| `ci_daily_metrics` | permanente |
| `ci_sessions`, `ci_visitors` | sem expurgo automático — decisão de produto |

### 13.7 Nomes legados preservados

Cookies `jmf_ci_visitor_id` (2 anos) e `jmf_ci_session_id` (30 min, rolante),
views em `resources/views/plugins/jmf-ci/` e componentes `x-jmf-ci-*`. O prefixo é
histórico; renomear cookie zeraria a identidade dos visitantes conhecidos.

### 13.8 Limitação conhecida — GOV-02

A política é avaliada **na requisição em que o evento nasce**.
`pedido.pagamento_confirmado` nasce no webhook (nunca coletado) e `pedido.enviado`
na sessão do lojista. Resolver exige persistir a decisão no `Visitor` — fase
própria, com decisão de produto.

---

## 14. Catalog Intelligence

> **Assistência inteligente ao cadastro e à classificação do catálogo.** Módulo
> `app/CatalogIntelligence`. **Não é o Customer Intelligence**: não coleta
> comportamento, não usa `ci_*` e não depende de consentimento.

### 14.1 Princípio

A IA externa é ferramenta da arquitetura, não a arquitetura. O patrimônio é o
conhecimento acumulado e validado pela Feira.

```text
cadastro → contexto do item → base de conhecimento → itens semelhantes
        → conhecimento suficiente?  sim → sugestão interna
                                    não → provider externo (opcional)
        → revisão humana → aceitar / editar / rejeitar → salvar → feedback
```

**Regras invioláveis:**

1. **Não inventa fatos objetivos** — material, medidas, origem, certificações,
   propriedades terapêuticas só com informação dada ou conhecimento curado. Na
   dúvida, omite e pede ao lojista.
2. **Aprovação humana é obrigatória** — gerar nunca é salvar.
3. **Falha da inteligência não bloqueia cadastro.**
4. **Dado de lojista é dado, nunca instrução.**
5. **Fontes não são equivalentes** — curado > aprovado em cadastro > derivado >
   saída de IA.

### 14.2 Base de conhecimento (CAT-03)

| Tabela | Papel | Unicidade |
|---|---|---|
| `catalog_knowledge_entries` | conceito | `(type, normalized_name)` |
| `catalog_knowledge_terms` | sinônimo, termo comercial, alias, keyword | `(knowledge_entry_id, normalized_term)` |
| `catalog_knowledge_relations` | relação dirigida entre conceitos | `(from, to, relation_type)` |
| `catalog_product_knowledge` | ponte item ↔ conceito, com `source` | `(product_id, knowledge_entry_id)` |

- **`products` não recebe nenhuma coluna de inteligência** (há teste).
- Enums: `KnowledgeEntryType`, `KnowledgeSource`, `KnowledgeStatus`,
  `KnowledgeTermType`, `KnowledgeRelationType`.
- `KnowledgeNormalizer` é a **única** chave de deduplicação: minúsculas, sem
  acento, pontuação normalizada. O acento vive em `name`.
- Governança: origem assinada por pessoa (`human_curated`, `seed`) nasce
  `approved`; o resto nasce `draft`; status nunca sobe sozinho; origem de menor
  confiança não sobrescreve a de maior. Ordem ordinal em
  `KnowledgeSource::trustLevel()`: `human_curated > seed > approved_listing >
  derived > external_ai`. `confidence` fica **nula**.
- Escrita só por `CreateOrUpdateKnowledge`, `AttachKnowledgeTerm`,
  `RelateKnowledge`. Unicidade garantida pelo banco.
- `CatalogKnowledgeSeeder`: 28 conceitos lidos dos 75 itens reais; idempotente.

### 14.3 Motor de similaridade (CAT-04)

| Peça | Papel |
|---|---|
| `ProductTextNormalizer` | junta os campos textuais e delega ao `KnowledgeNormalizer` |
| `MatchProductKnowledge` | item → conceitos aprovados; **não grava**; ≤ 3 consultas |
| `AssociateProductKnowledge` | única porta do pivot; só evidência direta; grava `Derived`; nunca sobrescreve humano |
| `FindSimilarProducts` | item → itens; ≤ 3 consultas; **só oferece itens com oferta vigente** (D-CAT-05B-2) |
| `SimilarityScorer` | fonte única dos pesos: nome 10 · termo 8 · relação 3 · conceito humano-humano 6 · com lado automático 4 · mesma categoria 2 |
| `catalog-intelligence:associate-products` | backfill com `--dry-run`, `--product=`, `--chunk=` |

Decisões estruturais: casamento **por frase inteira** cercada de espaços, nunca por
token; expansão por relação em **um salto**; **candidato ≠ associação**; score não
persistido, serve para **ordenar**, não é porcentagem; nada de FULLTEXT; alcance
global entre lojas, lendo só campos públicos. A origem da consulta **não** é
filtrada por vigência — item sem vendedor continua enxergando o catálogo
(D-CAT-21).

### 14.4 Assistente de conteúdo (CAT-05)

```text
ListingContext::paraItemNovo() | ::deProduct()
  → MatchProductKnowledge          (captura de falha → sugestão vazia)
  → comConhecimento()
  → FindSimilarProducts            (só item salvo; falha → sem semelhantes)
  → comSemelhantes()
  → GenerateListingSuggestion::compor()
  → ListingSuggestion
```

- **Sugere e nunca aplica** (D-CAT-05B-1): não chama `SaveProductWithOffer`, não
  aciona `ProductPolicy`, não associa conhecimento. Aplicar é CAT-09.
- **`ListingContext`** — construtor privado, campos fixos, **sem parâmetro que
  aceite `ProductOffer` ou `Expositor`**; lê só identidade de catálogo
  (D-CAT-05B-3). `knowledge` e `similarItems` entram por cópia imutável.
  `lacunas()` diz o que falta.
- **`ContextSanitizer`** — minimização na construção. O contexto é lista de
  permissão; `knownAttributes` é a exceção, protegido por **lista de proibição**
  que importa de `SaveProductWithOffer` os campos comerciais (dívida **C-1**).
  Só conceito `approved` entra. Termos úteis: nome canônico + `commercial_term` +
  `synonym`; `alias` e `keyword` fora.
- **`ListingSuggestion`** — forma congelada, sete chaves: `suggested_name`
  (sempre nulo no caminho interno), `short_description`, `description`,
  `keywords`, `missing_information`, `source` (`SuggestionSource`), `confidence`
  (nula). Campo já preenchido não recebe proposta.
- **`missing_information`** vem de `ListingGap` (enum de 5 casos), em linguagem de
  lojista; lacuna que a própria sugestão preenche não vira pedido.
- **Resiliência** (D-CAT-05F-1/2): as duas etapas do motor são capturadas em
  separado — degradação parcial —, registradas em `Log::warning` com etapa e
  classe. `QueryException` entra no log **só pelo SQLSTATE**, sem SQL nem bindings
  (§5.3 da especificação original: nenhum conteúdo sensível em log).
- **Fronteira do cadastro**: `SaveProductWithOffer` e `ProdutoController` não
  referenciam o módulo. O `ProdutoForm` passou a conhecê-lo na CAT-09, para gerar
  e aplicar a sugestão, mas `ProdutoForm::save()` não o referencia; a sugestão só
  chega à persistência pelos campos da tela (há teste).
- **Custo**: teto exato de **6 consultas** para o assistente inteiro; montar o
  contexto de um `Product` custa 1 consulta por ancestral de categoria não
  carregado (zero com `with('category.parent')`).
- **S-2**: a sugestão ecoa texto do lojista — renderizar sempre escapado, nunca
  `{!! !!}`.

### 14.5 IA externa (CAT-06)

**Ao fim da CAT-06 nenhum texto sai da aplicação.** A fase entrega contrato,
`Null`, `Fake`, limiar, redator e guard; nenhum fornecedor real, credencial ou
segredo. O domínio não conhece nome de fornecedor nem de modelo — e continua sem
conhecer depois da **CAT-10A**, que acopla o primeiro provider real fora do módulo,
desligado por padrão e, desde a **CAT-10A.1**, configurado pelo painel
([abaixo](#provider-real-cat-10a)).

Entregue (06C, 06D, 06E, 06F, 06G):

```php
interface CatalogAiProvider          // App\CatalogIntelligence\Contracts
{
    /** @throws CatalogAiProviderException */
    public function isAvailable(): bool;

    /** @throws CatalogAiProviderException */
    public function suggest(GuardedPrompt $prompt): ListingSuggestion;   // 06G; na 06D era ListingContext
}
```

| Peça | Comportamento |
|---|---|
| `NullCatalogAiProvider` | caminho padrão sem credencial e **fallback do contrato**: binding padrão na 06G (D-CAT-06G-9) e, desde a CAT-10A, o que o `CatalogAiProviderSelector` devolve sempre que o provider real não está pronto (D-CAT-10A-2); `isAvailable() === false`; `suggest()` devolve `vazia()` e **nunca lança** — operar sem IA externa é **estado normal** (D-CAT-06B-5). Não carrega política nem fallback |
| `FakeCatalogAiProvider` | determinístico; `indisponivel()`, `disponivel()`, `respondendo($dto)`, `queFalha()` e `queEsgotaOTempo()` (lançam `CatalogAiProviderException` de propósito); `chamadas()` e `promptsRecebidos()`; nunca registrado no container |
| `ProviderResponseValidator` | valida só o que o tipo não garante — texto em branco, keywords e missing_information malformadas, confiança fora de `[0,1]`, procedência incorreta; devolve lista de `ProviderResponseViolation` e nunca lança |
| `SuggestionPolicy` | lê `ListingContext::lacunas()` (não reconta); devolve `KnowledgeSufficiency`: `Sufficient` · `ExternalMayHelp` (falta **texto**) · `AwaitsMerchant` (falta **fato** — consultar seria pagar por invenção) |
| `config/catalog-intelligence.php` | só `fallback.minimum_gaps` (`CATALOG_AI_MINIMUM_GAPS`, padrão 3; padrão de segurança 5 se a chave sumir); **proibido** credencial, fornecedor ou endpoint (há teste). A configuração do provider real fica fora do módulo: na CAT-10A, em `config/services.php` (`catalog_ai`); desde a CAT-10A.1, no banco (`site_settings`), lida por `CatalogAiSettings` — em `config/services.php` restou só a trava `force_disabled` (D-CAT-10A1-1, D-CAT-10A1-3) |
| `FreeTextRedactor` (06E) | `Support/`, `redigir(string): string`, marcador único `[redigido]`. D-CAT-06B-2 sem reabertura: telefone, e-mail, CPF/CNPJ (inclusive o alfanumérico) e CEP **sempre**; medidas, preço, quantidade **nunca**; URL e `@handle` **não por padrão** — dado pessoal dentro de URL é redigido e a URL fica. Sem máscara, CPF/CNPJ só com dígito verificador válido e CEP só depois da palavra "CEP"; fixo sem máscara e fixo sem DDD **não** são reconhecidos (limitação declarada). Preserva o resto byte a byte, é idempotente, não tem dependência, não registra e nunca lança; texto ilegível **falha fechado** (sai só o marcador). Chamado na saída ligada pelo `GuardedPromptRedactor` (06G) — **fecha C-2** |
| `PromptGuard` (06F) | `Support/`, `__invoke(ListingContext): GuardedPrompt`. Três canais em `DTOs/GuardedPrompt` (`final`, `readonly`, nenhum método além do construtor): `instruction` — `Enums/ProviderInstruction`, enum **puro** fixado pelo guard, que nenhum texto escolhe; `context` — `knowledge` e `similar_items`, por lista de permissão; `data` — todo o resto do `ListingContext`, e toda chave nova. Classifica por origem e **não lê conteúdo**: não redige, não escapa, não altera, não lança, não reconhece frase; payload hostil e conteúdo legítimo parecido com instrução atravessam intactos no canal de dado. Não escreve texto de instrução nem formato de fornecedor. Chamado por `GenerateListingSuggestion` na saída ligada (06G) — **fecha S-1** |
| `GuardedPromptRedactor` (06G) | `Support/`, `__invoke(GuardedPrompt): GuardedPrompt`, só depende do `FreeTextRedactor`. Redige toda string de `context` e `data` em qualquer profundidade, e número cuja forma escrita é dado pessoal; `instruction` nunca chega ao redator; chaves estruturais intactas; chaves de `known_attributes` — as únicas vindas de fora (C-1) — redigidas, e na colisão fica a primeira. Não lança, não registra (D-CAT-06G-2) |
| `CatalogAiProviderException` (06G) | `Exceptions/`, `final`, `extends RuntimeException`, sem hierarquia; `tempoEsgotado()`. A única falha que o assistente trata como falha do provider (D-CAT-06G-7) |
| `ListingOutcome` · `ListingOutcomeState` (06G) | DTO do desfecho (`state`, `violations`) e enum exaustivo de 8 estados, com `ehFalha()` e `convidaARepetir()`; `violations` só existe na resposta inválida — **fecha F-1** (D-CAT-06G-3) |

O único binding do `CatalogIntelligenceServiceProvider` é o de `CatalogAiProvider`. Na
06G ele apontava direto para o `NullCatalogAiProvider`; desde a CAT-10A resolve pelo
`CatalogAiProviderSelector`, que devolve o `Null` ou o adaptador real (D-CAT-10A-2).
`EmbeddingProvider` **não existe** (B-3 em aberto, trava de teste guarda só ele); a B-3
não restringe o provider de sugestão.

**Fluxo ligado (06G)** — fecha **F-1** e decide **B-5**. O orquestrador é
`GenerateListingSuggestion`; nenhum service novo.

```text
comContexto(ListingContext, ?Product)
  → completar()                   motor da CAT-04; falha na etapa de conhecimento → InternalIntelligenceFailed, sem provider
  → compor()                      sugestão interna, sempre
  → SuggestionPolicy              Sufficient → InternalKnowledgeSufficient
                                  AwaitsMerchant → InternalKnowledgeInsufficient
                                  ExternalMayHelp ↓
  → provider->isAvailable()       false → ProviderUnavailable
  → PromptGuard
  → GuardedPromptRedactor         GuardedPrompt seguro (S-1 + C-2)
  → provider->suggest(prompt)     CatalogAiProviderException → ProviderFailed
  → ProviderResponseValidator     violações → ProviderResponseInvalid
  → complementar()                algo entrou → ExternalSuggestionUsed · nada entrou → ExternalSuggestionNotUsed
  → [ListingSuggestion, ListingContext (original), ListingOutcome]
```

| `ListingOutcomeState` | Falha? | Convida a repetir? | Quando |
|---|---|---|---|
| `InternalKnowledgeSufficient` | não | não | política `Sufficient` |
| `InternalKnowledgeInsufficient` | não | não | política `AwaitsMerchant` |
| `InternalIntelligenceFailed` | sim | sim | a etapa de conhecimento lançou; provider não consultado |
| `ProviderUnavailable` | não | não | consulta justificada e `isAvailable() === false` |
| `ProviderFailed` | sim | sim | `CatalogAiProviderException` em `isAvailable()` ou `suggest()`, prazo esgotado incluído |
| `ProviderResponseInvalid` | sim | não | o validador devolveu violações; elas vão em `violations` |
| `ExternalSuggestionUsed` | não | não | algum campo da resposta entrou na sugestão |
| `ExternalSuggestionNotUsed` | não | não | resposta válida, e nada dela entrou — nem nome equivalente ao atual |

Os quatro estados da D-CAT-06B-1 continuam com o mesmo significado: provider
ausente → `ProviderUnavailable`; base não conhece → `InternalKnowledgeInsufficient`;
provider falhou → `ProviderFailed`; resposta inválida → `ProviderResponseInvalid`.

- **Fronteira** (D-CAT-06G-1, D-CAT-06G-2): o provider recebe `GuardedPrompt`, e o
  tipo impede enviar `ListingContext`. A redação é composta **depois** do guard; o
  contexto devolvido ao chamador segue original.
- **Exceções** (D-CAT-06G-7): só `CatalogAiProviderException` vira `ProviderFailed`,
  capturada só em volta das duas chamadas ao provider. `RuntimeException` genérica,
  `TypeError` e defeito de guard, redator, validador ou desfecho **sobem**. O log
  da falha tem etapa, classe do provider e classe da exceção — nunca a mensagem.
  O motor interno mantém a captura da CAT-05F (D-CAT-05F-1), que é ampla: qualquer
  `Throwable` do casamento vira `InternalIntelligenceFailed`, e o da similaridade,
  degradação acessória. A assimetria com a fronteira do provider é dívida registrada
  na 06H — **H-12 (CAT-06H)**, §24 —, e não arquitetura reafirmada.
- **B-5** (D-CAT-06G-5, D-CAT-06G-6): prazo total de **8 s** para a tentativa
  externa, aplicado pelo adaptador no transporte; esgotado →
  `CatalogAiProviderException::tempoEsgotado()` → `ProviderFailed`, sugestão
  interna preservada. **0 novas tentativas.** A chave de config nasceu com o
  adaptador que a lê, na CAT-10A: `CATALOG_AI_TIMEOUT`, limitada a 8 pelo
  `CatalogAiProviderSelector` (D-CAT-10A-2). Desde a CAT-10A.1, o prazo é
  `catalog_ai_timeout`, gravado pelo painel — inteiro de 1 a 8, ou em branco para 8 s —, a
  variável foi removida, e o seletor continua limitando a 8 (D-CAT-10A1-1).
- **Resposta válida complementa, não substitui** (D-CAT-06G-8): texto externo só
  onde o lojista não escreveu e a base não compôs; nome por `nomeSugerido()`, e só
  se diferente do atual pela chave do `KnowledgeNormalizer` (D-CAT-06G-12);
  palavras-chave internas primeiro e externas sem duplicata pela mesma chave;
  `missing_information` recalculado por `oQueFalta()`; `source = External` e
  `confidence` da resposta só com contribuição. Proposta para campo preenchido é
  descartada e **não** é violação. Sem contribuição, o desfecho é
  `ExternalSuggestionNotUsed` (D-CAT-06G-11).
- `__invoke()` continua devolvendo só `ListingSuggestion`.

A 06H validou e encerrou a fase sem alterar contrato: reconciliou a nomenclatura,
varreu as travas de asserção e registrou H-11 e H-12 (§24). A H-11 foi resolvida na
CAT-10A (D-CAT-10A-3).

Ordem **06E/06F antes de 06G** (D-CAT-06B-6): resiliência antes do acoplamento.

#### Provider real (CAT-10A)

O primeiro provider real entra **fora** do módulo — na CAT-10A, por configuração de
ambiente; desde a CAT-10A.1, pela configuração que o painel grava no banco
([abaixo](#configuração-pelo-painel-cat-10a1)) —, e o contrato não muda: `GenerateListingSuggestion` continua conhecendo só
`CatalogAiProvider`, e o fluxo ligado acima vale como está.

| Peça | Comportamento |
|---|---|
| `CatalogAiProviderSelector` | `app/Services/CatalogAi`. Resolve a cada pedido — o binding do módulo o chama, sem `singleton`. Na CAT-10A lia `config('services.catalog_ai')`; **desde a CAT-10A.1** verifica primeiro a trava `CATALOG_AI_FORCE_DISABLED` e lê a configuração gravada no banco por `CatalogAiSettings` (D-CAT-10A1-3, D-CAT-10A1-4). Devolve `OpenAiCatalogAiProvider` só com `enabled` verdadeiro, provider `openai`, chave e modelo não vazios e prazo válido; em qualquer outro caso, `NullCatalogAiProvider`. Prazo ausente → 8 s; acima de 8 → 8; ≤ 0 ou não numérico → `Null`. Verifica condições: não tem `try`, não registra log, não conhece o `Fake` (D-CAT-10A-2) |
| `OpenAiCatalogAiProvider` | `app/Services/CatalogAi`, `final`. Responses API: `instruction` em `instructions`, `context` e `data` em dois itens distintos de `input`, Structured Outputs com JSON Schema estrito, `store: false`, sem tools nem conversa; uma chamada, prazo total e de conexão iguais, 0 retry (D-CAT-10A-6). Texto da instrução por `match` exaustivo sobre `ProviderInstruction` (D-CAT-10A-3), com as regras de D-CAT-10A-4 e D-CAT-10A-5. `confidence` nula; `missingInformation` vazia, porque o assistente a recalcula. Não grava nem registra nada |
| `config/services.php` · `catalog_ai` | **Desde a CAT-10A.1, só `force_disabled`** (`CATALOG_AI_FORCE_DISABLED`, padrão `false`) — a trava de D-CAT-10A1-3. Na CAT-10A trazia `CATALOG_AI_ENABLED` (padrão `false`), `CATALOG_AI_PROVIDER`, `CATALOG_AI_MODEL`, `CATALOG_AI_API_KEY` e `CATALOG_AI_TIMEOUT` (padrão 8) (D-CAT-10A-1) — **removidas** na CAT-10A.1, que levou a configuração ao banco (D-CAT-10A1-1). Nenhum modelo fixado no código; chave nunca versionada |

Da resposta do fornecedor ao desfecho:

| Resposta | Resultado |
|---|---|
| Prazo esgotado, conexão, HTTP fora de 2xx, corpo ilegível, `status` não concluído, recusa, sem texto estruturado, JSON que não cabe em `ListingSuggestion` | `CatalogAiProviderException` com mensagem fixa (no máximo o status HTTP) e sem `previous` → `ProviderFailed` |
| Cabe no DTO, mas com conteúdo inválido (texto em branco, palavra vazia) | `ProviderResponseValidator` → `ProviderResponseInvalid` |
| `RuntimeException` genérica, `TypeError`, `Error` | sobe (D-CAT-06G-7) |

Operar sem o provider continua sendo o estado normal (D-CAT-06B-5): desligado ou mal
configurado, o contrato é o `Null`, e a consulta que a `SuggestionPolicy` justificaria
termina em `ProviderUnavailable`.

#### Configuração pelo painel (CAT-10A.1)

Desde a CAT-10A.1 o **banco é a única autoridade operacional** do provider externo
(D-CAT-10A1-1). O `.env` não fornece provider ativo, provider, modelo, chave nem timeout;
dele restou só a trava técnica, que só desliga (D-CAT-10A1-3).

```text
GenerateListingSuggestion
  → CatalogAiProvider                     contrato, app/CatalogIntelligence
  → binding do módulo                     CatalogIntelligenceServiceProvider: um bind, sem singleton — inalterado
  → CatalogAiProviderSelector::resolve()  app/Services/CatalogAi
       CATALOG_AI_FORCE_DISABLED ligada → NullCatalogAiProvider, sem ler o banco
  → CatalogAiSettings::atual()            app/Services/CatalogAi
  → SiteSetting::query()->find(1)         site_settings; sem linha, sem configuração — nada é criado
       ausente, desligada, incompleta ou inválida → NullCatalogAiProvider
       válida → OpenAiCatalogAiProvider(chave, modelo, prazo ≤ 8 s)
```

| Peça | Comportamento |
|---|---|
| `CatalogAiSettings` | `app/Services/CatalogAi`, fora do módulo. `atual()` entrega ao seletor o que está gravado, lido a cada resolução, sem cache e sem `config()`; a chave só é decriptada com o provider ligado. `paraOPainel()` entrega à tela tudo menos a chave — só se ela existe. Lê com `find(1)`, e não com `SiteSetting::instance()`, que cria a linha: ler nunca escreve (D-CAT-10A1-8). Sem `try` e sem log: `DecryptException` da chave com o provider ligado sobe como defeito de infraestrutura (D-CAT-10A1-4). Grava pela `SiteSettingService`; remover a chave também desliga o provider (D-CAT-10A1-7). Quem valida é o seletor |
| `CatalogAiSettingsForm` | **Admin → Configurações → Inteligência Artificial** (`/admin/settings/inteligencia-artificial`). `configuracoes.editar` para abrir a tela e em cada ação; item de menu só para quem edita. Provider só `openai`; modelo obrigatório para ativar, sem espaço, até 100 caracteres; timeout inteiro de 1 a 8, ou em branco (8 s); ativar exige chave gravada ou digitada. A chave gravada nunca é carregada, e a digitada é esvaziada antes de autorizar, validar ou gravar (D-CAT-10A1-2, D-CAT-10A1-6) |
| `CATALOG_AI_FORCE_DISABLED` · `services.catalog_ai.force_disabled` | Trava técnica, padrão `false`. Verdadeira — ou qualquer valor que não seja claramente falso —, o seletor devolve o `Null` sem ler o banco. Nunca liga o provider e nunca fornece provider, modelo, chave ou timeout. Não é configuração do administrador. O `phpunit.xml` a força (D-CAT-10A1-3, D-CAT-10A1-9) |

- A mudança feita no painel vale na **resolução seguinte** do provider: sem SSH, sem
  editar `.env`, sem `config:clear` ou `config:cache` e sem reiniciar a aplicação.
- O que a CAT-10A decidiu continua valendo: contrato inalterado, adaptador e seletor fora
  do módulo, `Null` para configuração ausente, desligada, incompleta ou inválida, e defeito
  que sobe em vez de virar "sem provider" — o seletor, a leitura da configuração e o
  ServiceProvider do módulo não têm `try` nem `catch`, e um teste trava isso.
- **Homologação real pendente:** nenhuma chamada real à OpenAI foi feita, e a configuração
  pelo painel não muda a B-6 (D-CAT-10A1-10).

### 14.6 Segurança e privacidade do módulo

- **O que pode atravessar a fronteira** (06G): só o que o `GuardedPrompt` carrega,
  depois do `PromptGuard` e do `GuardedPromptRedactor` — o **conteúdo de catálogo**
  necessário à sugestão. No canal `data`: tipo do item, **nome do item**, caminho
  de categoria, resumo e descrição existentes e `known_attributes`. No canal
  `context`: conceitos aprovados (nome, tipo, descrição curada, termos) e itens
  semelhantes (nome, conceitos compartilhados, razões). Esse conteúdo **sai** para o
  provider, redigido. Desde a CAT-10A, com o provider real ligado — por ambiente na
  CAT-10A, pelo painel desde a CAT-10A.1 —, ele sai para a OpenAI com `store: false` — inclusive nomes de itens semelhantes de outros
  lojistas; desligado, nada sai. A revisão ampliada de privacidade e governança é da
  CAT-10B.
- **O que o `FreeTextRedactor` redige nesse conteúdo** (D-CAT-06B-2, CAT-06E, sem
  ampliação): telefone, e-mail, CPF/CNPJ e CEP, nas formas que ele reconhece — sem
  máscara, CPF/CNPJ só com dígito verificador válido e CEP só depois da palavra
  "CEP"; fixo sem máscara e fixo sem DDD **não** são reconhecidos. Medida, preço e
  quantidade nunca são redigidos; URL e `@handle`, não por padrão. A redação vale
  para os valores de `context` e `data` e para as chaves de `known_attributes`
  (D-CAT-06G-2); o caminho interno recebe o texto como o lojista o escreveu.
- **O que nunca pertence ao contexto**, e por isso não chega ao provider: dados de
  pedido, cookies, `visitor_uuid`, `session_uuid`, IP e qualquer campo de
  `ProductOffer` ou de `Expositor`. O `ListingContext` tem campos fixos e nenhum
  parâmetro que aceite oferta ou expositor (D-CAT-05B-3). Em `known_attributes`,
  único ponto por onde entra dado de fora, essas categorias caem pela lista de
  proibição de **chaves** do `ContextSanitizer`, que não protege o valor posto sob
  uma chave com nome não previsto (dívida C-1).
- **Limitações conhecidas — não são reconhecidos automaticamente**: nome de pessoa,
  endereço sem CEP, credencial e token escritos em texto livre, e dado pessoal
  escrito por extenso ou disfarçado. Se estiverem no nome, na descrição, num
  atributo ou no nome de um item semelhante, **atravessam** a fronteira.
- Nenhuma classe do módulo importa cliente HTTP, usa formato de mensagem de
  fornecedor, contém marca de credencial ou nomeia fornecedor — varreduras em
  `FronteiraDePromptTest` e `ContratosDeProviderTest`, com duas camadas
  independentes. O provider real da CAT-10A cumpre isso por estar **fora** do módulo
  (D-CAT-10A-1), e não por contornar a varredura.
- A falha do adaptador real tem mensagem fixa e não encadeia a exceção de transporte:
  o erro do fornecedor pode ecoar parte da chave, e o de transporte, parte do prompt
  (D-CAT-10A-6). O adaptador não grava nem registra prompt, resposta ou chave.
- A chave do provider fica em `site_settings`, criptografada pelo cast `encrypted`; nunca é
  reexibida, nunca é hidratada em propriedade pública do Livewire e sai da serialização do
  model por `$hidden` (D-CAT-10A1-5, D-CAT-10A1-6). Não há cofre externo, KMS, rotação
  automática nem auditoria de alteração da chave.
- Texto do lojista e contexto recuperado nunca viram instrução: o `PromptGuard`
  os entrega em canais próprios do `GuardedPrompt`, e a instrução é um enum que
  texto nenhum escolhe. A garantia é de estrutura, não de reconhecer frase. Desde a
  06G o provider só recebe `GuardedPrompt`.
- Falha do provider é registrada com etapa, classe do provider e classe da
  exceção, **sem a mensagem**, que é do adaptador e pode carregar prompt ou
  resposta (D-CAT-06G-7).
- Falha do motor interno é registrada com etapa e classe da exceção, e a mensagem
  passa por `mensagemSegura()`: de `QueryException` fica só o SQLSTATE, sem SQL nem
  bindings; das demais exceções, o `getMessage()`. A stack não é registrada. Nenhum
  ponto de lançamento alcançado hoje pelo assistente põe texto do lojista na
  mensagem, mas uma exceção futura que o fizesse o levaria ao log — risco latente de
  observabilidade/privacidade, não vulnerabilidade comprovada (**H-12 (CAT-06H)**,
  §24).
- Cachear conhecimento e contexto é legítimo; copiar atributo objetivo de um item
  para outro por efeito de cache, não.
- Futuro (**CAT-10B**): métricas por chamada externa — provider, modelo, tokens, custo,
  duração, sucesso/falha/fallback — sem conteúdo sensível em log, junto de custo e rate
  limit (B-6). A CAT-10 — hoje dividida em 10A e 10B — **herda a verificação** da regra
  3 com provider acoplado (D-CAT-05F-7); a autoria do teste é da CAT-05F.

### 14.7 Filas do módulo

Sugestão que o lojista espera na tela é síncrona. Embeddings, reindexação e
agregação de feedback, se vierem, vão para fila própria, **depois** de
`customer-intelligence` na prioridade do worker, e o `compose.yaml` precisa
listá-la.

---

## 15. API mobile

Contrato completo: [`docs/API.md`](API.md).

- **Sanctum em modo token pessoal (Bearer)**, não SPA/cookie: o app Flutter é
  cliente separado. Um token por dispositivo, revogável no logout.
- Login valida credenciais manualmente — `Auth::attempt` depende da sessão do
  guard `web`, indisponível em rota stateless.
- **Reaproveita os mesmos Services e Actions do site.** Nenhuma regra de negócio
  existe só na API.
- Envelope: recurso único em `data`; listagem paginada no formato padrão do
  Laravel; respostas compostas com chaves próprias. Erros no padrão Laravel
  (401, 403, 404, 422).
- Uploads em `PUT` usam `POST` com `_method=PUT` e `multipart/form-data`.
- **Carrinho exige login** no app — o `CartService` resolve o carrinho pelo
  usuário autenticado sem alteração.
- Autorização replica as regras do site: chat por split (cliente dono ou lojista
  dono), feed pelas regras de `FeedPostPolicy`/`FeedCommentPolicy`, lojista pelo
  `LojistaMiddleware` e pelos predicados de ownership.
- Evolução de contrato é **aditiva e compatível**: `product_offer_id` opcional
  (perguntas, carrinho), `shipping_options` com `shipping_total` depreciado.
  `OrderSplitResource` mantém a chave `expositor` mesmo com vendedor excluído.
- Fora da v1: construtor de curso, publicar no feed, email marketing, painel
  administrativo, recuperação de senha.

---

## 16. Filas

Conexão `database` (Redis disponível e não usado). Um worker, com prioridade pela
ordem:

```bash
php artisan queue:work --queue=default,email-marketing,customer-intelligence \
  --tries=3 --sleep=3 --timeout=120 --max-time=3600
```

| Fila | Conteúdo |
|---|---|
| `default` | e-mails transacionais, impressões de expositor (`ExpositorImpressionJob`), jobs gerais |
| `email-marketing` | `SendEmailCampaignJob` |
| `customer-intelligence` | `TrackCustomerEventJob` |

- **A ordem é prioridade**: o worker só olha a fila seguinte quando a anterior está
  vazia — pico de navegação nunca atrasa e-mail de pedido.
- Um teste lê o `compose.yaml` e falha se `customer-intelligence` sair do
  `--queue`.
- **A fila não é autoridade financeira** (D-FIN-41): nenhum listener financeiro é
  `ShouldQueue`; `OrderSplitConfirmed` e `OrderSplitReverted` rodam síncronos após
  o commit. Com o worker parado o dinheiro continua correto; atrasam efeitos
  secundários.
- O worker mantém código em memória: **reiniciar ao alterar um Job**.

---

## 17. Scheduler

`routes/console.php` — **cinco tarefas**:

| Quando | Tarefa | Por quê |
|---|---|---|
| 08h, 14h, 20h | `TrackShipmentsJob` | atualiza envios em trânsito e conclui pedidos entregues |
| a cada 5 min | despacho de campanhas agendadas | email marketing |
| a cada 5 min, `withoutOverlapping` | `orders:expire-payments` | libera estoque de intenções de pagamento e de checkouts vencidos |
| 03:20, `withoutOverlapping` | `customer-intelligence:prune-events` | retenção de 180 dias |
| 03:40, `withoutOverlapping` | `customer-intelligence:prune-audit-logs` | retenção de 730 dias |

**O scheduler é requisito operacional de produção, não conveniência** (D-FIN-42):
sem ele, reservas de Pix vencido e de checkout abandonado não são liberadas. Não há
serviço de scheduler no `compose.yaml`; em desenvolvimento roda sob demanda.

Comparações de prazo usam `now()` do PHP ligado como parâmetro — nunca `NOW()` do
SQL —, então o fuso do servidor MySQL (UTC) é irrelevante frente ao da aplicação
(`America/Sao_Paulo`).

---

## 18. Docker e infraestrutura

**Docker é exclusivamente o ambiente de desenvolvimento.** Produção não usa Docker.

```text
Docker Compose (projeto feira-esquerda-livre, rede "fel")
├── app          PHP 8.3 FPM + Composer
├── nginx        Nginx 1.27-alpine          :80
├── mysql        MySQL 8.4 (volume mysql-data, healthcheck)   :3306
├── phpmyadmin   phpMyAdmin 5.2              :8081
├── redis        Redis 7.4 (AOF, não usado)  :6380 → 6379
├── node         Node 22 + Vite 7 (HMR, polling)  :5173
├── queue        worker, mesma imagem do app
└── mailpit      SMTP :1025 · web :8025
```

Decisões:

- **`vendor/` e `node_modules/` em volume nomeado**, não em bind mount. Pelo
  virtiofs do WSL2 a home levava ~25s por requisição com `vendor/` no bind mount, e
  ~1s no volume; `node_modules/` do Windows contém binários win32 que não rodam em
  Linux. Consequência: `composer install` e `npm install` **dentro** dos
  containers.
- **`app` e `queue` não têm `env_file`.** Injetar o `.env` como ambiente real
  sobrepõe o `<env>` do `phpunit.xml`, e `php artisan test` rodaria
  `RefreshDatabase` **contra o MySQL de desenvolvimento, apagando-o** — aconteceu em
  agosto de 2026. O Laravel lê o `.env` do bind mount sozinho.
- `tmpfs` sobre `feira_esquerda_livre_app` no serviço `node`, para o Tailwind não
  varrer o projeto Flutter.
- `queue` espera `vendor/autoload.php` antes de subir, sem crash-loop na primeira
  instalação.
- Portas do host configuráveis por `DOCKER_*_PORT`; Redis publicado em 6380 porque a
  6379 do Windows costuma estar ocupada.
- Comunicação entre containers pelo **nome do serviço**, nunca `127.0.0.1`.
- Nginx resolve `app:9000` na subida: recriar só o `app` exige reiniciar o `nginx`.
- `docker compose down` preserva dados; `down -v` apaga o banco local.

---

## 19. Segurança

### 19.1 Decisões e correções vigentes

| Tema | Regra |
|---|---|
| IDOR no catálogo (SEC-02) | Guard escopado por expositor em cada método público; `expositor_id` imutável por construção; mesma invariante e 403 em web e API |
| Identidade nullable (SEC-03) | Identidade válida antes de comparar propriedade; histórico preservado não é permissão preservada |
| Autoridade × ownership (CAT-DOM-02) | Dois eixos independentes, nos dois sentidos ([§5.5](#55-proveniência-delegação-e-autoridade-canônica), [§5.6](#56-ownership-comercial)) |
| Valor econômico vindo do cliente (FIN-SEC-01C/C.1) | Nenhum valor de frete ou preço enviado pelo cliente é autoridade; o servidor recota |
| Webhook de pagamento | Corpo não é fonte de verdade; consulta autenticada ao gateway |
| Credenciais de integração | `SiteSetting` com cast `encrypted` (Mercado Pago, Melhor Envio, Frenet, SMTP e, desde a CAT-10A.1, a chave do provider da Catalog Intelligence), listadas em `SiteSetting::SEGREDOS` e tiradas da serialização do model por `$hidden`. **Credencial gravada nunca volta ao navegador** (CAT-10A.1): a tela recebe só se ela está configurada, sem hidratá-la em propriedade pública do Livewire; em branco, a gravada é mantida; remover é ação explícita e desativa a integração dependente; a mensagem bruta de exceção SMTP não chega à tela (D-CAT-10A1-6, D-CAT-10A1-7). Não há cofre externo, KMS nem rotação automática |
| Segredos | Nunca versionados; `.env.example` só com chaves vazias ou exemplos; `.gitignore` cobre `.env*`, `*.backup`, `*.bak` |
| Credencial legada (SEC-01) | Revogada na origem; histórico do Git não reescrito (string inerte) — reescrever quebraria clones e não desfaria a exposição |
| PII em log (CAT-05F) | `QueryException` do módulo de IA registrada só pelo SQLSTATE |
| IA externa (CAT-06 · CAT-10A) | Nenhum provider opera antes de C-2 (redação) e S-1 (PromptGuard) fecharem. C-2 fechado na 06E (`FreeTextRedactor`); S-1 fechado na 06F (`PromptGuard`); F-1 fechado na 06G: o provider só recebe `GuardedPrompt` redigido, e só a falha tipada vira fallback. Desde a CAT-10A, um provider real (OpenAI) acoplado fora do módulo e **desligado por padrão**; desde a CAT-10A.1, configurado pelo painel e gravado no banco, com a chave criptografada, nunca reexibida nem versionada, e `CATALOG_AI_FORCE_DISABLED` só para desligar (D-CAT-10A1-1, D-CAT-10A1-3, D-CAT-10A1-5); `store: false`; falha com mensagem fixa e sem exceção encadeada (D-CAT-10A-6). Ativação ampla em produção bloqueada pela B-6 até a CAT-10B |
| Download de material AVA | URL assinada temporária + matrícula ativa |

### 19.2 Dívidas de segurança abertas

Estado e destino em [`ROADMAP.md`](../ROADMAP.md) §17.

- **SEC-DEP-01** — `league/commonmark` 2.9.0, 4 advisories HIGH, correção em
  `>=2.10.0`. É dependência direta e é usada por `DocsShow`.
- **F-06** — webhook sem verificação de assinatura; risco residual limitado a ruído
  na fila de conflitos.
- **LGPD-01** — CPF/CNPJ de `lojista_solicitacoes` sem criptografia.
- **C-1** — Catalog Intelligence (**C-2** fechado na CAT-06E; **S-1** na CAT-06F;
  **S-2** resolvida na CAT-09).

---

## 20. LGPD

Descrição do **comportamento técnico**, não parecer jurídico.

| Área | Comportamento |
|---|---|
| Analytics | Opt-in (GOV-01). `visitor_uuid` é **dado pseudonimizado**, não anônimo. Não coleta IP, user-agent, nome, e-mail, telefone, CPF, CNPJ, endereço nem query string. `PropertySanitizer` redige chaves sensíveis recursivamente até 5 níveis |
| Direito ao esquecimento | Conta excluída → FKs `nullOnDelete` desvinculam visitantes e eventos. Sem excluir a conta → `customer-intelligence:forget-user`, que desvincula e **rotaciona o `visitor_uuid`**. Agregados não têm granularidade individual e não são apagados |
| Auditoria de acesso a dados comportamentais | `ci_audit_logs`, 730 dias, sem metadata livre |
| Email marketing | Todo envio com descadastro; `unsubscribed_at` bloqueia reenvio; campanhas promocionais exigem `marketing_opt_in`; `consent_at` em assinantes |
| Pedido | Snapshot do vendedor **mínimo** — só o nome; CNPJ, endereço e dados bancários não são copiados para tabelas históricas |
| Impressões de expositor | `session_hash` SHA-256 da sessão; nunca IP |
| IA externa | Minimização estrutural de campos; redação de texto livre antes de sair (06E). Com o provider real ligado (CAT-10A, desligado por padrão), o conteúdo de catálogo redigido — inclusive nomes de itens semelhantes de outros lojistas — vai à OpenAI com `store: false`. Revisão ampliada de privacidade e governança na CAT-10B |

Princípios do roadmap original **não implementados** (dívidas LGPD-01 e LGPD-02):
CPF/CNPJ criptografado, retenção de 90 dias de logs de acesso ao painel e expiração
de 7 dias de carrinho anônimo.

---

## 21. Testes e validação

- **A suíte roda em SQLite em memória** (`phpunit.xml`); o MySQL de
  desenvolvimento não é tocado. Por isso a regra de §18 sobre `env_file`.
- **Critério de aceite de toda fase:** suíte completa verde sobre o código final.
  Mudança posterior, inclusive de comentário, exige nova execução. **Exceção
  deliberada, registrada:** na CAT-10A, a suíte completa (1411 · 8617 · 0) rodou sobre
  a implementação técnica antes do ajuste textual final da regra 1 da instrução do
  adaptador; depois dele passaram só os testes dirigidos (85 · 2579) e o Catalog
  Intelligence (442 · 5581), e a repetição da suíte completa foi dispensada
  explicitamente na revisão da fase. Os 1411 não valem como execução sobre o conteúdo
  final de `10fe2bf`. **Segunda exceção, na mesma forma:** na CAT-10A.1, a suíte completa
  (1492 · 9653 · 0) rodou sobre a implementação antes do hardening isolado final do
  `MailSettingsForm` (mensagem fixa no e-mail de teste); depois dele passaram só a
  segurança das configurações (30 · 288) e o Catalog Intelligence (493 · 6322), e a
  repetição da suíte completa foi dispensada na revisão. Os 1492 não valem como execução
  sobre o conteúdo final de `50498cf`. Nenhuma das duas exceções altera o critério.
- **Teste novo precisa de controle negativo**: reverter a correção e ver o teste
  falhar. Teste que passa pelo motivo errado não prova nada.
- **O que o SQLite não prova, prova-se em MySQL real.** SQLite não tem lock de
  linha nem MVCC: `lockForUpdate()` vira no-op. Dois bugs só apareceram em MySQL
  (snapshot em `REPEATABLE READ` na 01F-D, deadlock 1213 na 01G).
- **`tests/Concurrency/prove.sh`** (D-FIN-44): banco descartável
  (`fel_scratch_finsec01g`), doze disputas em processos paralelos, onze invariantes
  conferidos no fim; sai 0 ou 1. Nunca toca o banco de desenvolvimento.
- **Instalação limpa** se valida em MySQL descartável na rede do compose, com
  overrides `-e DB_*` no `docker compose exec`, trava `migrate:status` ("Migration
  table not found") antes de qualquer `migrate`, e reset só dentro do container
  descartável. Nenhum teste executa os seeders reais de ponta a ponta; o
  `DemoProductSeederTest` cobre o seeder de demonstração.
- **Nunca** `migrate:fresh`, `db:wipe` ou `ANALYZE TABLE` no banco de
  desenvolvimento para validar — `ANALYZE TABLE` faz commit implícito e anula
  `ROLLBACK`.
- Testes de fronteira travam decisões estruturais: módulo sem cliente HTTP, cadastro
  sem referência à inteligência, allowlists sem `expositor_id`, `products` sem
  colunas comerciais, cadastro produzindo uma oferta só.
- **Nenhum teste chama provider externo real** (D-CAT-10A-7, D-CAT-10A1-9): desde a
  CAT-10A.1, o `phpunit.xml` força `CATALOG_AI_FORCE_DISABLED=true` com `force="true"`,
  seja qual for a configuração gravada; os testes que precisam do adaptador desligam a
  trava só em memória, e os do adaptador, do seletor e da tela usam `Http::fake` com
  `preventStrayRequests`. Na CAT-10A, o mecanismo era forçar `CATALOG_AI_ENABLED=false` e
  `CATALOG_AI_API_KEY` vazia — variáveis removidas na CAT-10A.1.
- **Pint só nos arquivos tocados**, nunca global.

---

## 22. Invariantes arquiteturais

Regras que **não podem ser violadas** sem nova decisão explícita no Decision Log.

### Catálogo

1. `Product` é identidade canônica; `ProductOffer` é a única autoridade comercial por vendedor.
2. `products` não recebe de volta nenhum espelho comercial (`price`, `price_type`, `modality`, `duration_min`, `weight`, `height`, `width`, `length`, `has_stock`, `stock_quantity`, `is_featured`, `sort_order`).
3. `Product.is_active` é validade canônica e só a curadoria o altera; `ProductOffer.is_active` é disponibilidade comercial do dono da oferta.
4. `products.expositor_id` é proveniência e não autoriza nada.
5. Autoridade canônica é curadoria ou delegação explícita e revogável — nunca cardinalidade de ofertas.
6. Delegação canônica não concede ownership comercial, e ownership comercial não concede autoridade canônica.
7. Ownership comercial deriva só de `product_offers.expositor_id`, por `ProductOffer::pertenceAoExpositorDe()`.
8. Vigência vive só em `ProductOffer::scopeVigente()`.
9. Seleção de oferta para compra, histórico ou resposta nunca usa heurística implícita.
10. Histórico comercial nunca é reconstruído a partir da oferta vigente.
11. Imagem canônica e imagem da oferta nunca compartilham arquivo físico; fallback é leitura.
12. FAQ comercial não faz fallback nem concatenação com a canônica.
13. Pergunta é respondida só pelo dono da oferta destinatária.
14. Semelhança nunca funde identidade.
15. Multi-oferta permanece desabilitada até decisão de produto e fechamento dos gates.

### Pedidos, pagamento e estoque

16. Pedido é fato histórico; exclusão de cadastro nunca apaga fato comercial.
17. Snapshots comerciais são escritos uma vez, no servidor.
18. Nenhum valor econômico vem do cliente como autoridade.
19. Confirmação e reversão de pagamento são atômicas, idempotentes e gateway-agnostic.
20. Estado terminal não ressuscita; `cancelled` após pagamento e refund parcial viram conflito, nunca estorno.
21. Conflito financeiro é gravado fora da transação que falhou.
22. Estoque opera na oferta, sob lock em ordem determinística; reserva ativa impede exclusão e desligamento do controle.
23. Reversão financeira não repõe estoque físico.
24. Split só é confirmável sobre pedido com pagamento confirmado.
25. A fila não é autoridade financeira; o scheduler é obrigatório em produção.

### Módulos inteligentes

26. Customer Intelligence: coleta só sob consentimento aceito, decisão só no `TrackingPolicy`; analytics nunca derruba fluxo de compra; auditoria nunca passa por `track()`.
27. Catalog Intelligence: não inventa fato objetivo, nunca salva sem aprovação humana, falha não bloqueia cadastro, dado do lojista nunca é instrução.
28. Nenhum dado comercial ou pessoal entra no `ListingContext`; nenhum provider externo opera antes de redação e PromptGuard.
29. Operar sem IA externa é estado normal, não degradado.

### Operação

30. `app` e `queue` nunca recebem `env_file`.
31. Nenhuma validação destrói ou polui o banco de desenvolvimento.

### Configuração e credenciais

32. Credencial gravada nunca volta ao navegador: não é hidratada em propriedade pública do Livewire nem aparece em HTML, snapshot, serialização do model ou mensagem de exceção devolvida à tela; a tela recebe só se ela está configurada.
33. A configuração operacional do provider externo da Catalog Intelligence vem só do banco; o ambiente só pode desligá-lo.

---

## 23. Decision Log

### Como ler

- **IDs preservados** exatamente como foram cunhados nas fases. Nada renumerado,
  nada reaproveitado.
- Decisões sem ID na origem aparecem como **`(sem ID)`** — nenhum ID foi inventado
  para elas.
- Estados:

| Estado | Significa |
|---|---|
| **VIGENTE** | vale hoje |
| **SUPERADA** | deixou de valer; a linha aponta para o que vale |
| **SUBSTITUÍDA** | trocada por decisão de mesmo tema com ID novo |
| **HISTÓRICA** | cumprida ou descreve um estado de fase; mantida como registro |

- Decisão nova: ID novo no prefixo da fase, linha nova na tabela da trilha, estado
  **VIGENTE**; se ela superar outra, atualizar o estado da antiga.

### 23.1 Plataforma e produto

| ID | Decisão | Motivo | Estado |
|---|---|---|---|
| Fase 4 `(sem ID)` | MVP de checkout 100% manual (PIX do lojista, confirmação manual), 16/06/2026 | Validar o fluxo rápido | **SUPERADA** — Mercado Pago integrado; confirmação manual restrita a pedido pago (D-FIN-45) |
| Fase 4.4 `(sem ID)` | Rastreio é por loja; código informado pelo lojista e consulta automática | Pedido multilojas | VIGENTE |
| Fase 5.3 `(sem ID)` | Email marketing por SMTP do painel, sem API de terceiros no MVP; rastreio de abertura/clique ativo | Custo e independência | VIGENTE |
| Fase 5.4 `(sem ID)` | Impressão registrada por renderização, `session_hash` SHA-256, cobrança de destaque fora do sistema | Zero JS de rastreio, LGPD | VIGENTE |
| Fase 6 `(sem ID)` | Papel único em `users.role` + `CustomerProfile` complementar; separação por `marketplace_status` | Multi-papel sem duplicar conta | VIGENTE |
| Fase 6.4 `(sem ID)` | Adotar `spatie/laravel-permission` com seeders idempotentes | Solução madura | VIGENTE |
| Fase 9 `(sem ID)` | Sanctum em modo token pessoal, não SPA/cookie | App é cliente separado | VIGENTE |
| Fase 9 `(sem ID)` | Carrinho exige login no app | Reaproveitar `CartService` sem mudança | VIGENTE |
| Fase 9 `(sem ID)` | API reaproveita Services do site; construtor de curso, feed-publish, admin e recuperação de senha fora da v1 | Evitar segunda regra | VIGENTE |
| Fase 10 `(sem ID)` | Customer Intelligence via SDK externo por HTTP, com `try/catch` nas chamadas | Integração rápida | **SUBSTITUÍDA** pela Trilha CI (CI-01 #1–#6); o `try/catch` permanece VIGENTE |
| Docker `(sem ID)` | `vendor/` e `node_modules/` em volume nomeado; `app` e `queue` sem `env_file`; Redis disponível e não usado | Performance no WSL2; proteção do banco de dev | VIGENTE |

### 23.2 Customer Intelligence

| ID | Decisão | Motivo | Estado |
|---|---|---|---|
| CI-01 #1 | Corte direto na migração das chamadas, sem escrita dupla | Dual-write mantém o acoplamento | HISTÓRICA (cumprida na CI-05) |
| CI-01 #2 | Recomeçar o histórico, sem importar a VPS | Volume pequeno | HISTÓRICA |
| CI-01 #3 | Manter os cookies `jmf_ci_*` | Renomear zeraria identidades | VIGENTE |
| CI-01 #4 | Eventos brutos por 180 dias, agregado diário permanente | Retenção curta viável | VIGENTE |
| CI-01 #5 | `expositor_impressions` separado do módulo | Analytics nativo já existente | VIGENTE |
| CI-01 #6 | Manter a forma da chamada de tracking (fachada) | CI-05 mecânica | VIGENTE |
| CI-02 `(sem ID)` | `ci_events` append-only; UUID ordenado público + bigint interno; dimensões `NOT NULL DEFAULT ''`; nomes `TrackedEvent`/`VisitorSession` | Integridade e ausência de colisão de nomes | VIGENTE |
| CI-03 `(sem ID)` | Middleware anexado pelo ServiceProvider do módulo, não por `bootstrap/app.php` | Ordem determinística | VIGENTE |
| CI-04 `(sem ID)` | Fila própria `customer-intelligence`, última na prioridade; contexto capturado no despacho | Não atrasar e-mail; worker sem cookie | VIGENTE |
| CI-06 `(sem ID)` | Painel lê o banco por `Queries/`; nenhum cache; `lead_score` substituído por contagem de eventos | Honestidade do dado | VIGENTE |
| CI-09 `(sem ID)` | `event_uuid` no despacho (idempotência); evento e agregados na mesma transação; `forget-user` rotaciona o `visitor_uuid` | Retentativa segura; LGPD | VIGENTE |
| GOV-01 #1 | Consentimento **opt-in**, estado inicial `UNKNOWN` não autoriza nada | Controle da pessoa | VIGENTE |
| GOV-01 #2 | Eventos transacionais seguem a mesma regra | Fatos vivem em `orders` | VIGENTE |
| GOV-01 #3 | Consentimento vale 12 meses, cookie próprio `fel_privacy_consent` | — | VIGENTE |
| GOV-01 #4 | Auditoria retida por 730 dias, expurgo próprio, desacoplado | Naturezas diferentes | VIGENTE |
| GOV-01 #5 | Tela de auditoria com permissão própria, só administrador | Quem vê métrica não vê quem olhou | VIGENTE |
| GOV-01 `(sem ID)` | Decisão centralizada no `TrackingPolicy`; auditoria sem metadata livre, síncrona, fora do `track()`, sem consultar consentimento | Um lugar só; auditado não desliga a auditoria | VIGENTE |

### 23.3 Segurança

| ID | Decisão | Motivo | Estado |
|---|---|---|---|
| SEC-01 `(sem ID)` | Revogar a credencial na origem e **não** reescrever o histórico do Git | Reescrita quebra clones e não desfaz exposição | VIGENTE |
| SEC-02 `(sem ID)` | Guard escopado por expositor, sem `ProductPolicy` para ownership | `Gate::before` concede tudo a admin, que não tem expositor | VIGENTE (reafirmada por D-02F-1) |
| SEC-02B `(sem ID)` | Recurso alheio responde 403 na área do lojista | Padrão já existente | VIGENTE |
| SEC-02C `(sem ID)` | Guard em cada método público; antes de I/O destrutivo; `expositor_id` fora do update | Livewire: cada método é endpoint | VIGENTE |
| SEC-02D `(sem ID)` | API não alterada; o Livewire sobe ao nível da API | Não reduzir segurança existente | VIGENTE |
| SEC-03 `(sem ID)` | Identidade válida antes de comparar propriedade | `null === null` | VIGENTE |

### 23.4 Catalog Intelligence — fundação (CAT-01 a CAT-04)

| ID | Decisão | Motivo | Estado |
|---|---|---|---|
| CAT-01 #1 | Namespace `App\CatalogIntelligence` | Três tipos de item | VIGENTE |
| CAT-01 #2 | Espelhar a forma do Customer Intelligence | Precedente maduro | VIGENTE |
| CAT-01 #3 | Prefixo `catalog_` nas tabelas | Coerente com `ci_` | VIGENTE |
| CAT-01 #4 | Dados da inteligência fora de `products` | `products` não é depósito de IA | VIGENTE |
| CAT-01 #5 | `short_description` como campo do domínio | Útil sem IA | HISTÓRICA (implementada na CAT-02) |
| CAT-01 #6 | Contratos + Fake + Null desde o início | Escolha de fornecedor não bloqueia | **SUPERADA** em parte por D-CAT-05B-4: as classes só nascem quando há quem as chame (nasceram na CAT-06D) |
| CAT-01 #7 | Similaridade começa sem infraestrutura nova | Embeddings são aceleração | VIGENTE |
| CAT-01 #8 | Integrar primeiro no Livewire do lojista | Admin não cadastra item | VIGENTE |
| CAT-01 #9 | API integrada depois, pela mesma porta | Evita terceira duplicação | VIGENTE |
| CAT-01 #10 | Risco de autorização tratado fora da trilha | Virou a SEC-02 | HISTÓRICA |
| CAT-01 #11 | FAQ automático fora da primeira entrega | Escopo | VIGENTE |
| CAT-01 #12 | Sem fine-tuning | Primeiro dataset e memória | VIGENTE |
| CAT-02 `(sem ID)` | `short_description` `varchar(500)` nullable, sem backfill | Texto truncado não é resumo | VIGENTE |
| CAT-03 `(sem ID)` | Quatro tabelas `catalog_*`; normalizador único sem acento; origem humana nasce aprovada, status nunca sobe sozinho; `confidence` nula | Governança de proveniência | VIGENTE |
| CAT-04 `(sem ID)` | Casamento por frase; candidato ≠ associação; só evidência direta persiste; pesos em `SimilarityScorer`; score não persistido; sem FULLTEXT; backfill em command, não em migration | Explicabilidade e contra a verdade circular | VIGENTE |

### 23.5 Catalog Domain — CAT-DOM-01 e CAT-DOM-02B

| ID | Decisão | Motivo | Estado |
|---|---|---|---|
| CAT-DOM-01 `(sem ID)` | Separar `Product` (identidade) de `ProductOffer` (relação comercial); backfill 1:1; nenhuma fusão | O item sobrevive à saída do vendedor | VIGENTE |
| **H-1** | Item de expositor inativo sai das vitrines; produto e conhecimento permanecem | Coerência entre catálogo e loja | VIGENTE |
| **H-2** | Colunas comerciais permanecem em `products`, mantidas em espelho | Sem migration destrutiva naquela fase | **SUPERADA** — espelho encerrado na 02C, colunas removidas na 02H (D-02H-2) |
| CAT-DOM-01 `(sem ID)` | `SaveProductWithOffer` como porta única de escrita (fecha D-3 da CAT-DOM-01) | Regra duplicada entre Livewire e API | VIGENTE |
| CAT-DOM-01 `(sem ID)` | Excluir item no painel/API remove a oferta, não o produto | Coerência com a separação | VIGENTE |
| D-CAT-01 | *Proposta* de autoridade sobre `Product` (relatório de auditoria CAT-DOM-02) | — | **SUBSTITUÍDA** por D-CAT-09 |
| D-CAT-02 | *Proposta* sobre `products.is_active` | — | **SUBSTITUÍDA** por D-CAT-10 |
| D-CAT-03 | *Proposta* sobre imagens | — | **SUBSTITUÍDA** por D-CAT-14 e D-CAT-15 |
| D-CAT-04 | *Proposta* sobre FAQ e perguntas | — | **SUBSTITUÍDA** por D-CAT-16 e D-CAT-17 |
| D-CAT-05 a D-CAT-08 | *Propostas* do mesmo relatório, sem conteúdo registrado no repositório | — | HISTÓRICA — número reservado, não reutilizar |
| **D-CAT-09** | Autoridade sobre `Product` é da plataforma; delegação explícita ao expositor de origem, revogável, termina com o compartilhamento e nunca deduzida da cardinalidade; compartilhar é ato de curadoria | Evitar que um vendedor reescreva a identidade de outro | VIGENTE (implementada na 02C) |
| **D-CAT-10** | `products.is_active` é validade canônica, exclusiva da curadoria, distinta de `product_offers.is_active` | O vendedor já tem interruptor próprio | VIGENTE |
| **D-CAT-11** | `products.expositor_id` é proveniência, nunca ownership | Coluna sem semântica viraria autorização | VIGENTE |
| **D-CAT-12** | Curadoria = portadores de `produtos.moderar`, fora do caminho crítico | Não virar gargalo | VIGENTE |
| **D-CAT-13** | Estados pertencem à contribuição, não ao `Product` | `Product` só ativo/inativo | VIGENTE (contribuição não implementada) |
| **D-CAT-14** | Imagem canônica e imagem da oferta são conceitos distintos | Em artesanato a foto é a peça | VIGENTE |
| **D-CAT-15** | Fallback oferta → canônica; canônica nunca removida automaticamente; backfill copia arquivo | Arquivo compartilhado quebra em silêncio | VIGENTE |
| **D-CAT-16** | FAQ canônica e FAQ da oferta separadas; a FAQ existente é da oferta | Afirmação do vendedor ≠ afirmação do catálogo | VIGENTE |
| **D-CAT-17** | Pergunta carrega produto **e** oferta; contexto é o destinatário | O cliente escolheu com quem falar | VIGENTE |
| **D-CAT-18** | Resposta vira conhecimento canônico só por curadoria | Pode ser verdade só de um vendedor | VIGENTE |
| **D-CAT-19** | Matriz de edição em multi-oferta; guard "esta oferta é sua?" para imagem, FAQ, pergunta, curso | Guard por produto autoriza demais | VIGENTE (implementada na 02F) |
| **D-CAT-20** | Identidade exige evidência; semelhança nunca funde | Não há GTIN/SKU; fusão errada destrói autoria | VIGENTE |
| **D-CAT-21** | `Product` sem oferta é preservado, ativo, invisível e não indexável | Histórico e conhecimento | VIGENTE |

### 23.6 Catalog Domain — CAT-DOM-02C a 02I

| ID | Decisão | Motivo | Estado |
|---|---|---|---|
| CAT-DOM-02C `(sem ID)` | Delegação representada por **três colunas** em `products` (delegado, concedida em, revogada em), não por tabela | Unicidade da delegação ativa por construção; histórico completo sem consumidor | VIGENTE |
| CAT-DOM-02C `(sem ID)` | `ProductPolicy` com `updateCanonical` (curadoria ou delegação) e `updateStatus` (só curadoria); curadoria identificada por `produtos.moderar`, sem permissão nova | Fonte única da autoridade canônica | VIGENTE |
| CAT-DOM-02C `(sem ID)` | Autoridade exigida só quando campo canônico **muda**, não quando está presente no payload | Lojista sem delegação continua editando a própria oferta | VIGENTE |
| CAT-DOM-02C `(sem ID)` | Fim do write-through dos doze espelhos; `toggleActive` escreve só a oferta; criação concede delegação | Executar D-CAT-09 e D-CAT-10 | VIGENTE |
| CAT-DOM-02D `(sem ID)` | Imagem da oferta em coluna JSON `product_offers.images`, não em tabela; sem `image_path` na oferta | Proporcionalidade; 13 leitores | VIGENTE — reverter para tabela só se imagem ganhar metadado próprio |
| CAT-DOM-02D `(sem ID)` | `product_faqs` fica canônica; nasce `product_offer_faqs` com FK `NOT NULL` CASCADE e `UNIQUE(product_offer_id, sort_order)` | Cada tabela com um significado, sem XOR | VIGENTE |
| CAT-DOM-02D `(sem ID)` | `product_questions.product_offer_id` nullable, SET NULL; `product_id` permanece `NOT NULL`; `answered_by` inalterado | Conteúdo do cliente sobrevive; loja derivável | VIGENTE |
| CAT-DOM-02D `(sem ID)` | Migration faz só schema; backfill em command (`catalog:backfill-offer-content`) com modos inicial e reconciliação; reconciliação destrutiva exige declaração explícita e é proibida depois do primeiro writer novo | Filesystem não é transacional | HISTÓRICA (cutover feito na 02E) |
| CAT-DOM-02D `(sem ID)` | Backfill copia bytes preservando a extensão de origem; nunca compartilha caminho | Cópia não é conversão | VIGENTE |
| **D-02E-1** | FAQ: reader primário da página comercial é `ProductOffer.offerFaqs`; proibido fallback para `ProductFaq` e proibida concatenação; canônica, se exibida, em seção própria | Autoridade e autoria diferentes | VIGENTE |
| CAT-DOM-02E `(sem ID)` | Contexto comercial lê da oferta, contexto canônico lê do produto; fallback de imagem centralizado em `ProductOffer` e só de leitura; `main_image_url` de `Product` continua canônico | Não fazer `Product` escolher vendedor | VIGENTE |
| CAT-DOM-02E `(sem ID)` | Limpeza da FAQ comercial legada por prova de correspondência linha a linha, nunca `DELETE` por tabela | Tabela sem autoria | HISTÓRICA |
| CAT-DOM-02E `(sem ID)` | O cutover local seguiu ordem diferente da prescrita; a exceção **não** vira regra: com tráfego, a ordem é bloquear writer legado → reconciliar → validar → ativar writers | Registro honesto de desvio | VIGENTE como procedimento |
| **D-02F-1** | Ownership comercial deriva exclusivamente de `product_offers.expositor_id`, em `ProductOffer::pertenceAoExpositorDe()` | Uma definição | VIGENTE |
| **D-02F-2** | `products.expositor_id` não participa de autorização comercial | Proveniência | VIGENTE |
| **D-02F-3** | Delegação canônica não concede ownership de oferta | Eixos independentes | VIGENTE |
| **D-02F-4** | Só o dono da oferta da pergunta responde; autoridade sai de `question.product_offer_id` | O cliente escolheu o vendedor | VIGENTE |
| **D-02F-5** | Pergunta sem `product_offer_id` não tem destinatário comercial; ninguém a assume | Não escolher pelo cliente | VIGENTE |
| **D-02F-6** | `Product.is_active` permanece exclusivo de curadoria, inclusive contra delegado | Reafirmação de D-CAT-10 | VIGENTE |
| **D-02F-7** | `ProductOffer.expositor_id` e `product_id` não alteráveis por lojista, nem por payload | Transferência é governança | VIGENTE |
| CAT-DOM-02F `(sem ID)` | Ownership comercial por predicado e escopo, **não** por Policy | `Gate::before` e admin sem expositor | VIGENTE |
| **D-02G-1** | `Product` não implica `ProductOffer`; exatamente uma oferta hoje não é contrato | Preparar 1:N | VIGENTE |
| **D-02G-2** | Contexto comercial ambíguo exige seleção explícita; nunca `first()`, `orderBy`, `latest` ou `ofertaVigente` para decidir de quem se compra | Buy box é decisão de produto | VIGENTE |
| **D-02G-3** | Resolução automática só com exatamente uma oferta, onde a compatibilidade 1:1 é aceita | Compatibilidade de contrato | VIGENTE |
| **D-02G-4** | Histórico nunca resolve oferta por estado atual | O catálogo muda depois | VIGENTE |
| **D-02G-5** | Curso pertence ao `Product`; a oferta de origem da matrícula vem da compra | Conteúdo canônico, venda comercial | VIGENTE |
| **D-02G-6** | *Versão original:* ownership do curso independente do da oferta, autorização de curso mantida e registrada como dívida | — | **SUBSTITUÍDA** pela versão revisada abaixo |
| **D-02G-6 (revisada)** | A autoridade sobre o curso é a autoridade canônica do item (`ProductPolicy::updateCanonical`); ownership de oferta não concede acesso ao curso | A versão original deixava vendedor sem delegação editar o curso e curadoria sem acesso | VIGENTE |
| **D-02G-7** | URL e slug não escolhem vendedor; URL comercial é expositor + produto; `product_offers` não tem slug | Par já identifica a oferta | VIGENTE |
| **D-02G-8** | Multi-oferta continua desabilitada após a 02G | — | VIGENTE |
| CAT-DOM-02G `(sem ID)` | `products.slug` desambiguado só na criação | Não reescrever permalink publicado | VIGENTE |
| **D-02H-1** | Coluna legada só é removida após provar zero writer e zero reader | Auditoria → prova → remoção | VIGENTE |
| **D-02H-2** | `ProductOffer` é a autoridade comercial; `products` não mantém espelho | — | VIGENTE |
| **D-02H-3** | `products.is_active` permanece canônico e não é legado | — | VIGENTE |
| **D-02H-4** | `products.expositor_id` permanece proveniência e não é removido | História | VIGENTE |
| **D-02H-5** | Rollback de schema não restaura valores eliminados | `down()` recria estrutura vazia | VIGENTE |
| **D-02H-6** | FIN-SEC não depende das colunas removidas | — | VIGENTE |
| **D-02H-7** | AVA não reconstrói oferta a partir de espelho em `products` | — | VIGENTE |
| **D-02H-8** | Remover legado não habilita multi-oferta | — | VIGENTE |
| **D-02H-9** | Nenhum fallback silencioso para `products` onde a oferta é a autoridade comercial | — | VIGENTE |
| **D-02I-1** | `Product` permanece identidade canônica e não recebe de volta espelhos comerciais | — | VIGENTE |
| **D-02I-2** | `ProductOffer` é a única autoridade comercial por vendedor | — | VIGENTE |
| **D-02I-3** | `products.expositor_id` permanece somente proveniência | — | VIGENTE |
| **D-02I-4** | Delegação não concede ownership comercial, e ownership não concede autoridade canônica | — | VIGENTE |
| **D-02I-5** | Seleção de `ProductOffer` não depende de heurística implícita | — | VIGENTE |
| **D-02I-6** | Histórico comercial nunca é reconstruído a partir da oferta vigente | — | VIGENTE |
| **D-02I-7** | AVA preserva o curso canônico e a origem histórica da matrícula | — | VIGENTE |
| **D-02I-8** | Multi-oferta pode ser simulada por fixture e permanece desabilitada no produto | — | VIGENTE |
| **D-02I-9** | Nenhuma funcionalidade reintroduz espelho `Product ← ProductOffer` sem decisão explícita | — | VIGENTE |
| **D-02I-10** | A CAT-DOM-02 encerra a fundação; funcionalidades inteligentes pertencem a outra trilha | — | VIGENTE |

### 23.7 Integridade comercial — FIN-SEC-01

| ID | Decisão | Estado |
|---|---|---|
| **D-FIN-01** | Pedido é fato histórico; o catálogo atual não reescreve o passado | VIGENTE |
| **D-FIN-02** | FK viva não destrói `OrderItem` nem `OrderSplit`; CASCADE só para composição | VIGENTE |
| **D-FIN-03** | Identidade histórica mínima do vendedor é snapshot (`expositor_name`) | VIGENTE |
| **D-FIN-04** | Remoção da oferta não remove histórico | VIGENTE |
| **D-FIN-05** | Excluir expositor pode remover a oferta viva, não o fato comercial (`SET NULL`, não `RESTRICT`) | VIGENTE |
| **D-FIN-06** | Valores comerciais aplicados a um pedido são fatos históricos | VIGENTE |
| **D-FIN-07** | Alteração posterior em `ProductOffer` não reescreve o pedido | VIGENTE |
| **D-FIN-08** | Alteração posterior da comissão não recalcula `OrderSplit` | VIGENTE |
| **D-FIN-09** | Taxa de gateway só vira snapshot quando for fato conhecido; nunca estimada nem zero | VIGENTE |
| **D-FIN-10** | `OrderSplit` é cálculo comercial histórico, não recebível financeiro | VIGENTE |
| **D-FIN-11** | Nenhum valor de frete enviado pelo cliente é autoridade econômica; o servidor conhece o preço | VIGENTE |
| **D-FIN-12** | Confirmação de pagamento é operação de domínio centralizada | VIGENTE |
| **D-FIN-13** | Confirmação é idempotente | VIGENTE |
| **D-FIN-14** | `Order` e `OrderSplit` transitam atomicamente | VIGENTE |
| **D-FIN-15** | `OrderSplitConfirmed` representa transição real, não execução de método | VIGENTE |
| **D-FIN-16** | Efeitos externos ocorrem após o commit | VIGENTE |
| **D-FIN-17** | Estoque pertence à `ProductOffer` | VIGENTE |
| **D-FIN-18** | Carrinho não reserva estoque | VIGENTE |
| **D-FIN-19** | Reserva é criada atomicamente com o pedido | VIGENTE |
| **D-FIN-20** | Pagamento consome reserva existente; pedido legado disputa o estoque atual | VIGENTE |
| **D-FIN-21** | Toda transição de estoque é idempotente | VIGENTE |
| **D-FIN-22** | Concorrência é resolvida no banco, não por validação prévia de UI | VIGENTE |
| **D-FIN-23** | Produto digital não participa do estoque físico | VIGENTE |
| **D-FIN-24** | Oferta com reserva ativa não pode ser excluída; desativar continua permitido | VIGENTE |
| **D-FIN-25** | Prazo de pagamento é evidência do gateway, nunca estimativa da aplicação | VIGENTE |
| **D-FIN-26** | `payment_expires_at` nulo significa sem evidência, nunca expirado | VIGENTE |
| **D-FIN-27** | Expirar (relógio) e cancelar (decisão de alguém) são estados distintos | VIGENTE |
| **D-FIN-28** | Com reserva ativa, o controle de estoque da oferta não pode ser desligado | VIGENTE |
| **D-FIN-29** | Prazo interno de checkout e prazo do gateway são colunas distintas | VIGENTE |
| **D-FIN-30** | Quando ambos existem, o prazo do gateway prevalece | VIGENTE |
| **D-FIN-31** | Reversão financeira não implica retorno físico; sem restock | VIGENTE |
| **D-FIN-32** | `paid_at` é histórico e não é apagado; reversão usa coluna própria | VIGENTE |
| **D-FIN-33** | Conflito financeiro é persistente, idempotente e gravado fora da transação que falhou | VIGENTE |
| **D-FIN-34** | Refund parcial não vira estorno total; registra conflito | VIGENTE |
| **D-FIN-35** | Payment id, refund id e chargeback id são identidades distintas | VIGENTE |
| **D-FIN-36** | `Concluido` é projeção logística; entrega vive em `order_shippings` e sobrevive ao estorno | VIGENTE |
| **D-FIN-37** | Chargeback aberto não é reversão; só o desfecho no pagamento reverte | VIGENTE |
| **D-FIN-38** | Revogar acesso digital não apaga progresso, conclusão nem certificado | VIGENTE |
| **D-FIN-39** | `cancelled` após pagamento confirmado vira conflito, nunca `Estornado` | VIGENTE |
| **D-FIN-40** | Confirmação manual de repasse recusada só em estado terminal; `AguardandoPagamento` permitido | **SUPERADA** por D-FIN-45 |
| **D-FIN-41** | A fila não é autoridade financeira | VIGENTE |
| **D-FIN-42** | O scheduler é requisito operacional de produção | VIGENTE |
| **D-FIN-43** | Recuperação de colisão de conflito acontece fora da transação (deadlock a desfaz) | VIGENTE |
| **D-FIN-44** | Prova de concorrência em `tests/Concurrency/`, executável e versionada | VIGENTE |
| **D-FIN-45** | Confirmação de repasse segue a autoridade financeira do pedido (`temPagamentoConfirmado()`) | VIGENTE |
| FIN-SEC-01 `(sem ID)` | Não abrir subfase só para guardar dívida (não existe 01H) | VIGENTE como prática |

### 23.8 Catalog Intelligence — CAT-05, CAT-06, CAT-10A e CAT-10A.1

| ID | Decisão | Motivo | Estado |
|---|---|---|---|
| **D-CAT-05B-1** | A CAT-05 sugere e nunca aplica; aplicação é CAT-09 | Aplicar atravessa autoridade canônica, decisão de tela | VIGENTE |
| **D-CAT-05B-2** | `FindSimilarProducts` exige oferta vigente de quem é oferecido; a origem não é filtrada | Sugerir o que ninguém vende é sugerir 404; preservar D-CAT-21 | VIGENTE (fechou M-17) |
| **D-CAT-05B-3** | `ListingContext` lê só identidade de catálogo; oferta, imagem e FAQ fora | A sugestão é do item | VIGENTE |
| **D-CAT-05B-4** | `CatalogAiProvider`, `Fake` e `Null` pertencem à CAT-06 | Provider sem quem o injete é estética | HISTÓRICA (cumprida na 06D) |
| **D-CAT-05B-5** | Seguir a estrutura existente (`DTOs/`, `Support/`, `Actions/`); `Contracts/` e `Providers/` nascem na CAT-06 | Especificação da CAT-01 não é compromisso de nome | VIGENTE |
| **D-CAT-05C-1** | `ListingContext` com construtor privado e duas portas nomeadas | Não contornar o sanitizer | VIGENTE |
| **D-CAT-05C-2** | `knowledge` e `similarItems` entram por cópia | Contexto reproduzível e auditável | VIGENTE |
| **D-CAT-05C-3** | Vigência dos semelhantes não é reconferida pelo sanitizer | Evitar segunda definição de vigência | VIGENTE |
| **D-CAT-05C-4** | `knownAttributes` por lista de proibição, com a obrigação C-1 no código | Sem vocabulário de atributos não há whitelist | VIGENTE |
| **D-CAT-05C-5** | Redação de PII em texto livre fora, destino em aberto | Decisão de produto | **SUPERADA** por D-CAT-05F-5 (gate da CAT-06) e D-CAT-06B-2 (`FreeTextRedactor`) |
| **D-CAT-05C-6** | `lacunas()` entra no contexto | Fonte única do que falta | VIGENTE |
| **D-CAT-05C-7** | Lista de campos da oferta importada de `SaveProductWithOffer`, nunca copiada | Cópia envelhece em silêncio | VIGENTE |
| **D-CAT-05D-1** | Backfill adiado para a CAT-05H; só `--dry-run` | Sem tela para desfazer associação errada | **SUPERADA** por D-CAT-05H-1 e D-CAT-05H-2 |
| **D-CAT-05D-2** | `suggested_name` sempre nulo no caminho interno | Não há base para preferir um nome | VIGENTE |
| **D-CAT-05D-3** | `confidence` nula | Score ordena, não mede | VIGENTE — estendida ao provider real na CAT-10A: o adaptador não pede confiança ao modelo (D-CAT-10A-6) |
| **D-CAT-05D-4** | Campo já preenchido não recebe proposta | Não piorar texto humano | VIGENTE |
| **D-CAT-05D-5** | `Product` é parâmetro opcional do assistente, nunca do contexto | Similaridade exige item salvo | VIGENTE |
| **D-CAT-05D-6** | `SuggestionSource` nasce com `internal` e `external` | Destinatário já no roadmap | VIGENTE |
| **D-CAT-05D-7** | `keywords` só com nomes de conceito | P-4 ainda aberta | **SUPERADA** por D-CAT-05E-1 |
| **D-CAT-05D-8** | O assistente não chama `AssociateProductKnowledge` | Sugerir texto ≠ afirmar conhecimento | VIGENTE |
| **D-CAT-05E-1** | `keywords` = nome canônico + `commercial_term` + `synonym` | Termo comercial é como o público procura | VIGENTE |
| **D-CAT-05E-2** | `alias` fica fora das keywords | Quase todos são grafia sem acento | VIGENTE |
| **D-CAT-05E-3** | `keyword` fica fora por ora | Nenhum registro usa | VIGENTE (dívida E-1) |
| **D-CAT-05E-4** | A regra de termos úteis mora no `ContextSanitizer` | Decisão de fronteira | VIGENTE |
| **D-CAT-05E-5** | `ListingGap` é enum | `match` falha quando surgir a sexta lacuna | VIGENTE |
| **D-CAT-05E-6** | Lacuna que a sugestão preenche não vira pedido | Ruído desacredita pedidos | VIGENTE |
| **D-CAT-05E-7** | `lacunas()` intocado; muda quem consome | Pertence à CAT-05C | VIGENTE |
| **D-CAT-05F-1** | O assistente captura exceção do motor e devolve `vazia()` | Garantia na única porta | VIGENTE — a captura ampla de `Throwable` e a assimetria com a fronteira do provider estão registradas como dívida **H-12 (CAT-06H)** (§24) |
| **D-CAT-05F-2** | Captura separada por etapa — degradação parcial | Não perder o principal pelo acessório | VIGENTE |
| **D-CAT-05F-3** | `QueryException` no log só pelo SQLSTATE | Bindings carregam texto do lojista | VIGENTE |
| **D-CAT-05F-4** | Sem sinal de modo degradado por ora (dívida F-1) | Não reabrir a forma da sugestão | **SUPERADA** por D-CAT-06B-1 (DTO de desfecho, implementado na 06G) |
| **D-CAT-05F-5** | C-2 vira **gate** da CAT-06, não item da CAT-10 | Na CAT-10 o texto já teria saído por quatro fases | VIGENTE |
| **D-CAT-05F-6** | Timeout fora de escopo, destino CAT-06 | Não há chamada que penda; PDO global afetaria o checkout | HISTÓRICA — B-5 decidida na 06G (D-CAT-06G-5) |
| **D-CAT-05F-7** | A CAT-05F escreve o teste da regra 3; a CAT-10 herda a verificação | Um dono por teste | VIGENTE |
| **D-CAT-05G-1** | Teto do assistente é exatamente 6 consultas | Teto folgado aceita regressão | VIGENTE |
| **D-CAT-05G-2** | Prompt injection é gate da CAT-06 (S-1) | Sem prompt, o teste passaria pelo motivo errado | HISTÓRICA (cumprida na 06F) |
| **D-CAT-05G-3** | No lugar do teste, trava-se a precondição (`FronteiraDePromptTest`) | Chegada do prompt como decisão consciente | **SUPERADA** por D-CAT-06F-5 |
| **D-CAT-05G-4** | Eco do texto do lojista vira dívida S-2, escrita no docblock de `ListingSuggestion` | Marcação, não instrução | VIGENTE |
| **D-CAT-05G-5** | Custo de `deProduct()` é observação medida, não dívida | Nenhum chamador em produção | VIGENTE |
| **D-CAT-05G-6** | Dívidas de segurança com prefixo `S-` | `G-1` já ocupado | VIGENTE |
| **D-CAT-05H-1** | Backfill rodado em desenvolvimento, validado e revertido | Irreversibilidade vale para produção, não para pivot vazio | HISTÓRICA |
| **D-CAT-05H-2** | P-1 não é fechada; backfill de produção segue pendente | G-1 aberto | VIGENTE |
| **D-CAT-05H-3** | Achados D-1…D-4 viram dívida, não correção | Validação não altera comportamento | VIGENTE |
| **D-CAT-05H-4** | Script de validação descartável e não versionado | Subfase sem código | HISTÓRICA |
| CAT-06A `(sem ID)` | Subdivisão A→H; gates atribuídos (C-2→06E, S-1→06F, F-1→06G); F-1 não é binário (≥ 4 estados); validação de resposta mapeada para 06D; `EmbeddingProvider` registrado como órfão sem decisão | Gate fechado no meio de outra entrega não tem onde ser revisado | VIGENTE — decisões de produto formalizadas na 06B |
| **D-CAT-06B-1** | Desfecho da sugestão é DTO próprio, devolvido por `comContexto()`; 4 estados, um deles não é falha; `ListingSuggestion` não é reaberta; desfecho fora de `missing_information` | Operação normal sem IA não pode parecer avaria | VIGENTE — implementada na 06G, com os 4 estados preservados dentro de 8 (D-CAT-06G-3, D-CAT-06G-11) |
| **D-CAT-06B-2** | Redação de PII em `Support/FreeTextRedactor`, só na fronteira de saída; telefone/e-mail/CPF/CNPJ/CEP sempre; medidas/preço/quantidade nunca; URL e `@handle` não por padrão; teste positivo e negativo por faixa | Sanitizer serve o caminho interno, que não sai da aplicação | VIGENTE (implementada na 06E; ligada na 06G) |
| **D-CAT-06B-3** | `SuggestionPolicy` lê `lacunas()`, limiar em config, não conhece provider | Testável sem dublê | VIGENTE (implementada na 06C) |
| **D-CAT-06B-4** | Validação de resposta nasce junto dos contratos, na 06D | Produz o quarto estado do desfecho | VIGENTE (implementada na 06D) |
| **D-CAT-06B-5** | Operar sem IA externa é estado normal; `Null` é o caminho padrão de produção | — | VIGENTE |
| **D-CAT-06B-6** | 06E e 06F antes de 06G | Resiliência antes do acoplamento | VIGENTE |
| **D-CAT-06C-1** | `SuggestionPolicy::__invoke()` recebe o `ListingContext` inteiro | Fonte única estrutural | VIGENTE |
| **D-CAT-06C-2** | Veredito é o enum `KnowledgeSufficiency` de três casos | Falta de texto e falta de fato pedem ações opostas | VIGENTE |
| **D-CAT-06C-3** | Limiar lido no ponto de uso via `config()` | Octane; config não decorativo | VIGENTE |
| **D-CAT-06C-4** | `minimum_gaps` = 3, revalidado na 06G contra dados reais | Ponto em que falta mais do que existe | VIGENTE — revalidado na 06G sobre os 75 itens reais (D-CAT-06G-10) |
| **D-CAT-06C-5** | Trava da `SuggestionPolicy` substituída pela garantia que representava; `PromptGuard` segue travado | Gatilho cumpriu a função | VIGENTE — a trava do `PromptGuard` foi substituída na 06F (D-CAT-06F-5) |
| **D-CAT-06C-6** | Padrão de segurança do limiar ausente é 5 | Config sumido nunca liga fallback | VIGENTE |
| **D-CAT-06D-1** | `NullCatalogAiProvider::suggest()` devolve `vazia()` e nunca lança | Erro de chamador não vira exceção em produção | VIGENTE |
| **D-CAT-06D-2** | Validação da B-4 cobre só o que o tipo não garante | Revalidar tipo é cerimônia | VIGENTE |
| **D-CAT-06D-3** | O validador devolve lista de violações e nunca lança | O desfecho precisa registrar o motivo | VIGENTE |
| **D-CAT-06D-4** | Comprimento de texto não é validado no validador | O limite é da coluna; segunda fonte | VIGENTE |
| **D-CAT-06D-5** | `FakeCatalogAiProvider::queFalha()` lança de propósito | Simular falha é a função dele | VIGENTE — desde a 06G lança `CatalogAiProviderException` (D-CAT-06G-7) |
| **D-CAT-06D-6** | O `Fake` conta chamadas | Provar que não houve consulta | VIGENTE |
| **D-CAT-06D-7** | A trava da CAT-05D passa a guardar só o `EmbeddingProvider` | B-3 é o único sem decisão | VIGENTE |
| **D-CAT-06D-8** | `EmbeddingProvider` não é criado | Interface sem consumidor | VIGENTE |
| **D-CAT-06F-1** | Instrução, contexto recuperado e dado do lojista em **três propriedades** de `GuardedPrompt` (`final`, `readonly`), sem método que os junte em texto | A §5.2 pede separação estrutural; delimitador em string pode ser fechado pelo próprio conteúdo | VIGENTE |
| **D-CAT-06F-2** | O canal de instrução é o enum **puro** `ProviderInstruction`, fixado pelo guard, sem parâmetro de entrada e sem texto nesta fase | `string` ou enum com valor de apoio deixariam texto de fora escolher a instrução; o texto da instrução é o prompt, que é da 06G | VIGENTE — a 06G não escreveu texto de instrução; onde ele mora quando houver adaptador real era a dívida **H-11 (CAT-06H)**, resolvida por D-CAT-10A-3: o texto mora no adaptador, e o enum continua puro |
| **D-CAT-06F-3** | Classificação por origem: `knowledge` e `similar_items` são contexto recuperado, por lista de permissão; todo o resto do `ListingContext`, e toda chave nova, é dado do lojista | É a forma que o `ListingContext` já tem (campos próprios × cópias); o padrão fica no lado não confiável, nunca no de instrução | VIGENTE — o que cada canal pode provar ao provider real: D-CAT-10A-4 (`data`) e D-CAT-10A-5 (`context`) |
| **D-CAT-06F-4** | O guard não lê conteúdo: não redige, não escapa, não altera, não descarta, não lança e não registra; vazio e nulo atravessam como estão | Proteção por lista de frases destrói conteúdo legítimo e envelhece na primeira frase não prevista; PII é a C-2, e compor as duas peças é a 06G | VIGENTE |
| **D-CAT-06F-5** | `FronteiraDePromptTest` reescrito: as 3 precondições da CAT-05G trocadas pelo que vigiavam (guard sem provider nem função de texto; varredura permanente de formato de fornecedor, rede e credencial); o teste do texto hostil sobrevive e vira a base dos testes de injeção | Precondição vencida vira garantia, não é apagada (mesma regra da D-CAT-06C-5) | VIGENTE |
| **D-CAT-06G-1** | `CatalogAiProvider::suggest()` recebe `GuardedPrompt`, e não `ListingContext`; os dois métodos declaram `@throws CatalogAiProviderException`. **Evolução da 06D, não correção**: a assinatura transcrita da §3.3 bastava enquanto redator e guard existiam isolados | Compor as peças mostrou que `ListingContext` não é fronteira segura de transporte — texto cru, sem canais, e nada no tipo obrigava a proteger antes de enviar | VIGENTE |
| **D-CAT-06G-2** | Composição externa `PromptGuard` → `GuardedPromptRedactor` → provider. Redige toda string de `context` e `data`, e número cuja forma escrita é dado pessoal; `instruction` nunca; chaves estruturais intactas; chaves de `known_attributes` — as únicas vindas de fora (C-1) — redigidas, e na colisão fica a primeira | `ListingContext` não gera cópia redigida sem mudar o DTO; redigir depois da classificação não junta canais nem altera responsabilidade de guard ou redator | VIGENTE |
| **D-CAT-06G-3** | Desfecho em `DTOs/ListingOutcome` + `Enums/ListingOutcomeState`, **exaustivo**, 7 estados; os 4 da D-CAT-06B-1 preservados em significado; `violations` só na resposta inválida | Com o fluxo composto, 4 estados não cobriam sucesso interno, falha do motor nem uso externo; `null`, `source` e `missing_information` não são estado | VIGENTE — ampliada para 8 estados por D-CAT-06G-11 |
| **D-CAT-06G-4** | Falha do motor interno ≠ falha do provider: estado próprio, e o provider não é consultado quando a etapa de conhecimento falhou; falha só da similaridade segue acessória | A lacuna de conhecimento seria produto da falha; ampliar `ProviderFailed` apagaria de que lado está o defeito | VIGENTE |
| **D-CAT-06G-5** | B-5: prazo total de 8 s para a tentativa externa, aplicado pelo adaptador no transporte; esgotado → `CatalogAiProviderException::tempoEsgotado()` → `ProviderFailed`; sem chave de config até existir adaptador | Tela síncrona com "timeout curto" (spec §7); config sem leitor seria decorativo (D-CAT-06C-3) | VIGENTE — a chave de config nasceu com o adaptador que a lê, na CAT-10A (`CATALOG_AI_TIMEOUT`, D-CAT-10A-2); desde a CAT-10A.1 o prazo é `catalog_ai_timeout`, gravado pelo painel, e `CATALOG_AI_TIMEOUT` foi removida (D-CAT-10A1-1) |
| **D-CAT-06G-6** | 0 novas tentativas, no adaptador e no assistente; retry futuro exige decisão nova | Previsibilidade, latência, custo e chamada duplicada | VIGENTE — cumprida pelo adaptador real da CAT-10A (D-CAT-10A-6) |
| **D-CAT-06G-7** | A falha esperada da fronteira é `Exceptions/CatalogAiProviderException` (`final`, sem hierarquia); só ela é capturada, só em volta de `isAvailable()` e `suggest()`; log com etapa, classe do provider e da exceção, sem mensagem | `Throwable` mascararia `TypeError` e defeito interno como falha transitória; a mensagem do adaptador pode carregar prompt | VIGENTE |
| **D-CAT-06G-8** | Resposta válida complementa: texto externo só onde o lojista não escreveu e a base não compôs; nome por `nomeSugerido()`; keywords internas primeiro e externas sem duplicata (`KnowledgeNormalizer`); `missing_information` recalculado; `source` e `confidence` externos só com contribuição; proposta para campo preenchido é descartada e não é violação; sem contribuição → `InternalKnowledgeInsufficient` | Preservar D-CAT-05D-4 e D-CAT-05E-6; o validador olha forma (D-CAT-06D-2); o desfecho descreve contribuição, não chamada | **VIGENTE PARCIALMENTE** — a composição conservadora permanece vigente; a cláusula "sem contribuição → `InternalKnowledgeInsufficient`" foi **SUPERADA** por D-CAT-06G-11; a regra do nome foi **precisada** por D-CAT-06G-12 |
| **D-CAT-06G-9** | `CatalogAiProvider` resolve para `NullCatalogAiProvider` no container; `Fake` nunca registrado; o `Null` não carrega política nem fallback | D-CAT-06B-5: sem credencial, ausência de provider é o estado normal | **VIGENTE PARCIALMENTE** — `Fake` nunca registrado e `Null` sem política nem fallback continuam vigentes; "resolve para `NullCatalogAiProvider`" foi **SUPERADA** por D-CAT-10A-2: o contrato resolve pelo `CatalogAiProviderSelector`, e o `Null` passa a ser o fallback |
| **D-CAT-06G-10** | `minimum_gaps` = 3 **mantido** após revalidação só de leitura sobre os 75 itens reais do MySQL de desenvolvimento (transação desfeita, 0 SQL fora de SELECT, contagens iguais, script descartável removido). Lacunas: 2 em 35 itens, 3 em 40. Com 3: 40 consultas, cobrindo os 30 itens cuja lacuna de texto a base não preenche, 10 sem essa lacuna, 0 itens perdidos. Com 1–2: 75 consultas (45 sem lacuna aberta). Com 4–5: 0 consultas (30 perdidos) | Único limiar sem item perdido e com menor desperdício. `attributes` e `short_description` abertos em 75/75 (CAT-02, B-4) fazem o limiar operar hoje como "falta categoria ou conhecimento" | VIGENTE |
| **D-CAT-06G-11** | Oitavo estado `ExternalSuggestionNotUsed` — não é falha, não convida a repetir, sem violações: o provider foi consultado, respondeu validamente e nada da resposta foi aproveitado. `InternalKnowledgeInsufficient` volta a significar só `AwaitsMerchant`. O desfecho exaustivo passa de 7 para 8 estados | A revisão pré-commit mostrou que "o provider não contribuiu" e "o conhecimento interno foi insuficiente" são eventos distintos. Refinamento que só a composição real expôs, e não correção da CAT-06B | VIGENTE |
| **D-CAT-06G-12** | Nome externo equivalente ao atual pela chave do `KnowledgeNormalizer` não entra e não conta como contribuição, decidido em `nomeSugerido()`; outra contribuição real na mesma resposta ainda resulta em `ExternalSuggestionUsed` | Devolver o nome que o item já tem, ou a mesma grafia sem acento, seria fingir contribuição; a chave é a mesma que desduplica palavras-chave | VIGENTE |
| **D-CAT-10A-1** | O adaptador do provider real (`OpenAiCatalogAiProvider`) e o seletor ficam em `app/Services/CatalogAi`, **fora** do módulo. A aplicação continua dependendo só de `CatalogAiProvider`; nome de fornecedor, formato de mensagem, cliente HTTP e credencial não entram em `app/CatalogIntelligence`, e a configuração fica em `config/services.php` (`catalog_ai`), não em `config/catalog-intelligence.php` | A fronteira travada pela `FronteiraDePromptTest` (D-CAT-06F-5) e a proibição de credencial na config do módulo se cumprem pelo lugar, não por contorno de grafia; trocar de fornecedor não toca o domínio | **VIGENTE PARCIALMENTE** — adaptador e seletor fora do módulo, sem fornecedor, transporte ou credencial em `app/CatalogIntelligence` nem em `config/catalog-intelligence.php`, continuam vigentes; "a configuração fica em `config/services.php` (`catalog_ai`)" foi **SUPERADA** por D-CAT-10A1-1: a configuração operacional é do banco, e em `config/services.php` restou só a trava de D-CAT-10A1-3 |
| **D-CAT-10A-2** | O contrato resolve pelo `CatalogAiProviderSelector` — um binding só no módulo, resolvido a cada pedido, sem `singleton`. Adaptador real só com `enabled` verdadeiro, provider `openai`, chave e modelo não vazios e prazo válido; em qualquer outro caso, `NullCatalogAiProvider`. Prazo ausente → 8 s; acima de 8 → 8; ≤ 0 ou não numérico → `Null`. O seletor verifica condições: não captura exceção nem registra log. `Fake` nunca em runtime. Recurso desligado por padrão. Revê a H-13 (CAT-06H) | Operar sem IA externa segue estado normal (D-CAT-06B-5): configuração incompleta não pode virar erro de tela nem chamada; e defeito não pode virar "sem provider" em silêncio, pela mesma razão da D-CAT-06G-7 | VIGENTE — supera em parte D-CAT-06G-9; **REFINADA** por D-CAT-10A1-1, D-CAT-10A1-3 e D-CAT-10A1-4: condições, fallback `Null`, binding e ausência de captura continuam; os valores vêm do banco, por `CatalogAiSettings`, e a trava técnica é verificada antes de tudo |
| **D-CAT-10A-3** | O texto da instrução, específico do fornecedor, reside no adaptador: `match` exaustivo sobre `ProviderInstruction`, sem `default`; caso novo sem texto é defeito (`UnhandledMatchError` sobe). O domínio continua carregando só o enum puro (D-CAT-06F-2). Resolve a H-11 (CAT-06H) | Texto de instrução é formato de fornecedor; no domínio, violaria D-CAT-10A-1 e daria ao enum o valor de apoio que a D-CAT-06F-2 recusou | VIGENTE |
| **D-CAT-10A-4** | Fato objetivo sobre o item — material, medidas, origem, técnica, quantidade, prazo, garantia, certificação e demais características factuais — só pode ser afirmado a partir de `dados_do_item` (canal `data` do `GuardedPrompt`); na dúvida, omite | Forma, no canal externo, da regra inviolável 1 (§14.1) e do invariante 27: o que o lojista informou é a única fonte factual daquele item | VIGENTE |
| **D-CAT-10A-5** | `contexto_recuperado` (canal `context`: conceitos da base e itens semelhantes cadastrados por outros lojistas) é apoio semântico — terminologia, clareza, organização e palavras-chave — e **nunca** evidência de característica específica do item | Semelhança não transfere atributo nem funde identidade (D-CAT-20); o item semelhante é de outro lojista. Ajuste da revisão pré-commit da CAT-10A, com teste próprio e controle negativo | VIGENTE |
| **D-CAT-10A-6** | Transporte do provider real: Responses API; `instruction` em `instructions`, `context` e `data` em dois itens distintos de `input`, sem texto que junte canais; Structured Outputs com JSON Schema estrito; `store: false`, sem tools, busca, conversa ou `previous_response_id`; uma chamada por geração, 0 retry (D-CAT-06G-6), prazo total e de conexão ≤ 8 s (D-CAT-06G-5). Falha esperada → `CatalogAiProviderException` com mensagem fixa e sem `previous`; resposta que cabe no DTO mas é inválida → `ProviderResponseValidator`; `confidence` nula (D-CAT-05D-3) | A separação estrutural da S-1 chega até o fornecedor; nada fica guardado do outro lado; o erro do fornecedor pode ecoar parte da chave, e o de transporte, parte do prompt | VIGENTE |
| **D-CAT-10A-7** | Nenhuma chamada real ao provider na suíte: `phpunit.xml` força `CATALOG_AI_ENABLED=false` e `CATALOG_AI_API_KEY` vazia (`force="true"`), e os testes do adaptador e do seletor usam `Http::fake` com `preventStrayRequests` | Uma chave real no `.env` faria a suíte chamar a API, com custo e envio de dados | **VIGENTE PARCIALMENTE** — nenhuma chamada real e `Http::fake` com `preventStrayRequests` continuam vigentes; o mecanismo do `phpunit.xml` (`CATALOG_AI_ENABLED=false`, chave vazia) foi **SUPERADO** por D-CAT-10A1-9 |
| **D-CAT-10A1-1** | O **banco é a única autoridade operacional** do provider externo: provider ativo, provider, modelo, API key e timeout ficam em `site_settings` (`catalog_ai_*`), lidos a cada resolução, sem cache e sem `config()`. `CATALOG_AI_ENABLED`, `CATALOG_AI_PROVIDER`, `CATALOG_AI_MODEL`, `CATALOG_AI_API_KEY` e `CATALOG_AI_TIMEOUT` foram removidas, e valores antigos em `services.catalog_ai` não participam da resolução. A mudança vale na resolução seguinte, sem SSH, edição de `.env`, `config:clear`, `config:cache` ou reinício | Operar o provider — ativar, desativar, trocar modelo ou chave — não pode depender de acesso ao servidor; ambiente e banco ao mesmo tempo tornariam ambíguo o que está valendo | VIGENTE — supera em parte D-CAT-10A-1; refina D-CAT-10A-2 |
| **D-CAT-10A1-2** | A interface de configuração é a tela **Admin → Configurações → Inteligência Artificial** (`/admin/settings/inteligencia-artificial`, `CatalogAiSettingsForm`). Exige `configuracoes.editar` inclusive para abrir, e em cada ação; o item de menu só aparece para quem edita. Valida provider (só `openai`), modelo (obrigatório para ativar, sem espaço, até 100 caracteres) e timeout (inteiro de 1 a 8, ou em branco = 8 s), e exige chave gravada ou digitada para ativar | É a configuração que liga envio de conteúdo de catálogo a terceiro, com custo; quem só visualiza configurações não precisa dela | VIGENTE |
| **D-CAT-10A1-3** | `CATALOG_AI_FORCE_DISABLED` (`services.catalog_ai.force_disabled`, padrão `false`) é **trava técnica que só desliga**: verdadeira — ou qualquer valor que não seja claramente falso —, o seletor devolve o `Null` antes de ler o banco. Nunca liga o provider e nunca fornece provider, modelo, chave ou timeout. Não é configuração do administrador | O ambiente precisa impedir chamadas externas sem depender do que está gravado no banco — na suíte, e em ambiente que não pode chamar fora; um interruptor que também ligasse recriaria a segunda fonte de verdade | VIGENTE |
| **D-CAT-10A1-4** | `CatalogAiSettings` (`app/Services/CatalogAi`, fora do módulo) é a fronteira entre a persistência e o seletor: entrega o que está gravado, e quem valida é o `CatalogAiProviderSelector`. O módulo continua dependendo só de `CatalogAiProvider`, com o mesmo binding. Configuração ausente, desligada, incompleta ou inválida resolve o `Null`; defeito sobe — sem `try` nem `catch` no seletor, na leitura da configuração e no ServiceProvider do módulo (há teste) —, inclusive `DecryptException` da chave com o provider ligado, que é defeito de infraestrutura | O domínio segue sem banco de configuração, Livewire, fornecedor ou credencial (D-CAT-10A-1); "sem provider" silencioso esconderia `APP_KEY` trocada ou defeito de código (D-CAT-06G-7) | VIGENTE |
| **D-CAT-10A1-5** | A API key é gravada com o cast `encrypted` do `SiteSetting` e **nunca é reexibida**: a tela recebe só se há chave. Em branco ao salvar, a gravada é mantida; substituir continua possível mesmo quando a gravada não se decripta mais; parâmetros que recebem a chave em texto puro levam `#[\SensitiveParameter]` | A chave dá acesso pago a terceiro; mostrá-la de novo não ajuda a operar e amplia a exposição. Não há cofre externo, KMS, rotação automática nem auditoria de alteração | VIGENTE |
| **D-CAT-10A1-6** | **Credencial gravada nunca é hidratada em propriedade pública do Livewire** — vale para a chave do provider e para SMTP, Mercado Pago, Frenet e Melhor Envio. A tela recebe só se cada credencial está configurada (`segredoConfigurado()`, que não decripta, em indicador `#[Locked]`); o campo guarda só o valor novo, esvaziado antes de autorizar, validar ou gravar, inclusive quando a validação falha. `SiteSetting::SEGREDOS` lista as credenciais com cast `encrypted` — teste exige igualdade nos dois sentidos — e `$hidden` as tira da serialização do model, como defesa em profundidade. Credencial em branco vira `null`. A mensagem bruta de exceção SMTP não volta ao navegador | Toda propriedade pública viaja no snapshot, também para quem só visualiza configurações; a auditoria da CAT-10A.1 encontrou as credenciais das telas de e-mail e de frete e pagamento assim expostas | VIGENTE — invariante 32 (§22) |
| **D-CAT-10A1-7** | Remover credencial é ação explícita e desativa a integração que depende dela: remover a chave desativa o provider; remover o client secret do Melhor Envio, o token da Frenet ou o access token do Mercado Pago desativa a integração correspondente, e o pagamento volta ao modo manual | Integração ativa sem credencial é configuração que parece válida e falha na primeira chamada | VIGENTE |
| **D-CAT-10A1-8** | Ler a configuração não tem efeito colateral: `CatalogAiSettings` usa `SiteSetting::query()->find(1)`, e não `SiteSetting::instance()`, que cria a linha. Sem linha, não há configuração, e o contrato resolve o `Null` | A resolução acontece a cada geração de sugestão; leitura que escreve criaria registro em ambiente sem configuração e misturaria leitura com gravação | VIGENTE |
| **D-CAT-10A1-9** | A suíte continua sem chamada real ao provider: o `phpunit.xml` força `CATALOG_AI_FORCE_DISABLED=true` (`force="true"`), seja qual for a configuração gravada, e um teste confirma que a trava está ativa; os testes que precisam do adaptador a desligam só em memória, e os do adaptador, do seletor e da tela usam `Http::fake` com `preventStrayRequests` | Com a configuração no banco, forçar variáveis de ambiente vazias não isolaria mais nada; só a trava de desligamento isola | VIGENTE — supera o mecanismo de D-CAT-10A-7 |
| **D-CAT-10A1-10** | A CAT-10A.1 fecha tecnicamente **sem homologação real**: nenhuma chamada real à OpenAI foi feita. Homologar — atualizar o ambiente, aplicar a migration, configurar e ativar pelo painel, gerar sugestões com produtos reais, validar desfechos, fallback, redação e tela — é o próximo passo operacional, e não fase nova. A configuração pelo painel não altera a B-6: ativação ampla em produção só depois da CAT-10B | Configurar pelo painel facilita ativar, e não prova o comportamento com dados reais; a B-6 trata de custo, rate limit e observabilidade, que a CAT-10A.1 não entrega | VIGENTE |

### 23.9 Documentação

| ID | Decisão | Motivo | Estado |
|---|---|---|---|
| DOC-CONSOLIDATION-01 `(sem ID)` | Três documentos principais — `README.md`, `ROADMAP.md`, `docs/ARCHITECTURE.md`; fim dos documentos por fase; histórico detalhado no Git | Fragmentação impedia saber estado, decisão e próxima fase | VIGENTE |
| CAT-06A `(sem ID)` | Entregável de auditoria gravado em disco antes da subfase seguinte | Perda de sessão apagou a primeira versão da 06A | VIGENTE — o destino passa a ser `ROADMAP.md` / `ARCHITECTURE.md` |

---

## 24. Dívidas técnicas arquiteturais

Estado, severidade e destino de cada dívida ficam **só** no
[`ROADMAP.md`](../ROADMAP.md), §17. Aqui fica o que elas significam para a
arquitetura.

| Dívida | Restrição arquitetural que ela impõe |
|---|---|
| **G-1** — sem superfície de curadoria | A `ProductPolicy` decide sem interface. Enquanto existir: multi-oferta fechada, FAQ canônica vazia, conhecimento `draft` sem aprovação, backfill de produção (P-1) proibido |
| Workflow de proposta e vinculação de oferta a item existente | Lojista sem delegação é só recusado; não existe caminho legítimo para compartilhar um item |
| Apresentação e SEO sob multi-oferta | `ofertaVigente` em vitrine é apresentação provisória; a ativação exige regra de destaque e `rel=canonical` |
| **F-07** | CASCADE de `products` sobre curso e matrículas contradiz "pedido/matrícula é histórico"; hoje só alcançável por SQL |
| **F-08 · F-11** e domínio financeiro | Não há entidade de pagamento, recebível, repasse nem ledger; `OrderSplit` não é recebível. Refund parcial e chargeback só geram conflito |
| **F-06** | Integridade do pagamento depende de a verdade vir da API do gateway, não do webhook |
| **SEC-DEP-01** | Dependência de Markdown com advisories HIGH, usada em runtime pelo painel |
| **LGPD-01 · LGPD-02** | Princípios de proteção declarados e nunca implementados |
| **Provider real (CAT-10A · CAT-10A.1)** | Acoplado fora do módulo (D-CAT-10A-1) e resolvido pelo seletor, com o `Null` de fallback (D-CAT-10A-2) — por ambiente na CAT-10A; desde a CAT-10A.1, pela configuração do painel gravada no banco, com `CATALOG_AI_FORCE_DISABLED` só para desligar (D-CAT-10A1-1, D-CAT-10A1-3); recebe `GuardedPrompt` já redigido, não junta os três canais, aplica 8 s no transporte, não tenta de novo e converte só a falha esperada em `CatalogAiProviderException` (D-CAT-10A-6). Enquanto a B-6 estiver aberta, serve só à homologação controlada — ainda pendente: não há controle de custo, rate limit nem observabilidade por chamada |
| **B-3** | `EmbeddingProvider` sem decisão; não restringe o provider de sugestão |
| **B-6** | Custo e rate limit sem decisão: nenhuma ativação ampla de provider externo em produção antes da CAT-10B |
| **H-11 (CAT-06H)** — onde mora o texto da instrução | Deixou de restringir por falta de decisão: com o primeiro adaptador real, o texto mora no adaptador, que traduz `ProviderInstruction` por `match` exaustivo, sem formatter nem prompt builder no domínio (D-CAT-10A-3). Continua proibido ao domínio carregar texto de instrução ou formato de fornecedor; `ProviderInstruction` segue enum puro, e caso novo sem texto no adaptador é defeito |
| **H-12 (CAT-06H)** — captura ampla de `Throwable` no motor interno | `GenerateListingSuggestion::completar()` converte qualquer `Throwable` do motor — inclusive `TypeError` e `Error` — em degradação (D-CAT-05F-1), enquanto a fronteira do provider deixa defeito genérico subir (D-CAT-06G-7). Um defeito permanente de programação pode aparecer como `InternalIntelligenceFailed`, que convida a repetir, e a mensagem de exceção que não seja `QueryException` vai para o log. Não há teste de `TypeError`/`Error` no motor, de propósito: o comportamento é mantido como dívida, não como contrato desejado. Uma decisão futura explícita define se `Error`/`TypeError` propagam, quais exceções do motor são operacionais, quando `InternalIntelligenceFailed` convida a repetir, a política de log e de minimização de mensagens de exceção e os testes que protegem o contrato decidido |
| **D-3 (CAT-05H)** | Casamento por frase exata limita o alcance; mudar reabre a CAT-04 e troca falso negativo por falso positivo |
| **GOV-02** | Consentimento avaliado na requisição de origem não cobre eventos assíncronos |
| **R-4** / `ImageService` sem contagem de referências | Toda exclusão de arquivo precisa checar referências manualmente; compartilhar caminho é proibido |
| Ponteiros de documentação (DOC-01) | Três comentários de código apontam para arquivos-ponteiro; remover os ponteiros junto com a atualização dos comentários |
