# CAT-DOM-02F — Isolamento, Autorização e Governança por Oferta

**De quem é o dado, e quem pode mexer nele.**

A 02D respondeu *onde o dado mora*. A 02E respondeu *quem escreve e de onde a
aplicação lê*. Esta fase responde a pergunta que faltava: **esta oferta é sua?**

Decisões congeladas:
[`CAT_DOM_02B_AUTORIDADE_E_CURADORIA_DO_CATALOGO.md`](CAT_DOM_02B_AUTORIDADE_E_CURADORIA_DO_CATALOGO.md)
·
[`CAT_DOM_02C_AUTORIDADE_PRODUCT_E_WRITE_THROUGH.md`](CAT_DOM_02C_AUTORIDADE_PRODUCT_E_WRITE_THROUGH.md)
·
[`CAT_DOM_02E_WRITERS_READERS_E_CUTOVER.md`](CAT_DOM_02E_WRITERS_READERS_E_CUTOVER.md)

---

## 1. Baseline

| | |
|---|---|
| Repositório | `D:/projeto-feira-esquerda-livre/feira-esquerda-livre` |
| Branch / HEAD | `main` · `d0825a33c4c2b8f5823eaac4f3296390a82e05f4` |
| Parent | `3532bd151a2d662134ef05d777160a8493ffbbda` |
| Suíte antes da fase | **962 passed · 2745 assertions · 0 failed** (622s) |
| MySQL / PHP / Laravel | 8.4.11 · 8.3.33 · 12.65.0 |

**Nenhuma migration.** A fase é autorização, escopo de consulta, testes e
documentação — nada de estrutura nova.

## 2. O achado da auditoria

A auditoria (02F-1) encontrou o ownership comercial **já correto em quase todo
lugar** — e uma lacuna real, aberta desde a 02B.

| Superfície | Autorizava por | Veredito |
|---|---|---|
| `ProdutoForm` (`mount`/`guard`/`save`/`removeImage`) | `offer.expositor_id` | ✅ |
| `ProdutoIndex` (`toggleActive`/`delete`) | escopo `ProductOffer::where(expositor_id)` | ✅ |
| `Api/Lojista/ProdutoController::authorizeProduct` | `offer.expositor_id` | ✅ |
| `ProductShareImage/PreviewController` | `offer.expositor_id` | ✅ |
| `SaveProductWithOffer` | allowlist sem `expositor_id`/`product_id` | ✅ |
| `ProductPolicy` | curadoria ou delegação | ✅ |
| **`PerguntaIndex`** (3 métodos) | **`product.offers` — qualquer oferta minha** | ❌ |
| **`Api/Lojista/PerguntaController`** (3 métodos) | **idem** | ❌ |

### A lacuna, e por que ela era invisível

Os dois pontos de resposta a perguntas perguntavam:

```php
ProductQuestion::whereHas('product', fn ($q) =>
    $q->whereHas('offers', fn ($o) => $o->where('expositor_id', $expositorId)))
```

Isso lê: *"qualquer pergunta num produto em que eu tenho alguma oferta"*.

Com `Product` e `ProductOffer` em 1:1, essa consulta e a correta devolvem
exatamente o mesmo conjunto — por isso nenhum teste acusava. Com dois vendedores
no mesmo item, **B responde o que o cliente perguntou a A**, e a resposta sai
assinada pela loja de B: uma promessa de prazo, troca ou garantia que quem
respondeu não tem como cumprir. É o R-2 que a trilha vinha carregando desde a
02B, esperando `product_questions.product_offer_id` existir — o que a 02E
entregou.

## 3. Decisões de domínio

> **D-02F-1 — Ownership comercial deriva exclusivamente de
> `product_offers.expositor_id`.**
>
> Não de `products.expositor_id`, não de `canonical_delegate_expositor_id`, não
> de cardinalidade. Uma definição, um lugar:
> `ProductOffer::pertenceAoExpositorDe(?User)`.

> **D-02F-2 — `products.expositor_id` não participa de autorização comercial.**
>
> É proveniência (D-CAT-11): registra quem trouxe o item ao catálogo, um fato
> histórico que não acompanha quem vende hoje. A coluna permanece; o que se
> proíbe é usá-la como autoridade.

> **D-02F-3 — Delegação canônica não concede ownership de oferta.**
>
> `canonical_delegate_expositor_id` é poder sobre *o que o item é*, concedido e
> revogável (D-CAT-09). São eixos independentes: quem tem delegação não ganha a
> oferta alheia, e quem tem a oferta não ganha autoridade canônica.

> **D-02F-4 — Só o expositor dono da oferta da pergunta pode respondê-la.**
>
> A autoridade sai de `question.product_offer_id`, nunca de `product_id`.

> **D-02F-5 — Pergunta sem `product_offer_id` não tem destinatário comercial.**
>
> Nulo significa literalmente "não se sabe a quem foi feita". Nenhum lojista a
> assume; deduzir por produto, proveniência, primeira oferta ou `ofertaVigente`
> seria escolher o destinatário no lugar do cliente.

> **D-02F-6 — `Product.is_active` permanece exclusivo de curadoria.**
>
> Reafirmação da D-CAT-10, agora com teste: nem o dono da oferta, nem o
> delegado canônico. O interruptor comercial do lojista é
> `ProductOffer.is_active`.

> **D-02F-7 — `ProductOffer.expositor_id` e `ProductOffer.product_id` não são
> alteráveis por lojista.**
>
> Nem por formulário, nem por payload de API. Transferência de oferta e
> revinculação a outro item são governança, e não existem nesta fase.

## 4. A regra central, e por que não é uma Policy

```php
ProductOffer::pertenceAoExpositorDe(?User $user): bool
ProductQuestion::podeSerRespondidaPor(?User $user): bool
ProductQuestion::scopeDirigidaAoExpositor(?int $expositorId)
```

Um predicado para decidir sobre um registro carregado, um escopo para filtrar
consulta. Nada mais.

**Deliberadamente não é uma Policy**, e a razão é a mesma que a SEC-02 já
registrava em `guardOwnership`: `Gate::before` concede tudo a admin **antes** de
qualquer Policy rodar, e admin **não tem expositor**. Uma Policy responderia
"pode" e o código seguinte quebraria no expositor nulo. Mover isso para Policy
por estética enfraqueceria a defesa existente.

A divisão que o projeto já tinha, e que esta fase preserva:

| | Mecanismo | Admin passa por cima? |
|---|---|---|
| Autoridade **canônica** (`Product`) | `ProductPolicy` + `Gate` | **sim**, e é desejado |
| Ownership **comercial** (`ProductOffer`) | predicado + escopo explícitos | **não**, e é desejado |

## 5. Matriz de autorização

| Ação | Lojista dono | Lojista terceiro | Curadoria | Admin | Mecanismo |
|---|---|---|---|---|---|
| Editar oferta (preço, estoque, dimensões) | **sim** | não | não¹ | não¹ | `guardOwnership` → D-02F-1 |
| Ativar/desativar oferta | **sim** | não | não¹ | não¹ | escopo `ProdutoIndex` |
| Excluir oferta | **sim** | não | não¹ | não¹ | escopo `ProdutoIndex` |
| Alterar imagem da oferta | **sim** | não | não¹ | não¹ | `guardOwnership` |
| Editar FAQ da oferta | **sim** | não | não¹ | não¹ | `guardOwnership` |
| Responder pergunta da oferta | **sim** | não | não¹ | não¹ | D-02F-4 |
| Ocultar/exibir pergunta da oferta | **sim** | não | não¹ | não¹ | D-02F-4 |
| Responder pergunta **sem** oferta | não | não | não¹ | não¹ | D-02F-5 |
| Editar identidade do `Product` | só com delegação | não | **sim** | **sim** | `ProductPolicy::updateCanonical` |
| Alterar `Product.is_active` | **não**, nem delegado | não | **sim** | **sim** | `ProductPolicy::updateStatus` |
| Promover conteúdo comercial a canônico | não | não | — ² | — ² | não existe superfície |
| Vincular oferta a outro `Product` | **não** | não | — ² | — ² | não existe superfície |
| Transferir oferta entre expositores | **não** | não | — ² | — ² | não existe superfície |

¹ **Não por falta de permissão, e sim por falta de superfície.** O painel do
lojista exige um expositor, e curadoria/admin não têm um. Não há hoje tela
administrativa que edite oferta alheia, e criar uma é decisão de outra fase —
esta não a inventou.

² Superfície inexistente. Registrado como dívida no §10.

**Papéis reais, nenhum novo:** `administrador`, `gerente`, `supervisor`,
`editor`, `lojista`, `cliente`. **Curadoria = permissão `produtos.moderar`**
(administrador, gerente, supervisor), que já existia e que a `ProductPolicy` já
usava.

## 6. O que mudou no código

| Arquivo | Mudança |
|---|---|
| `ProductOffer` | `pertenceAoExpositorDe()` — a definição única de D-02F-1 |
| `ProductQuestion` | `podeSerRespondidaPor()` e `scopeDirigidaAoExpositor()` |
| `PerguntaIndex` | 3 métodos passam a escopar pela oferta da pergunta |
| `Api/Lojista/PerguntaController` | `baseQuery()` passa a escopar pela oferta |
| `ProdutoForm::guardOwnership()` | mesma regra, agora vinda do predicado |
| `Api/Lojista/ProdutoController::authorizeProduct()` | escopo + predicado |

Os dois últimos **não mudam comportamento**: são a mesma comparação, dita no
lugar onde ela mora. O ganho é que a resposta passa a ser a mesma em todos os
caminhos por construção, e não por cinco cópias coincidirem.

## 7. Isolamento A × B, provado

O cenário monta duas ofertas sobre o mesmo produto **direto pelas factories**.
Isso é fixture estrutural, não ativação: o cadastro continua produzindo uma
oferta só, e há teste que falha se isso mudar.

| Prova | Resultado |
|---|---|
| A edita oferta A; B edita oferta B, na mesma tela | preços gravados em ofertas diferentes |
| Terceiro sem oferta abre o formulário | **403** |
| FAQ de A editada não toca a FAQ de B | conjuntos separados |
| Imagem de A enviada não toca a de B | arquivo de B intacto no banco e no disco |
| A alterna status da oferta de B por id | recusado, `is_active` inalterado |
| A exclui a oferta de B por id | recusado, oferta permanece |
| API: A atualiza produto compartilhado | só a oferta de A muda |
| API: terceiro sem oferta | **403** |

## 8. Tampering

| Vetor | Resultado |
|---|---|
| Payload da API com `expositor_id`, `product_id`, `product_offer_id` alheios | ignorados; oferta mantém dono e produto |
| Formulário Livewire salvando | dono e produto inalterados |
| Id hidratado trocado pelo da oferta alheia (`toggleActive`, `delete`) | recusado, zero escrita |
| `CAMPOS_DA_OFERTA` / `CAMPOS_DO_PRODUTO` | teste falha se `expositor_id` ou `product_id` entrarem |

O último é a proteção estrutural: o tampering é impossível **por construção**,
porque a allowlist não tem esses campos — não por vigilância em cada writer.

## 9. Perguntas — autoridade de resposta

| Cenário | Resultado |
|---|---|
| A responde pergunta dirigida à oferta A | permitido; `answered_by` = pessoa A |
| B responde pergunta dirigida à oferta A | **recusado**, nada gravado |
| Cliente comum responde | recusado |
| Pergunta com `product_offer_id` nulo | **ninguém responde** (D-02F-5) |
| Listagem e contagem do painel | só as perguntas da própria oferta |
| API: B responde pergunta de A | **404**, nada gravado |
| `product_id` e `product_offer_id` divergentes | manda a **oferta**; nada é corrigido silenciosamente |

O último é defesa contra estado que o sistema não produz — a 02E resolve a oferta
pela página ou a valida contra o produto da rota. Se chegar por importação ou
manipulação, a autoridade continua saindo da oferta, e o dado divergente
permanece como está, para ser investigado.

**`answered_by` continua sendo `users.id`.** Nenhuma coluna de autoria comercial
foi criada: a loja é derivável por `question.productOffer.expositor`.

**Moderação não foi ampliada.** O lojista alterna `is_visible` da pergunta
dirigida à oferta dele — o poder que já tinha. Ele não apaga a pergunta, não
edita o texto do cliente e não alcança moderação global, que é da curadoria.

## 10. Fora de escopo, registrado como dívida

**Superfície de curadoria (G-1).** Não existe tela que exerça `produtos.moderar`
sobre o catálogo. A `ProductPolicy` responde corretamente há duas fases, e nada
a invoca a partir de uma interface. Enquanto isso, `product_faqs` fica vazia e a
FAQ canônica não tem como nascer.

**Workflow de proposta/contribuição.** Não há caminho para o lojista *sugerir*
mudança canônica sem delegação — hoje ele apenas é recusado. A fronteira está
imposta; o caminho alternativo é fase futura, e esta não o construiu.

**Vinculação de oferta a `Product` existente.** Continua sendo governança, e
continua sem superfície. Nenhum lojista consegue mover a oferta entre itens.

**Transferência de ownership de oferta.** Deliberadamente não implementada.

**AVA — `CursoController` autoriza por "tenho oferta neste produto".** É a mesma
forma da lacuna corrigida nas perguntas, mas o curso pende de `Product`
(`ava_courses.product_id` UNIQUE), e resolver isso é o **G-10**, explicitamente
adiado. Sob multi-oferta, dois vendedores do mesmo item digital controlariam o
mesmo curso. **Fica registrado como dívida ativa**, e é o candidato natural a
primeiro item da fase que tratar AVA multi-autoria.

**M-05 — imagem canônica gravável sem autoridade.** A superfície diminuiu muito
com a 02E (o formulário do lojista não escreve mais em `products.images`), mas a
coluna continua sem guard próprio, porque não há writer que a alcance.

**Pint:** `ProductQandA.php` e `ProductFaqTest.php` mantêm violações de estilo
**preexistentes**, em regiões que esta fase não tocou.

## 11. Gates

| Gate | Prova |
|---|---|
| **G-F1** | oferta A não é alterada por B — formulário, painel e API (§7) |
| **G-F2** | imagem de A intacta após B operar (§7) |
| **G-F3** | FAQ de A e de B em conjuntos separados (§7) |
| **G-F4** | só o dono da oferta responde; B recusado no painel e na API (§9) |
| **G-F5** | pergunta sem `offer_id` não é assumida por ninguém (§9) |
| **G-F6** | `Product.is_active` recusado a lojista **e a delegado**; aceito à curadoria |
| **G-F7** | dono legítimo alterna `ProductOffer.is_active`; terceiro recusado |
| **G-F8** | nenhuma autorização usa `products.expositor_id` — varredura em §12 |
| **G-F9** | delegação não autoriza operação comercial (§7) |
| **G-F10** | `expositor_id` fora da allowlist; teste dedicado (§8) |
| **G-F11** | `product_id` fora da allowlist; teste dedicado (§8) |
| **G-F12** | SEC-02 verde, sem expectativa alterada |
| **G-F13** | 02C verde |
| **G-F14** | 02D verde |
| **G-F15** | 02E verde |
| **G-F16** | FIN-SEC intocada |
| **G-F17** | AVA sem alteração semântica |
| **G-F18** | cadastro produz uma oferta só; teste dedicado |

## 12. Varredura de `expositor_id` (G-F8)

Todas as ocorrências em `app/` foram classificadas. **Nenhuma autoriza operação
comercial a partir de `products.expositor_id`:**

- `product_offers.expositor_id` — ownership comercial, o mecanismo correto;
- `order_splits` / `order_items` / `expositor_impressions` — domínio financeiro e
  de métricas, fora desta trilha;
- `SaveProductWithOffer` — as duas únicas escritas em `products.expositor_id`,
  ambas na **criação**, como proveniência declarada;
- `Product::delegaCanonicoPara()` — compara
  `canonical_delegate_expositor_id`, que é autoridade canônica e não ownership.

A coluna **não foi removida** — isso é 02H.

## 13. Fronteiras preservadas

02G não iniciada · multi-oferta não habilitada · nenhum workflow de propostas ·
nenhuma promoção a canônico · nenhuma sincronização reversa `ProductOffer →
Product` · `answered_by` inalterado · FIN-SEC intocada · AVA não redesenhado ·
`ofertaVigente` na área do aluno continua sendo só apresentação de capa ·
Catalog Intelligence com `Product` como eixo canônico.

## 14. Pendências para as próximas fases

1. **Superfície de curadoria (G-1)** — sem ela, a `ProductPolicy` decide no
   vazio e a FAQ canônica não nasce.
2. **AVA multi-autoria (G-10)** — a autorização de curso ainda é por produto.
3. **Workflow de proposta** — o lojista sem delegação é recusado e não tem
   caminho alternativo.
4. **Governança de vinculação** — associar oferta a `Product` existente.
5. **02H** — remoção das colunas legadas, incluindo a avaliação de
   `products.expositor_id` e `image_path`.

---

**Status:** ownership comercial com definição única e derivado exclusivamente de
`ProductOffer.expositor_id`; autoridade canônica preservada e separada;
isolamento A × B provado sobre oferta, imagem, FAQ, status e perguntas.
Multi-oferta não habilitada, 02G não iniciada.
