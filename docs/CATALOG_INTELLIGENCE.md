# Catalog Intelligence — Documento Arquitetural

Inteligência de catálogo da **Feira Esquerda Livre**: base de conhecimento
própria, similaridade entre itens, assistente de cadastro e memória de feedback
humano.

> **Status:** CAT-01 concluída (auditoria e arquitetura). Nenhuma linha do módulo
> foi implementada ainda. Este documento descreve o que **existe hoje** e o que
> **se propõe construir** — as duas coisas estão explicitamente separadas.

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

`products` é o catálogo unificado dos três eixos, separados por `item_type`.

`app/Enums/ItemType.php` — três casos, **confirmados, não presumidos**:

| Caso | Valor | Rota pública |
|---|---|---|
| `Produto` | `produto` | `/produtos` |
| `Servico` | `servico` | `/servicos` |
| `Cuidado` | `cuidado` | `/cuidados` |

Nenhum outro tipo existe. A inteligência nasce cobrindo os três.

### 2.2 Colunas reais de `products`

Verificadas no MySQL de desenvolvimento, não apenas nas migrations:

```text
id  item_type  expositor_id  category_id  name  slug  description
image_path  images(json)  price  weight  height  width  length
price_type  modality  duration_min  is_featured  is_active  is_digital
has_stock  stock_quantity  sort_order  created_at  updated_at
```

Migrations que formam essa tabela:

- `2026_06_11_000002_create_products_table.php`
- `2026_06_13_000003_alter_products_add_fase3_fields.php`
- `2026_07_01_000002_add_shipping_dimensions_to_products_table.php`
- `2026_07_01_800001_add_is_digital_to_products_table.php`

**Achado central para a CAT-02:** existe `description` (`text`, nullable) e
**não existe `short_description`**. Também não existe nenhuma coluna de
palavras-chave, tags ou atributos estruturados. Hoje um card, um resultado de
busca e um compartilhamento têm que truncar a descrição longa.

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
> **Não corrigido nesta fase** — é falha pré-existente, sem relação com Catalog
> Intelligence, e corrigi-la aqui misturaria trilhas. Recomendação: tratar como
> item próprio (sugestão: **SEC-02**) **antes** da CAT-09, que integra
> exatamente neste componente.

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

**Pré-requisito prático:** criar `ProductFactory` (e provavelmente
`ExpositorFactory`/`ContentCategoryFactory`) antes da CAT-04 — hoje montar
corpus de teste exige `Product::create` à mão.

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
| 5 | `short_description` como campo real do domínio | Card, busca, compartilhamento, SEO e app mobile precisam dele independentemente de IA (CAT-02) |
| 6 | Contratos + Fake + Null desde o início | Escolha comercial de fornecedor não bloqueia CAT-03…CAT-05 |
| 7 | Similaridade começa sem infraestrutura nova | Níveis 1 e 2 cabem no MySQL existente; embeddings são aceleração |
| 8 | Integrar primeiro no Livewire do lojista | É o único fluxo de criação com UI; admin não cadastra produto |
| 9 | API mobile integrada depois, no mesmo `ListingAssistant` | Evita terceira duplicação da regra |
| 10 | Risco de autorização de §2.4 documentado, não corrigido | Falha pré-existente e sem relação com a trilha; merece item próprio antes da CAT-09 |
| 11 | FAQ automático fora da primeira entrega | Decisão da trilha; arquitetura apenas não impede |
| 12 | Sem fine-tuning | Primeiro dataset e memória |

---

## 10. Histórico

| Fase | Status | Data | Resumo |
|---|---|---|---|
| CAT-01 | Concluída | 2026-08-26 | Auditoria do catálogo, arquitetura proposta, riscos e plano de testes. Nenhum código de módulo criado. |
