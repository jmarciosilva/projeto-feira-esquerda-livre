# CAT-DOM-02I — Hardening Final e Encerramento da Fundação

**A fase que não constrói nada — só prova que o que foi construído se sustenta.**

Da 02A à 02H o catálogo foi separado em duas verdades. Esta fase existe para
**provar, endurecer e congelar** essa separação, e para deixar a fronteira difícil
de desfazer por engano.

Fases anteriores:
[`01`](CAT_DOM_01_DECISAO_PRODUTO_MESTRE_E_OFERTAS.md) ·
[`02B`](CAT_DOM_02B_AUTORIDADE_E_CURADORIA_DO_CATALOGO.md) ·
[`02D`](CAT_DOM_02D_ESTRUTURA_CONTEUDO_POR_OFERTA.md) ·
[`02E`](CAT_DOM_02E_WRITERS_READERS_E_CUTOVER.md) ·
[`02F`](CAT_DOM_02F_ISOLAMENTO_AUTORIZACAO_E_GOVERNANCA.md) ·
[`02G`](CAT_DOM_02G_PREPARACAO_MULTI_OFERTA_AVA_SLUG.md) ·
[`02H`](CAT_DOM_02H_REMOCAO_COLUNAS_LEGADAS_PRODUCTS.md)

---

## 1. Baseline

| | |
|---|---|
| Branch / HEAD | `main` · `e67ebf9f8b346169249b124a3cd65d6b643a8066` |
| Parent (02G) | `861c193476d78b1faad9e79fb63a3088acfff0d3` |
| Suíte antes da fase | **1037 passed · 2995 assertions · 0 failed** (576s) |
| MySQL / PHP / Laravel | **8.4.11** · **8.3.33** · **12.65.0** |

**Nenhuma migration. Nenhuma mudança de código de produção.** A 02I é auditoria,
invariantes e documentação — e o fato de não ter precisado corrigir nada é, em si,
o resultado que ela buscava.

## 2. A fronteira, em uma imagem

```text
Product                              ProductOffer
├── identidade / curadoria           ├── vendedor
├── slug, categoria, descrição       ├── preço, estoque, logística
├── imagem canônica                  ├── status comercial
├── is_active (validade)             ├── imagem comercial
├── expositor_id (proveniência)      └── FAQ comercial
└── delegação canônica

ProductQuestion  → Product (agrupamento) + ProductOffer (destinatário)
OrderItem        → ProductOffer histórica
AvaCourse        → Product canônico
AvaEnrollment    → origem comercial histórica, via order_split → order_item
```

## 3. Matriz de invariantes

| Invariante | Prova | Onde | MySQL real |
|---|---|---|---|
| `Product` é canônico | 17 colunas, todas de identidade/governança | `CatalogoHardeningFinalTest` · schema | ✅ |
| `ProductOffer` é comercial | os 12 campos vivem lá | idem | ✅ |
| Seller não controla `Product.is_active` | `can('updateStatus')` falso a lojista e a delegado | `Hardening` · `AutoridadeCanonica` | — |
| Seller não altera oferta alheia | A × B em formulário, painel e API | `OfertaIsolamentoComercial` | — |
| `products.expositor_id` não é ownership | proveniência não alcança a oferta de B | `Hardening` · `OfertaIsolamento` | ✅ |
| Delegação não é ownership comercial | nos dois sentidos, com revogação | `Hardening` · `AvaAutoridadeDoCurso` | — |
| Ter oferta não dá autoridade canônica | `updateCanonical` falso a quem só vende | `Hardening` · `AutoridadeCanonica` | — |
| Pergunta pertence ao contexto da oferta | destinatário por `product_offer_id` | `PerguntaAutoridadeDeResposta` | ✅ |
| FAQ comercial é da oferta | `product_offer_faqs`, sem fallback | `CutoverFaqComercial` · `Fronteira` | ✅ |
| Imagem comercial é da oferta | fallback é leitura, nunca escrita | `ConteudoComercialDaOferta` | ✅ |
| Curso é canônico do `Product` | `ava_courses.product_id` UNIQUE | `AvaOfertaHistorica` | ✅ |
| Matrícula preserva a oferta histórica | via `order_split → order_item` | `AvaOfertaHistorica` | ✅ |
| Carrinho recebe oferta determinada | 422 no ambíguo | `OfertaSelecaoExplicita` | — |
| Estoque opera na oferta | `disponivel()` da oferta | `Hardening` · `EstoqueTest` | ✅ |
| Preço opera na oferta | não há coluna no produto | `Hardening` · `ColunasLegadas` | ✅ |
| Slug não colide | três itens de mesmo nome | `Hardening` · `SlugMultiOferta` | ✅ |
| Oferta não é auto-selecionada | 0/1/2 + id explícito | `Hardening` · `OfertaSelecaoExplicita` | — |
| Multi-oferta não habilitada | um único ponto de criação + UNIQUE | `Hardening` | ✅ |

## 4. Auditoria de schema (I-1, I-10)

MySQL 8.4.11 real, 16 lotes de migration aplicados, **zero pendentes**.

`products` — **17 colunas**, e nenhuma delas comercial:

```text
id · item_type · expositor_id · canonical_delegate_expositor_id
canonical_delegated_at · canonical_delegation_revoked_at · category_id
name · slug · short_description · description · image_path · images
is_active · is_digital · created_at · updated_at
```

Consulta ao `information_schema` pelos doze espelhos: **0 achados**.

| Tabela | Colunas |
|---|---:|
| `products` | 17 |
| `product_offers` | 20 |
| `product_faqs` | 7 |
| `product_offer_faqs` | 7 |
| `product_questions` | 11 |
| `ava_courses` | 13 |
| `ava_enrollments` | 13 |
| `order_items` | 12 |
| `order_splits` | 14 |

### Chaves estrangeiras — cada `ON DELETE` conta uma decisão

| Tabela · coluna | Referência | Regra | Decisão |
|---|---|---|---|
| `order_items.product_offer_id` | `product_offers` | **SET NULL** | pedido é fato histórico (FIN-SEC-01B) |
| `order_items.product_id` | `products` | SET NULL | idem |
| `product_questions.product_offer_id` | `product_offers` | **SET NULL** | conteúdo do cliente sobrevive à loja (D-02F-5) |
| `product_offer_faqs.product_offer_id` | `product_offers` | **CASCADE** | FAQ é composição da oferta (02D §3.2) |
| `product_offers.product_id` / `expositor_id` | — | CASCADE | a oferta morre com o item ou com a loja |
| `products.expositor_id` | `expositores` | SET NULL | proveniência sobrevive à saída (D-CAT-11) |
| `products.canonical_delegate_expositor_id` | `expositores` | SET NULL | delegação some, item fica (D-CAT-09) |
| `ava_enrollments.order_split_id` | `order_splits` | SET NULL | origem comercial preservada (D-02G-5) |

## 5. Writers e readers do legado (I-2, I-3)

**Zero, em ambos.** Depois da 02H a garantia deixou de depender de vigilância:
não há coluna a ler nem a escrever. O `CatalogoHardeningFinalTest` fixa também o
estado do model — os doze fora de `$fillable` e de `casts`, fora de
`CAMPOS_DO_PRODUTO` e dentro de `CAMPOS_DA_OFERTA`.

## 6. Autorização consolidada (I-4)

| Eixo | Mecanismo | Admin passa por cima? |
|---|---|---|
| Autoridade **canônica** (`Product`, curso) | `ProductPolicy` + `Gate` | **sim**, e é desejado |
| Ownership **comercial** (`ProductOffer`) | `ProductOffer::pertenceAoExpositorDe()` | **não**, e é desejado |

A separação é deliberada e vem da SEC-02: `Gate::before` concede tudo a admin, e
admin **não tem expositor** — uma Policy responderia "pode" e o código seguinte
quebraria no expositor nulo.

### `products.expositor_id` — todos os usos classificados (I-4)

| Uso | Local | Classificação |
|---|---|---|
| Escrita na criação | `SaveProductWithOffer` | **proveniência** ✅ |
| `Product::expositor()` | model | relação `@deprecated`, documentada como proveniência ✅ |
| `Expositor::products()` | model | idem ✅ |
| Todas as demais menções | `ProductPolicy`, `ProductOffer`, `ProductQuestion`, `ResolveProductOffer`, `ProductQandA`, `Dashboard` | **docblocks explicando por que não se usa** ✅ |

Nenhum uso em autorização, resolução de vendedor, checkout ou autoria de curso. O
`Dashboard` conta por `$expositor->offers()` e explica no comentário por que
trocou a relação legada.

### Delegação canônica (I-4)

Toda a maquinaria vive em `Product` (`delegarCanonicoPara`, `delegaCanonicoPara`,
`temDelegacaoCanonicaAtiva`, `revogarDelegacaoCanonica`), e a única consumidora é
a `ProductPolicy`. Revogar encerra a autoridade **imediatamente** e **não toca na
oferta** — provado nos dois sentidos.

## 7. Seleção de oferta (I-7)

`ResolveProductOffer` + `Contexto`. Provado: 0 ofertas → nulo · 1 → resolve · 2
sem id → nulo · id correto → resolve exatamente aquela · id de outro item → nulo.
Com preços 900 × 9, continua recusando — não há caminho para a mais barata
vencer.

`Contexto::Compra` exige oferta vigente; `Contexto::Historico` aceita inativa,
porque pedido e matrícula apontam para o que foi vendido.

### As cinco ocorrências restantes de `ofertaVigente` / `first()`

| Local | Classificação |
|---|---|
| `CatalogoController` (3×) | apresentação pública |
| `ProductResource::oferta()` | apresentação / compatibilidade |
| `Lojista/ProdutoController:37` | fixa a oferta **do próprio** lojista, não seleciona |
| `Lojista/Ava/CursoIndex:27` | coleção já escopada ao próprio expositor; pelo `UNIQUE(product_id, expositor_id)` só pode conter uma |
| `Product::ofertaVigente()` | a definição da relação |

**Nenhuma decide de quem se compra, de quem se comprou ou quem responde.**

## 8. FIN-SEC (I-5)

**Intocada.** Nenhum fluxo financeiro lê preço ou estoque de `products` — e
depois da 02H isso é impossível. Preço, estoque, reserva, consumo e liberação
operam em `ProductOffer`; o histórico vive em `order_items` e `order_splits`,
com `expositor_name` e `unit_price` em snapshot.

`order_items.product_offer_id` é `SET NULL`: a oferta pode sumir, o pedido não.

## 9. AVA (I-6)

Curso canônico (`ava_courses.product_id` UNIQUE); autoridade sobre ele é
canônica (`ProductPolicy::updateCanonical`), não do dono da oferta; origem da
matrícula vem de `order_split → order_item.product_offer_id`, nunca de
`ofertaVigente`. A oferta comprada e depois recolhida **não** migra para a que
sobrou ativa.

## 10. Conteúdo comercial (I-8)

Imagem, FAQ e pergunta pertencem à oferta. O fallback de imagem
(`oferta → canônica → placeholder`) é **decisão de leitura e nunca persistência**
— copiar o caminho canônico para `ProductOffer.images` recriaria o
compartilhamento de arquivo físico que a 02D proibiu. A FAQ comercial **não**
faz fallback para a canônica nem concatena os dois conjuntos (D-02E-1).

## 11. Slug (I-9)

`products.slug` UNIQUE global, com desambiguação na criação. Três itens de mesmo
nome recebem três slugs. `product_offers` **não tem slug** e não precisa: a URL
comercial `/loja/{expositor}/{produto}` já identifica a oferta, e não existe rota
`/produto/{slug}` que precisasse desempatar entre vendedores.

## 12. Multi-oferta não habilitada (I-11)

| Pergunta | Resposta |
|---|---|
| Existe rota para o Seller B anexar oferta a `Product` existente? | **NÃO** |
| Existe Livewire para isso? | **NÃO** |
| Existe API pública para isso? | **NÃO** |
| `SaveProductWithOffer` cria mais de uma oferta automaticamente? | **NÃO** |
| O resolvedor do carrinho escolhe entre A/B sem `offer_id`? | **NÃO** |

**A prova é estrutural.** Existe **um único** `ProductOffer::create` em todo o
`app/` — `SaveProductWithOffer:161` —, dentro do ramo que cria o `Product` na
mesma transação. O `product_id` vem sempre de um item recém-criado; nenhuma
assinatura aceita id existente. Dois vendedores cadastrando o mesmo nome geram
**dois itens distintos**, com slugs distintos.

Segunda linha de defesa, no banco: `UNIQUE(product_id, expositor_id)`.

Os cenários A × B dos testes são montados por factory — **fixture estrutural, não
ativação**.

## 13. Gates

| Gate | Status | Evidência |
|---|:--:|---|
| **I-1** Schema consolidado | ✅ | 17 colunas · 0 espelhos |
| **I-2** Zero reader legado | ✅ | não há coluna a ler |
| **I-3** Zero writer legado | ✅ | model, allowlist e seeders limpos |
| **I-4** Autorização consolidada | ✅ | dois eixos, nos dois sentidos |
| **I-5** FIN-SEC íntegra | ✅ | financeiro na oferta e no snapshot |
| **I-6** AVA íntegro | ✅ | curso canônico · origem histórica |
| **I-7** Seleção determinística | ✅ | 0/1/2 + id, nunca a mais barata |
| **I-8** Conteúdo comercial isolado | ✅ | imagem, FAQ e pergunta na oferta |
| **I-9** Slug seguro | ✅ | três de mesmo nome, três slugs |
| **I-10** MySQL real | ✅ | 8.4.11 · FKs · UNIQUE · 0 pendentes |
| **I-11** Multi-oferta desabilitada | ✅ | um ponto de criação + UNIQUE |
| **I-12** Hardening antirregressão | ✅ | `CatalogoHardeningFinalTest` |
| **I-13** Documentação coerente | ✅ | ROADMAP e docs conferidos |

## 14. Decision Log

> **D-02I-1** — `Product` permanece a identidade canônica e não recebe de volta
> espelhos comerciais.
>
> **D-02I-2** — `ProductOffer` é a única autoridade comercial por vendedor.
>
> **D-02I-3** — `products.expositor_id` permanece somente proveniência.
>
> **D-02I-4** — Delegação canônica não concede ownership comercial, e ownership
> comercial não concede autoridade canônica.
>
> **D-02I-5** — Seleção de `ProductOffer` não pode depender de heurística
> implícita.
>
> **D-02I-6** — Histórico comercial nunca é reconstruído a partir da oferta
> vigente.
>
> **D-02I-7** — O AVA preserva o curso canônico e a origem histórica da
> matrícula.
>
> **D-02I-8** — Multi-oferta pode ser simulada por fixture para hardening, e
> permanece desabilitada no produto.
>
> **D-02I-9** — Nenhuma funcionalidade futura pode reintroduzir espelho
> `Product ← ProductOffer` sem nova decisão arquitetural explícita.
>
> **D-02I-10** — A CAT-DOM-02 encerra a **fundação**; funcionalidades
> inteligentes pertencem a outra trilha.

## 15. Dívidas — explicitamente fora desta fase

**Governança sem superfície.** Curadoria (G-1), workflow de proposta e vinculação
de oferta a `Product` existente continuam sem tela. A `ProductPolicy` decide
corretamente há quatro fases e quase nada a invoca a partir de uma interface —
e é justamente essa ausência que mantém multi-oferta fechada.

**Apresentação sob multi-oferta.** Vitrine, home, catálogo e `ProductResource`
usam `ofertaVigente`. Hoje é apresentação; no dia da ativação, *qual oferta o
card destaca* vira decisão de produto.

**SEO canônico sob multi-oferta.** Duas URLs para o mesmo item; avaliar
`rel=canonical` e sitemap.

**Fora de escopo, sem exceção:** habilitação real de multi-oferta, seller
linking, buy box, ranking de ofertas, IA, scoring, similaridade, recommendation
engine, trend/opportunity score, feature store e embeddings.

**Pint:** `AvaEnrollment.php`, `CursoBuilder.php`, `ProductQandA.php`,
`ProductFaqTest.php` e `ProductQandATest.php` mantêm violações de estilo
**preexistentes**, em regiões que esta trilha não tocou.

## 16. Conclusão

```text
CAT-DOM-02 — FUNDAÇÃO TECNICAMENTE CONCLUÍDA
```

`Product` é identidade; `ProductOffer` é comércio. Nenhum espelho comercial
permanece, nenhum fluxo depende das colunas removidas, nenhuma autorização
comercial deriva de proveniência ou delegação, o financeiro e o AVA continuam
íntegros, a seleção de oferta é explícita e multi-oferta permanece desabilitada
— com a arquitetura pronta para o dia em que for uma decisão, e não um acidente.

> A declaração de **PUBLICADA** depende de commit, push e validação remota, e
> não é feita aqui.
