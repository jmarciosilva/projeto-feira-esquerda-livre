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
| **FIN-SEC-01C.1** | ✅ Pronta para revisão | Eliminação do F-13 — frete confiável no checkout da API |
| **FIN-SEC-01D** | ✅ Pronta para revisão | Ciclo de confirmação de pagamento atômico, idempotente e por evento |
| **FIN-SEC-01E** | ✅ Pronta para revisão | Integridade e concorrência de estoque |
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
| ~~F-02~~ | Estoque nunca validado, decrementado ou reservado; overselling ilimitado | ~~BLOCKER~~ | **RESOLVIDO na 01E** |
| ~~F-03~~ | `splits()->update()` em massa não disparava `OrderSplitConfirmed` — matrícula AVA não ocorria no pagamento online | ~~HIGH~~ | **RESOLVIDO na 01D** |
| F-04 | Sem snapshot do nome do expositor | HIGH | **01B** |
| ~~F-05~~ | `applyPayment` não era transacional | ~~HIGH~~ | **RESOLVIDO na 01D** |
| F-06 | Webhook sem verificação de assinatura | MEDIUM | 01D |
| F-07 | `DELETE` de produto apaga curso, matrículas e progresso de alunos | HIGH | fase própria de AVA |
| F-08 | Sem entidade de pagamento nem de recebível; taxas de gateway inexistentes | HIGH | 01C/01D |
| F-09 | Colisão de slug global → 500 no cadastro | MEDIUM | fase própria |
| F-10 | Sem Pix expirado, estorno, devolução ou troca | MEDIUM | 01F |
| F-11 | Domínio financeiro acoplado ao Mercado Pago | MEDIUM | 01D |
| F-12 | Nenhum model usa SoftDeletes | DOCUMENTED DEBT | — |
| ~~F-13~~ | `POST /api/v1/checkout` aceitava `shipping_total` do payload do cliente | ~~HIGH~~ | **RESOLVIDO na 01C.1** |

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

Registrado como **F-13 — HIGH, preexistente**. No fluxo web o problema estava
fechado; na API, não — o que foi corrigido em seguida, na FIN-SEC-01C.1, logo
abaixo.

---

## FIN-SEC-01C.1 — Eliminação do F-13

A 01C congelou o frete. Esta microfase garante que estamos congelando **o frete
verdadeiro** também no checkout da API — onde o valor ainda vinha do cliente.

### A vulnerabilidade

`POST /api/v1/checkout` validava `shipping_total` apenas como
`nullable|numeric|min:0` e o persistia. Validar o formato nunca respondeu à
pergunta que importa, que não é *"o número é válido?"* e sim **"quem decidiu
esse número?"**.

```text
shipping_total=0      -> aceito | cobrado 0.00
shipping_total=0.01   -> aceito | cobrado 0.01
shipping_total=99999  -> aceito | cobrado 99999.00
shipping_total=-10    -> HTTP 422   (único limite existente)
```

### A solução

A auditoria encontrou o caminho já pronto: a API **já tem**
`POST /api/v1/frete/cotacao`, que reaproveita o mesmo controller do checkout
web. O servidor sempre soube cotar — o checkout é que não usava isso.

O payload passou a informar **qual serviço** foi escolhido por loja, e o preço
vem de uma recotação feita no servidor, com o endereço que o cliente selecionou
e os itens que estão de fato no carrinho:

```json
{
  "delivery_type": "entrega",
  "customer_address_id": 12,
  "shipping_options": [
    { "expositor_id": 3, "service_id": "PAC-01" }
  ]
}
```

A cotação foi extraída para `CartShippingQuoter`, usado pelas **duas**
superfícies — o mesmo princípio que resolveu a D-3 na CAT-DOM-01: uma regra
econômica em dois lugares acaba divergindo em um deles, e foi exatamente o que
aconteceu aqui.

### Contrato da API

| Campo | Antes | Depois |
|---|---|---|
| `shipping_options` | não existia | **obrigatório** em entrega com item físico |
| `shipping_total` | decidia o valor cobrado | **depreciado** — não decide nada; se enviado e divergente do cotado, o pedido é recusado |

A recusa por divergência é deliberada: melhor barrar um app desatualizado do que
cobrar do cliente um valor diferente do que ele viu na tela.

### Decisão registrada

| # | Decisão |
|---|---|
| **D-FIN-11** | Nenhum valor de frete enviado pelo cliente é autoridade econômica. O cliente escolhe o serviço; o servidor conhece o preço; o pedido registra o fato |

### Provas

| Tentativa | Resultado |
|---|---|
| `shipping_total` = 0 · 0,01 · 99999 | **422**, pedido não criado |
| Sem `shipping_total`, com escolha válida | cobrado o valor cotado (25,00) |
| `service_id` inexistente | **422** |
| Sem `shipping_options` (app antigo) | **422** |
| Loja fora do carrinho no payload | ignorada; frete só da loja comprada |
| Duas lojas | 25,00 + 25,00 = 50,00, com `Σ shipping_amount = shipping_total` |
| Retirada com `shipping_total` = 99999 | frete 0,00 |

O checkout web também ganhou teste de regressão: a refatoração para o serviço
compartilhado não quebrou a cotação nem a seleção da opção real.

### O que muda para o snapshot

```text
antes:  Web confiável · API o cliente controla
depois: Web confiável · API confiável
```

`orders.shipping_total` e `order_splits.shipping_amount` passam a ser fatos de
origem verificada nas duas superfícies.

---

### Revisão pré-commit da 01C.1

A revisão não perguntou se o F-13 tinha sido corrigido, e sim se **a correção
criou alguma nova forma de manipular o frete**. Um achado, corrigido.

| # | Achado | Severidade |
|---|---|---|
| **R-1** | **Loja repetida no payload era resolvida silenciosamente.** Com duas escolhas válidas para a mesma loja — PAC a 25,00 e SEDEX a 45,00 —, o servidor ficava com a última e criava o pedido. O cliente decidia, por ambiguidade, qual frete pagar | MEDIUM |

Corrigido com `distinct` no `expositor_id`: em campo econômico, ambiguidade se
recusa, não se resolve.

> Nota de método: o primeiro teste dessa duplicidade **passou por acidente** — o
> segundo `service_id` do payload não existia na cotação, e a recusa vinha daí,
> não da duplicidade. Refeito com duas opções reais, ele falhou, como devia.
> Teste que passa pelo motivo errado não prova nada.

### O que a revisão confirmou

**Fail closed em toda a cadeia.** Nenhum caminho transforma falha de cotação em
frete zero. Sete modos de falha foram exercitados — timeout, HTTP 500, resposta
vazia, formato inesperado, preço ausente, preço não numérico e preço negativo —
e em todos o pedido **não é criado**. O `resolvePriceAndError` já tratava preço
inválido, e preço zero vindo do provedor é considerado indisponível: o único
zero legítimo nasce de um pedido sem item despachável.

**Cobertura obrigatória.** Toda loja com item físico no carrinho precisa de
exatamente uma opção válida. Loja sem escolha, loja acrescentada ao carrinho
depois da escolha e falha em uma das lojas de um pedido multi-loja: todas as
três recusam o pedido **inteiro**, sem `Order`, `OrderItem` ou `OrderSplit`
parciais.

**Escopo do `service_id`.** A arquitetura usa **um provedor por vez**
(`SiteSetting.frete_provedor`), então não existe o cenário de dois provedores
com o mesmo identificador respondendo simultaneamente. E a busca é sempre dentro
das cotações **daquela loja** — mandar o serviço de outra loja não casa, e, se
casar por coincidência de código, o preço usado é o da loja certa.

**Sem IDOR de endereço.** O endereço é resolvido por `$user->addresses()`, então
o de outro cliente simplesmente não é encontrado.

**Recotação reflete o carrinho do momento**, não a escolha antiga: mudar a
quantidade antes do checkout muda o que é cotado (verificado inspecionando o
payload enviado ao provedor).

**HTTP fora da transação.** A cotação acontece antes de `createFromCart`; nenhuma
conexão externa é esperada com transação de banco aberta.

### Dívida registrada

**Latência e custo do checkout da API.** A recotação é sequencial: uma chamada ao
provedor por loja com item físico. Um pedido de N lojas faz N chamadas em série
no momento da compra. É o preço de ter um valor confiável, e paralelizar é
otimização que não deve ser feita sacrificando o fail closed — fica como dívida,
não como bloqueio.

---

## FIN-SEC-01D — Consolidação do ciclo de confirmação de pagamento

Confirmar um pagamento deixou de ser um punhado de updates independentes e
passou a ser **uma transição de domínio**: ou o pedido fica pago, com os splits
confirmados e os efeitos executados, ou nada acontece.

### O que havia antes

Três caminhos — o Payment Brick, o retorno do gateway e o webhook — chamavam o
mesmo `applyPayment`, que fazia duas escritas soltas:

```php
$order->forceFill($changes)->save();
$order->splits()->update([...]);   // query em massa
```

E a confirmação manual do lojista seguia por um quarto caminho, `confirmar()`,
que era o **único** a disparar `OrderSplitConfirmed`.

### Problemas reproduzidos antes de corrigir

| # | Problema | Como aparecia |
|---|---|---|
| **F-03** | Update em massa não instancia models e não dispara evento | Quem comprava um curso digital pagando por Pix ou cartão **não era matriculado** até o lojista clicar em "confirmar pagamento" |
| **F-05** | Sem transação | Falha entre as duas escritas deixava pedido pago com split pendente |
| **Idempotência** | Reprocessamento repetia efeitos | Webhook duplicado disparava a matrícula de novo |
| **`paid_at`** | Recalculado a cada confirmação | Sem `date_approved` no payload, caía em `now()` e reescrevia o momento do pagamento |
| **Valor** | Nunca comparado | Pagamento de R$ 1 aprovado marcava um pedido de R$ 500 como pago |

Os cinco foram reproduzidos em teste **antes** da correção — quatro testes
falhando no estado anterior.

### A arquitetura adotada

`app/Actions/Payments/ConfirmOrderPayment` concentra a transição, e é
**gateway-agnostic**: recebe um `PaymentConfirmation` — provedor, id externo,
valor, momento, payload — e decide sozinha se aquilo confirma o pedido.

```text
GATEWAY afirma "aprovado"
        ↓
MercadoPagoService traduz  →  PaymentConfirmation
        ↓
ConfirmOrderPayment  [DB::transaction + lockForUpdate]
        ├── ja pago? devolve sem efeito nenhum
        ├── valor cobre o pedido? senao recusa
        ├── Order → pago, paid_at da primeira confirmacao
        └── cada split pendente → confirmar()
                                     ↓
                          DB::afterCommit → OrderSplitConfirmed
                                     ↓
                          matricula · tracking
```

O `MercadoPagoService` voltou a ser o que devia: integração. Sabe ler payload,
normalizar status e guardar a resposta crua; não decide consistência de domínio.

### Decisões registradas

| # | Decisão |
|---|---|
| **D-FIN-12** | A confirmação de pagamento é uma operação de domínio centralizada |
| **D-FIN-13** | Confirmação de pagamento é idempotente — receber duas vezes produz o mesmo estado e os mesmos efeitos que receber uma |
| **D-FIN-14** | `Order` e `OrderSplit` transitam atomicamente |
| **D-FIN-15** | `OrderSplitConfirmed` representa transição real de estado, não execução de método |
| **D-FIN-16** | Efeitos externos ocorrem após o commit |

### Estados

| Entidade | Antes | Depois | Condição |
|---|---|---|---|
| `orders.status` | `aguardando_pagamento` | `pagamento_confirmado` | pagamento validado pelo gateway e suficiente |
| `orders.payment_status` | `pending` | `approved` | idem |
| `orders.paid_at` | `null` | timestamp | **primeira** confirmação; nunca reescrito |
| `order_splits.status` | `pendente` | `confirmado` | pedido confirmado, um split por vez |

### Provas

| Cenário | Resultado |
|---|---|
| Pagamento aprovado | evento disparado, 1 por split |
| Curso digital pago online | **matrícula automática**, sem ação do lojista |
| Webhook repetido | nenhum efeito novo, `paid_at` preservado |
| Brick + webhook do mesmo pagamento | uma única transição |
| Falha no segundo split | rollback total — pedido não pago, split A não confirmado |
| Falha ao salvar o pedido | nenhum split confirmado |
| Falha no listener | pagamento **continua pago** (efeito externo não desfaz fato financeiro) |
| `pending`, `in_process`, `rejected`, status desconhecido | não confirmam |
| Pagamento de valor menor | recusado, pedido intacto |
| Confirmação manual do lojista | continua funcionando igual |

### Concorrência, no MySQL real

O SQLite da suíte não prova lock de linha. Com duas conexões contra o MySQL 8:

```text
T1: lock adquirido sobre o pedido
T2: BLOQUEADA por 2s e recusada — o lock e efetivo
T1: confirmou e liberou o lock
T2: releu o pedido -> status=pagamento_confirmado
T2: encontra o pedido ja pago e nao repete a transicao
```

### F-06 — webhook sem assinatura

Auditado e **mitigado por desenho**, não corrigido: o endpoint não confia no
payload. Ele extrai apenas o `payment_id` e consulta o Mercado Pago
server-to-server com o token da própria loja; o status e o `external_reference`
vêm dessa resposta. Um POST forjado com `"status": "approved"` não confirma nada.

O que resta é a possibilidade de disparar sincronizações de pagamentos que já
pertencem à conta da Feira — o que produz o mesmo estado que a sincronização
legítima produziria. **MEDIUM**, segue registrado para a trilha de pagamento.

---

### Revisão pré-commit da 01D

A revisão procurou combinações capazes de transformar um fato financeiro
inválido em pedido pago, ou de executar os efeitos de um pagamento mais de uma
vez. Quatro achados, todos corrigidos e cobertos por teste que falha no estado
anterior.

| # | Achado | Severidade |
|---|---|---|
| **R-1** | **`confirmar()` não olhava o estado do split.** Dois cliques do lojista — ou dois requests da API — disparavam `OrderSplitConfirmed` duas vezes, e cada disparo carrega efeito de negócio. O evento representava a chamada do método, não a transição | **HIGH** |
| **R-2** | **Pedido cancelado ressuscitava.** Um `approved` chegando atrasado confirmava um pedido terminal, produzindo um pedido pago que ninguém espera atender | **HIGH** |
| **R-5** | **Aprovado sem valor confiável confirmava.** Quando `transaction_amount` vinha ausente, nulo ou ilegível, a validação era pulada e o pedido era confirmado assim mesmo — a regra apostava que o gateway sempre informa o valor. Aposta não é regra de domínio financeiro: agora, sem valor legível, não há confirmação | **HIGH** |
| **R-6** | **Comparação monetária dependia de ponto flutuante.** `abs($pago - $esperado) > 0,01` variava com a representação IEEE-754 — `499.99 * 100` vale 49998.999999999993 em binário. Passou a comparar centavos inteiros | MEDIUM |
| **R-3** | **Valor maior era aceito.** A comparação era de suficiência (`>=`): R$ 999 num pedido de R$ 500 confirmava. Passou a ser **igualdade**, com tolerância de um centavo para o arredondamento entre o float do gateway e o decimal do banco | MEDIUM |
| **R-4** | **Notificação de outro pagamento reescrevia o rastro.** Um segundo `payment_id` para um pedido já quitado sobrescrevia `mercado_pago_payment_id` e `payment_status`, fazendo o pedido apontar para o pagamento errado. Agora o payload é guardado como auditoria e o rastro do pagamento que quitou permanece | MEDIUM |

### O que a revisão confirmou seguro

- **Vínculo pagamento ↔ pedido.** O `external_reference` vem da consulta
  server-to-server, nunca do request. Um pagamento de outro pedido não alcança
  este — provado por teste com dois pedidos de mesmo valor.
- **Rollback não deixa escapar evento.** Com falha no segundo split, nenhum
  `OrderSplitConfirmed` é despachado: os callbacks de `afterCommit` morrem com a
  transação.
- **O lock relê o estado.** `lockForUpdate` é feito numa consulta nova, não sobre
  o model já carregado — travar depois de ler não travaria nada.
- **Valor exato confirma; menor e maior recusam.**
- **Falha de listener não desfaz o pagamento.** Efeito externo não derruba fato
  financeiro.

### Política de comparação monetária

Valores são convertidos para **centavos inteiros** — `(int) round($valor * 100)`
— e comparados por igualdade. O `round()` antes do corte é o que impede o
truncamento binário: sem ele, `(int) (499.99 * 100)` daria 49998.

O real não representa frações de centavo, então o arredondamento é para o
centavo mais próximo: R$ 500,001 e R$ 500,00 são o mesmo dinheiro e confirmam um
pedido de R$ 500,00; R$ 500,006 arredonda para R$ 500,01 e é recusado, como
qualquer outro centavo de diferença.

### Limitação registrada

O sistema distingue "a mesma confirmação chegando de novo" de "outra
confirmação para pedido já pago" **pelo `payment_id`**, não por um registro de
tentativas. Dá para saber que um segundo pagamento chegou e ignorá-lo com
segurança, mas não há histórico de todas as notificações recebidas. Uma tabela
de tentativas resolveria — e pertence à trilha financeira, não a esta fase.

### Dívida operacional: pedidos digitais anteriores

Enquanto o F-03 existiu, quem comprou curso digital pagando online **não foi
matriculado** até o lojista confirmar à mão. A correção vale dali para frente e
**não regulariza pedidos antigos**.

A reconciliação é tarefa própria, e precisa ser auditável, com `--dry-run`,
idempotente e restrita a pedidos comprovadamente pagos. Não foi feita aqui.

---

## FIN-SEC-01E — Integridade e concorrência de estoque

### O que a auditoria encontrou

`stock_quantity` era um número que o lojista digitava e que **ninguém consultava
para decidir uma venda**. Não havia validação, baixa, reserva nem lock em ponto
algum do sistema.

O achado central dispensa concorrência para se manifestar:

```text
estoque = 1
cliente A finaliza  → pedido criado
cliente B finaliza  → pedido criado
estoque = 1
```

Dois pedidos, uma unidade, **sequencialmente**. Reproduzido em teste antes de
qualquer correção, junto com: oferta esgotada vendendo, pedido de 2 com estoque
1 aceito, e nenhuma das seis combinações de `has_stock`/`stock_quantity`
impedindo a venda.

### O modelo: físico + comprometido

```text
stock_quantity     estoque físico — o que existe, e o que o lojista edita
reserved_quantity  comprometido por pedidos ainda não pagos   (novo)
disponível         stock_quantity − reserved_quantity
```

`stock_quantity` **continua significando o físico**. Transformá-lo em
"disponível" seria mais curto, mas faria a tela do lojista mentir: ele digita 10
porque tem 10, e com Pix pendente precisa enxergar a diferença entre ter 10 e
ter 10 com 3 já saindo.

Ilimitado continua sendo ilimitado: `has_stock` falso ou `stock_quantity` nulo —
as duas formas que o cadastro já oferecia — não reservam nem consomem nada.

### O ciclo

| Momento | O que acontece | Onde |
|---|---|---|
| Carrinho | **nada** — carrinho não reserva | — |
| Checkout | `reserved_quantity += qty` | dentro da transação de `createFromCart` |
| Pagamento | `stock_quantity -= qty` e `reserved_quantity -= qty` | dentro da transação de `ConfirmOrderPayment` |
| Cancelamento | `reserved_quantity -= qty` | `ReleaseOrderStock`, pronta para a 01F |

As três operações travam as ofertas com `lockForUpdate` **em ordem crescente de
id**. A ordenação não é estética: dois pedidos que travam as mesmas duas ofertas
em ordens opostas se bloqueiam em círculo, e o banco mata uma das transações por
deadlock. Subindo sempre por `id`, a segunda apenas espera.

### Prova de que a marca do pedido resolve o legado

O status do pedido sozinho **não** distingue um `aguardando_pagamento` criado
hoje — que reservou — de um criado antes desta fase — que não reservou. Por isso
o `Order` ganhou `stock_reserved_at`, `stock_consumed_at` e `stock_released_at`.
A **ausência** da marca é o que identifica um pedido legado: sem backfill, sem
inferência por data de deploy, e com evidência persistida para provar que cada
transição aconteceu no máximo uma vez.

### Decisões registradas

| # | Decisão |
|---|---|
| **D-FIN-17** | Estoque pertence à `ProductOffer`, não ao `Product` |
| **D-FIN-18** | Carrinho não reserva estoque |
| **D-FIN-19** | Reserva é criada atomicamente com o pedido |
| **D-FIN-20** | Pagamento consome uma reserva existente; pedido legado disputa o estoque atual |
| **D-FIN-21** | Toda transição de estoque é idempotente |
| **D-FIN-22** | Concorrência é resolvida no banco, não por validação prévia de UI |
| **D-FIN-23** | Produto digital não participa do estoque físico |
| **D-FIN-24** | Oferta com reserva ativa não pode ser fisicamente excluída; desativar continua liberado |

### Invariantes

| # | Invariante | Situação |
|---|---|---|
| EST-INV-01 | Disponível nunca é negativo | ✔ |
| EST-INV-02 | Reserva nunca supera o físico | ✔ |
| EST-INV-03 | Cada pedido reserva no máximo uma vez | ✔ `stock_reserved_at` |
| EST-INV-04 | Cada reserva é consumida no máximo uma vez | ✔ `stock_consumed_at` |
| EST-INV-05 | Cada reserva é liberada no máximo uma vez | ✔ `stock_released_at` |
| EST-INV-06 | Reserva de um vendedor não afeta oferta de outro | ✔ chave é `product_offer_id` |
| EST-INV-07 | Produto digital não consome estoque físico | ✔ |
| EST-INV-08 | Falha transacional não deixa reserva parcial | ✔ |
| EST-INV-09 | Pedido histórico não é reescrito por estoque atual | ✔ |
| **EST-INV-10** | Pedido anterior à adoção de reservas nunca é considerado reservado retroativamente | ✔ |
| **EST-INV-11** | Toda reserva ativa mantém uma `ProductOffer` operacionalmente referenciável até ser consumida ou liberada | ✔ |

### Pedidos anteriores à fase

Política decidida: **não têm reserva e não a ganham retroativamente**. Ao chegar
o pagamento, o pedido **disputa** o estoque que existir naquele momento, sob
lock. Havendo estoque, consome e confirma; não havendo, a confirmação inteira
falha fechada — pedido não confirmado, splits não confirmados, nenhuma baixa
parcial.

O pagamento fica recebido no gateway e registrado como conflito operacional, sem
estado de domínio próprio. Criar um enum às pressas seria pior; a limitação está
registrada como dívida da 01F.

### Experiência do cliente

Falha de estoque vira recado, não exceção: *"Tapete de crochê esgotou enquanto
você finalizava a compra"* no checkout web, e erro de validação com a mesma
mensagem na API. As duas superfícies passam pela mesma autoridade — não existe
web protegido e API livre.

### O lojista não invalida reservas

Reduzir o físico para menos do que já está comprometido é recusado: criaria
disponível negativo, com unidades prometidas a pedidos existentes. Aumentar
continua livre e não mexe nas reservas.

### Concorrência, no MySQL real

```text
T1: lock adquirido, disponivel=1
T2: BLOQUEADA por 2s — o lock e efetivo
T1: reservou 1 e liberou o lock
T2: releu -> stock=1 reserved=1 disponivel=0
T2: recusa o pedido — overselling impedido
```

### R-1 — a oferta comprometida não pode ser apagada

A primeira versão desta fase tratava a exclusão de uma oferta com reserva ativa
como perda de rastreabilidade, a resolver depois. Era leitura curta demais.

Enquanto deve unidades, a `ProductOffer` **não é só um registro de catálogo**: é o
recurso operacional de que `ConsumeOrderStock` e `ReleaseOrderStock` precisam
para baixar ou devolver aquelas unidades. Apagada a linha, a FK
`order_items.product_offer_id` vira `NULL` por `SET NULL` e o pedido reservado
passa a apontar para o nada: o pagamento posterior não acha o que consumir, o
cancelamento não acha o que liberar. E a obrigação **não pode ser assumida em
silêncio** por outra oferta do mesmo produto — seria outro vendedor, outro preço,
outra relação comercial.

A recusa vive em `DeleteProductOffer`, e não em cada superfície. Ler
`reserved_quantity` e depois apagar são dois momentos, e entre eles cabe um
checkout inteiro:

```text
T1 exclusão lê reserved = 0
T2 checkout cria o pedido e reserva 1
T1 exclusão apaga a oferta
```

Por isso a leitura acontece sob `lockForUpdate`, dentro da mesma transação que
apaga. Provado em MySQL real nas duas ordens possíveis: com o checkout na
frente, a exclusão esperou o lock e releu `reserved = 2`, recusando; com a
exclusão na frente, o checkout esperou e, ao entrar, já não encontrou a oferta.
Não existe o estado "pedido reservado + oferta inexistente".

O modelo guarda a mesma regra em `deleting`, como última linha de defesa — não
como controle de concorrência. É o que impede um comando, um painel novo ou um
`tinker` de apagar uma oferta comprometida só por não conhecer a regra.

**Desativar continua liberado**, e é a saída oferecida ao lojista na própria
mensagem de recusa: `is_active = false` tira a oferta da vitrine por
`scopeVigente()` e **preserva** a linha e a reserva. Nem `ConsumeOrderStock` nem
`ReleaseOrderStock` passam por `scopeVigente()`, justamente porque um pedido
existente tem direito à oferta que comprometeu suas unidades, mesmo que ela já
não esteja à venda.

Fora do alcance desta regra, e registrado com honestidade: `product_offers`
cascateia de `products` e de `expositores` no banco. Apagar um produto ou um
expositor levaria a oferta junto **sem passar por PHP nenhum**. Hoje isso não é
alcançável pela aplicação — nenhuma superfície exclui produto ou expositor —, e
mexer nessas FKs é decisão de ciclo de vida de catálogo, não de estoque.

### Dívidas para a FIN-SEC-01F

| Item | Por quê |
|---|---|
| **Chamar `ReleaseOrderStock`** | A operação existe e é idempotente; falta decidir os gatilhos — cancelamento, expiração |
| **Expiração de Pix** | O domínio não sabe quando um pedido deixa de ser pagável. O Mercado Pago **fornece** `date_of_expiration`, mas o serviço não o solicita nem o lê; o payload cru fica em `orders.payment_payload` sem consumidor |
| **Reserva presa** | Pedido abandonado retém estoque até alguém liberar |
| **Pagamento sem estoque** | Refund, substituição, reposição ou revisão humana |

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
