# FIN-SEC-01 — Integridade Comercial e Preservação Histórica

> **Projeto:** Feira Esquerda Livre
> **Trilha:** independente da Catalog Intelligence e da Trilha CI
> **Origem:** achados preexistentes encontrados na revisão pré-commit da CAT-DOM-01
> **Objetivo:** garantir que um fato comercial já ocorrido continue existindo e
> legível independentemente do que aconteça depois com os cadastros vivos.

---

## Princípio da trilha

```text
O relacionamento com o vendedor é temporal.
O pedido é histórico.

DELETE de cadastro operacional  ≠  DELETE de fato comercial
```

O estado atual do catálogo — nome da loja, preço da oferta, existência do
produto — **não pode reescrever o passado**. Um pedido de agosto tem de
continuar dizendo o que foi vendido, por quem e por quanto, mesmo que em
setembro a loja mude de nome, o produto saia do ar e a oferta seja removida.

---

## Situação das fases

| Fase | Status | Escopo |
|---|---|---|
| **FIN-SEC-01A** | ✅ Concluída | Auditoria, matriz de riscos e invariantes |
| **FIN-SEC-01B** | ✅ Implementada | Preservação histórica e correção das FKs comerciais |
| **FIN-SEC-01C** | ✅ Pronta para revisão | Snapshot comercial imutável — frete por loja e origem confiável |
| FIN-SEC-01D | ⬜ Não iniciada | Ciclo de confirmação de pagamento atômico e por evento |
| FIN-SEC-01E | ⬜ Não iniciada | Integridade e concorrência de estoque |
| FIN-SEC-01F | ⬜ Não iniciada | Cancelamento, expiração e restauração |
| FIN-SEC-01G | ⬜ Não iniciada | Hardening, MySQL real e documentação final |

---

## FIN-SEC-01A — Auditoria (baseline `a0c36f3`)

A auditoria não alterou nenhum arquivo. Rodou sobre o schema real do MySQL e
reproduziu os cenários em transação com `ROLLBACK`.

### Achado central

```text
DELETE Expositor
 ├── order_items ............ APAGADOS      ← fato comercial destruído
 ├── order_splits ........... APAGADOS      ← obrigação de repasse destruída
 │    ├── order_messages ..... APAGADAS
 │    ├── order_shippings .... APAGADOS
 │    │    └── tracking ....... APAGADO
 │    └── ava_enrollments ..... SET NULL
 ├── order_shippings ........ APAGADOS
 └── product_offers ......... APAGADAS      ← correto, decisão da CAT-DOM-01
```

O `Order` sobrevivia — com `items_total` preenchido e **nenhuma linha que o
sustentasse**. Classificado como **BLOCKER BEFORE PRODUCTION**.

### Demais achados registrados

| ID | Achado | Severidade | Fase |
|---|---|---|---|
| F-01 | `DELETE` de expositor destrói histórico comercial | BLOCKER | **01B** |
| F-02 | Estoque nunca validado, decrementado ou reservado; overselling ilimitado | BLOCKER | 01E |
| F-03 | `splits()->update()` em massa não dispara `OrderSplitConfirmed` — matrícula AVA e tracking não ocorrem no pagamento online | HIGH | 01D |
| F-04 | Sem snapshot do nome do expositor | HIGH | **01B** |
| F-05 | `applyPayment` não é transacional | HIGH | 01D |
| F-06 | Webhook sem verificação de assinatura | MEDIUM | 01D |
| F-07 | `DELETE` de produto apaga curso, matrículas e progresso de alunos | HIGH | fase própria de AVA |
| F-08 | Sem entidade de pagamento nem de recebível; taxas de gateway inexistentes | HIGH | 01C/01D |
| F-09 | Colisão de slug global → 500 no cadastro | MEDIUM | fase própria |
| F-10 | Sem Pix expirado, estorno, devolução ou troca | MEDIUM | 01F |
| F-11 | Domínio financeiro acoplado ao Mercado Pago | MEDIUM | 01D |
| F-12 | Nenhum model usa SoftDeletes | DOCUMENTED DEBT | — |
| F-13 | `POST /api/v1/checkout` aceita `shipping_total` do payload do cliente sem recotar no servidor | HIGH — **preexistente, aberto** | trilha de pagamento/frete |

---

## FIN-SEC-01B — Preservação histórica

### Decisões registradas

| # | Decisão | Racional |
|---|---|---|
| **D-FIN-01** | Pedido é fato histórico | O estado atual do catálogo não reescreve o passado |
| **D-FIN-02** | FK viva não pode destruir `OrderItem` nem `OrderSplit` | `CASCADE` é para composição; item de pedido não é composição do cadastro do vendedor |
| **D-FIN-03** | A identidade histórica mínima do vendedor é snapshot | `expositor_name`, gravado na compra e nunca recalculado |
| **D-FIN-04** | Remoção da oferta não remove histórico | Já garantido pela CAT-DOM-01 (`SET NULL`), mantido e coberto por teste |
| **D-FIN-05** | Excluir o expositor pode remover a oferta viva, mas não o fato comercial | Foi o que decidiu `SET NULL` em vez de `RESTRICT` |

### Por que `SET NULL` e não `RESTRICT`

`RESTRICT` também protegeria o histórico, mas tornaria o expositor **indelével**:
qualquer loja que tivesse vendido uma vez jamais poderia sair do cadastro. Isso
contradiz a D-FIN-05. `SET NULL` deixa a operação acontecer e preserva o fato,
porque o que identifica a venda passou a ser snapshot — não a chave estrangeira.

### FKs alteradas

| Tabela | Coluna | Antes | Depois |
|---|---|---|---|
| `order_items` | `expositor_id` | CASCADE · NOT NULL | **SET NULL · nullable** |
| `order_splits` | `expositor_id` | CASCADE · NOT NULL | **SET NULL · nullable** |
| `order_shippings` | `expositor_id` | CASCADE · NOT NULL | **SET NULL · nullable** |

Permanecem em `CASCADE`, de propósito: `order_id` em items e splits, e
`order_split_id` em shippings e messages. Essas são composições reais — apagar o
próprio pedido leva junto o que só existe dentro dele. O caminho perigoso era o
outro, e ele foi cortado.

### Snapshots acrescentados

| Campo | Tabela | Motivo |
|---|---|---|
| `expositor_name` | `order_items` | Sem ele, renomear a loja reescrevia pedidos antigos |
| `expositor_name` | `order_splits` | O split é obrigação de repasse a um vendedor identificado |

Deliberadamente mínimo: só o nome. CNPJ, endereço e dados bancários pertencem ao
cadastro operacional e não fazem falta para reconstruir o fato comercial —
copiá-los espalharia dado pessoal por uma tabela que nunca mais é revisada.

### Migrations

| Migration | O que faz |
|---|---|
| `2026_08_28_100001_add_expositor_snapshot_to_order_tables` | Cria `expositor_name` nullable em `order_items` e `order_splits`, com backfill por subconsulta correlacionada (portável MySQL + SQLite) |
| `2026_08_28_100002_alter_commercial_fks_for_historical_integrity` | Troca as três FKs de `CASCADE` para `SET NULL` e torna as colunas nullable |

O backfill **não inventa nome**: pedido cujo expositor já não existe fica sem
snapshot, e é honesto que fique.

### O que passou a ser verdade

`OrderItem` agora responde sozinho pelas cinco perguntas do fato comercial —
o que foi comprado, por qual preço, em que quantidade, por qual total e de quem —
sem depender de `Product`, `ProductOffer` ou `Expositor` vivos.

### Invariantes

| # | Invariante | Antes | Depois |
|---|---|---|---|
| INV-FIN-01 | Excluir expositor não apaga pedido | ✘ | **✔** |
| INV-FIN-02 | Excluir oferta não apaga item | ✔ | ✔ |
| INV-FIN-03 | Alterar/excluir produto não altera snapshot | ✔ | ✔ |
| INV-FIN-04 | Alterar preço não altera pedido anterior | ✔ | ✔ |
| INV-FIN-10 | Histórico não depende do vendedor vivo | ✘ | **✔** |
| INV-FIN-05 a 09 | Estoque, atomicidade, idempotência, cancelamento | ✘ | ✘ — fases 01D/01E/01F |

---

## Revisão pré-commit da 01B

A nullable introduzida nesta fase é intencional — `expositor_id = NULL` significa
que o fato histórico sobreviveu ao cadastro operacional. Mas ela muda um
invariante antigo, e a revisão procurou consumidores que passassem a estar
errados por causa disso. Três achados, todos corrigidos.

| # | Achado | Severidade |
|---|---|---|
| **R-1** (registrado como **SEC-03**) | `OrderChat` autorizava por `null === null`. A página do pedido é **pública** e o componente Livewire é endpoint próprio: com o expositor excluído, um visitante anônimo passava na checagem de lojista e alcançava o chat do split órfão. O mesmo `null === null` já valia para pedido feito por visitante (`orders.user_id` é nullable) — brecha preexistente que caiu junto | **HIGH — autorização** |
| **R-2** | `OrderSplitResource` usava `whenLoaded('expositor')`, que **omite a chave** quando a relação carregada é nula. Todo pedido de loja excluída perderia o objeto `expositor` do JSON — quebra de contrato para o app mobile | **MEDIUM — contrato de API** |
| **R-3** | `PedidoChat` e `PedidoMensagemController` comparavam com `?->`, seguro hoje porque as rotas exigem autenticação, mas frágil pela mesma razão do R-1 | LOW — endurecido |

O R-1 foi registrado como achado de segurança próprio — **SEC-03**, em
`ROADMAP.md` — porque alcançava também um cenário preexistente, sem relação com
esta fase: pedido feito por visitante. A correção do R-1 é a regra da trilha
aplicada à autorização:

```text
histórico preservado  ≠  permissão preservada
```

O passado continua consultável sem o vendedor vivo; ninguém herda o papel dele.

### O que a revisão confirmou como já seguro

- **Nenhuma agregação histórica agrupa por `expositor_id`.** Os únicos
  `groupBy`/`pluck` sobre a coluna são de carrinho e de slots de visibilidade,
  onde o expositor está sempre vivo — não há risco de dois vendedores removidos
  colidirem num mesmo balde `NULL`.
- **Escopos de painel são seguros por construção**: `where('expositor_id', $id)`
  nunca casa com `NULL`, então o split órfão simplesmente desaparece do painel
  do lojista e da API dele — e nenhum outro expositor o alcança.
- **`confirmar()` e a marcação de envio** partem sempre de um escopo por
  expositor autenticado; split órfão não chega até elas. Nenhuma guarda extra
  foi necessária.
- **Snapshot é do servidor e imutável**: gravado só no `OrderService`, dentro da
  transação, a partir da relação Eloquent do item de carrinho; não aparece em
  nenhum FormRequest nem formulário, e **nenhuma rotina o sincroniza depois**.
- **Sem `withDefault()`** em lugar nenhum: a relação devolve `null` explícito, e
  não um objeto vazio que fingiria existir na hora de autorizar.
- **Sem N+1** no checkout: `CartService::items()` já carrega `expositor`.
- **Relatórios globais preservados**: a receita total soma splits confirmados
  inclusive os órfãos; o ranking de lojas parte de `Expositor` e naturalmente
  não lista quem não existe mais.

### Invariantes verificadas na revisão

| # | Invariante | Resultado |
|---|---|---|
| INV-B-01 | `DELETE` expositor não apaga `OrderItem` | ✔ |
| INV-B-02 | `DELETE` expositor não apaga `OrderSplit` | ✔ |
| INV-B-03 | `DELETE` expositor não apaga shipping histórico | ✔ |
| INV-B-04 | Rename não altera vendedor histórico | ✔ |
| INV-B-05 | Pedido legível sem expositor vivo | ✔ |
| INV-B-06 | Outro expositor não opera histórico órfão | ✔ — coberto por teste |
| INV-B-07 | Snapshot não vem do cliente | ✔ |
| INV-B-08 | Snapshot não sincroniza com o cadastro vivo | ✔ |
| INV-B-09 | FK nullable não quebra API nem view histórica | ✔ — após R-2 |
| INV-B-10 | Relações internas `Order → Item/Split` intactas | ✔ |

---

## FIN-SEC-01C — Snapshot comercial imutável

A 01B garantiu que o pedido **sobreviva**. A 01C garante que ele continue
**dizendo a verdade**: que consiga explicar, anos depois, quais condições
comerciais valiam no dia da venda, sem consultar o catálogo, a regra de comissão
ou a tabela de frete de hoje.

### Auditoria — o que já era congelado

A maior parte do retrato já existia. A auditoria confirmou, e três testes novos
provam, que estes campos **já eram** snapshots corretos:

| Informação | Onde | Situação |
|---|---|---|
| Produto vendido | `order_items.product_name` | já congelado |
| Vendedor | `order_items.expositor_name` · `order_splits.expositor_name` | congelado na 01B |
| Preço unitário | `order_items.unit_price` (de `cart_items.price_snapshot`) | já congelado |
| Quantidade e total | `order_items.quantity` · `total_price` | já congelados |
| Bruto do vendedor | `order_splits.gross_amount` | já congelado |
| Percentual e valor de comissão | `order_splits.commission_percent` · `commission_amount` | já congelados |
| Líquido do vendedor | `order_splits.net_amount` | já congelado |
| Frete total do pedido | `orders.shipping_total` | já congelado |
| **Frete por loja** | — | **ausente** |
| Desconto / cupom | — | não existe no sistema |
| Taxa de gateway | — | ainda não é fato conhecido |

A comissão vem de `SiteSetting.comissao_percentual` — configuração global,
aplicada e congelada no momento do pedido. A regra não foi alterada.

### O gap: frete por loja

O cliente escolhe uma cotação **por loja** no checkout, mas o pedido só guardava
a soma. O detalhe por vendedor virava texto livre em `shipping_note` —
*"Loja 154: Correios PAC - R$ 25,00"* —, com o **id** da loja, onde nenhum
relatório consegue ler. E `order_shippings.price`, que existia para isso, nascia
com `0.00` porque o registro só é criado quando o lojista marca o envio.

Consequência: o split não sabia quanto daquela venda foi mercadoria e quanto foi
transporte — exatamente a pergunta que qualquer acerto de repasse faz.

### O achado de segurança: o frete era escolhido pelo cliente

`Checkout::selectShippingOption()` gravava o **preço enviado pelo navegador**.
Como todo método público de Livewire é um endpoint próprio — a mesma lição da
SEC-02C e da SEC-03 —, nada impedia uma chamada com `price` arbitrário, e
`confirmar()` não revalidava. O valor ia para `orders.shipping_total`,
para `total_amount` e para a cobrança no Mercado Pago.

Congelar esse número seria congelar uma mentira. Agora o cliente escolhe **qual**
cotação, nunca **quanto** ela custa: o servidor casa a escolha contra
`$shipping_quotes`, que ele mesmo calculou e que viaja protegida pelo checksum
do componente.

### Campos adicionados

| Campo | Tabela | Motivo |
|---|---|---|
| `shipping_amount` | `order_splits` | Quanto de frete o cliente pagou àquela loja |

Um só campo. `commission_percent`, `gross_amount` e `net_amount` já existiam, e
duplicá-los no item seria repetir fato sem necessidade.

**Nullable, não `default 0`.** Zero é uma afirmação — "não houve frete para esta
loja" — e só pode ser feita quando o pedido não teve frete ou quando há uma
única loja. Quando chega apenas o total agregado de um pedido com várias lojas,
a divisão é desconhecida e `NULL` é a resposta honesta. Ratear por conta própria
seria inventar um fato que ninguém afirmou.

Sem backfill além do caso deduzível (pedidos com frete zero): não há fonte
confiável para dividir retroativamente o frete de um pedido antigo, e o texto de
`shipping_note` é frase, não dado.

### Decisões registradas

| # | Decisão |
|---|---|
| **D-FIN-06** | Valores comerciais aplicados a um pedido são fatos históricos |
| **D-FIN-07** | Alteração posterior em `ProductOffer` não reescreve o pedido |
| **D-FIN-08** | Alteração posterior na regra de comissão não recalcula `OrderSplit` |
| **D-FIN-09** | Taxa de gateway só vira snapshot quando for fato conhecido; não será estimada nem preenchida com zero |
| **D-FIN-10** | `OrderSplit` é cálculo comercial histórico, **não** recebível financeiro |

### Imutabilidade

Varredura por escritas posteriores em `unit_price`, `gross_amount`,
`commission_percent`, `commission_amount`, `net_amount` e `shipping_amount`:
**nenhuma**. Esses campos são escritos uma única vez, na criação do pedido,
dentro da transação do `OrderService`. Nenhuma rotina os sincroniza com o
cadastro vivo.

### Fora do escopo, com motivo

| Item | Por quê |
|---|---|
| Taxa de gateway | Só nasce depois do pagamento/liquidação. Preenchê-la agora seria inventar (D-FIN-09) |
| Desconto / cupom | Não existe no sistema — nada a congelar |
| `seller_receivables`, `payments`, ledger | Fases financeiras posteriores (D-FIN-10) |
| Regra dos 33 dias | Depende de decisão de negócio sobre o marco de contagem |

### Revisão pré-commit da 01C

Dois achados, ambos corrigidos, e ambos com teste que falha no estado anterior.

| # | Achado | Severidade |
|---|---|---|
| **R-1** | **Frete de loja que saiu do carrinho continuava sendo cobrado.** `selected_shipping_options` guarda a escolha por loja, mas o carrinho pode mudar por fora do checkout — outra aba, o drawer, a API — e os métodos que limpam as cotações não são acionados. O total somava a loja ausente, cobrando transporte de mercadoria que não estava no pedido, e a soma dos splits deixava de fechar com `orders.shipping_total` | **HIGH — financeiro** |
| **R-2** | A escolha da cotação casava por `service_id` mesmo quando nulo, e uma opção sem identificador podia ser selecionada por engano | LOW — endurecido |

A correção do R-1 ficou no `OrderService`, e não no componente: o total do frete
passou a ser **derivado** das lojas que realmente entraram no pedido, em vez de
aceito como número pronto. Isso fecha a divergência para qualquer chamador, não
só para a tela que originou o problema — e corrige de quebra uma cobrança
indevida que era anterior a esta fase.

### O que a revisão confirmou

- **Sem dupla contabilização.** `total_amount = items_total + shipping_total`, e
  `shipping_amount` não entra em `gross_amount`, `commission_amount` nem
  `net_amount`. O frete aparece uma única vez.
- **Comissão inalterada.** Ela incide sobre a mercadoria (`gross_amount` é a
  soma dos subtotais dos itens), nunca sobre o frete. A 01C não tocou nisso.
- **Imutabilidade.** Varredura por `update`, `forceFill`, `fill`, `increment` e
  `decrement` sobre os campos históricos: **nenhuma escrita posterior**.
- **Nenhum relatório soma `order_splits`** por agregação — não havia como o
  campo novo contaminar dashboard algum.
- **Sem N+1.** O casamento da cotação lê um array em memória.
- **`NULL` é ignorado por `SUM`**, o que é o comportamento correto: um total
  agregado não deve fingir conhecer uma divisão que ninguém registrou.

### F-13 no fluxo da API — evidência

O endpoint `POST /api/v1/checkout` valida `shipping_total` apenas como
`nullable|numeric|min:0`. Executado contra o código atual:

```text
payload shipping_total=0      -> aceito | orders.shipping_total=0.00      | total_amount=100.00
payload shipping_total=0.01   -> aceito | orders.shipping_total=0.01      | total_amount=100.01
payload shipping_total=99999  -> aceito | orders.shipping_total=99999.00  | total_amount=100099.00
payload shipping_total=-10    -> HTTP 422
```

O único limite existente é o negativo. O cliente autenticado escolhe quanto paga
de frete, e o valor vai para o total cobrado pelo gateway.

**Consequência para o snapshot:** no fluxo da API, `shipping_amount` herda essa
origem — ele congela fielmente o que o payload disse. Não é uma regressão (o
`orders.shipping_total` sempre foi assim), mas é preciso dizer com todas as
letras: **um snapshot é tão confiável quanto sua origem.** No checkout web a
origem foi corrigida; na API, não.

---

### Achado preexistente **não corrigido**

`POST /api/v1/checkout` aceita `shipping_total` **direto do payload do cliente**,
sem recotar no servidor — mesma classe do achado acima, e mais ampla, porque o
app manda o número final. Corrigir exige decidir se o servidor recota ou se
passa a validar contra uma cotação previamente emitida, o que **muda o contrato
público da API** e pertence à trilha de pagamento/frete.

Registrado como **F-13 — HIGH, preexistente**. No fluxo web o problema está
fechado; na API, não.

---

## O que a 01B deliberadamente não fez

Estoque (01E), `applyPayment` transacional e por evento (01D), entidade de
pagamento e recebível (01C/01D), regra dos 33 dias, refund/chargeback (01F),
slug global, CAT-DOM-02 e CAT-05. Nenhuma coluna legada da dívida D-1 foi
removida e a autoria de conteúdo da D-2 não foi tocada.

`DELETE Product` continua apagando curso, matrículas e progresso de alunos
(F-07). É **HIGH e segue aberto**: corrigir exige decidir se matrícula é fato
histórico do aluno — mesma discussão desta trilha, aplicada ao AVA — e isso
merece fase própria em vez de carona aqui.
