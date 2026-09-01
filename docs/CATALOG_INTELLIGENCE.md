# Catalog Intelligence — Documento Arquitetural

Inteligência de catálogo da **Feira Esquerda Livre**: base de conhecimento
própria, similaridade entre itens, assistente de cadastro e memória de feedback
humano.

> **Status:** CAT-01, CAT-02, CAT-03 e CAT-04 concluídas. As seções **§3A** e
> **§3B** descrevem o que já está no código; a **§3** descreve a arquitetura
> proposta, cuja maior parte ainda é planejamento. Implementado e planejado
> estão explicitamente separados — nada aqui deve ser lido como pronto só por
> estar escrito.

Trilha independente. Não se confunde com Customer Intelligence (CI-01…CI-09),
SEC-01 ou GOV-01, e não antecipa a GOV-02.

Roadmap executável: [`ROADMAP_CATALOG_INTELLIGENCE.md`](ROADMAP_CATALOG_INTELLIGENCE.md).

---

## 1. Princípio central

A IA externa é uma ferramenta da arquitetura, não a arquitetura.

```text
Novo cadastro
      ↓
Contexto do item
      ↓
Base de Conhecimento da Feira
      ↓
Busca de itens semelhantes
      ↓
Conhecimento suficiente?
   ┌──────────────┴──────────────┐
  SIM                           NÃO
   ↓                             ↓
Sugestão interna          Provider externo
                                 ↓
                          contexto interno
                                 ↓
                             sugestão
   └──────────────┬──────────────┘
                  ↓
            revisão humana
                  ↓
      aceitar / editar / rejeitar
                  ↓
          produto final salvo
                  ↓
              feedback
                  ↓
       conhecimento reutilizável
```

O patrimônio da plataforma é o conhecimento acumulado e validado pela Feira, não
o modelo externo de terceiros.

### Regras invioláveis

1. **A inteligência não inventa fatos objetivos.** Material, composição,
   dimensões, peso, lavabilidade, origem, certificações, ingredientes,
   propriedades terapêuticas, garantias, sustentabilidade e "feito à mão" só
   entram no texto se tiverem sido informados ou derivarem de conhecimento
   curado. Na dúvida: omitir e pedir a informação ao lojista.
2. **Aprovação humana é obrigatória.** Gerar nunca é salvar. A sugestão vai para
   pré-visualização, o humano aplica o que quiser, edita o que quiser e só então
   salva.
3. **Falha da inteligência não bloqueia cadastro.** Provider fora do ar, sem
   credencial, resposta inválida, timeout — o cadastro manual continua
   funcionando integralmente. Essa propriedade terá teste explícito.
4. **Dados de lojista são dados, nunca instruções.** Nome, descrição e atributos
   são conteúdo não confiável e não podem alterar o comportamento do prompt.
5. **Fontes de conhecimento não são equivalentes.** Curado por admin vale mais
   que aprovado por humano, que vale mais que derivado, que vale mais que saída
   bruta de IA.

---

## 2. Estado atual auditado

Auditoria realizada em 2026-08-26 sobre `main`, commit `bb932fe`, com os 8
containers do `compose.yaml` no ar e MySQL 8.4 com dados de desenvolvimento.
Baseline da suíte no momento da auditoria: **455 passed, 1318 assertions, 0
failures** em 521,44s.

### 2.1 O catálogo é uma tabela só

> **Atualizado pela CAT-DOM-01 (2026-08-27).** Continua sendo uma tabela por
> eixo, mas `products` deixou de responder por preço, estoque, dimensões, dono e
> status comercial: isso passou para `product_offers`. Para a inteligência, a
> mudança é a favor — `Product` agora é só a identidade do item, exatamente o
> que o matcher e a similaridade sempre leram. Motivação e decisão em
> [`CAT_DOM_01_DECISAO_PRODUTO_MESTRE_E_OFERTAS.md`](CAT_DOM_01_DECISAO_PRODUTO_MESTRE_E_OFERTAS.md).
>
> Consequência prática: **o conhecimento sobrevive à saída do lojista**. Quando
> uma oferta é desativada ou removida, `catalog_product_knowledge` não é tocado,
> e o item continua servindo de referência para os outros — que é o objetivo
> declarado da trilha desde a CAT-01.

`products` é o catálogo unificado dos três eixos, separados por `item_type`.

`app/Enums/ItemType.php` — três casos, **confirmados, não presumidos**:

| Caso | Valor | Rota pública |
|---|---|---|
| `Produto` | `produto` | `/produtos` |
| `Servico` | `servico` | `/servicos` |
| `Cuidado` | `cuidado` | `/cuidados` |

Nenhum outro tipo existe. A inteligência nasce cobrindo os três.

### 2.2 Colunas reais de `products`

> **Atualizado pela CAT-DOM-02H (2026-08-31).** Esta lista descrevia as **29**
> colunas que `products` tinha quando a trilha CAT começou, incluindo os doze
> espelhos comerciais. Eles foram **removidos do schema**; a tabela tem hoje
> **17 colunas**, todas canônicas. A lista abaixo é a atual — a anterior fica
> registrada logo em seguida, porque explica de onde a inteligência partiu.

Verificadas no MySQL 8.4 de desenvolvimento, não apenas nas migrations:

```text
id  item_type  expositor_id  canonical_delegate_expositor_id
canonical_delegated_at  canonical_delegation_revoked_at  category_id
name  slug  short_description  description  image_path  images(json)
is_active  is_digital  created_at  updated_at
```

**As doze colunas que saíram**, e para onde foram — todas para
`product_offers`, que é a autoridade comercial desde a CAT-DOM-02C:

```text
price  price_type  modality  duration_min  weight  height  width  length
has_stock  stock_quantity  is_featured  sort_order
```

A lista canônica delas vive em código, não aqui:
`SaveProductWithOffer::ESPELHOS_COMERCIAIS_LEGADOS`.

**O que ficou, e por quê.** `is_active` é validade canônica do item e pertence à
curadoria (D-CAT-10) — nunca foi espelho, e existe nas duas tabelas com
significados diferentes. `expositor_id` é **proveniência** (D-CAT-11): registra
quem trouxe o item ao catálogo, e não autoriza nada. As três colunas
`canonical_*` são a delegação de edição canônica (D-CAT-09).

**Para a inteligência, a mudança é a favor.** O matcher e a similaridade sempre
leram só identidade — nome, descrição, resumo, eixo, categoria. Agora `products`
não tem mais nada além disso, e nenhuma leitura futura pode se apoiar em preço
ou estoque por acidente.

Migrations que formam essa tabela:

- `2026_06_11_000002_create_products_table.php`
- `2026_06_13_000003_alter_products_add_fase3_fields.php`
- `2026_07_01_000002_add_shipping_dimensions_to_products_table.php`
- `2026_07_01_800001_add_is_digital_to_products_table.php`
- `2026_08_26_150001_add_short_description_to_products_table.php` — CAT-02
- `2026_08_30_100001_add_canonical_delegation_to_products.php` — CAT-DOM-02C
- `2026_08_31_200001_remove_legacy_offer_columns_from_products_table.php` —
  CAT-DOM-02H, a que removeu os doze espelhos

**Achado central para a CAT-02:** na auditoria existia `description` (`text`,
nullable) e **não existia `short_description`** — um card, uma busca e um
compartilhamento tinham que truncar a descrição longa. **A CAT-02 acrescentou
`short_description`** (`varchar(500)`, nullable, antes de `description`), e por
isso ela já aparece na lista acima.
Palavras-chave, tags e atributos estruturados continuam **fora** de `products`,
de propósito: são multivalorados e pertencem às estruturas `catalog_*`.

### 2.3 Volume disponível como corpus

| | |
|---|---|
| `products` | 75 (28 produto · 24 serviço · 23 cuidado) |
| `content_categories` | 20 (11 produto · 4 serviço · 4 cuidado · 1 sem eixo) |
| `expositores` | 14 |
| `product_faqs` | 0 |

Todos os 75 itens têm `description` preenchida. É corpus suficiente para
similaridade nível 1 e 2 já na CAT-04 — mas é conteúdo **de seeder demonstrativo**,
não conteúdo real de lojista, e não deve ser promovido a conhecimento curado.

### 2.4 Existem dois caminhos de escrita do catálogo — e só um está protegido

Este é o achado mais importante da auditoria.

**Caminho 1 — Livewire (web):** `app/Livewire/Lojista/Produtos/ProdutoForm.php`,
271 linhas, view de 375 linhas. Rotas em `routes/web.php:600-603`, dentro de
`Route::middleware(['auth', 'lojista'])`.

**Caminho 2 — API REST (mobile):**
`app/Http/Controllers/Api/V1/Lojista/ProdutoController.php`. Rotas em
`routes/api.php:118-122`, com `auth:sanctum` + `lojista`.

Os dois fazem a mesma coisa — validam, montam `$data`, criam/atualizam o
`Product`, sincronizam FAQs e o curso AVA — em código duplicado. Qualquer
integração da inteligência precisa considerar os dois, ou o app mobile fica para
trás.

> ### ⚠️ Risco de segurança pré-existente (fora do escopo desta trilha)
>
> A API verifica propriedade; o formulário Livewire **não**.
>
> `Api/V1/Lojista/ProdutoController.php:89-91`:
> ```php
> private function authorizeProduct(Request $request, Product $product): void
> {
>     abort_unless($product->expositor_id === $request->user()->expositor->id, 403);
> }
> ```
>
> `ProdutoForm::mount()` (linha 74) recebe o `Product` por route-model binding e
> **não confere `expositor_id`**. `ProdutoIndex.php:42` filtra a listagem por
> expositor, então a falha não aparece navegando — mas a URL
> `/minha-loja/produtos/{id}/editar` aceita o id de qualquer lojista. Pior:
> `save()` grava `'expositor_id' => $expositor->id` (linha 180) com o expositor
> **de quem está logado**, o que transfere o item de dono.
>
> Não existe `ProductPolicy` (`app/Policies/` só tem `FeedPostPolicy` e
> `FeedCommentPolicy`) e nenhum teste cobre esse cenário.
>
> **Resolvido pela SEC-02** (trilha própria de segurança, concluída depois da
> CAT-01 e antes da CAT-02). O `ProdutoForm` passou a conferir propriedade no
> `mount()` e no início de cada mutação, e `expositor_id` saiu do payload de
> update. O texto acima descreve o estado auditado na CAT-01, mantido aqui como
> registro do achado. Detalhes em `ROADMAP.md`, seção SEC-02.

### 2.5 Não existe cadastro de produto no admin

`app/Livewire/Admin/` tem 19 subáreas (Banners, Categorias, Clientes,
CustomerIntelligence, EmailMarketing, Events, Expositores, Feed, Lojistas,
Media, Menus, Pages, Pedidos, Permissoes, Posts, Settings, Usuarios) e
**nenhuma de produtos**. O admin governa categorias, expositores e moderação; o
item em si é sempre cadastrado pelo lojista.

O `LojistaMiddleware` deixa `admin` e `editor` entrarem no painel do lojista
(linhas 21-23), mas `ProdutoForm::save()` faz
`$expositor = auth()->user()->expositor` sem checar nulo — um admin sem expositor
que tentar salvar tomaria erro.

Consequência para a trilha: **a CAT-09 integra em um fluxo só, o do lojista.**
A permissão `produtos.moderar` existe em `RolePermissionSeeder` mas não governa
criação.

### 2.6 Categorias

`content_categories` tem `name`, `slug`, `description`, `eixo`, `parent_id`,
`is_active` — já é hierárquica e já é filtrada por eixo
(`ProdutoForm::render()`: `whereNull('eixo')->orWhere('eixo', $item_type)`).

A categoria é o gancho natural do primeiro nível de similaridade e o ponto de
ancoragem do conhecimento curado. `description` de categoria é subaproveitada
hoje e pode alimentar contexto.

### 2.7 FAQ e Q&A

- `ProductFaq` — FAQ estático, escrito pelo lojista, no máximo 15 por item,
  regravado por `delete` + `create` a cada save (`ProdutoForm::syncFaqs()`).
- `ProductQuestion` — Q&A público, pergunta de cliente e resposta de lojista.

Zero FAQs no banco de desenvolvimento. Conforme a decisão da trilha, **geração
de FAQ fica fora da primeira entrega** — a arquitetura só precisa não impedi-la.

### 2.8 Infraestrutura disponível

| Recurso | Situação | Uso possível pela trilha |
|---|---|---|
| MySQL 8.4 | Ativo, `mysql:8.4` | Tabelas do módulo; JSON nativo; índices FULLTEXT disponíveis |
| Redis 7.4 | Container no ar, porta 6380 | **Não usado** — cache/sessão/filas em `database` |
| Filas | `default`, `email-marketing`, `customer-intelligence` nessa prioridade | Uma fila própria seria o 4º nível |
| Scheduler | 4 tarefas, sem serviço dedicado no compose | Reindexação/manutenção futura |
| Testes | SQLite em memória (`phpunit.xml`) | Ver §6 sobre divergência MySQL/SQLite |

### 2.9 Convenções que o módulo deve seguir

O Customer Intelligence é o precedente direto e maduro de "módulo interno":

```text
app/CustomerIntelligence/          33 arquivos PHP
├── Actions/      5   ForgetUser, IncrementDailyMetric, RecordAuditLog, ...
├── Console/      4   comandos com prefixo customer-intelligence:
├── Enums/        4   AuditAction, ConsentState, EventName, MetricName
├── Facades/
├── Http/             Controllers + Middleware próprios
├── Jobs/
├── Models/
├── Queries/
├── Services/
├── Support/      5   TrackingPolicy, VisitorContext, PropertySanitizer, ...
└── CustomerIntelligenceServiceProvider.php
```

Registrado em `bootstrap/providers.php`; config em
`config/customer-intelligence-internal.php`; tabelas com prefixo `ci_`;
migrations `create_ci_*_table`; bindings `scoped()` e não `singleton()` (por
causa do Octane); decisão de política centralizada em uma classe
(`TrackingPolicy`) em vez de espalhada.

Fora do módulo, o projeto usa `app/Services/` (16 services). **`app/Actions/`
existe e está vazio** — a convenção de Actions só vive dentro do CI.

`app/Support/` tem um único arquivo (`PublicUrl.php`). Não há pasta de DTOs.

### 2.10 Infraestrutura de testes

46 arquivos de teste. **Existe apenas `UserFactory`** — não há `ProductFactory`,
`ExpositorFactory` nem `ContentCategoryFactory`. Todos os testes montam catálogo
com `Product::create([...])` na mão, em 22 arquivos distintos.

Isso é dívida herdada relevante para esta trilha: similaridade e base de
conhecimento precisam de muitos itens variados por teste, e escrever cada um à
mão fica insustentável. **Criar `ProductFactory` é pré-requisito prático da
CAT-04.**

---

## 3. Arquitetura proposta

> Tudo nesta seção é proposta da CAT-01. Nada foi implementado.

### 3.1 Namespace e forma

`App\CatalogIntelligence`, espelhando o Customer Intelligence:

```text
app/CatalogIntelligence/
├── Contracts/          EmbeddingProvider, CatalogAiProvider
├── Providers/          Fake*, Null*, e futuramente a implementação real
├── Actions/            GenerateListingSuggestion, RecordSuggestionFeedback, ...
├── DTO/                ListingContext, ListingSuggestion, SuggestionSource
├── Enums/              KnowledgeOrigin, KnowledgeKind, SuggestionOutcome, TrustLevel
├── Models/             CatalogKnowledgeEntry, CatalogKnowledgeTerm, ...
├── Queries/            similaridade e recuperação de conhecimento
├── Services/           KnowledgeBase, SimilarityEngine, ListingAssistant
├── Support/            SuggestionPolicy, ContextSanitizer, PromptGuard
├── Console/            catalog-intelligence:*
└── CatalogIntelligenceServiceProvider.php
```

Config em `config/catalog-intelligence.php`. Tabelas com prefixo `catalog_`.
Nome **CatalogIntelligence** e não ProductIntelligence porque o domínio tem três
tipos de item, e todos precisam da mesma inteligência.

### 3.2 Camadas e dependências

```text
  ProdutoForm (Livewire)        Api/V1/Lojista/ProdutoController
            │                                │
            └────────────┬───────────────────┘
                         ↓
                  ListingAssistant            ← única porta de entrada
                         │
      ┌──────────────────┼──────────────────┐
      ↓                  ↓                  ↓
 KnowledgeBase    SimilarityEngine    CatalogAiProvider (contrato)
      │                  │                  │
      ↓                  ↓          ┌───────┴────────┐
 catalog_knowledge_*   products   Fake          Null (fallback)
                                    │
                              (futuro: real)
```

`ListingAssistant` é a única classe que o cadastro conhece. Ela decide se o
conhecimento interno basta; só se não bastar consulta o provider.

**Nenhuma chamada HTTP em Livewire, Controller, Model ou Service de negócio.**
A integração externa fica atrás de `CatalogAiProvider` e ponto.

### 3.3 Contratos

```php
interface CatalogAiProvider
{
    public function isAvailable(): bool;
    public function suggest(ListingContext $context): ListingSuggestion;
}

interface EmbeddingProvider
{
    public function isAvailable(): bool;
    public function embed(string $text): array;
}
```

O domínio **não** conhece OpenAI, Anthropic, Gemini nem nome de modelo. Sem
credencial configurada:

```text
CatalogAiProvider
        ↓
FakeCatalogAiProvider   (testes, determinístico)
        ↓
NullCatalogAiProvider   (produção sem credencial: isAvailable() = false)
```

`NullCatalogAiProvider` não lança exceção: devolve indisponibilidade, o
assistente cai para conhecimento interno e a UI informa o modo degradado.

### 3.4 DTOs

`ListingContext` carrega **o mínimo necessário** para entender o item:

```text
itemType            produto | servico | cuidado
name
categoryPath        nome e ancestrais da categoria
existingDescription
knownAttributes     só o que foi informado
knowledge           trechos curados relevantes
similarItems        referências internas
```

Nunca models Eloquent completos, nunca relações carregadas por engano. O que não
entra está em §5.

`ListingSuggestion` é estruturado, nunca texto solto:

```json
{
    "suggested_name": "...",
    "short_description": "...",
    "description": "...",
    "keywords": [],
    "missing_information": [],
    "source": "internal | external",
    "confidence": 0.0
}
```

`missing_information` é o mecanismo antialucinação: em vez de inventar material,
a inteligência devolve "informe o material".

### 3.5 Base de conhecimento

Tabelas propostas (nomes finais a confirmar na CAT-03):

| Tabela | Papel |
|---|---|
| `catalog_knowledge_entries` | Unidade de conhecimento: família/conceito, texto conceitual, eixo, categoria, origem, confiança, ativo |
| `catalog_knowledge_terms` | Termos recomendados, termos proibidos, sinônimos, vinculados a uma entry |
| `catalog_knowledge_relations` | Ligações entre entries e entre entry e categoria/item |

Modelo conceitual — o que **pode** ser representado:

```text
Família: Crochê      Categoria: Artesanato
Técnicas: crochê, tricô
Termos relacionados: artesanal, decoração, peça autoral
Produtos relacionados: tapete, toalha, sousplat, capa
```

E o que **não pode** ser presumido sem fonte:

```text
material = algodão      lavável = sim      feito à mão = sim
```

Não normalizar além disso sem benefício demonstrado.

### 3.6 Origem e confiança

`KnowledgeOrigin` (enum proposto):

```text
human_curated       admin escreveu e validou
approved_listing    texto final aprovado por humano num cadastro real
external_ai         saída bruta de provider
derived             inferido pelo próprio sistema
seed                fixture de desenvolvimento
```

Ordem de confiança:

```text
human_curated  >  approved_listing  >  derived  >  external_ai
```

Saída de IA **nunca** é promovida automaticamente a conhecimento curado.
`seed` existe para ser reconhecível e descartável: conteúdo de desenvolvimento
não pode se disfarçar de conhecimento de produção.

### 3.7 Similaridade evolutiva

```text
nível 1   categoria + termos + atributos            ← CAT-04, sem infra nova
nível 2   similaridade textual (MySQL FULLTEXT)     ← CAT-04
nível 3   embeddings via EmbeddingProvider          ← preparado, não acoplado
nível 4   busca semântica avançada                  ← futuro
```

O sistema funciona nos níveis 1 e 2 sem nenhuma credencial externa. Embeddings
entram como aceleração, não como requisito.

### 3.8 Feedback

Registrar, de forma estruturada: sugestão gerada, o que foi aplicado, o texto
final salvo, o desfecho (aceito / editado / rejeitado), a origem, a confiança e
o momento.

```text
IA sugeriu  →  humano alterou  →  versão final
```

A versão final humana vale mais que a saída bruta. É ela que, aprovada, vira
candidata a `approved_listing`.

**Isto não é treinamento.** Nada de fine-tuning nesta trilha. Constrói-se
dataset e memória; a evolução pretendida é RAG + similaridade + feedback +
base curada.

---

## 3A. Estado implementado (CAT-03)

Tudo nesta seção existe no código e está coberto por teste. O que a CAT-03
entregou foi **memória**, não inteligência: nada aqui deduz, sugere ou gera
texto.

### 3A.1 Tabelas

| Tabela | Papel | Unicidade |
|---|---|---|
| `catalog_knowledge_entries` | O conceito: "Crochê", "Barro", "Feito à mão" | `(type, normalized_name)` |
| `catalog_knowledge_terms` | Outras formas de escrever ou procurar o conceito | `(knowledge_entry_id, normalized_term)` |
| `catalog_knowledge_relations` | Ligação dirigida entre dois conceitos | `(from, to, relation_type)` |
| `catalog_product_knowledge` | Ponte item comercial ↔ conceito | `(product_id, knowledge_entry_id)` |

`products` **não ganhou nenhuma coluna** — há teste que falha se ganhar.

As unicidades são do banco, não de `if (! exists())`. Duas requisições
simultâneas cadastrando "Crochê" e "croché" colidem de verdade; a Action trata a
colisão como reencontro do registro existente.

Índices além das unicidades: `(status, type)` e `normalized_name` em entries,
`normalized_term` em terms, `(to_entry_id, relation_type)` em relations — este
último para tornar barata a leitura inversa do grafo.

### 3A.2 Enums

| Enum | Valores |
|---|---|
| `KnowledgeEntryType` | `product_type`, `technique`, `material`, `context`, `theme`, `attribute` |
| `KnowledgeSource` | `human_curated`, `seed`, `approved_listing`, `derived`, `external_ai` |
| `KnowledgeStatus` | `draft`, `approved`, `rejected`, `inactive` |
| `KnowledgeTermType` | `synonym`, `keyword`, `alias`, `commercial_term` |
| `KnowledgeRelationType` | `related_to`, `technique_of`, `used_in`, `belongs_to` |

`style` e `audience` foram deixados fora de `KnowledgeEntryType`: nenhum item do
catálogo atual os exigiria. Tipo sem uso convida a classificação arbitrária, e
acrescentar um caso depois é barato — a coluna é string.

### 3A.3 Normalização

`KnowledgeNormalizer` é a **única** forma de produzir a chave de deduplicação.
Minúsculas, sem acento, hífen e apóstrofo viram espaço, resto da pontuação sai,
espaços colapsados.

**A remoção de acentos é uma escolha com custo.** `Str::ascii()` reduz tanto
"sabiá" quanto "sabia" a `sabia`. Aceitamos porque o dano é assimétrico: dois
conceitos distintos colidindo é raro e **visível** — a UNIQUE recusa o segundo
cadastro e uma pessoa resolve; já o mesmo conceito duplicado por acento é
frequente, **silencioso** e envenena a base sem ninguém perceber. E "crochê",
"macramê", "cerâmica" são exatamente o caso em que a grafia varia e o
significado não.

O acento não se perde: `name` guarda o texto como a pessoa escreveu e é ele que
aparece na tela. A normalização produz só a chave de comparação.

### 3A.4 Governança

Três regras, todas com teste:

1. **Origem assinada por pessoa** (`human_curated`, `seed`) nasce `approved`.
   **Todo o resto nasce `draft`** — é esta linha que impede
   "produto cadastrado → conhecimento aprovado".
2. **Status nunca sobe sozinho.** Reencontrar um conceito não o aprova. Forçar
   `approved` numa origem não humana lança exceção.
3. **Origem de menor confiança não sobrescreve a de maior.** Uma dedução
   automática não corrige a descrição de um curador; no máximo preenche uma que
   estava vazia.

A ordem de confiança é **ordinal**, vive em `KnowledgeSource::trustLevel()`:

```text
human_curated > seed > approved_listing > derived > external_ai
```

A coluna `confidence` existe e fica **nula**. Atribuir 0,7 a uma origem hoje
seria inventar precisão que ninguém mediu; o campo está reservado para valores
derivados das fases seguintes.

Apenas `approved` é reutilizável — `KnowledgeEntry::usable()`.

### 3A.5 Actions

Escrita só por estas portas; nada cria os models direto:

| Action | Garante |
|---|---|
| `CreateOrUpdateKnowledge` | Normaliza, respeita unicidade, preserva proveniência, nunca promove status |
| `AttachKnowledgeTerm` | Termo único por conceito |
| `RelateKnowledge` | Relação única por par+tipo; recusa auto-relação |

São três Actions pequenas em vez de um service que faz tudo: conceito, termo e
relação têm ciclos de vida diferentes.

### 3A.6 Relações

Gravadas **dirigidas e uma vez só**. `related_to` lê-se como simétrico, mas
duplicar o par criaria duas fontes da mesma verdade; quem percorrer o grafo olha
os dois lados (`isSymmetric()` sinaliza quais).

A ponte com `products` é **capacidade estrutural apenas**: a CAT-03 não infere
associação nenhuma a partir do texto do lojista, e há teste que prova isso. A
ponte carrega `source` própria porque um conceito ligado por curadoria vale mais
que o mesmo conceito ligado por inferência.

### 3A.7 Base inicial

28 conceitos, semeados por `CatalogKnowledgeSeeder`, escolhidos a partir da
leitura dos 75 itens que a Feira realmente tem — não de uma lista genérica de
artesanato. Idempotente e sem tocar em `products`.

### 3A.8 O que deliberadamente NÃO foi feito

| Item | Onde pertence | Por quê |
|---|---|---|
| Painel administrativo de curadoria | **CAT-08** | O roadmap da CAT-01 já atribuía a interface administrativa à CAT-08; antecipá-la aqui bagunçaria as fases |
| Permissão `catalog_intelligence.*` | **CAT-08** | Sem UI não há o que proteger; permissão sem tela é configuração morta |
| `CatalogIntelligenceServiceProvider` | quando houver responsabilidade | Nada a registrar nesta fase — sem config, middleware, comando ou binding. Provider vazio é estética |
| `config/catalog-intelligence.php` | quando houver o que configurar | A base é funcionalidade interna e não depende de configuração |
| Inferência produto → conceito | **CAT-04** | É similaridade, não memória |
| Similaridade, embeddings, IA externa | CAT-04/05/06 | Fora do escopo por decisão |

---

## 3B. Motor de similaridade (CAT-04)

Responde a duas perguntas, sempre com o motivo junto: *quais conceitos se
aplicam a este item?* e *quais itens do catálogo se parecem com ele?*

Determinístico, explicável e sem nenhuma chamada externa. O valor da fase não é
o score — é conseguir responder **por quê**.

### 3B.1 O caminho

```text
texto do item
      ↓  ProductTextNormalizer (delega ao KnowledgeNormalizer da CAT-03)
texto normalizado
      ↓  MatchProductKnowledge — busca por FRASE, só conceito aprovado
candidatos diretos (nome canônico / termo)
      ↓  um passo pelas relações
candidatos de contexto (peso muito menor)
      ↓  AssociateProductKnowledge — só evidência direta vira registro
catalog_product_knowledge
      ↓  FindSimilarProducts — interseção de conceitos
itens semelhantes, com razões
```

### 3B.2 Casamento por frase, não por token

A busca é por **frase normalizada inteira**, cercada por espaços. Conceitos do
catálogo são compostos — "feito à mão", "ervas medicinais", "economia
solidária" — e quebrar por espaço destruiria o que os torna conceitos.

A auditoria dos 75 itens reais deu o exemplo: "solidária" aparece 10 vezes, mas
sempre em "Consultoria Solidária" e "Tecnologia Solidária". Nenhuma é economia
solidária, e a busca por frase acerta ao não marcar nenhuma. As bordas de espaço
também impedem que "crochê" seja encontrado dentro de "crocheteiro".

Sem stemming e sem NLP: reduzir "tapetes" a "tapete" exigiria regras de
português que erram o bastante para não valerem o risco agora.

### 3B.3 Uma normalização só

`ProductTextNormalizer` **não normaliza** — delega ao `KnowledgeNormalizer` da
CAT-03. Se houvesse duas regras, "Crochê" digitado pelo lojista e `croche`
gravado na base deixariam de se encontrar por um detalhe de acento, e o defeito
seria invisível porque cada lado estaria certo pela sua própria régua.

### 3B.4 Pesos

Fonte única: `SimilarityScorer`. Nenhum `+10` espalhado por aí.

| Evidência | Peso |
|---|---|
| Texto contém o **nome canônico** do conceito | 10 |
| Texto contém um **termo** do conceito | 8 |
| Conceito alcançado por **relação** | 3 |
| Conceito compartilhado, **ambos os lados humanos** | 6 |
| Conceito compartilhado, **algum lado automático** | 4 |
| Mesma categoria pública | 2 |

Os números não foram medidos — codificam uma **ordem de confiança**. O score
serve para ordenar, não para ser lido como porcentagem: "87,3% de similaridade"
seria falsa ciência; "12 pontos, por técnica e atributo compartilhados" é
auditável.

Relação vale 3 contra 10 de propósito: "crochê se relaciona com tricô" não torna
um tapete de crochê uma peça de tricô. É contexto, não fato.

### 3B.5 Candidato ≠ associação

| | Candidato | Associação |
|---|---|---|
| Onde vive | memória | `catalog_product_knowledge` |
| Quem produz | `MatchProductKnowledge` | `AssociateProductKnowledge` |
| Escreve no banco | **nunca** | sim |
| Inclui contexto por relação | sim | **nunca** |

O matcher roda inteiro sem gravar uma linha — é o que permitirá sugerir durante
um cadastro que ainda nem foi salvo.

**Só evidência direta vira registro.** Falso negativo custa uma sugestão a
menos; falso positivo entra na base, é lido depois como conhecimento e volta
reforçando outros itens — o sistema passaria a confirmar o próprio engano. Os
dois erros não têm o mesmo preço.

### 3B.6 Contra a verdade circular

Associação automática grava `KnowledgeSource::Derived`, valor que já existia na
CAT-03 — nenhuma proveniência nova foi inventada. Na similaridade, um conceito
compartilhado só vale peso cheio quando **os dois lados** foram assinados por
pessoa. Basta um lado automático para o par valer menos, e é assim que um erro
automático não se amplifica.

Associação humana nunca é sobrescrita nem rebaixada por uma passagem automática.

### 3B.7 Alcance: catálogo inteiro

A similaridade atravessa lojistas, por decisão. O objetivo declarado da trilha é
reaproveitar conhecimento **entre** lojas; limitar ao próprio expositor
esvaziaria isso — um lojista novo não teria referência nenhuma.

O que é lido já é público: apenas itens `is_active`, e apenas nome, categoria e
conceitos — o mesmo que qualquer visitante vê em `/produtos` e `/loja/{slug}`.
Nada de estoque, custo, dono ou pedido. A **SEC-02 continua intacta**: ela
protege *edição* de item alheio, e nada aqui escreve em produto.

### 3B.8 Custo

Contagem de consultas travada por teste, não tempo de relógio:

| Operação | Consultas |
|---|---|
| Casar item → conhecimento | ≤ 3, independente do tamanho da base |
| Item → itens semelhantes | ≤ 3, independente do tamanho do catálogo |

Sem consulta dentro de laço. O matcher carrega os conceitos aprovados de uma vez
e casa em PHP: troca memória por previsibilidade, adequado enquanto a base for
da ordem de centenas de conceitos — que é o horizonte declarado desta fase.

Nada de FULLTEXT: só Blueprint e consultas relacionais, iguais em MySQL e
SQLite.

### 3B.9 Score não é persistido

É derivado do texto atual do item e ficaria desatualizado no instante em que o
lojista editasse a descrição. Recalcular é barato; invalidar cache não é.

### 3B.10 Backfill

`catalog-intelligence:associate-products`, com `--dry-run`, `--product=` e
`--chunk=`. Vive num comando e não numa migration porque **migration muda
esquema e comando muda dado** — backfill em migration roda sozinho no deploy,
sem ninguém ler o resultado.

Dry-run sobre os 75 itens reais: **45 com evidência direta, 30 sem nenhuma**.
Conceitos mais encontrados: Artesanato (13), Feito à mão (11), Kit (10), Bem
viver (9), Decoração (7), Presente (7), Cerâmica (5), Ervas medicinais (5).

O backfill em massa **não foi executado**: fica como decisão humana informada,
agora que o relatório existe.

### 3B.11 Exemplo real (MySQL, itens do catálogo)

```text
Semelhantes a "Vaso de Cerâmica Esmaltado":

  [12] Produto Artesanal de Demonstração - Cerâmica Viva
         - Técnica compartilhada: Cerâmica.
         - Contexto compartilhado: Decoração.
         - Atributo compartilhado: Feito à mão.
  [4]  Tigela de Barro Nordestina
         - Técnica compartilhada: Cerâmica.
```

### 3B.12 O que a CAT-04 implementou e o que não implementou

| Implementado | Onde |
|---|---|
| Casamento item → conhecimento, por frase | `MatchProductKnowledge` |
| Associação controlada, só evidência direta | `AssociateProductKnowledge` |
| Similaridade item → item | `FindSimilarProducts` |
| Score explicável, fonte única | `SimilarityScorer` |
| Razões em português junto do resultado | `MatchReason` |
| Comando manual com `--dry-run` | `catalog-intelligence:associate-products` |

| **Não** implementado | Onde pertence |
|---|---|
| Backfill persistente global do catálogo | Decisão humana, depois do relatório de dry-run |
| Integração no `ProdutoForm` do lojista | CAT-05+ |
| Tela de "produtos semelhantes" | CAT-05+ |
| Geração de descrição | **CAT-05** |
| Provider de IA externa | **CAT-06** |
| Embeddings, busca vetorial, FULLTEXT | CAT-04 nível 3+ / futuro |
| Agendamento automático do comando | Nenhuma — permanece manual por decisão |

O motor existe e é exercitado por testes e por linha de comando. **Ele ainda não
aparece em nenhuma tela.**

### 3B.13 Limitações conhecidas

- Sem stemming: "tapete" e "tapetes" são coisas diferentes para o matcher.
- Sem tolerância a erro de digitação.
- A base carrega os conceitos aprovados inteiros em memória a cada casamento.
- A expansão por relação para em um salto.
- `short_description` está vazia nos 75 itens (a CAT-02 não fez backfill), então
  hoje ela não contribui — mas já é lida.

---

## 4. Fronteiras — o que esta trilha não faz

- Não integra com Customer Intelligence. A arquitetura só é preparada para o
  cruzamento futuro ("qual conteúdo converte melhor?").
- Não implementa GOV-02.
- Não toca checkout, pagamentos, frete, AVA, feed nem email marketing.
- Não escolhe nem contrata fornecedor de IA.
- Não cria credencial nem versiona segredo.
- Não gera FAQ automaticamente na primeira entrega.
- Não corrige o risco de autorização de §2.4 — item próprio, fora desta trilha.

---

## 5. Segurança e privacidade

### 5.1 O que nunca sai para provider externo

Nome do usuário, e-mail, CPF, CNPJ, endereço, telefone, cookies, `visitor_uuid`,
`session_uuid`, IP, dados de pedidos e qualquer informação pessoal. `sellerContext`
é descritivo do ofício da loja, jamais identificação do lojista.

A minimização é responsabilidade de `ContextSanitizer`, aplicada na construção do
`ListingContext` — não confiada a quem chama.

### 5.2 Prompt injection

Conteúdo digitado por lojista é entrada não confiável. Nome, descrição,
categoria, atributos e textos são **dados**, nunca instruções. Um lojista não
pode escrever "ignore as instruções anteriores" e mudar o comportamento.

Separação explícita entre instrução do sistema, contexto recuperado e dado do
usuário, em `PromptGuard`. Terá teste dedicado quando existir provider externo.

### 5.3 Custos e logs

Toda chamada externa deve permitir medir provider, modelo, tokens (ou medida
equivalente), custo estimado, duração, sucesso, falha e uso de fallback. Sem
registrar conteúdo sensível em log.

### 5.4 Cache

Cachear **conhecimento e contexto** é legítimo. Copiar **atributo objetivo** de
um item para outro não é: dois produtos diferentes nunca podem passar a
compartilhar um fato por efeito de cache.

---

## 6. Estratégia de testes

Cada fase adiciona os seus. Cobertura mínima pretendida:

| Área | O que provar |
|---|---|
| Migrations e Models | colunas, casts, índices, FKs |
| DTOs | `ListingContext` não carrega dado pessoal |
| KnowledgeBase | origem, confiança, ativo/inativo |
| SimilarityEngine | encontra semelhante real; não devolve lixo |
| ListingAssistant | usa interno quando basta; só então chama externo |
| Provider ausente | `NullCatalogAiProvider` degrada sem exceção |
| Resposta inválida | JSON quebrado não derruba cadastro |
| Antialucinação | atributo não informado não aparece no texto |
| Não sobrescrita | gerar ≠ salvar; aplicar não apaga edição do usuário |
| Autorização | lojista não acessa conhecimento/feedback de outro |
| Prompt injection | dado de lojista não vira instrução |
| Resiliência | **inteligência fora do ar, cadastro manual continua** |

Regra: não reduzir cobertura existente para fazer teste passar.

**Divergência MySQL/SQLite.** A suíte roda em SQLite em memória, mas o ambiente
real é MySQL 8.4. Onde o comportamento diverge de forma relevante — FULLTEXT,
unique constraints, JSON, foreign keys, concorrência — validar no banco real.
FULLTEXT em particular **não existe em SQLite**: se a similaridade nível 2 usar
`MATCH ... AGAINST`, precisa de estratégia declarada (driver alternativo em
teste, ou teste marcado que só roda em MySQL). Decisão a tomar na CAT-04.

**Pré-requisito prático:** `ProductFactory` e `ExpositorFactory` — **criadas na
CAT-02** e reutilizáveis pela CAT-04. `ContentCategoryFactory` segue inexistente;
criar quando a similaridade por categoria precisar.

---

## 7. Filas

Nem tudo vai para fila. Distinguir:

| Natureza | Onde roda |
|---|---|
| Sugestão que o lojista espera na tela | Síncrono, com timeout curto |
| Embeddings, reindexação, manutenção, agregação de feedback | Fila própria |

Se uma fila própria for criada, ela entra **depois** de
`customer-intelligence` na prioridade do worker, e o `compose.yaml` precisa
listá-la em `--queue`.

---

## 8. Critério de sucesso da primeira versão

Um lojista consegue: iniciar cadastro → informar dados básicos → pedir
assistência → o sistema consulta conhecimento interno → localiza referências
semelhantes quando existem → usa provider externo só quando necessário e
disponível → recebe sugestões estruturadas → sem fatos inventados → aplica
seletivamente → edita → salva normalmente → o feedback é registrado → e, se toda
a inteligência estiver indisponível, cadastra manualmente como sempre.

---

## 9. Registro de decisões — CAT-01

| # | Decisão | Motivo |
|---|---|---|
| 1 | Namespace `App\CatalogIntelligence` | Três tipos de item confirmados no `ItemType`; "Product" seria estreito |
| 2 | Espelhar a forma do Customer Intelligence | Precedente maduro no próprio projeto; provider próprio, config própria, prefixo de tabela próprio |
| 3 | Prefixo `catalog_` nas tabelas | Coerente com `ci_`; separa da tabela de domínio `products` |
| 4 | Dados da inteligência fora de `products` | `products` não vira depósito de IA |
| 5 | `short_description` como campo real do domínio | Card, busca, compartilhamento, SEO e app mobile precisam dele independentemente de IA — **implementado na CAT-02**, `varchar(500)` nullable, sem backfill |
| 6 | Contratos + Fake + Null desde o início | Escolha comercial de fornecedor não bloqueia CAT-03…CAT-05 |
| 7 | Similaridade começa sem infraestrutura nova | Níveis 1 e 2 cabem no MySQL existente; embeddings são aceleração |
| 8 | Integrar primeiro no Livewire do lojista | É o único fluxo de criação com UI; admin não cadastra produto |
| 9 | API mobile integrada depois, no mesmo `ListingAssistant` | Evita terceira duplicação da regra |
| 10 | Risco de autorização de §2.4 tratado fora da trilha | Virou a SEC-02, concluída antes da CAT-02; misturar segurança com funcionalidade esconderia as duas |
| 11 | FAQ automático fora da primeira entrega | Decisão da trilha; arquitetura apenas não impede |
| 12 | Sem fine-tuning | Primeiro dataset e memória |

---

## 10. Histórico

| Fase | Status | Data | Resumo |
|---|---|---|---|
| CAT-01 | Concluída | 2026-08-26 | Auditoria do catálogo, arquitetura proposta, riscos e plano de testes. Nenhum código de módulo criado. |
| SEC-02 | Concluída | 2026-08-26 | Trilha de segurança própria: corrigiu o IDOR do §2.4 e isolou o catálogo por expositor. Pré-requisito da CAT-02. |
| CAT-02 | Concluída | 2026-08-26 | `short_description` no domínio, formulário, API e factories. Sem IA, sem tags, sem atributos estruturados. |
| CAT-03 | Concluída | 2026-08-26 | Base de conhecimento: 4 tabelas `catalog_*`, 5 enums, normalizador, 3 Actions, governança de proveniência e base inicial de 28 conceitos. Zero IA externa. |
| CAT-04 | Concluída | 2026-08-26 | Motor de similaridade determinístico e explicável: casamento por frase, expansão por relação, associação conservadora e similaridade item↔item. Zero IA externa. |
