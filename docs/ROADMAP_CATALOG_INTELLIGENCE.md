# Roadmap — Catalog Intelligence

Roadmap executável da trilha de inteligência de catálogo da **Feira Esquerda
Livre**. Documento arquitetural:
[`CATALOG_INTELLIGENCE.md`](CATALOG_INTELLIGENCE.md).

Trilha independente de CI-01…CI-09, SEC-01 e GOV-01. Não antecipa a GOV-02.

---

## Situação

| | |
|---|---|
| Fase atual | **CAT-05D — `ListingAssistant` interno** (a CAT-05 foi subdividida em A→H) |
| Concluído antes | **CAT-DOM-02** (02A→02I) — fundação do domínio; **CAT-05A** — auditoria; **CAT-05B** — decisões e contratos; **CAT-05C** — contexto e minimização |
| Próxima | CAT-05E — antialucinação e `missing_information` |
| Suíte | 1079 passed · 3258 assertions · 0 failures |
| Código do módulo | `App\CatalogIntelligence`: 6 enums, 3 models, 5 Actions, 5 DTOs, 4 Support, 1 Query, 1 Command, 1 Provider |
| Branch | `main` |

---

## Baseline oficial da trilha

Registrado antes da primeira alteração, em 2026-08-26.

| Item | Valor |
|---|---|
| Commit | `bb932fe` |
| Working tree | Limpa |
| Containers | 8 no ar (`app`, `nginx`, `mysql`, `phpmyadmin`, `redis`, `node`, `queue`, `mailpit`) |
| Laravel | 12.65.0 |
| PHP | 8.3 (container) |
| MySQL | 8.4 |
| Tabelas no banco de desenvolvimento | 58 |
| Migrations relacionadas a catálogo | 7 |
| Arquivos de teste | 46 |
| **Suíte** | **455 passed · 1318 assertions · 0 failures** |
| Duração da suíte | 521,44s (~8min46s) no container `app` |
| `products` | 75 (28 produto · 24 serviço · 23 cuidado) |
| `content_categories` | 20 |
| `expositores` | 14 |
| `product_faqs` | 0 |

Qualquer regressão em relação a esses números precisa ser justificada.

---

## Fases

| Fase | Status | Entregável |
|---|---|---|
| **CAT-01** | ✅ Concluída | Auditoria, arquitetura, riscos, plano de testes, documentação |
| **CAT-02** | ✅ Concluída | `short_description` no domínio, formulário, API e factories |
| **CAT-03** | ✅ Concluída | Base de conhecimento, proveniência e governança |
| **CAT-04** | ✅ Concluída | Motor de similaridade determinístico e explicável |
| **CAT-DOM-01** | ✅ Concluída | Separação entre produto mestre e oferta do expositor |
| **CAT-DOM-02** | ✅ Concluída | Fundação do domínio (02A→02I): autoridade, conteúdo por oferta, cutover, isolamento, preparação multi-oferta, remoção do espelho legado e hardening — detalhada no `ROADMAP.md` principal |
| **CAT-05** | 🔍 Em andamento | Assistente de conteúdo — subdividida em A→H logo abaixo |
| **CAT-06** | ⬜ | Integração opcional com IA externa |
| **CAT-07** | ⬜ | Feedback humano e memória |
| **CAT-08** | ⬜ | Interface administrativa da inteligência |
| **CAT-09** | ⬜ | Integração no cadastro |
| **CAT-10** | ⬜ | Observabilidade, custos e segurança |
| **CAT-11** | ⬜ | Hardening, testes e documentação final |

### Subfases da CAT-05

A auditoria de reconciliação (CAT-05A) subdividiu a fase em oito subfases, pelo
mesmo motivo que levou a CAT-03, a CAT-04 e a CAT-DOM-01 a se subdividirem: a
fase mistura decisão de produto, contrato, implementação e validação, e sem
subfase não há onde parar entre uma coisa e outra.

| Subfase | Status | Entregável |
|---|---|---|
| **CAT-05A** | ✅ Concluída | Auditoria de reconciliação — 13 itens, blockers B-1 a B-6, divergências doc × código |
| **CAT-05B** | ✅ Concluída | Decisões de produto (B-1, B-2) e contratos das subfases seguintes |
| **CAT-05C** | ✅ Concluída | `ListingContext` + `ContextSanitizer` |
| **CAT-05D** | ⬜ | `ListingAssistant` interno, sem provider externo |
| **CAT-05E** | ⬜ | Antialucinação e `missing_information` |
| **CAT-05F** | ⬜ | Resiliência e fronteiras |
| **CAT-05G** | ⬜ | Testes, custo de consulta e segurança |
| **CAT-05H** | ⬜ | Validação real sobre os 75 itens e documentação final |

---

## CAT-01 — Auditoria e arquitetura ✅

**Concluída em 2026-08-26.** Prioritariamente documental; nenhum código alterado.

Entregue:

- baseline oficial acima;
- auditoria de `Product`, migrations, `ItemType`, `ContentCategory`,
  `ProductFaq`, `ProductQuestion`, `ProdutoForm`, `ProdutoIndex`, view Blade,
  API de lojista, middlewares, permissões, rotas, factories, seeders, testes,
  Docker, MySQL, Redis, filas e scheduler;
- arquitetura proposta, contratos, DTOs, tabelas e enums;
- riscos e plano de testes;
- `docs/CATALOG_INTELLIGENCE.md` e este arquivo;
- referência no roadmap geral e no README.

### Achados que mudam o plano

1. **Não existe cadastro de produto no admin.** Só o lojista cria itens. A
   CAT-09 integra em um fluxo, não em dois.
2. **Existem dois caminhos de escrita:** Livewire (`ProdutoForm`) e API REST
   (`Api/V1/Lojista/ProdutoController`), com regra duplicada. A inteligência
   precisa de porta única (`ListingAssistant`) para não virar terceira cópia.
3. **`short_description` não existe** — confirma a CAT-02.
4. **Não existe `ProductFactory`.** 22 arquivos de teste montam catálogo com
   `Product::create` à mão. Vira pré-requisito da CAT-04.
5. **Risco de autorização pré-existente** no `ProdutoForm` (ver
   `CATALOG_INTELLIGENCE.md` §2.4). Tratado fora desta trilha, pela **SEC-02**,
   concluída antes da CAT-02.
6. **FULLTEXT não existe em SQLite**, e a suíte roda em SQLite. Decisão de
   estratégia de teste fica na CAT-04.

---

## CAT-02 — Evolução do modelo de catálogo ✅

**Concluída em 2026-08-26.** Baseline 476 → final 498 testes, 0 falhas.

`short_description` entrou como campo real do domínio — `VARCHAR(500)`, nullable,
antes de `description` na tabela. Ele existe por si: cards, busca,
compartilhamento, SEO e app mobile precisavam de um resumo escrito para ser lido
fora da página, independentemente de qualquer IA. O assistente da CAT-05 vai
preencher esse campo; não foi ele que criou a necessidade.

**500 caracteres** porque o consumidor concreto de hoje — a meta description em
`loja/produto.blade.php` — corta em 160, e 500 dá folga para um card de duas ou
três linhas sem o resumo virar um segundo `description`. `VARCHAR` e não `TEXT`
para manter aberta a porta de um índice com prefixo.

**Nullable, sem backfill.** Os 75 itens existentes seguiram com `NULL`. Copiar
`description` para o resumo produziria 75 resumos errados de uma vez — texto
longo cortado não é resumo.

Entregue: migration aditiva, `Product::$fillable`, `ProdutoForm` (propriedade,
mount, validação, payload), campo próprio no Blade com rótulos que separam
resumo de descrição completa, `ProdutoRequest`/`ProdutoController`/`ProductResource`
na API, `ProductFactory` e `ExpositorFactory`, e a meta SEO do item passando a
preferir o resumo quando existe.

**SEC-02 preservada** — os três `guardOwnership()` seguem no lugar e
`expositor_id` continua fora do payload de update. Teste dedicado prova que o
campo novo não abriu brecha lateral.

**Decisões de escopo:** atributos estruturados (`material`, `technique`, `color`,
`style`, `usage`) e `keywords`/`tags` **não** entraram em `products`. São
multivalorados e pertencem a estruturas `catalog_*` próprias, nas CAT-03/CAT-04.
Nenhum botão de IA foi adicionado ao formulário.

## CAT-03 — Base de conhecimento ✅

**Concluída em 2026-08-26.** Baseline 498 → final 542 testes, 0 falhas.

A memória estrutural da trilha. Entregou **memória, não inteligência**: nada aqui
deduz, sugere ou gera texto — a CAT-03 termina exatamente onde a CAT-04 começa.

| Subfase | Status |
|---|---|
| CAT-03A — Auditoria do domínio existente | CONCLUÍDA |
| CAT-03B — Schema da base de conhecimento | CONCLUÍDA |
| CAT-03C — Models, enums e normalização | CONCLUÍDA |
| CAT-03D — Proveniência e governança | CONCLUÍDA |
| CAT-03E — Relações e associação com catálogo | CONCLUÍDA |
| CAT-03F — Seed inicial controlado | CONCLUÍDA |
| CAT-03G — Testes e hardening | CONCLUÍDA |
| CAT-03H — Validação final | CONCLUÍDA |

**Auditoria.** `content_categories` existe e é taxonomia de **navegação** — um
nível, escopada por eixo, sem sinônimo, sem relação entre categorias, sem
proveniência e sem aprovação. Não serve como base de conhecimento e não foi
reaproveitada como tal; as duas permanecem separadas, e a junção acontecerá via
produto na CAT-04. Nenhuma tabela `catalog_*`, `knowledge`, `tag` ou `term`
existia.

**Quatro tabelas**, todas fora de `products`: `catalog_knowledge_entries`,
`catalog_knowledge_terms`, `catalog_knowledge_relations` e
`catalog_product_knowledge`. Unicidade no banco, não em `if (! exists())`.

**Governança.** Origem assinada por pessoa nasce aprovada; todo o resto nasce
rascunho. Status nunca sobe sozinho, e origem de menor confiança não sobrescreve
a de maior. É o que impede "produto cadastrado → conhecimento aprovado".

**Base inicial de 28 conceitos**, escolhidos lendo os 75 itens reais do catálogo
— xilogravura porque existe uma gravura, ervas medicinais porque existem
tinturas e cremes, costura porque existe ajuste de roupa. Idempotente.

**Fora de escopo por decisão registrada:** painel administrativo e permissão
(pertencem à CAT-08), ServiceProvider e config (nada a registrar nesta fase),
inferência produto → conceito (CAT-04). Detalhes em
`CATALOG_INTELLIGENCE.md` §3A.8.

---

## CAT-04 — Motor de similaridade ✅

**Concluída em 2026-08-26.** Baseline 542 → final 577 testes, 0 falhas.

O sistema passou a responder "quais conceitos se aplicam a este item?" e "quais
itens se parecem com ele?" — sempre com o motivo junto. Determinístico,
explicável, sem nenhuma chamada externa.

| Subfase | Status |
|---|---|
| CAT-04A — Auditoria da superfície de similaridade | CONCLUÍDA |
| CAT-04B — Representação normalizada do produto | CONCLUÍDA |
| CAT-04C — Matching produto → conhecimento | CONCLUÍDA |
| CAT-04D — Associação produto → conhecimento | CONCLUÍDA |
| CAT-04E — Similaridade produto → produto | CONCLUÍDA |
| CAT-04F — Score explicável | CONCLUÍDA |
| CAT-04G — Testes, performance e segurança | CONCLUÍDA |
| CAT-04H — Validação real e documentação | CONCLUÍDA |

**Auditoria.** Dos campos disponíveis, `name` (75/75) e `description` (75/75)
carregam o sinal; `category_id` cobre 65/75; `short_description` está vazia nos
75 (a CAT-02 não fez backfill) e por isso não contribui hoje, embora já seja
lida. A contagem de palavras dos itens reais mostrou que o catálogo é
majoritariamente texto de seeder — "para" (52), "expositor" (46),
"demonstração" (28) lideram sem dizer nada sobre o item. Sinal real:
"artesanal" (11), "cerrado" (16), "cerâmica" (9), "solidária" (10).

**Casamento por frase**, nunca por token solto — conceitos compostos como
"ervas medicinais" e "economia solidária" se perderiam. E "solidária" em
"Consultoria Solidária" corretamente **não** casa com economia solidária.

**Candidato ≠ associação.** O matcher não escreve nada; só evidência direta vira
registro no pivot. Contexto por relação nunca é persistido.

**Alcance global**, atravessando lojistas — é o objetivo declarado da trilha.
Só item ativo, só campo público. **SEC-02 intacta**: nada aqui escreve em
produto.

**Backfill não executado.** O comando existe com `--dry-run`; a passagem sobre
os 75 itens reais deu 45 com evidência direta e 30 sem nenhuma. Persistir em
massa fica como decisão humana informada.

Detalhes de algoritmo, pesos e limitações em `CATALOG_INTELLIGENCE.md` §3B.

---

## CAT-DOM-01 — Produto mestre × oferta do expositor ✅

**Fase intermediária de domínio, inserida entre CAT-04 e CAT-05 sem renumerar as
fases existentes.** Decisão comercial, motivação e critério de aceite em
[`CAT_DOM_01_DECISAO_PRODUTO_MESTRE_E_OFERTAS.md`](CAT_DOM_01_DECISAO_PRODUTO_MESTRE_E_OFERTAS.md).

Um produto não deixa de existir porque o expositor que o cadastrou saiu da
Feira. Hoje `Product` responde a duas perguntas de uma vez — *o que é este
item?* e *quem vende, por quanto e em que condições?* — e a CAT-05 construiria o
assistente de conteúdo em cima dessa ambiguidade. A fase separa identidade de
catálogo (`Product`) de relação comercial (`ProductOffer`).

| Subfase | Status |
|---|---|
| CAT-DOM-01A — Auditoria completa do domínio atual | CONCLUÍDA |
| CAT-DOM-01B — Arquitetura e invariantes | CONCLUÍDA |
| CAT-DOM-01C — Estratégia de migração | CONCLUÍDA |
| CAT-DOM-01D — Implementação do modelo | CONCLUÍDA |
| CAT-DOM-01E — Migração das superfícies comerciais | CONCLUÍDA |
| CAT-DOM-01F — Segurança e isolamento (SEC-02) | CONCLUÍDA |
| CAT-DOM-01G — Catalog Intelligence | CONCLUÍDA |
| CAT-DOM-01H — Testes, dados reais e documentação | CONCLUÍDA |

**Baseline da fase** (2026-08-27): commit `3cab7e2`, working tree limpa,
**577 passed · 1568 assertions · 0 failures** em 592,07s.
**Resultado:** **594 passed · 1626 assertions · 0 failures** (após revisão pré-commit), três migrations
aditivas, 75 ofertas backfilladas 1:1 com **zero divergências** no MySQL real.

**Decisões humanas da fase.** (H-1) Item de expositor inativo **sai das
vitrines** — o produto e o conhecimento continuam no catálogo e voltam a
aparecer quando outro expositor criar uma oferta. (H-2) As colunas comerciais
**permaneceram** em `products` naquela fase, sem migration destrutiva; ficaram
em espelho, mantido pela `SaveProductWithOffer`, como dívida D-1.

> **A dívida D-1 foi quitada.** A CAT-DOM-02C encerrou a escrita no espelho e a
> **CAT-DOM-02H removeu as doze colunas** do schema — `products` foi de 29 para
> 17 colunas. A decisão (H-2) descreve o estado daquela fase, e não o atual.
> Ver [`CAT_DOM_02H_REMOCAO_COLUNAS_LEGADAS_PRODUCTS.md`](CAT_DOM_02H_REMOCAO_COLUNAS_LEGADAS_PRODUCTS.md).

**Auditoria (01A).** 75 produtos, todos ativos, todos com expositor e com preço;
**zero** nomes repetidos entre lojas, **zero** pedidos e itens de carrinho.
O backfill é **1 produto → 1 oferta**, sem fusão e sem perda: a fase cria a
capacidade de um produto ter várias ofertas, não faz nenhum produto existente
passar a ser compartilhado — deduplicar é proibido pelo §9 da decisão.

Achados que mudam o plano:

1. **O histórico já é snapshot.** `cart_items` e `order_items` gravam
   `expositor_id`, preço e nome do produto. A integridade de pedidos passados
   não depende desta fase; `product_offer_id` entra como coluna aditiva.
2. **Visibilidade pública é incoerente.** `/produtos` não filtra expositor
   ativo, `/loja/{slug}` filtra. Item de loja inativa aparece na listagem e dá
   404 ao ser clicado — o achado que originou a fase. Regra correta é decisão
   de produto, tratada na 01B.
3. **O conhecimento já está no lugar certo.** `catalog_product_knowledge` aponta
   para `products`, nunca para expositor: a 01G é verificação, não reescrita.
4. **A regra de cadastro está duplicada** entre `ProdutoForm` (Livewire) e
   `ProdutoController::buildData()` (API). Separar produto e oferta dobraria a
   duplicação — a 01D extrai uma action compartilhada.

Matriz de campos completa em `CAT_DOM_01_...md` §19.4.

**Revisão pré-commit.** Cinco achados corrigidos antes do commit — dois HIGH: a
home lia o espelho legado (e com N+1), e `toggleActive` escrevia oferta e espelho
fora de transação. Detalhes em `CAT_DOM_01_...md` §29.

**Multi-oferta: estrutura pronta, funcionalidade não exposta.** Nenhum caminho da
aplicação cria uma segunda oferta sobre um produto existente, e D-1/D-2 são
bloqueadores antes de expor isso.

**Restrições da fase:** nada de CAT-05, IA externa, embeddings, merge automático
de produtos, `migrate:fresh`, Pint global ou enfraquecimento da SEC-02.

---

## CAT-05 — Assistente de conteúdo 🔍

`ListingContext` → `ListingAssistant` → `ListingSuggestion` estruturado:
`suggested_name`, `short_description`, `description`, `keywords`,
`missing_information`.

Antialucinação com teste explícito: atributo não informado não aparece no texto;
falta vira pedido de informação.

> **Fase subdividida em CAT-05A…CAT-05H pela auditoria de reconciliação**
> (2026-09-01). A subdivisão e os seus motivos estão em
> [`CAT_05A_AUDITORIA_DE_RECONCILIACAO.md`](CAT_05A_AUDITORIA_DE_RECONCILIACAO.md),
> junto com o estado real do módulo, as divergências entre documentação e código
> e os seis blockers levantados. A tabela das subfases está em **Fases**, acima.

**Decisões de produto, tomadas na CAT-05B** (detalhe, justificativa e decision
log em [`CAT_05B_DECISOES_DE_PRODUTO_E_CONTRATOS.md`](CAT_05B_DECISOES_DE_PRODUTO_E_CONTRATOS.md)):

- **B-1 — a CAT-05 só sugere, nunca aplica.** Nenhum caminho da fase escreve em
  `Product`, chama `SaveProductWithOffer` ou aciona
  `ProductPolicy::updateCanonical`. `ListingSuggestion` é sempre
  pré-visualização. Aplicar a sugestão a um item existente — inclusive o caso do
  lojista sem delegação canônica — é **CAT-09**.
- **B-2 — a similaridade passa a ler apenas o que está vigente.** `FindSimilarProducts`
  filtrava `products.is_active` solto, que depois da D-CAT-10 significa validade
  canônica e não visibilidade. Passou a exigir a mesma vigência de
  `ProductOffer::scopeVigente()`. Fecha a dívida **M-17**, que já estava
  endereçada nominalmente a esta fase.

**CAT-05C — o insumo e a fronteira**
([`CAT_05C_LISTING_CONTEXT_E_SANITIZER.md`](CAT_05C_LISTING_CONTEXT_E_SANITIZER.md)).
`ListingContext` carrega só identidade de catálogo e não tem parâmetro que
aceite `ProductOffer` ou `Expositor` — a fronteira é estrutural, não
disciplinar. `ContextSanitizer` faz a minimização na construção, importando de
`SaveProductWithOffer` a lista do que é condição de venda em vez de copiá-la.
Duas dívidas ficam registradas e rastreadas: **C-1**, `knownAttributes` é
protegido por lista de proibição e quem o preencher na CAT-09 deve mapear campo
a campo, nunca repassar payload em bloco; **C-2**, texto livre não é redigido, e
o destino — CAT-05F ou CAT-10 — segue em aberto.

---

## CAT-06 — IA externa (opcional) ⬜

`CatalogAiProvider` + `FakeCatalogAiProvider` + `NullCatalogAiProvider`.
Threshold de fallback configurável e documentado. Sem credencial, sem
fornecedor, sem segredo versionado. Ausência de IA externa não quebra cadastro.

---

## CAT-07 — Feedback humano e memória ⬜

Sugestão, conteúdo aplicado, conteúdo final, desfecho, origem, confiança,
momento. Sem fine-tuning.

---

## CAT-08 — Interface administrativa ⬜

Visualizar, criar, editar, desativar e revisar conhecimento; identificar
conteúdo gerado; acompanhar feedback. Permissão própria server-side —
lojista não administra conhecimento global.

---

## CAT-09 — Integração no cadastro ⬜

Botão de gerar sugestões no formulário do lojista, pré-visualização, aplicação
seletiva, edição livre, salvamento normal.

**Aplicar sugestão não salva o produto.**

> O risco de autorização do `ProdutoForm` foi resolvido pela SEC-02.

---

## CAT-10 — Observabilidade, custos e segurança ⬜

Métricas por chamada externa (provider, modelo, tokens, custo, duração,
sucesso/falha/fallback) sem conteúdo sensível em log. Teste explícito de que a
falha da inteligência não impede cadastro manual. Testes de prompt injection.

---

## CAT-11 — Hardening, testes e documentação final ⬜

Revisão de cobertura, comportamentos críticos no MySQL real, documentação final
e dívidas remanescentes.

---

## Protocolo por fase

1. working tree limpa;
2. baseline registrado;
3. implementar;
4. testar;
5. revisar diff;
6. `git diff --check`;
7. relatório e **parada antes do commit**.

Relatório com: FASE · STATUS · BASELINE · TESTES FINAIS · ARQUIVOS CRIADOS ·
ARQUIVOS ALTERADOS · MIGRATIONS · DECISÕES · RISCOS · GIT STATUS ·
RECOMENDAÇÃO DE COMMIT · PRÓXIMA FASE.

Sem push sem autorização explícita. Sem Pint global. Sem refatoração oportunista.

---

## Dívidas e riscos abertos

| # | Item | Severidade | Onde |
|---|---|---|---|
| 1 | ~~`ProdutoForm` não verifica propriedade do produto~~ | — | **Resolvido pela SEC-02** |
| 2 | Regra de cadastro duplicada entre Livewire e API | Média | §2.4 |
| 3 | ~~Ausência de `ProductFactory`~~ | — | **Resolvido na CAT-02** (com `ExpositorFactory`) |
| 4 | FULLTEXT indisponível em SQLite | Média | §6 |
| 5 | `ProdutoForm::save()` assume `auth()->user()->expositor` não nulo | Baixa | §2.5 |
| 6 | `product_faqs` vazio — sem corpus de FAQ | Baixa | §2.7 |
| 7 | ~~Catálogo por eixo e home não filtram expositor inativo~~ | — | **Resolvido na CAT-DOM-01** (decisão H-1) |
| 8 | Colunas comerciais legadas em `products` (espelho, sem leitores) | Média — **bloqueia multi-oferta** | CAT-DOM-01, dívida D-1 |
| 9 | Imagens, FAQs, perguntas e curso AVA são autorais do vendedor mas ficam no produto mestre | Média — **bloqueia multi-oferta** | CAT-DOM-01, dívida D-2 |
| 10 | `order_items.expositor_id` é `CASCADE`: excluir expositor apaga itens de pedido | Alta — **preexistente**, fora da trilha | CAT-DOM-01 §29.7 |
| 11 | Estoque nunca é decrementado nem validado no checkout | Média — **preexistente** | CAT-DOM-01 §29.7 |
| 12 | `products.slug` é UNIQUE global e o cadastro não desambigua nomes iguais | Média — **preexistente** | CAT-DOM-01 §29.7 |
