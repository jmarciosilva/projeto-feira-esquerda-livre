# CAT-05A — Auditoria de reconciliação

> **Subfase de auditoria da CAT-05.** Nenhum arquivo de código foi alterado,
> nenhuma migration criada. O produto desta subfase é o diagnóstico abaixo — o
> estado real do módulo, o que as fases anteriores de fato entregaram, o que a
> CAT-DOM-02 mudou por baixo da especificação da CAT-05, e as divergências entre
> a documentação e o código.

Executada em **2026-09-01**, sobre `main`, working tree limpo.

Roadmap da trilha:
[`ROADMAP_CATALOG_INTELLIGENCE.md`](ROADMAP_CATALOG_INTELLIGENCE.md).
Documento arquitetural: [`CATALOG_INTELLIGENCE.md`](CATALOG_INTELLIGENCE.md).
Decisões tomadas a partir daqui:
[`CAT_05B_DECISOES_DE_PRODUTO_E_CONTRATOS.md`](CAT_05B_DECISOES_DE_PRODUTO_E_CONTRATOS.md).

---

## 1. Baseline

| Item | Valor |
|---|---|
| Branch | `main` |
| HEAD | `8c84517582f9914d354b3745e278d075d17c0d1b` |
| Working tree | Limpo |
| `origin/main` | `e7ae4da` — 1 commit local à frente, sem push |
| **Suíte** | **1048 passed · 3117 assertions · 0 failures** |
| Duração | 1042,42s (~17min22s), container `app`, SQLite em memória |

O número da suíte bate exatamente com o que a documentação reconciliada declara.
A duração cresceu de 521s na CAT-01 para 1042s — o dobro do tempo para 2,3× os
testes.

Banco de desenvolvimento (MySQL 8.4, leitura por `information_schema`, sem
`ANALYZE TABLE`):

| | |
|---|---|
| `products` | **17 colunas**, na ordem exata da documentação · 75 linhas · 75 ativas |
| `product_offers` | 75 · **0 inativas** |
| `expositores` | 14 · **0 inativos** |
| `content_categories` | 20 |
| `catalog_knowledge_entries` | 28 |
| **`catalog_product_knowledge`** | **0 linhas** |
| `products.short_description` preenchida | **0 de 75** |
| Produtos com oferta vigente | 75 de 75 |

---

## 2. Estado atual de `App\CatalogIntelligence`

24 arquivos PHP, 1855 linhas. A composição declarada no roadmap confere item a
item:

```text
Enums/     6   KnowledgeEntryType, KnowledgeRelationType, KnowledgeSource,
               KnowledgeStatus, KnowledgeTermType, MatchType
Models/    3   KnowledgeEntry, KnowledgeRelation, KnowledgeTerm
Actions/   5   CreateOrUpdateKnowledge, AttachKnowledgeTerm, RelateKnowledge,
               MatchProductKnowledge, AssociateProductKnowledge
DTOs/      4   ProductKnowledgeInput, KnowledgeCandidate, MatchReason, SimilarProduct
Support/   3   KnowledgeNormalizer, ProductTextNormalizer, SimilarityScorer
Queries/   1   FindSimilarProducts
Console/   1   AssociateProductsCommand
Provider/  1   CatalogIntelligenceServiceProvider
```

Fatos que importam para a CAT-05:

- **O módulo não tem um único chamador fora de si.** `grep -rn "CatalogIntelligence"`
  em `app/`, `routes/` e `resources/` devolve zero, tirando o registro em
  `bootstrap/providers.php`. O motor só é exercitado por teste e pela linha de
  comando. A frase da §3B.12 — *"ele ainda não aparece em nenhuma tela"* — é
  literal.
- **Não existe `config/catalog-intelligence.php`.** O provider registra o comando
  e nada mais.
- Não existem as pastas `Contracts/`, `Providers/` nem `Services/` que a §3.1
  propõe. A CAT-05 criará as primeiras.
- Quatro migrations `catalog_*`; `products` não ganhou nenhuma coluna do módulo,
  e há teste que falha se ganhar.

---

## 3. O que a CAT-03 realmente entrega

**Memória, não inteligência.** Nada nela deduz, sugere ou gera texto.

- **Quatro tabelas** `catalog_*`, com unicidade no banco —
  `(type, normalized_name)`, `(entry, normalized_term)`,
  `(from, to, relation_type)`, `(product, entry)` — e não em `if (! exists())`.
- **`KnowledgeNormalizer`** como única forma de produzir chave de deduplicação.
  Remove acento por decisão declarada e argumentada: o dano é assimétrico —
  colisão de conceitos distintos é rara e visível, duplicata por acento é
  frequente e silenciosa.
- **Governança em três regras, todas com teste:** origem assinada por pessoa
  nasce `approved` e todo o resto nasce `draft`; status nunca sobe sozinho;
  origem de menor confiança não sobrescreve a de maior. `confidence` existe e
  fica **nula** de propósito.
- **Ordem de confiança ordinal** em `KnowledgeSource::trustLevel()`:
  `human_curated > seed > approved_listing > derived > external_ai`.
- **Três Actions** como únicas portas de escrita.
- **Seeder de 28 conceitos**, escolhidos lendo os 75 itens reais, idempotente e
  sem tocar `products`.
- A ponte com `products` é **capacidade estrutural apenas** — a CAT-03 não infere
  nada, e há teste provando.

Fora de escopo por decisão registrada: painel (CAT-08), permissão, inferência
(CAT-04) e — na época — o ServiceProvider, criado depois pela CAT-04, quando
surgiu o comando que precisava ser registrado.

---

## 4. O que a CAT-04 realmente entrega

Motor determinístico e explicável, zero chamada externa.

| Peça | Papel |
|---|---|
| `ProductTextNormalizer` | Junta campos textuais, delega a normalização ao `KnowledgeNormalizer`, cerca com espaços para busca por frase |
| `MatchProductKnowledge` | Item → conceitos. **Não grava nada.** ≤3 consultas, independentes do tamanho da base |
| `AssociateProductKnowledge` | Única porta que grava no pivot. Só evidência direta. Grava `Derived`. Nunca sobrescreve humano |
| `FindSimilarProducts` | Item → itens. 2–3 consultas, independentes do tamanho do catálogo |
| `SimilarityScorer` | Fonte única dos pesos: 10 nome · 8 termo · 3 relação · 6 conceito humano-humano · 4 se algum lado é automático · 2 mesma categoria |
| `AssociateProductsCommand` | `--dry-run`, `--product=`, `--chunk=` |

Decisões estruturais que a CAT-05 herda: casamento **por frase**, nunca por
token; expansão pelo grafo para em **um salto**; só conceito `approved` entra;
**candidato ≠ associação**; score **não é persistido**; nada de FULLTEXT.

**O backfill nunca foi executado** — e isso não é só documental:
`catalog_product_knowledge` tem **0 linhas** no banco de desenvolvimento. O
dry-run relatado (45 de 75 itens com evidência direta) é o único resultado que
existe.

---

## 5. Especificação atual prevista para a CAT-05

O que estava escrito antes desta auditoria, somando as duas fontes:

**Roadmap.** `ListingContext` → `ListingAssistant` → `ListingSuggestion`
estruturado com `suggested_name`, `short_description`, `description`,
`keywords`, `missing_information`. Antialucinação com teste explícito: atributo
não informado não aparece no texto; falta vira pedido de informação.

**Documento arquitetural.** `ListingAssistant` é a **única porta** que o cadastro
conhece (§3.2) e decide sozinha se o conhecimento interno basta antes de
consultar provider. `ListingSuggestion` é estruturado, nunca texto solto (§3.4).
`ContextSanitizer` faz a minimização na construção do contexto, não confiada a
quem chama (§5.1). `PromptGuard` separa instrução de dado (§5.2). Critério de
sucesso em §8.

**Fronteiras implícitas pela numeração.** Provider externo é CAT-06, feedback é
CAT-07, tela é CAT-09. Logo a CAT-05 é **o assistente interno, sem IA externa e
sem UI** — o que a torna, na prática, a primeira fase que *produz texto* a partir
do que a CAT-03 e a CAT-04 acumularam.

---

## 6. Impactos da CAT-DOM-02 sobre a CAT-05

Sete impactos reais. Três são a favor, quatro abrem decisão.

**(a) O texto que a CAT-05 gera é canônico — e mexer nele exige autoridade.**
`product_offers` **não tem nenhuma coluna de texto**; `name`,
`short_description` e `description` continuam em `products` e estão em
`Product::CAMPOS_CANONICOS`. Portanto qualquer caminho que *aplique* uma sugestão
a um item existente passa por `ProductPolicy::updateCanonical` e pode levar
`SemAutoridadeCanonica`. Na **criação** não há atrito — a delegação é concedida
no mesmo ato, dentro de `SaveProductWithOffer`; na **edição**, um lojista sem
delegação é recusado. Isto não existia quando a CAT-05 foi especificada.
→ **Blocker B-1.**

**(b) O contexto do item deixou de caber num objeto só.** Identidade em
`Product`; preço, dimensões, estoque, imagem e FAQ em `ProductOffer`. A §3.4
lista `knownAttributes` sem dizer de onde vêm — e agora a resposta é "dos dois
lados". `ListingContext` precisa decidir isso explicitamente.

**(c) Imagem e FAQ viraram conteúdo da oferta** (02D/02E), com fallback de
leitura em `ProductOffer::imagensParaExibicao()` e FAQ canônica × FAQ da oferta
separadas (D-CAT-16). Se o assistente usar qualquer um dos dois como sinal,
escolhe entre canônico e comercial.

**(d) A favor — `products` ficou só com identidade.** O matcher sempre leu nome,
resumo, descrição e categoria; agora é *tudo* que a tabela tem. Nenhuma leitura
futura pode se apoiar em preço ou estoque por acidente, e o trabalho do
`ContextSanitizer` fica estruturalmente mais fácil.

**(e) A favor — a escrita já é uma porta só.** `SaveProductWithOffer` é o único
ponto onde cadastro vira produto + oferta, chamado pelo Livewire e pela API. A
duplicação que a CAT-01 apontou como risco para a integração já foi resolvida; a
CAT-09 integrará dois canais sobre um gravador só.

**(f) A favor — D-CAT-18 formaliza a fonte de conteúdo.** Resposta de expositor
só vira conhecimento canônico por ato de curadoria; a Catalog Intelligence
"recebe candidatas, não fatos". É a regra 5 da §1 escrita como decisão de
domínio, e o documento diz explicitamente: *"Impacto: alimenta a trilha Catalog
Intelligence e a CAT-05."*

**(g) `products.is_active` mudou de significado** (D-CAT-10): não é mais "visível
ao público", é validade canônica sob curadoria. Visibilidade agora é
`ProductOffer::scopeVigente()`. Isso atinge `FindSimilarProducts` diretamente.
→ **Blocker B-2**, detalhado em §7, divergência D-5.

---

## 7. Premissas históricas obsoletas e divergências documentação × código

Dez achados. **D-5, D-6 e D-7 são divergências que exigem decisão humana** — as
demais são envelhecimento natural do texto.

| # | Onde | O que a documentação diz | O que o código diz |
|---|---|---|---|
| D-1 | `CATALOG_INTELLIGENCE.md` §2.9 | *"`app/Actions/` existe e está vazio — a convenção de Actions só vive dentro do CI"* | `app/Actions/` tem **16 arquivos** em `Catalog/`, `Orders/`, `Payments/`, `Stock/` |
| D-2 | §2.10 | *"Existe apenas `UserFactory`"*; *"criar `ProductFactory` é pré-requisito da CAT-04"* | **5 factories**: User, Expositor, Product, ProductOffer, KnowledgeEntry |
| D-3 | §2.10 | *"46 arquivos de teste"* | **79** |
| D-4 | §2.4 | `ProdutoForm` com 271 linhas | **428 linhas**. O achado de IDOR já traz o aviso "Resolvido pela SEC-02" — o texto está honesto, só os números envelheceram |
| D-8 | §2.3, §3B.13 | *"`short_description` está vazia nos 75 itens"* | **Ainda verdade** (0 de 75). Não é obsolescência — é um fato adverso ainda vigente |
| D-10 | §3.1 | Propõe `Contracts/`, `Providers/`, `Services/`, `DTO/` (singular) | Código usa `DTOs/` (plural) e não tem as outras três pastas |

O roadmap já marca as dívidas 1 e 3 da sua tabela como resolvidas; **o documento
arquitetural §2.10 não foi atualizado junto** — os dois discordam entre si.

### D-5 — `FindSimilarProducts` promete visibilidade pública e entrega outra coisa

A §3B.7 e o docblock da classe afirmam, nas mesmas palavras: *"apenas itens
`is_active`… o mesmo que qualquer visitante vê em `/produtos` e
`/loja/{slug}`"*.

Isso deixou de ser verdade na CAT-DOM-01. O visitante vê
`Product::scopeComOfertaVigente()`, que exige produto ativo **e** oferta ativa
**e** expositor ativo (`ProductOffer::scopeVigente()`). A consulta filtrava
apenas `p.is_active` — e depois da D-CAT-10 essa coluna significa outra coisa.

Isto **já estava registrado**: é a dívida **M-17 (`FindSimilarProducts` sem
vigência)**, marcada **Aberta** na tabela da CAT-DOM-02B e endereçada
nominalmente a esta fase — a CAT-DOM-01 escreveu *"pode devolver um item cuja
página pública responde 404. **Relevante para a CAT-05**, não para esta fase."*

Dois detalhes que dimensionam o problema: o teste
`test_inactive_products_are_not_returned_as_similar` cobria só o eixo antigo; e
**no banco de hoje a diferença é zero** — 0 expositores inativos, 0 ofertas
inativas, 75 de 75 com oferta vigente. A dívida era latente, não manifesta. Isso
é propriedade do dado atual, não garantia do código.

→ Resolvida na **CAT-05B**, por decisão **D-CAT-05B-2**.

### D-6 — A tabela de dívidas contradiz o texto do próprio documento

Em `ROADMAP_CATALOG_INTELLIGENCE.md`, a seção CAT-DOM-01 declara **"A dívida D-1
foi quitada"**. Mas a tabela *Dívidas e riscos abertos*, mais abaixo, ainda traz:

```text
| 8 | Colunas comerciais legadas em products (espelho, sem leitores)
    | Média — bloqueia multi-oferta | CAT-DOM-01, dívida D-1 |
```

sem tachado, ao lado dos itens 1, 3 e 7 que **estão** tachados. Também merecem
revisão, na mesma tabela:

- **item 9** (D-2, "imagens, FAQs, perguntas e curso AVA ficam no produto
  mestre") — a 02D/02E moveram imagem e FAQ para a oferta. Não se afirma aqui que
  está resolvido (a imagem canônica segue sem superfície, G-1), mas o texto já
  não descreve o código;
- **item 4** (FULLTEXT indisponível em SQLite, "Média") — a CAT-04 decidiu **não
  usar FULLTEXT**. A dívida só volta a existir se uma fase futura quiser
  similaridade nível 2 por esse caminho.

**Não corrigido nesta subfase, por decisão de escopo.** Aguarda decisão humana.

### D-7 — Os dois roadmaps discordam sobre o status da CAT-DOM-02

`docs/ROADMAP.md` (o principal) ainda marca a 02I com 🔍 *"Implementação
concluída · aguardando revisão pré-commit"*, e o mesmo texto para 02D…02H. Mas os
commits existem (`3532bd1` … `e7ae4da`) e `ROADMAP_CATALOG_INTELLIGENCE.md`
declara CAT-DOM-02 concluída.

**Não corrigido nesta subfase, por decisão de escopo.** Aguarda decisão humana.

### Premissas verificadas e ainda válidas

Contra a expectativa da auditoria, seguem corretas: §2.8 (Redis no ar mas não
usado — `.env` confirma `CACHE_STORE`, `SESSION_DRIVER` e `QUEUE_CONNECTION` em
`database`), §2.5 (nenhuma área de produtos em `app/Livewire/Admin/`) e §7 (o
worker segue em `default,email-marketing,customer-intelligence`).

---

## 8. Decisões ainda válidas

Nenhuma das cinco **regras invioláveis** da §1 foi tocada pela CAT-DOM-02, e a
quinta ganhou reforço em D-CAT-18. Seguem de pé:

- **Arquitetura:** namespace `App\CatalogIntelligence`; espelhar a forma do
  Customer Intelligence; prefixo `catalog_`; dados da inteligência **fora** de
  `products`; contratos + Fake + Null desde o início; similaridade sem
  infraestrutura nova; embeddings como aceleração, não requisito.
- **Motor:** candidato ≠ associação; só evidência direta persiste; associação
  automática grava `Derived`; humano nunca é sobrescrito; conceito compartilhado
  só vale peso cheio com os dois lados humanos; score não persistido;
  normalização única; só conceito `approved` influencia.
- **Integração:** a decisão nº 8 (integrar primeiro no Livewire do lojista)
  continua válida — o admin não cadastra produto. A decisão nº 9 ficou *mais
  fácil*: a regra já não está duplicada, está em `SaveProductWithOffer`.
- **Escopo:** sem fine-tuning; FAQ automático fora da primeira entrega; a trilha
  não escolhe fornecedor nem versiona segredo.
- **Processo:** o protocolo por fase — working tree limpa → baseline →
  implementar → testar → revisar diff → `git diff --check` → **parada antes do
  commit** —, sem Pint global e sem refatoração oportunista.

---

## 9. Blockers

| # | Natureza | Blocker | Destino |
|---|---|---|---|
| **B-1** | Decisão de produto | A CAT-05 aplica sugestão ou só sugere? Se aplica, atravessa `ProductPolicy::updateCanonical` e um lojista sem delegação é recusado ao salvar. Se só sugere, o problema some — mas precisa estar dito, porque o critério de sucesso da §8 termina em *"aplica seletivamente → edita → salva normalmente"*, escrito antes de a autoridade canônica existir | **Decidido na CAT-05B** (D-CAT-05B-1) |
| **B-2** | Decisão de produto | M-17. O assistente lê o catálogo inteiro ou só o vigente? Há tensão real entre D-CAT-21 (produto sem oferta é *preservado, ativo no catálogo interno e na Catalog Intelligence*) e a promessa da §3B.7 (só o que é público) | **Decidido e corrigido na CAT-05B** (D-CAT-05B-2) |
| **B-3** | Dado | `catalog_product_knowledge` tem **0 linhas**. `FindSimilarProducts` devolve coleção vazia para todo produto hoje. O assistente nasceria sem uma única referência interna. O backfill existe e foi deixado como decisão humana informada | Aberto — CAT-05D/05H |
| **B-4** | Corpus | `short_description` vazia em 75/75 e texto majoritariamente de seeder (*"demonstração"* 28×, *"expositor"* 46×). Não impede **implementar**; impede **validar** | Aberto — CAT-05H |
| **B-5** | Governança | **G-1 aberto.** Sem superfície de curadoria, conhecimento nascido `draft` não tem tela para ser aprovado, e `approved_listing` não tem como ser exercido. A `ProductPolicy` decide corretamente há quatro fases e quase nada a invoca | Aberto — CAT-08 |
| **B-6** | Registro | D-6 e D-7: documentação internamente inconsistente. Não impede código; impede tratar a documentação como fonte única | Aberto — aguarda decisão humana |

B-1 e B-2 eram os que **paravam a fase**: decisão de produto, e o protocolo manda
parar nelas. Os dois foram decididos na CAT-05B.

---

## 10. Subfases da CAT-05

| Subfase | Entrega | Código? |
|---|---|---|
| **CAT-05A** | Auditoria de reconciliação — este documento | Não |
| **CAT-05B** | Decisões de produto (B-1, B-2) e contratos das subfases seguintes | Mínimo — só o fechamento de M-17 |
| **CAT-05C** | `ListingContext` + `ContextSanitizer` — DTO e minimização, com teste de que nada pessoal nem comercial entra | Sim |
| **CAT-05D** | `ListingAssistant` interno — monta `ListingSuggestion` a partir de `MatchProductKnowledge` + `FindSimilarProducts` e só conhecimento `approved`. Sem provider | Sim |
| **CAT-05E** | Antialucinação e `missing_information` — atributo não informado vira pedido, nunca texto | Sim |
| **CAT-05F** | Resiliência e fronteiras — assistente indisponível não bloqueia nada; gerar nunca escreve | Sim |
| **CAT-05G** | Testes, custo de consulta travado e segurança | Sim |
| **CAT-05H** | Validação real sobre os 75 itens e documentação (§3C do documento arquitetural) | Não |

**B-3 (backfill) precisa entrar em alguma subfase ou ser explicitamente
adiado.** Sem associações, a 05H não tem o que validar — a validação real da
CAT-04 só existiu porque o dry-run rodou.

---

## 11. Arquivos que provavelmente serão alterados na CAT-05

**Criados:**

```text
app/CatalogIntelligence/DTOs/ListingContext.php
app/CatalogIntelligence/DTOs/ListingSuggestion.php
app/CatalogIntelligence/Support/ContextSanitizer.php
app/CatalogIntelligence/{Actions|Services}/…ListingAssistant   ← §3.1 propõe Services/, que não existe
tests/Feature/CatalogIntelligence/ListingAssistantTest.php
docs/CAT_05*.md
```

**Alterados:**

```text
docs/ROADMAP_CATALOG_INTELLIGENCE.md        situação, fases, dívidas
docs/CATALOG_INTELLIGENCE.md                nova §3C + as divergências do §7
app/CatalogIntelligence/CatalogIntelligenceServiceProvider.php   só se houver binding ou config novo
app/CatalogIntelligence/Queries/FindSimilarProducts.php          M-17 (feito na CAT-05B)
```

**Não devem ser tocados:** `Product`, `ProductOffer`, `SaveProductWithOffer`,
`ProductPolicy`, `ProdutoForm`, `ProdutoController`, qualquer migration. Se algum
deles precisar mudar, é sinal de que a fase saiu do escopo.

---

## 12. Testes necessários na CAT-05

Mapeados contra a §6 do documento arquitetural.

| O que provar | Origem |
|---|---|
| `ListingContext` não carrega dado pessoal (nome, e-mail, CPF, IP, `visitor_uuid`) **nem comercial** (preço, estoque, custo, dono) | §6 + novo por CAT-DOM-02 |
| O assistente usa **só** conhecimento `approved` | §6 |
| **Antialucinação:** atributo não informado não aparece no texto gerado | Roadmap, exigido nominalmente |
| Falta de informação vira `missing_information`, não invenção | Roadmap |
| **Gerar ≠ salvar:** nenhuma escrita em `products`, `product_offers` ou `catalog_*` | §6 |
| Contagem de consultas travada, independente do catálogo | Padrão CAT-04 |
| Funciona com item **ainda não salvo** (`ProductKnowledgeInput` já suporta) | §3B.5 |
| Base vazia não quebra: devolve sugestão degradada com `missing_information` | §6, resiliência |
| Isolamento: o assistente não expõe de item alheio nada além do público | §6, autorização |
| Item sem oferta vigente não entra em `similarItems` | **Novo — M-17, feito na CAT-05B** |
| Regressão: `CatalogoHardeningFinalTest`, `ColunasLegadasDeProductRemovidasTest` e `test_products_table_gained_no_knowledge_column` continuam passando | Hardening existente |

---

## 13. Fora de escopo da CAT-05, explicitamente

- **Provider de IA externa**, credencial, segredo, escolha de fornecedor — CAT-06.
- **Botão, tela, pré-visualização e aplicação seletiva** no formulário do lojista
  — CAT-09.
- **Painel de curadoria** e permissão `catalog_intelligence.*` — CAT-08. G-1
  continua aberto ao fim da fase.
- **Registro de feedback e memória** (`sugerido → aplicado → final → desfecho`) —
  CAT-07.
- **Observabilidade, custo por chamada, testes de prompt injection** — CAT-10; o
  `PromptGuard` só tem o que testar quando houver provider.
- **Embeddings, FULLTEXT, busca vetorial**, similaridade nível 3+.
- **Geração de FAQ** — decisão da trilha; a arquitetura só não impede.
- **Habilitar multi-oferta**, criar segunda oferta sobre item existente, seller
  linking, buy box, ranking, SEO canônico.
- **Qualquer migration em `products`** ou coluna nova nele.
- **Executar o backfill em massa** sem decisão humana explícita (B-3).
- **Corrigir as divergências D-6 e D-7** sem decisão humana.
- **Fine-tuning**, Pint global, refatoração oportunista, push sem autorização.

---

## 14. Encerramento

```text
CAT-05A — CONCLUÍDA
```

Nenhum arquivo de código alterado, nenhuma migration, nenhum commit de código.
Suíte verde em 1048 testes. Os dois blockers que paravam a fase — B-1 e B-2 —
foram levados à decisão humana e respondidos na CAT-05B.
