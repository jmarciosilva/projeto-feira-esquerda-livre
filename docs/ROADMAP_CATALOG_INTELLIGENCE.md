# Roadmap — Catalog Intelligence

Roadmap executável da trilha de inteligência de catálogo da **Feira Esquerda
Livre**. Documento arquitetural:
[`CATALOG_INTELLIGENCE.md`](CATALOG_INTELLIGENCE.md).

Trilha independente de CI-01…CI-09, SEC-01 e GOV-01. Não antecipa a GOV-02.

---

## Situação

| | |
|---|---|
| Fase atual | **CAT-02 concluída** — evolução do modelo de catálogo |
| Próxima | CAT-03 — base de conhecimento |
| Código do módulo | Nenhum arquivo de `App\CatalogIntelligence` ainda — a CAT-02 mexeu só no domínio de catálogo |
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
| **CAT-03** | ⬜ Próxima | Base de conhecimento |
| **CAT-04** | ⬜ | Motor de similaridade |
| **CAT-05** | ⬜ | Assistente de conteúdo |
| **CAT-06** | ⬜ | Integração opcional com IA externa |
| **CAT-07** | ⬜ | Feedback humano e memória |
| **CAT-08** | ⬜ | Interface administrativa da inteligência |
| **CAT-09** | ⬜ | Integração no cadastro |
| **CAT-10** | ⬜ | Observabilidade, custos e segurança |
| **CAT-11** | ⬜ | Hardening, testes e documentação final |

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

## CAT-03 — Base de conhecimento ⬜

`catalog_knowledge_entries`, `catalog_knowledge_terms`,
`catalog_knowledge_relations` (nomes finais a confirmar), com enum
`KnowledgeOrigin` (`human_curated`, `approved_listing`, `external_ai`,
`derived`, `seed`) e noção de confiança.

Seed de conhecimento identificável como desenvolvimento — nunca centenas de
itens fictícios passando por conhecimento validado.

---

## CAT-04 — Motor de similaridade ⬜

Níveis 1 (categoria + termos + atributos) e 2 (textual) sobre a infraestrutura
existente. `EmbeddingProvider` como contrato, sem acoplar fornecedor. Funciona
sem embeddings.

Decidir aqui a estratégia FULLTEXT vs. SQLite. `ProductFactory` já existe, criada
na CAT-02.

---

## CAT-05 — Assistente de conteúdo ⬜

`ListingContext` → `ListingAssistant` → `ListingSuggestion` estruturado:
`suggested_name`, `short_description`, `description`, `keywords`,
`missing_information`.

Antialucinação com teste explícito: atributo não informado não aparece no texto;
falta vira pedido de informação.

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
