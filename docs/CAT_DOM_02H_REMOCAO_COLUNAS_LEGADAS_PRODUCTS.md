# CAT-DOM-02H — Remoção das Colunas Legadas de `products`

**O espelho comercial sai do schema.**

A 02C parou de escrever nas doze colunas que `products` mantinha como cópia da
oferta. A 02D deu à oferta o conteúdo, a 02E migrou writers e readers, a 02F o
ownership e a 02G a seleção. As colunas ficaram lá, congeladas. Esta fase as
remove — depois de provar, uma a uma, que ninguém as usa.

Decisões congeladas:
[`02B`](CAT_DOM_02B_AUTORIDADE_E_CURADORIA_DO_CATALOGO.md) ·
[`02E`](CAT_DOM_02E_WRITERS_READERS_E_CUTOVER.md) ·
[`02F`](CAT_DOM_02F_ISOLAMENTO_AUTORIZACAO_E_GOVERNANCA.md) ·
[`02G`](CAT_DOM_02G_PREPARACAO_MULTI_OFERTA_AVA_SLUG.md)

---

## 1. Baseline

| | |
|---|---|
| Branch / HEAD | `main` · `861c193476d78b1faad9e79fb63a3088acfff0d3` |
| Parent | `bc03f331ab01a0993f2222ef0bdc109cffacdacb` |
| Suíte antes da fase | **1030 passed · 2902 assertions · 0 failed** (1078s) |
| MySQL / PHP / Laravel | 8.4.11 · 8.3.33 · 12.65.0 |

## 2. O princípio

Nenhuma coluna sai porque "parece antiga" ou porque "já existe na oferta". A
sequência é **auditoria → prova → remoção**, e cada candidata precisou de zero
writer, zero reader, zero autorização, zero FIN-SEC, zero AVA e zero contrato
público.

`products` tinha **29 colunas**. Ficou com **17**.

## 3. Inventário — o schema real, não o presumido

Os candidatos são exatamente `SaveProductWithOffer::ESPELHOS_COMERCIAIS_LEGADOS`,
a lista que a 02C nomeou. Não foram presumidos doze: foram conferidos contra o
`information_schema` antes de qualquer decisão.

| Coluna | Tipo | Null | Default | Destino | Removida? |
|---|---|---|---|---|---|
| `price` | `decimal(10,2)` | sim | — | `product_offers.price` | **sim** |
| `price_type` | `varchar(255)` | sim | — | `product_offers.price_type` | **sim** |
| `modality` | `varchar(255)` | sim | — | `product_offers.modality` | **sim** |
| `duration_min` | `smallint unsigned` | sim | — | `product_offers.duration_min` | **sim** |
| `weight` | `decimal(8,3)` | sim | — | `product_offers.weight` | **sim** |
| `height` | `decimal(8,2)` | sim | — | `product_offers.height` | **sim** |
| `width` | `decimal(8,2)` | sim | — | `product_offers.width` | **sim** |
| `length` | `decimal(8,2)` | sim | — | `product_offers.length` | **sim** |
| `has_stock` | `tinyint(1)` | não | `1` | `product_offers.has_stock` | **sim** |
| `stock_quantity` | `int unsigned` | sim | — | `product_offers.stock_quantity` | **sim** |
| `is_featured` | `tinyint(1)` | não | `0` | `product_offers.is_featured` | **sim** · tinha índice |
| `sort_order` | `smallint unsigned` | não | `0` | `product_offers.sort_order` | **sim** |

### O que ficou, e por quê

| Coluna | Motivo |
|---|---|
| `is_active` | **Validade canônica** do item, da curadoria (D-CAT-10). Nunca foi espelho: existe nas duas tabelas com significados diferentes |
| `expositor_id` | **Proveniência** (D-CAT-11) — quem trouxe o item ao catálogo. Não é ownership, e ownership não é motivo para apagar história |
| `canonical_delegate_expositor_id`, `canonical_delegated_at`, `canonical_delegation_revoked_at` | Governança da delegação (D-CAT-09) |
| `images`, `image_path` | **Imagem canônica** do catálogo, que a 02E preservou e que o fallback de leitura ainda usa |
| `slug`, `name`, `description`, `short_description`, `item_type`, `category_id`, `is_digital` | Identidade do item |

## 4. Auditoria

### Writers

| Origem | Achado |
|---|---|
| Runtime (`SaveProductWithOffer`, `ProdutoForm`, API, jobs, commands) | **zero** — a 02C encerrou a escrita, e a varredura por `$product->price`, `products.<coluna>` e `DB::table('products')` não achou nada |
| `ProductFactory` | já removia os doze do produto e os roteava para a oferta |
| `SincronizaOfertaDoItem` (trait de seed) | já excluía os doze do `Product` |
| **`ProductLogisticDataSeeder`** | **writer residual** — um bloco rotulado *"Espelho legado (dívida D-1)"* gravava `weight/height/width/length` em `products`. **Removido nesta fase** |

### Readers

**Zero de runtime.** Os próprios scopes do `Product` já liam tudo da oferta e
diziam isso em comentário: `ofertaVigente()` ordena por `product_offers.price`,
`scopeComOfertaVigente()` filtra `product_offers.is_featured`,
`scopeOrdenadoPelaVitrine()` usa uma subconsulta sobre
`product_offers.sort_order` justamente para não depender de `products`.

O único acerto da varredura por `$item->sort_order` foi `MenuItem`, outro model.

### Autorização

Nenhuma candidata participa de Policy, Gate, middleware ou escopo de ownership.
Ownership comercial continua em `ProductOffer::pertenceAoExpositorDe()` (02F);
autoridade canônica em `ProductPolicy` (02C).

### FIN-SEC

**Independente.** Nenhuma leitura em `OrderService`, `ReserveOrderStock`,
`ConsumeOrderStock`, `ReleaseOrderStock`, pagamentos, checkout ou webhooks.
Preço, estoque e vendedor históricos vêm de `product_offers`, `order_items` e dos
snapshots financeiros.

### AVA

**Independente.** A origem comercial da matrícula vem de
`order_items.product_offer_id` (02G), e nunca de espelho em `products`.

### API

**Contrato preservado.** `ProductResource` já emitia os doze campos **a partir da
oferta** (`$oferta?->price`, `$oferta?->stock_quantity`, …). A resposta pública
tem exatamente a mesma forma e a mesma fonte de antes — a remoção não a alcança.

## 5. Dados reais antes do drop

| | |
|---|---|
| Linhas em `products` | 75 |
| Com `price` | 75 · faixa `0.00` – `120.00` |
| `price_type` distintos | 3 · `modality` distintos | 2 |
| `is_featured = 1` | 56 · `sort_order` | 1 – 102 |
| **Divergência contra `product_offers`** | **zero, em todas as colunas** |

A ausência total de divergência é a prova de que os valores estavam congelados
desde o cutover da 02C e idênticos ao destino: a remoção não perde nada que já
não esteja em `product_offers`.

## 6. Migration

`2026_08_31_200001_remove_legacy_offer_columns_from_products_table.php`

O `up()` derruba o índice `products_is_featured_index` e as doze colunas. Nada
mais: nenhuma feature, tabela ou índice não relacionado.

### Rollback — e o que ele não faz

O `down()` recria a **estrutura**: tipo, nullability, default, posição e o
índice. Validado em MySQL real, coluna a coluna.

> **Rollback de schema não é restauração de dados.** Os valores das doze colunas
> são apagados no `up()` e **não voltam** — o `down()` devolve colunas vazias.
> É aceitável porque a verdade comercial vive em `product_offers` desde a 02C, e
> a paridade era exata.

## 7. Ciclo validado no MySQL 8.4 real

```text
1. estado inicial              29 colunas em products
2. migrate --pretend           só DROP INDEX + DROP COLUMN
3. migrate                     17 colunas · product_offers intacta (75 preços)
4. migrate:rollback --step=1   12 colunas recriadas
                               tipos/null/defaults idênticos ao original
                               products_is_featured_index recriado
                               dados: 0 de 75 linhas com price (perda documentada)
5. migrate                     17 colunas · 0 migrations pendentes
```

Nenhum `migrate:fresh`, `migrate:refresh`, `db:wipe` ou `docker compose down -v`.

## 8. Um erro meu, e o que ele ensinou sobre a factory

Ao limpar o `$fillable` do `Product`, presumi que a `ProductFactory` deixaria de
capturar os campos comerciais — e reescrevi a captura de `afterMaking` para um
override de `newModel()`. **A premissa era falsa:** as factories do Laravel
gravam dentro de `Model::unguarded()`, então `$fillable` não filtra nada ali. O
`afterMaking` nunca esteve em risco, e a "correção" quebrou quatro testes.

Revertido. O bloco original continua sendo o que faz o açúcar de entrada
funcionar — `Product::factory()->create(['price' => 120])` segue entregando o
preço à oferta —, e agora com um motivo a mais para existir: sem ele, a chamada
tentaria escrever numa coluna que não existe.

## 9. Testes

### Novo

`ColunasLegadasDeProductRemovidasTest` — 7 testes: nenhum dos doze existe em
`products`; os doze continuam em `product_offers`; as catorze colunas canônicas
permanecem; o model não os declara em `$fillable` nem em `casts`; a factory
roteia o comercial para a oferta; `is_active` continua no produto; a vitrine
ordena pela oferta.

### Evoluídos, e por quê

Seis testes provavam a regra **construindo a divergência** — gravavam um valor
diferente no espelho e exigiam que a aplicação lesse a oferta. Sem as colunas, a
divergência é impossível de construir, e a prova muda de natureza: de "o espelho
ficou intacto" para "não há espelho".

| Teste | Antes | Depois |
|---|---|---|
| `AutoridadeCanonicaTest::campo_comercial_e_gravado_so_na_oferta` | comparava o legado antes/depois | oferta recebeu · coluna não existe |
| `…::criacao_real_nao_copia_os_espelhos_anulaveis` | 9 dos 12 (os `NOT NULL` não davam `assertNull`) | **os 12**, sem exceção |
| `…::item_novo_nao_alimenta_as_colunas_comerciais_legadas` | `assertNull` no legado | `assertArrayNotHasKey` |
| `…::factory_nao_grava_dado_comercial_no_produto` | idem | idem |
| `…::states_de_servico_e_cuidado…` | idem | idem |
| `…::seeder_nao_usa_products_como_area_de_passagem_comercial` | idem | idem |
| `IntegridadeDoCatalogoTest` — 3 testes da home | envelheciam o espelho para provar que a home lê a oferta | a home lê a oferta; o helper `envelhecerEspelho()` foi removido, não tem mais onde escrever |
| `ProdutoMestreOfertaTest::salvar_pela_action_nao_alimenta_mais_o_espelho_legado` | comparava o preço legado | `assertArrayNotHasKey` |

Nenhum teste foi apagado sem substituição, e nenhum foi mantido só para
preservar um campo que a arquitetura decidiu remover.

## 10. Decision Log

> **D-02H-1** — Uma coluna legada só é removida após provar zero writer e zero
> reader ativo. Auditoria, prova e só então remoção.
>
> **D-02H-2** — `ProductOffer` é a autoridade comercial; `products` não mantém
> espelho comercial depois do cutover.
>
> **D-02H-3** — `products.is_active` permanece canônico e **não** é coluna
> legada.
>
> **D-02H-4** — `products.expositor_id` permanece proveniência, não ownership, e
> não é removido por existir `product_offers.expositor_id`.
>
> **D-02H-5** — Rollback de schema não restaura valores eliminados.
>
> **D-02H-6** — FIN-SEC não depende das colunas removidas.
>
> **D-02H-7** — O AVA não reconstrói `ProductOffer` a partir de espelho em
> `products`.
>
> **D-02H-8** — A remoção de legado **não** habilita multi-oferta.
>
> **D-02H-9** — Nenhum fallback silencioso para `products` é permitido onde
> `ProductOffer` é a autoridade comercial.

## 11. Fronteiras

02I não iniciada · multi-oferta **não habilitada** (o cadastro continua criando
uma oferta por produto, e o guard da 02F continua recusando terceiros) · nenhum
buy box, ranking ou seleção automática · FIN-SEC intocada · AVA não redesenhado ·
02F e 02G preservadas — `ResolveProductOffer`, `Contexto` e os guards de
ownership não foram tocados.

## 12. Dívidas remanescentes

Inalteradas desde a 02G: superfície de curadoria (G-1), workflow de proposta,
apresentação sob multi-oferta e governança de vinculação de oferta a `Product`.

**Pint:** `AvaEnrollment.php` e `CursoBuilder.php` mantêm violações de estilo
**preexistentes**, em regiões que esta fase não tocou.

---

**Status:** doze colunas removidas com prova, `products` reduzido de 29 para 17
colunas, rollback validado em MySQL real e a verdade comercial concentrada em
`product_offers`.
