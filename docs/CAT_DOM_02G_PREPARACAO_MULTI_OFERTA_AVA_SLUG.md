# CAT-DOM-02G — Preparação para Multi-Oferta, AVA e Slug

**Tirar os pressupostos de 1:1 do caminho, sem abrir a porta.**

A 02D disse onde o dado mora, a 02E quem escreve e de onde se lê, a 02F de quem
ele é. Esta fase responde a pergunta que sobra: **qual oferta?** — e a responde
sem inventar um critério que ninguém aprovou.

Decisões congeladas:
[`CAT_DOM_02B`](CAT_DOM_02B_AUTORIDADE_E_CURADORIA_DO_CATALOGO.md) ·
[`CAT_DOM_02E`](CAT_DOM_02E_WRITERS_READERS_E_CUTOVER.md) ·
[`CAT_DOM_02F`](CAT_DOM_02F_ISOLAMENTO_AUTORIZACAO_E_GOVERNANCA.md)

---

## 1. Baseline

| | |
|---|---|
| Branch / HEAD | `main` · `bc03f331ab01a0993f2222ef0bdc109cffacdacb` |
| Parent | `d0825a33c4c2b8f5823eaac4f3296390a82e05f4` |
| Suíte antes da fase | **992 passed · 2825 assertions · 0 failed** (666s) |
| MySQL / PHP / Laravel | 8.4.11 · 8.3.33 · 12.65.0 |

**Nenhuma migration.** Os três gates fecharam sem alterar o schema.

## 2. O risco que a fase remove

Enquanto cada `Product` tem uma `ProductOffer`, `first()`, `orderBy('id')` e
`ofertaVigente` dão **todos** a resposta certa. É o que torna o defeito
invisível: nenhum teste acusa, nenhuma tela quebra, e o critério só aparece
quando o segundo vendedor chega — já em produção.

E o critério não é neutro. Dizer "a mais barata ganha" ou "a mais antiga ganha" é
a regra de um **buy box**: uma decisão de produto, sobre de quem o cliente compra
e quem fica sem a venda. Ela não deve nascer de um `->first()` escrito quando o
mundo era 1:1.

## 3. Auditoria — todas as seleções de oferta

| Local | Mecanismo antes | Classificação | Depois |
|---|---|---|---|
| `Api/V1/CarrinhoController::store` | `ofertaVigente` | **blocker G-9** — decidia preço, loja e estoque | seletor + 422 |
| `ProductShareImageController` (editor) | `offers()->orderBy('id')->first()` | **G-9** — divulgava loja arbitrária | seletor |
| `StoreProductQuestionRequest` | regra própria (02E) | duplicação da mesma regra | seletor |
| `cliente/ava/aprendizado-index` | `ofertaVigente` | **G-10** — capa por estado atual | oferta de origem |
| `Lojista/Ava/CursoBuilder::mount` | "tenho oferta neste produto" | **blocker G-10** — autoridade sobre conteúdo canônico | `ProductPolicy::updateCanonical` |
| `Api/Lojista/CursoController::publicar` | idem | **blocker G-10** | `ProductPolicy::updateCanonical` |
| `Lojista/Ava/CursoIndex` | `offers->first()` escopado ao próprio expositor | listagem do que o lojista **vende** | inalterado, e correto |
| `Api/V1/CatalogoController` | `ofertaVigente` | apresentação/compat | inalterado |
| `ProductResource::oferta()` | `ofertaFixada ?? ofertaVigente` | apresentação/compat | inalterado |
| `catalogo/index`, `welcome` (blades) | `ofertaVigente` | apresentação | inalterado |
| `routes/web.php` (destaques, catálogo) | `with('ofertaVigente')` | eager load de apresentação | inalterado |
| `CartDrawer::addToCart` | `find($offerId)` | **já determinístico** | — |
| rota `loja.produto` | expositor + slug → oferta | **já determinístico** | — |
| `ProductSharePreviewController` | expositor + slug → oferta | **já determinístico** | — |
| `CartService::add` | recebe `ProductOffer` | **já determinístico** | — |
| `OrderService` | `item->product_offer_id` | **já determinístico** | — |

### Por que `ofertaVigente` pode ficar onde ficou

Ela ordena por preço e devolve a mais barata vigente. Num **card de vitrine** —
"a partir de R$ X, na loja Y" — isso é uma escolha de apresentação legítima e
reversível: o cliente clica e vai para a página da loja, onde a oferta é
explícita. O que ela não pode fazer é **decidir de quem se compra**, **de quem
se comprou** ou **quem responde**. Os três usos assim foram removidos.

Quando multi-oferta for ativada, os pontos de apresentação passam a ser decisão
de produto — *qual oferta o card destaca* — e aí sim exigem regra explícita.
Ficam listados no §9 como dívida datada.

## 4. G-9 — seleção explícita de oferta

### O seletor

`App\Actions\Catalog\ResolveProductOffer`, com `Contexto` ao lado.

```text
id informado            → valida que a oferta é DESTE produto; se não for, nula
id ausente + 1 oferta   → resolve pela cardinalidade determinística
id ausente + 0 ou >1    → nula
```

Devolve `null` em vez de lançar: o `FormRequest` transforma em 422 com erro de
campo, o controller em 403 — cada superfície recusa na convenção dela, e o
seletor não escolhe o formato da recusa.

**Nunca usa** `first()` · `latest()` · `oldest()` · `orderBy('id')` ·
`ofertaVigente()` · `products.expositor_id` · `canonical_delegate_expositor_id`.

### Contexto: comprar ≠ olhar para trás

| | |
|---|---|
| `Contexto::Compra` | exige oferta **vigente** — não se compra de loja fechada nem item recolhido |
| `Contexto::Historico` | aceita oferta **inativa** — pedido e matrícula apontam para o que foi vendido |

Misturar os dois seria fazer a oferta desativada ontem trocar de dono hoje.

### O carrinho da API

`POST /api/v1/carrinho/itens` ganhou `product_offer_id` **opcional**, no mesmo
padrão que a 02E adotou para as perguntas. Nenhum cliente quebra: `product_id`
sozinho continua funcionando quando o item tem uma oferta vigente só. O que
acabou foi a resolução por `ofertaVigente`.

### O material de divulgação

O lojista sempre gerou a imagem da própria loja — ownership da 02F, sem escolha a
fazer. O **editor** recebia `orderBy('id')->first()`: hoje correto porque só há
uma, amanhã publicaria o preço e o nome de uma loja escolhida pela ordem do
banco. Passou pelo seletor.

## 5. G-10 — AVA e a oferta de origem

### A decisão

> **D-02G-5 — O curso pertence ao `Product`; a compra é que é comercial.**
>
> `ava_courses.product_id` é `UNIQUE`: o conteúdo educacional é canônico, as
> aulas do item são as mesmas independentemente de quem o vende. O que muda
> entre vendedores é **a venda** — preço, prazo, suporte —, e a matrícula já a
> referencia por `order_split_id`.

### O caminho histórico já existia

```text
ava_enrollments.order_split_id
  → order_splits (expositor_id + expositor_name em snapshot)
  → order_items  (product_offer_id — a oferta efetivamente comprada)
```

Desde a FIN-SEC-01B. **Nada precisou ser migrado**; o que faltava era alguém
percorrê-lo em vez de perguntar ao catálogo como ele está hoje.

`AvaEnrollment::ofertaDeOrigem()` faz esse percurso, casando produto **e**
expositor do split — um pedido reúne itens de várias lojas, e o split é de uma
só. `expositorDeOrigemId()` responde a pergunta mais simples direto do snapshot.

**A oferta histórica é a registrada no `OrderItem`**, e não uma recalculada a
partir de produto + vendedor: o método devolve `$item->offer`, que lê
`order_items.product_offer_id`. O par produto+expositor serve apenas para
**localizar o item certo dentro do pedido**, nunca para redescobrir qual oferta
foi comprada.

E o par é suficiente para isso porque `product_offers` tem
`UNIQUE(product_id, expositor_id)` — `product_offers_product_expositor_unique`,
verificada no MySQL real. Um vendedor tem no máximo uma oferta por item, então
não há ambiguidade a resolver nem sob 1:N. Ainda assim, a autoridade da origem
continua sendo o id histórico, e não a constraint.

### O erro que isso evita

O aluno compra de B. B recolhe a oferta. `ofertaVigente` passa a devolver A, e a
plataforma reescreve de quem ele comprou porque o catálogo mudou depois. Há teste
que monta exatamente esse cenário.

Matrícula de cortesia (`order_split_id` nulo) devolve `null`: **não houve oferta
de origem**, e não "descubra qual foi".

### Quem administra o curso — a reconciliação

A primeira versão desta fase decidiu que o curso é canônico **e deixou a
autorização perguntando "tenho alguma oferta neste produto?"**. As duas coisas
não podem ser verdade ao mesmo tempo, e a revisão externa pegou a contradição.

O bug foi provado antes de ser corrigido, e era pior do que o registro sugeria —
errava **dos dois lados**:

| | antes | depois |
|---|---|---|
| Vendedor que só acrescentou uma oferta abre o `CursoBuilder` | **conseguia** | 403 |
| Esse vendedor publica/despublica pela API | **conseguia** | 404 |
| Revogar a delegação encerra o acesso | **não encerrava** | encerra |
| Curadoria abre o builder | **`ViewException`** — não tem expositor, e o guard morria no `->id` de um nulo | abre |

O `CursoBuilder` não é uma tela de leitura: é o editor completo — configurações,
publicar, módulos, aulas, materiais, exclusões.

> **D-02G-6 (revisada) — A autoridade sobre o curso é a autoridade canônica do
> item.**
>
> `ProductPolicy::updateCanonical`: **curadoria (`produtos.moderar`) ou delegação
> canônica declarada e viva**. Nenhuma role nova, nenhuma Policy nova, nenhuma
> superfície nova — a regra existia desde a 02C e só não estava sendo usada aqui.
>
> Ownership de `ProductOffer` **não concede** autoridade sobre o curso.

**Por que não há regressão hoje.** Quem cadastra um item recebe a delegação no
mesmo ato (`SaveProductWithOffer`), então o autor do produto digital continua
entrando no builder. O que ele perde é o acesso que nunca deveria ter tido: o
curso de um item que outra pessoa trouxe ao catálogo.

**Sobre `Gate::before`.** Esta é autoridade **canônica**, e por isso passa pela
`Gate`, onde o override de admin é desejado — o lado oposto do ownership
comercial, que a 02F manteve fora de Policy justamente porque ali o override
seria errado. Os dois eixos continuam separados, e há teste que prova os dois
sentidos.

**A listagem continua como estava**, e corretamente: `CursoIndex` mostra os itens
que o lojista **vende**, não os que ele pode editar. O poder está no builder, e é
lá que a autoridade é conferida.

### A capa do aluno

A 02E deixou `ofertaVigente` ali como dívida declarada, porque o vínculo não
estava percorrido. Agora está: a capa vem da oferta de origem, com a imagem
canônica do item como último recurso.

## 6. G-11 — slug e URL

### A URL nunca escolheu vendedor, e isso é estrutural

A única URL comercial é `/loja/{expositor}/{produto}`. Ela resolve **loja e
item** — ou seja, exatamente uma oferta. Não existe rota `/produto/{slug}` que
precisasse desempatar entre vendedores, e há teste que falha se alguém criar uma.

> **D-02G-7 — Modelo de URL: canônico no `Product`, comercial em
> expositor + produto.**
>
> Dois expositores sobre o mesmo item terão URLs distintas por construção
> (`/loja/loja-a/camiseta` e `/loja/loja-b/camiseta`). O slug do produto,
> compartilhado, é a identidade canônica funcionando — e não uma colisão.
>
> `product_offers` **não ganha slug**. Não há necessidade a provar: o par já é
> único, e uma coluna nova seria estrutura para um problema que não existe.

**SEO:** a página comercial é de uma loja específica e sempre foi; a OG image e o
preview já resolvem pelo par expositor+produto. Sob multi-oferta, avaliar
`canonical` apontando para uma URL de item é decisão de conteúdo, e fica no §9.

### O defeito que a auditoria encontrou de lado

`products.slug` é `UNIQUE` global e era o **único** slug do projeto sem
desambiguação — `Expositor`, `Post`, `Page` e `Event` todos tinham a sua. Dois
itens de nomes iguais colidiam na constraint e o cadastro morria com erro de
banco, sem nada dizer ao lojista. Confirmado no MySQL real antes de corrigir.

É defeito **anterior a esta trilha e sem relação com multi-oferta** — mas foi
encontrado olhando exatamente para colisões de slug, e mais vendedores
cadastrando significa mais chance de dois quererem "camiseta vermelha".
Corrigido em `SaveProductWithOffer::slugUnico()`, só na criação: o slug sai do
payload de update, então nenhum permalink publicado muda.

**Não confundir com a pergunta do G-11.** Duas ofertas sobre o *mesmo* item
compartilham um `Product` e um slug — correto. O que se desambigua são **produtos
diferentes** de mesmo nome.

## 7. Decisões de domínio

> **D-02G-1** — `Product` não implica `ProductOffer`. A existência de exatamente
> uma oferta hoje não é contrato.
>
> **D-02G-2** — Contexto comercial ambíguo exige seleção explícita. Nunca
> `first()`, `orderBy`, `latest` ou `ofertaVigente` para decidir de quem se
> compra.
>
> **D-02G-3** — Resolução automática só com **exatamente uma** oferta, e só onde
> a compatibilidade 1:1 é explicitamente aceita.
>
> **D-02G-4** — Histórico nunca resolve oferta por estado atual.
>
> **D-02G-5** — O curso pertence ao `Product`; a oferta de origem da matrícula
> vem da compra, nunca de `ofertaVigente`.
>
> **D-02G-6** — Ownership do curso é independente do ownership da oferta. A
> autorização de curso continua como está, e é dívida aberta (§9).
>
> **D-02G-7** — Slug e URL não escolhem vendedor: o par expositor + produto já
> identifica a oferta.
>
> **D-02G-8** — Multi-oferta continua desabilitada após a 02G.

## 8. Multi-oferta continua desabilitada

O cadastro segue produzindo exatamente uma oferta por produto, e o guard da 02F
continua recusando quem não tem oferta sobre o item. Os cenários A × B destes
testes são montados **direto pelas factories** — fixture estrutural, não
ativação. Há teste que falha se o cadastro passar a produzir duas.

**Nada de buy box.** Nenhuma regra de menor preço, primeira oferta, oferta mais
antiga ou mais recente foi criada. O seletor recusa o caso ambíguo em vez de
resolvê-lo, e é essa recusa que mantém a decisão de produto em aberto para quem
tem autoridade de tomá-la.

## 9. Dívidas — o que a 02G deixa medido para a ativação

**Curadoria sem tela própria.** Com a D-02G-6 revisada, a curadoria passa a poder
abrir o `CursoBuilder` — mas por uma rota que vive sob o prefixo do lojista, o
que é acidental e não desenhado. Uma superfície administrativa própria pertence à
fase que resolver o G-1. Ausência de tela não justifica autorização errada: é
preferível que a operação fique temporariamente pouco acessível ao ator certo do
que acessível ao ator errado.

**Apresentação sob multi-oferta.** Vitrine, home, catálogo e `ProductResource`
usam `ofertaVigente`. Hoje é apresentação; no dia da ativação, *qual oferta o
card destaca* vira decisão de produto — com ou sem "a partir de R$", com ou sem
lista de vendedores. Os pontos estão listados no §3.

**SEO canônico sob multi-oferta.** Duas URLs para o mesmo item; avaliar
`rel=canonical` e sitemap.

**Superfície de curadoria (G-1)** e **workflow de proposta** — inalterados desde
a 02F.

**Vinculação de oferta a `Product` existente** continua sem superfície, e é
justamente o que manteria multi-oferta desabilitada mesmo com a arquitetura
pronta.

## 10. Fronteiras preservadas

02H não iniciada · multi-oferta não habilitada · nenhum buy box, ranking ou
seleção automática · nenhum workflow de propostas · FIN-SEC intocada · AVA não
redesenhado (nenhum LMS, curso builder, certificado ou pedagogia novos) ·
ownership da 02F preservado · `Product.is_active` sob curadoria ·
`ProductOffer.is_active` do dono · nenhuma coluna legada removida.

## 11. Gates

| Gate | Prova |
|---|---|
| **G-9** | seletor determinístico; 0/1/2 ofertas, id correto, id de outro produto, nunca a mais barata; carrinho da API com 422 no caso ambíguo |
| **G-10** | curso é do `Product`; **autoridade sobre ele é canônica, não da oferta** — vendedor com oferta recebe 403/404, delegado e curadoria administram, delegação revogada encerra o acesso; matrícula resolve a oferta pela compra, pelo id histórico do `OrderItem`; oferta comprada inativa **não** migra para a que sobrou ativa; cortesia devolve nulo |
| **G-11** | não existe URL comercial sem loja; cada loja tem URL própria para o mesmo item; loja sem oferta dá 404; oferta não tem slug; produtos de mesmo nome recebem slugs distintos |
| **não ativação** | cadastro produz uma oferta só; terceiro recusado pelo guard da 02F |

---

**Status:** seleção de oferta determinística, AVA com origem histórica e
slug/URL sem escolha implícita de vendedor. Multi-oferta não habilitada, 02H não
iniciada.
