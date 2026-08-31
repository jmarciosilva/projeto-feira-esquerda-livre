# CAT-DOM-02D — Estrutura de Conteúdo por Oferta

> **Projeto:** Feira Esquerda Livre
> **Trilha:** Catalog Intelligence — terceira fase executável da CAT-DOM-02
> **Baseline auditado:** `05a30d33bed604f7a89bc588f4a1f5cc3d29fc02`
> **Natureza deste documento:** especificação e auditoria. **A implementação não foi iniciada.**

---

## 1. Objetivo

A CAT-DOM-02B decidiu que imagem, FAQ e pergunta deixariam de ser do produto e
passariam a ter contexto de oferta (D-CAT-14 a D-CAT-17). A CAT-DOM-02C
implementou a autoridade canônica e encerrou o write-through comercial, mas
**não tocou em nenhum desses três** — por decisão explícita de escopo.

A CAT-DOM-02D é a fase que **cria o lugar** para esse conteúdo. Ela é uma fase de
estrutura: schema, chaves, cardinalidade, integridade e backfill. Ela não muda
como a aplicação lê nem como ela autoriza.

O documento existe para que a implementação futura possa acontecer sem invadir a
CAT-DOM-02E (writers e readers) nem a CAT-DOM-02F (isolamento e curadoria).

---

## 2. Baseline

| | |
|---|---|
| HEAD / origin/main | `05a30d33bed604f7a89bc588f4a1f5cc3d29fc02` |
| Branch | `main` |
| ahead / behind | 0 / 0 |
| Working tree | limpo · index vazio |

Todas as medições deste documento vêm do MySQL 8.4 real do ambiente de
desenvolvimento, por consulta somente leitura, e das migrations versionadas —
nunca de inferência sobre os models.

## 3. Fontes normativas

`CAT_DOM_02B_AUTORIDADE_E_CURADORIA_DO_CATALOGO.md` (decisões D-CAT-09 a
D-CAT-21, congeladas) · `CAT_DOM_02C_AUTORIDADE_PRODUCT_E_WRITE_THROUGH.md`
(autoridade implementada) · `ROADMAP.md`.

Nenhuma decisão congelada é reaberta aqui. Permanecem valendo: `Product` é
verdade canônica global; `ProductOffer` é verdade comercial do expositor;
autoridade canônica é curadoria **ou** delegação explícita válida, nunca derivada
de cardinalidade, de `products.expositor_id` ou da existência de uma oferta;
`Product.is_active` é validade canônica e `ProductOffer.is_active` é
disponibilidade comercial; `products.expositor_id` é proveniência histórica.

---

## 4. Escopo

Criar a estrutura de armazenamento para:

1. **imagem da oferta** — o conteúdo visual que pertence àquele expositor;
2. **FAQ da oferta** — o texto comercial que pertence àquele expositor;
3. **contexto de oferta nas perguntas** — `product_offer_id` em
   `product_questions`, ao lado do `product_id` que permanece.

Mais: chaves estrangeiras, índices, regras de exclusão, backfill 1:1 idempotente
e a prova de integridade correspondente.

## 5. Fora de escopo

Migração ampla de writers e readers · *dual-read* e fallback em runtime ·
alteração de formulários, API, views ou resources · alteração de `syncFaqs` ·
mudança de autorização ou de guard · fluxo de proposta ou contribuição ·
curadoria operacional · promoção de conteúdo a canônico · multi-oferta ·
qualquer alteração em checkout, estoque, FIN-SEC, AVA, slug ou `ofertaVigente`.

---

## 6. Limites entre CAT-DOM-02D, CAT-DOM-02E e CAT-DOM-02F

Esta fronteira fica congelada por este documento.

| | **02D — estrutura** | **02E — writers/readers** | **02F — isolamento/governança** |
|---|---|---|---|
| **Responde** | *onde o dado mora* | *quem escreve e quem lê* | *de quem ele é* |
| **Entrega** | schema, FKs, índices, constraints, backfill, integridade; relação de model apenas se inevitável para o backfill | migração de writers e readers, *dual-read*, fallback canônico, formulários, API, views, resources, `syncFaqs`, leitura de imagens | guard "esta oferta é sua?", isolamento A × B em imagem/FAQ/pergunta, contribuições, estados de proposta, curadoria, promoção a canônico |
| **Não faz** | não muda leitura, escrita nem autorização | não cria estrutura nova nem muda autorização | não cria estrutura nem migra readers |
| **Estado ao final** | a estrutura existe e está populada; a aplicação continua usando a antiga | a aplicação usa a nova estrutura; a autorização ainda é a de hoje | o conteúdo por oferta está protegido por dono |

**Consequência que precisa ficar explícita:** ao fim da 02D a aplicação continua
lendo e escrevendo `products.images` e `product_faqs` exatamente como hoje. A
estrutura nova existirá populada e **sem consumidor**. Isso é intencional — é o
que torna a 02D reversível — e é também o motivo de a 02E não poder demorar:
enquanto as duas estruturas coexistirem sem *dual-write*, a nova envelhece.

> **Mitigação obrigatória da 02D:** o backfill não pode ser "uma vez e pronto".
> Imediatamente antes de a 02E trocar os readers precisa acontecer uma
> **reconciliação final** entre a fonte legada — ainda ativa — e a estrutura
> nova. Ela **não é** uma simples reexecução do backfill: reexecutar com a regra
> "não sobrescrever destino preenchido" deixaria passar tudo o que mudou na
> janela. As duas execuções, e a diferença entre elas, estão no §16.0.

---

## 7. Estado atual — imagens

### 7.1 Onde a imagem mora

Não existe tabela de imagens. Tudo vive em duas colunas de `products`:

| Coluna | Tipo | Conteúdo |
|---|---|---|
| `images` | `json NULL` | Array de objetos `{"thumb": path, "medium": path}` |
| `image_path` | `varchar(255) NULL` | Espelho de `images[0].medium` |

`product_offers` **não tem nenhuma coluna de imagem**.

O arquivo físico fica em `storage/app/public/products/`, servido pelo disco
`public`. `ImageService::store()` gera um UUID e grava dois arquivos WebP —
`{uuid}_thumb.webp` (300px) e `{uuid}_medium.webp` (800px). Não há original
preservado, não há metadado por imagem (sem *alt*, sem crédito, sem ordem
explícita além do índice do array), e o limite é **4 imagens**, aplicado no
formulário e no `ProdutoRequest`.

`image_path` é legado: precede o array e hoje só duplica a primeira imagem média.

### 7.2 Quem escreve

| Ponto | O que faz |
|---|---|
| `SaveProductWithOffer` | `images` e `image_path` estão em `CAMPOS_DO_PRODUTO`; gravados em `products` **sem exigir autoridade canônica** — M-05, preservado de propósito pela 02C |
| `ProdutoForm::save()` | Monta o array; anexa até 4 via `ImageService::store()` |
| `ProdutoForm::removeImage()` | **Apaga o arquivo do disco** via `Storage::disk('public')->delete()` — direto, sem passar pelo `ImageService` — e depois grava `products.images` |
| `ProdutoController::buildData()` | Idem para a API; a exclusão usa `ImageService::delete()` sobre `remove_image_indexes` |
| `DemoProductImageSeeder` | `forceFill(['image_path', 'images'])` |

### 7.3 Quem lê — 13 pontos, 8 arquivos

`Product::getMainImageUrlAttribute()` · `ProductResource` (`main_image_url` e
`images`) · `ProductShareImageService` · `cart-drawer.blade` ·
`checkout.blade` · `produto-index.blade` · `loja/produto.blade` (OG image,
galeria e bloco de relacionados) · `loja/show.blade` · `welcome.blade` (quatro
blocos) · `produto-form.blade`.

**Esse número é o custo real da 02E**, e é a principal razão para preferir uma
estrutura cuja leitura seja uma expressão e não um *join* — ver §13.

### 7.4 Exclusão — o risco já existe hoje

`ImageService::delete(array $paths)` recebe caminhos crus e apaga. **Não verifica
propriedade e não conta referências.** `ProdutoForm::removeImage()` sequer passa
por ele: chama `Storage::delete()` nos dois caminhos do índice e segue.

Enquanto só `products` referencia o arquivo, isso funciona. No instante em que
duas entidades apontarem para o mesmo path, apagar uma quebra a outra em
silêncio — sem erro, sem log, sem recuperação.

---

## 8. Estado atual — FAQ

```sql
CREATE TABLE `product_faqs` (
  `id`         bigint unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint unsigned NOT NULL,
  `question`   varchar(255) NOT NULL,
  `answer`     text NOT NULL,
  `sort_order` smallint unsigned NOT NULL DEFAULT '0',
  `created_at` timestamp NULL, `updated_at` timestamp NULL,
  PRIMARY KEY (`id`),
  KEY `product_faqs_product_id_sort_order_index` (`product_id`,`sort_order`),
  CONSTRAINT `product_faqs_product_id_foreign`
    FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) AUTO_INCREMENT=9
```

**Não há coluna de autoria.** A FAQ não registra quem a escreveu — o vínculo é
só com o produto. Ordenação por `sort_order`, atribuído pelo índice do array no
salvamento.

**Escrita.** `ProdutoForm::syncFaqs()` e `ProdutoController::syncFaqs()` fazem
`ProductFaq::where('product_id', …)->delete()` e recriam a partir do que o
lojista enviou. A 02A corrigiu a omissão destrutiva na API — chave `faqs`
ausente passou a preservar —, mas **a substituição total permanece**: quem salva,
substitui tudo.

**Leitura.** `loja/produto.blade` (bloco público), `ProductResource`,
`CatalogoController`, `ProdutoController`, `produto-form.blade`, e o
*eager load* de `routes/web.php`.

`AUTO_INCREMENT=9` com 0 linhas: a tabela já teve 8 registros, apagados por
`syncFaqs` ou por reseed. É evidência de que a substituição total roda.

## 9. Estado atual — perguntas

```sql
CREATE TABLE `product_questions` (
  `id`          bigint unsigned NOT NULL AUTO_INCREMENT,
  `product_id`  bigint unsigned NOT NULL,
  `user_id`     bigint unsigned NOT NULL,
  `question`    text NOT NULL,
  `answer`      text NULL,
  `answered_at` timestamp NULL,
  `answered_by` bigint unsigned NULL,
  `is_visible`  tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL, `updated_at` timestamp NULL,
  PRIMARY KEY (`id`),
  KEY (`answered_by`),
  KEY (`product_id`,`answered_at`,`is_visible`),
  KEY (`user_id`,`product_id`),
  CONSTRAINT FK `answered_by` → `users`  (id) ON DELETE SET NULL,
  CONSTRAINT FK `product_id`  → `products`(id) ON DELETE CASCADE,
  CONSTRAINT FK `user_id`     → `users`  (id) ON DELETE CASCADE
) AUTO_INCREMENT=14
```

**Onde a pergunta é criada.** Em `/loja/{loja}/{produto}` — a página de **uma
oferta específica** —, pelo componente montado como
`<livewire:product-qand-a :product="$product" />`. Também por
`POST /api/v1/produtos/{product}/perguntas`.

**Qual loja aparece na página.** A do expositor da URL. O cliente escolheu um
comerciante e falou com ele.

**O que é gravado.** Apenas `product_id`. **A oferta — isto é, o destinatário —
não é registrada em lugar nenhum.**

**Quem responde.** `PerguntaIndex` (painel) e `PerguntaController` (API), ambos
autorizando por `whereHas('product.offers', expositor_id)` — *"você tem alguma
oferta sobre este produto?"*. Com uma oferta por produto isso coincide com o
destinatário; com duas, não coincide mais.

**`answered_by`** é `users.id`, `SET NULL`. Guarda a **pessoa**, não a loja.

---

## 10. Schema e FKs atuais

FKs que apontam para `product_offers` hoje — e que são o precedente do projeto
para conteúdo com valor histórico:

| Origem | Regra | Razão registrada |
|---|---|---|
| `cart_items.product_offer_id` | nullable · **SET NULL** | CAT-DOM-01C |
| `order_items.product_offer_id` | nullable · **SET NULL** | rastreabilidade, não fonte de verdade; o pedido é fato histórico |

FKs saindo de `products`: `expositor_id` → `expositores` SET NULL (proveniência),
`category_id` → `content_categories` SET NULL,
`canonical_delegate_expositor_id` → `expositores` SET NULL (delegação, 02C).

**Nenhum model do domínio usa `SoftDeletes`.** Toda exclusão é física. F-12
(SoftDeletes) permanece dívida aberta, e este documento não a antecipa.

## 11. Dados reais

Medidos no MySQL 8.4, somente leitura, no baseline `05a30d3`.

| Métrica | Valor |
|---|---:|
| `products` | **75** |
| `product_offers` | **75** |
| Produtos com **0** ofertas | **0** |
| Produtos com **1** oferta | **75** |
| Produtos com **> 1** oferta | **0** |
| `product_faqs` | **0** |
| Produtos com FAQ | **0** |
| `product_questions` | **0** |
| Perguntas respondidas | **0** |
| Perguntas sem resposta | **0** |
| Perguntas com `answered_by` | **0** |
| Produtos com `images` não vazio | **75** |
| Produtos com `image_path` | **75** |
| Produtos com **mais de uma** imagem | **0** |
| Total de referências de imagem no JSON | **75** |
| `image_path` distintos | **75** |

E dois fatos sobre a forma dessas imagens:

| Verificação | Resultado |
|---|---:|
| `images[0].thumb` **=** `images[0].medium` | **75 de 75** |
| `image_path` **=** `images[0].medium` | **75 de 75** |

### 11.1 Leitura destes números

**FAQ e perguntas têm custo de backfill zero.** Não há uma linha para migrar,
classificar ou arriscar. É a janela mais barata que este sistema terá, e ela se
fecha na primeira FAQ escrita e na primeira pergunta de cliente.

**Imagens têm custo real.** São 75 produtos, um imagem cada, e o backfill precisa
**copiar arquivo**, não path (§17).

**A base de desenvolvimento já compartilha arquivo físico entre três
referências.** `thumb`, `medium` e `image_path` apontam para o mesmo arquivo nos
75 produtos, porque o `DemoProductImageSeeder` grava o mesmo caminho nos três.
Isso é dado de demonstração, não o comportamento do `ImageService` — um upload
real gera dois arquivos distintos. Mas confirma que **o cenário de path
compartilhado não é hipotético neste banco**, e o backfill precisa tratá-lo.

> **Distinção que importa:** compartilhar `thumb` e `medium` **dentro da mesma
> entrada de imagem** é inofensivo — as duas são apagadas juntas pelo mesmo
> `removeImage`. O que não pode existir é compartilhamento **entre entidades**,
> canônico × oferta, porque aí a exclusão de uma alcança a outra.

## 12. Cardinalidades atuais

```text
Product 1 ──── N ProductOffer          (hoje sempre N = 1)
Product 1 ──── N ProductFaq            (hoje N = 0)
Product 1 ──── N ProductQuestion       (hoje N = 0)
Product 1 ──── 0..4 imagens            (array JSON; hoje sempre 1)
ProductOffer   ──── nenhuma imagem, FAQ ou pergunta
```

---

## 13. Modelo futuro — imagens

### 13.1 Alternativas

**A — coluna `images` (JSON) em `product_offers`.**
Espelha exatamente a forma que a aplicação já manipula.
*Prós:* uma coluna nullable, aditiva e reversível; o *shape* `[{thumb, medium}]`
é o que todos os 13 leitores já sabem ler, então a 02E vira uma expressão de
fallback e não um *join*; backfill trivial; nenhuma tabela nova; nenhum
*eager load* novo para evitar N+1.
*Contras:* sem FK por imagem e sem ordenação declarada — a posição no array
continua sendo a ordem; apagar uma imagem reescreve o array inteiro (como hoje);
não comporta metadado por imagem sem virar outra coisa.

**B — tabela `product_offer_images`.**
`id`, `product_offer_id`, `thumb_path`, `medium_path`, `sort_order`.
*Prós:* cardinalidade explícita, FK real com `onDelete`, exclusão por linha,
ordenação declarada, lugar natural para metadado futuro (*alt*, crédito,
moderação).
*Contras:* assimétrica — o lado canônico continuaria em JSON, e o sistema
passaria a ter duas formas de representar a mesma coisa; a 02E fica mais cara
(13 pontos passam a precisar de relação carregada, com risco de N+1 na home e no
catálogo); mais peça para manter sem consumidor que a justifique hoje.

**C — tabela única `product_images` com `product_id`/`product_offer_id`
nullable.** Rejeitada de imediato: exigiria **mover** a imagem canônica para fora
de `products`, e a 02D é obrigatoriamente aditiva (§26 do escopo, invariante D5).
Além disso importaria um invariante XOR que o schema teria de sustentar.

### 13.2 Recomendação — **Alternativa A**

Uma coluna `images` (`json NULL`) em `product_offers`.

O critério de decisão é o do §10 do escopo: **proporcional ao sistema atual**. O
sistema tem no máximo quatro imagens por item, sem metadado, sem ordenação além
do índice, e treze pontos de leitura. A tabela resolveria problemas que este
sistema ainda não tem e cobraria seu preço justamente onde a fase seguinte é
mais cara.

**Deliberadamente sem gêmeo de `image_path`.** `products.image_path` é espelho
de `images[0].medium` e é dívida da mesma família da D-1. Replicá-lo na oferta
seria importar o erro para a estrutura nova. A imagem principal da oferta é
`images[0]`, e a 02E expõe isso por acessor.

**Condição objetiva que inverteria a decisão para B**, registrada para não ser
redescoberta: no dia em que a imagem precisar de metadado próprio — texto
alternativo, crédito de autoria, estado de moderação — ou de reuso do mesmo
arquivo entre ofertas, a coluna deixa de servir e a migração A → B passa a ser
justificada. Nada disso está pedido hoje.

### 13.3 Cardinalidade futura

```text
ProductOffer 1 ──── 0..4 imagens da oferta   (array JSON)
Product      1 ──── 0..4 imagens canônicas   (array JSON, inalterado)
```

## 14. Modelo futuro — FAQ

### 14.1 Alternativas

**A — `product_faqs` recebe `product_offer_id` nullable e `product_id` vira
nullable, com invariante XOR.**
*Contras decisivos:* tornar `product_id` nullable é alteração de coluna
existente com semântica — mais perto de destrutivo que de aditivo; o XOR
precisaria de `CHECK` para ser garantido no banco; e uma tabela passaria a
significar duas coisas, com toda consulta precisando dizer qual.

**B — `product_faqs` permanece como FAQ canônica; nasce `product_offer_faqs`.**
*Prós:* cada tabela com um significado; **as duas FKs continuam `NOT NULL`**, sem
invariante XOR para sustentar; isolamento da 02F fica trivial (a FAQ da oferta
já é da oferta); backfill de **zero linhas**; a D-CAT-16 diz que a FAQ canônica
"nasce vazia", e com `product_faqs` hoje em 0 linhas isso é literalmente o estado
atual da tabela.
*Contras:* duas tabelas e dois caminhos de leitura na 02E; assimetria de nome —
`product_faqs` mantém o nome enquanto muda de papel.

**C — tabela polimórfica.** Rejeitada. O §13 do escopo pede explicitamente para
não escolher abstração genérica por elegância, e não há terceiro dono à vista.

### 14.2 Recomendação — **Alternativa B**

```text
product_faqs        → FAQ canônica do item        (curadoria)   — tabela atual, hoje vazia
product_offer_faqs  → FAQ comercial da oferta     (expositor)   — tabela nova
```

O que decide é a combinação de dois fatos: **0 linhas para migrar** e **FKs
`NOT NULL` dos dois lados**. A alternativa A pagaria um invariante XOR perpétuo
para resolver um problema de migração que, neste momento, não existe.

Sobre a assimetria de nome: `product_faqs` continua descrevendo com precisão o
que passa a conter — a FAQ do produto. O que muda é quem escreve. Renomear seria
migration destrutiva, proibida nesta fase; se incomodar, é cosmético e cabe na
02H.

**Estrutura proposta para `product_offer_faqs`:** mesma forma da atual —
`id`, `product_offer_id` (**NOT NULL**, FK CASCADE), `question`, `answer`,
`sort_order`, timestamps — com uma diferença deliberada em relação a
`product_faqs`:

```sql
UNIQUE KEY (`product_offer_id`, `sort_order`)
```

**`UNIQUE`, e não índice comum.** `sort_order` é **posição**, e duas FAQs não
podem ocupar a mesma posição dentro de uma oferta. Hoje `product_faqs` tem apenas
índice comum e a unicidade é acidental — vem de o writer atribuir o índice do
array e nunca de o banco exigir. A tabela nova transforma essa propriedade de
*"única na prática"* em **invariante garantido pelo schema**, o que é o que
sustenta a idempotência da reconciliação e protege contra duas execuções
concorrentes do backfill.

A `UNIQUE` substitui o índice comum — ela já serve às mesmas consultas
(`WHERE product_offer_id = ? ORDER BY sort_order`), então não há índice
redundante a criar.

Consequência que a implementação precisa conhecer: **a `UNIQUE` proíbe
reordenação por atualização incremental.** Trocar `A[0] B[1]` por `B[0] A[1]`
atualizando uma linha de cada vez viola a constraint no meio, porque o MySQL
valida por *statement* e não no commit. É um dos motivos de a reconciliação
substituir o conjunto inteiro em vez de calcular diferença — §16.1.2.

### 14.3 Cardinalidade futura

```text
Product      1 ──── N FAQ canônica    (hoje N = 0, e nasce vazia)
ProductOffer 1 ──── N FAQ da oferta   (hoje N = 0)
```

## 15. Modelo futuro — perguntas

### 15.1 Recomendação

Acrescentar **`product_offer_id`** a `product_questions`, **mantendo
`product_id`**. É a D-CAT-17 literal, e nenhuma das duas colunas substitui a
outra:

| Coluna | Significa |
|---|---|
| `product_id` | agrupamento canônico; eixo da Catalog Intelligence |
| `product_offer_id` | contexto comercial — a oferta onde a pergunta foi feita, e o destinatário dela |

**Nullability: `nullable`.** Três razões. A D-CAT-17 previu explicitamente que
linha legada mantém contexto nulo. `NOT NULL` exigiria backfill-e-constrain no
mesmo movimento, e a 02D é aditiva (§26). E há um caso legítimo de nulo no
futuro: pergunta cuja oferta foi removida — ver §18.

**`onDelete`: `SET NULL`.** Justificativa em §18.

Nenhuma coluna é removida. Nenhum índice existente é alterado. Índice novo
sugerido: `(product_offer_id, answered_at, is_visible)`, espelhando o índice que
já existe para `product_id` e que serve às mesmas três consultas — pendentes da
loja, respondidas visíveis, contador do painel.

### 15.2 `answered_by` — nenhuma mudança na 02D

`answered_by` é `users.id` com `SET NULL`. A CAT-DOM-02B registrou que ele
identifica a **pessoa** e não a **loja**, o que é insuficiente para exibir
autoria comercial da resposta.

**Recomendação: não mexer nesta fase.** Assim que a pergunta carregar
`product_offer_id`, a loja respondente passa a ser **derivável** —
`question.offer.expositor` —, e nenhuma coluna nova é necessária para responder
"que loja respondeu". O que sobra é decisão de apresentação e de autorização, que
pertence à 02F. O §17 do escopo pede preferência por não antecipar, e não há
necessidade estrutural objetiva que a justifique.

### 15.3 Cardinalidade futura

```text
Product      1 ──── N ProductQuestion         (product_id permanece NOT NULL)
ProductOffer 1 ──── N ProductQuestion         (product_offer_id nullable)
```

---

## 16. Estratégia de backfill

### 16.0 Duas execuções diferentes, com regras diferentes

O backfill roda **duas vezes**, em momentos distintos, e confundir os dois foi o
defeito da primeira versão desta especificação.

| | **Backfill inicial (02D)** | **Reconciliação final pré-cutover (imediatamente antes da 02E)** |
|---|---|---|
| **Quando** | Ao implementar a 02D | Imediatamente antes de a 02E trocar writers e readers |
| **Objetivo** | Popular a estrutura nova sem sobrescrever nada | Eliminar o *drift* acumulado na janela 02D → 02E |
| **Destino já preenchido** | **Não sobrescreve** | **Compara e, se divergiu, substitui** |
| **Pode apagar arquivo?** | Nunca | Sim — e só os arquivos que o próprio backfill criou (§16.4) |

**Por que a distinção é necessária.** A primeira versão deste documento propunha
`se O.images já preenchido → pular` como regra única de idempotência. Isso é
correto para a execução normal e **inutiliza a reconciliação**:

```text
T0   Product.images = A
02D  A é copiada fisicamente        →  ProductOffer.images = A'
T1   o lojista troca A por B no caminho legado, que ainda é o único ativo
     →  Product.images = B

reexecução com a regra "pular se preenchido":
     ProductOffer.images já existe  →  pula

resultado:
     Product      = B
     ProductOffer = A'   ← cópia de um conteúdo que não existe mais
```

A oferta entraria na 02E projetando uma imagem que o lojista já havia
substituído. A reconciliação existe exatamente para impedir isso, e a regra de
"pular" a tornava inócua.

### 16.1 FAQ — cópia na 02D, sem apagar a origem

`product_faqs` tem **0 linhas** hoje. O backfill da D-CAT-16 não move nada neste
banco, e ainda assim precisa existir, correto e reexecutável: entre a 02D e a
02E o lojista continua escrevendo FAQ pelo caminho antigo, que segue sendo o
único ativo.

**Na 02D o backfill é cópia, nunca movimentação.** `product_faqs` permanece
intacta — apagar a origem quebraria os readers e writers legados, que a 02D não
altera, e destruiria a reversibilidade da fase.

```text
BACKFILL INICIAL DE FAQ (02D) — conservador

para cada Product P com exatamente uma oferta O:
    se O ainda não tem nenhuma FAQ:
        copiar as FAQs de P para product_offer_faqs
    senão:
        não sobrescrever, não apagar        (D11-A)

P com 0 ou >1 ofertas → FAQ LEGADA NÃO RESOLVIDA · não migrar

product_faqs permanece intacta
```

> **A 02D termina com a FAQ comercial existindo nos dois lugares.** É estado de
> transição, deliberado, e o §16.6 define como ele termina.

### 16.1.1 A reconciliação da FAQ é projeção exata, não *upsert*

A primeira versão desta especificação propunha `updateOrCreate` sobre
`(product_offer_id, sort_order)`. Isso cobre **criação** e **edição** e é **cego
para remoção, redução, reordenação e limpeza**:

```text
T0   product_faqs:  0 → A   1 → B
     backfill    →  product_offer_faqs:  0 → A   1 → B

T1   o lojista remove B pelo caminho legado
     product_faqs:  0 → A

reconciliação por updateOrCreate:
     0 → A   (atualizado)
     1 → B   ← STALE: nada mandou apagá-la

pior: product_faqs = []  →  o destino permaneceria com A e B intactos
```

A oferta entraria no cutover carregando FAQ que o lojista já havia apagado.

**Fica definido: na reconciliação final, `product_offer_faqs` de uma oferta
elegível torna-se uma projeção exata do conjunto de FAQs do produto.**

```text
SOURCE       = FAQs atuais de P, ordenadas por sort_order
DESTINATION  = FAQs atuais de O

após a reconciliação:   DESTINATION = SOURCE      (igualdade semântica)
nunca apenas:           SOURCE ⊆ DESTINATION
```

### 16.1.2 Algoritmo da reconciliação final da FAQ

Roda **depois** de o writer legado ser bloqueado (§16.6, passo 1).

```text
RECONCILIAÇÃO FINAL DE FAQ

pré-condições:
    writer legado bloqueado
    nenhum writer da 02E habilitado          (D11-C)

para cada Product P:

    se P não tem exatamente uma oferta:
        FAQ LEGADA NÃO RESOLVIDA · não sincronizar · não excluir · não inferir
        (e a contagem > 0 bloqueia o cutover — §16.6)
        continuar

    O = a oferta de P
    source = FAQs de P ordenadas por sort_order

    dentro de uma transação:
        apagar todas as FAQs de O
        inserir source em O, preservando question, answer e sort_order
```

**Por que substituir o conjunto em vez de calcular a diferença.** Três razões, e
a terceira é a que decide.

Primeira: é **exata por construção**. Criação, edição, remoção, redução,
reordenação e limpeza total caem todas no mesmo caminho, sem caso especial —
`source = []` termina com o destino vazio porque não há o que inserir.

Segunda: é o **mesmo formato do writer legado**, que já faz `delete()` seguido de
`create()`. Reproduzir a semântica da origem evita divergência conceitual.

Terceira, e decisiva: **um diff posicional colidiria com a `UNIQUE` do §14.2**.
Trocar `A[0] B[1]` por `B[0] A[1]` exigiria atualizar A de 0 para 1 enquanto B
ainda ocupa 1 — e o MySQL valida unicidade por *statement*, não no commit. O
*upsert* posicional precisaria de deslocamento em duas fases para funcionar;
substituir o conjunto não precisa de nada.

**Isto é seguro apenas na janela pré-cutover**, e pela mesma premissa do §16.5:
enquanto nenhum writer da 02E existir, `product_offer_faqs` é propriedade
exclusiva do backfill, e apagar suas linhas não destrói trabalho de ninguém.
Depois do primeiro writer, esta operação passa a ser **proibida** — D11-C.

**Não depender de identidade de linha da origem.** O writer legado apaga e
recria, então os `product_faqs.id` mudam a cada salvamento e não significam nada.
A identidade relevante durante a transição é **o conjunto ordenado atual**.

**Idempotência:** rodar duas vezes produz o mesmo estado, porque a segunda
execução substitui um conjunto pelo mesmo conjunto.

### 16.2 Perguntas — custo zero, mesma cautela

`product_questions` tem **0 linhas**. Regra, tanto no backfill inicial quanto na
reconciliação final: preencher `product_offer_id` **somente quando a associação
for determinística** — produto com exatamente uma oferta. Caso contrário,
permanece nulo.

A reconciliação usa `WHERE product_offer_id IS NULL` como filtro. Isso é seguro e
suficiente **porque o writer legado nunca preenche a coluna**: toda linha nula é,
por construção, linha ainda não reconciliada. Não há drift possível aqui — a
coluna nova só é escrita pelo backfill, e o conteúdo da pergunta em si não muda
de dono.

**Nunca inferir a oferta por heurística.** Uma pergunta atribuída à loja errada
faz um comerciante responder por outro; é reescrever histórico por adivinhação, e
o custo de errar é maior que o de deixar nulo.

### 16.6 FAQ — o cutover pertence à 02E

A 02D deixa a FAQ comercial duplicada: original em `product_faqs`, cópia em
`product_offer_faqs`. **Esse estado não pode sobreviver ao cutover**, porque
`product_faqs` passa a ser a FAQ *canônica* — e conteúdo comercial permanecendo
lá significaria o mesmo texto existindo simultaneamente como afirmação do
vendedor e como afirmação do catálogo, que é exatamente o que a D-CAT-16 separou.

Ordem obrigatória da 02E, registrada aqui para que a 02D não seja implementada
sem ela à vista:

```text
CUTOVER DA FAQ — responsabilidade da CAT-DOM-02E

1. bloquear o writer antigo: syncFaqs deixa de escrever em product_faqs
2. executar a reconciliação final  product_faqs → product_offer_faqs
3. validar que TODA FAQ comercial legada tem destino determinístico
4. se houver qualquer FAQ sem destino determinístico:
      PARAR O CUTOVER — não apagar, não inferir
5. trocar readers e writers para a estrutura nova
6. remover de product_faqs apenas as linhas comerciais migradas com sucesso
7. product_faqs passa a representar exclusivamente FAQ canônica
```

O passo 1 vem antes do 2 de propósito: reconciliar com o writer ainda ativo
deixaria uma janela em que uma FAQ nova entra depois da cópia e antes da
remoção — e seria apagada no passo 6 sem nunca ter chegado ao destino.

**Caso não determinístico.** FAQ cujo produto tenha **0 ofertas** ou **mais de
uma** não tem vendedor identificável. Ela **não migra**, **não é apagada** e
**não vira canônica por omissão** — transformar conteúdo comercial ambíguo em
afirmação do catálogo é precisamente o erro que a D-CAT-18 proíbe.

Como multi-oferta continua desabilitada e o estado esperado é 1:1, a
recomendação é mais forte que "registrar": **bloquear o cutover inteiro se a
contagem de FAQs não resolvidas for maior que zero.** Uma linha ambígua é sinal
de que o mundo não é o que a fase pressupõe, e o custo de parar é menor que o de
seguir com conteúdo mal atribuído.

**Ao fim do cutover, a D-CAT-16 volta a ser literalmente verdadeira:** sem
curadoria tendo criado nada, `product_faqs` está **vazia**. A FAQ canônica não
nasce de cópia automática — nasce vazia, e só é povoada por governança
(D-CAT-16, D-CAT-18).

### 16.3 Imagens — backfill inicial

Para cada oferta cujo produto tenha imagens: **copiar fisicamente** cada arquivo
e gravar na oferta um array novo apontando para as cópias.

```text
BACKFILL INICIAL (02D)

para cada ProductOffer O elegível (produto com exatamente uma oferta), produto P:
    se O.images já preenchido → pular        (não sobrescreve; ver §16.4)
    para cada entrada { thumb, medium } de P.images:
        destino_thumb  = copia_fisica(thumb)
        destino_medium = copia_fisica(medium)
        acrescenta { thumb: destino_thumb, medium: destino_medium }
    grava O.images
```

Volume atual: **75 ofertas × 1 imagem**. Como `thumb` e `medium` apontam para o
mesmo arquivo em toda a base de demonstração, a cópia produz **duas** cópias por
entrada — ou uma só, reaproveitada nas duas chaves, o que é aceitável porque o
compartilhamento fica **dentro da mesma entrada** e as duas morrem juntas
(§11.1). O que a implementação **não pode** fazer é reaproveitar o arquivo do
produto.

Nomes de destino devem seguir o padrão do `ImageService` — UUID novo, sufixo
`_thumb.webp` / `_medium.webp`, mesmo diretório — para que a exclusão e a
listagem futuras não precisem distinguir origem.

### 16.4 Imagens — reconciliação final pré-cutover

Roda **uma vez**, imediatamente antes de a 02E habilitar os novos writers.

```text
RECONCILIAÇÃO FINAL PRÉ-CUTOVER

pré-condição obrigatória:
    nenhum writer runtime da 02E foi habilitado ainda   (§16.5)

para cada ProductOffer O elegível 1:1, produto P:

    1. ler P.images atual
    2. comparar com a projeção registrada em O.images

    3. se ainda corresponder  →  nada a fazer

    4. se P.images divergiu desde o backfill:
         a. gerar cópias físicas novas a partir de P.images
         b. atualizar O.images apontando para as cópias novas
         c. remover os arquivos antigos que O.images referenciava
            — e somente eles (§16.5)

    em nenhum caso compartilhar path entre P e O
```

**A comparação não é entre paths** — eles nunca são iguais, por construção (§17).
É entre a *projeção* e a *fonte*: a implementação precisa saber se `O.images`
ainda representa o `P.images` atual. Duas formas aceitáveis, à escolha da
implementação:

- **por conteúdo** — comparar o hash dos arquivos de origem com o hash dos
  arquivos que a oferta referencia. Não exige coluna nova; custa leitura de
  disco, irrelevante em 75 registros;
- **por marca de tempo** — comparar `products.updated_at` com o momento da última
  projeção. Mais barato e menos preciso: `updated_at` muda por qualquer edição do
  produto, então produziria recópias desnecessárias — inofensivas, porque a
  operação é idempotente em efeito.

A recomendação é **por conteúdo**: é exata, e o volume torna o custo irrelevante.

### 16.5 A premissa que autoriza a reconciliação a apagar

O passo 4c apaga arquivos. Isso só é seguro sob uma premissa, que fica registrada
como condição explícita e não como suposição:

> **Enquanto a 02E não habilitar seus writers, `product_offers.images` é
> propriedade exclusiva do backfill da 02D.** Nenhum caminho da aplicação escreve
> nesse campo — a 02D não altera writers (§5) —, logo todo arquivo que ele
> referencia foi criado pelo backfill, e apagá-lo não destrói trabalho de
> ninguém.

É por isso que **não é necessária estrutura de proveniência** — nenhuma coluna
`origem`, nenhuma tabela de rastreio. A propriedade é estrutural: deriva da
ausência de writer, não de um marcador.

**A premissa deixa de valer no instante em que o primeiro writer da 02E for
habilitado.** A partir daí, `O.images` pode conter arquivo enviado por um
lojista, e apagá-lo seria destruir conteúdo real.

> **Regra de encerramento:** a reconciliação destrutiva do passo 4c é **proibida**
> depois que qualquer writer da 02E entrar em operação. Se a 02E for entregue em
> partes, a reconciliação precisa acontecer **antes da primeira parte**, e não
> entre elas. Se houver dúvida sobre se algum writer já foi habilitado, a
> reconciliação deve rodar em modo não destrutivo — atualizar `O.images` e
> **deixar os arquivos antigos órfãos em disco**, que é desperdício e não perda.

## 17. Estratégia de arquivos de imagem — a regra que não pode ser esquecida

> **Imagem canônica e imagem da oferta nunca compartilham arquivo físico.**
> O backfill **copia o arquivo**; jamais duplica apenas o path.

O motivo é operacional e imediato: `ProdutoForm::removeImage()` apaga o arquivo
do disco assim que o lojista remove uma imagem, e `ImageService::delete()` apaga
por caminho, **sem verificar propriedade e sem contar referências** (§7.4). Com
path compartilhado, o lojista removendo a imagem da oferta dele apagaria a imagem
canônica do catálogo — e a de qualquer outra oferta que apontasse para o mesmo
arquivo. Silenciosamente, sem erro e sem recuperação.

**Isto é invariante de backfill, não de leitura.** A 02D o garante na origem; a
02E e a 02F não têm como consertá-lo depois, porque um arquivo apagado não volta.

Fica também registrado, sem correção nesta fase, que a ausência de contagem de
referências no `ImageService` é fragilidade preexistente (M-05 / CAT-DOM-02C §26).
A 02D não a corrige — apenas se recusa a construir sobre ela.

## 18. FKs e regras de exclusão

### 18.1 O que acontece hoje ao excluir uma oferta

`DeleteProductOffer` faz **hard delete**, dentro de transação, com
`lockForUpdate`, recusando quando `reserved_quantity > 0`. Não há `SoftDeletes`
em nenhum model. Desativar (`is_active = false`) preserva a linha; excluir a
remove. O produto sobrevive nos dois casos — decisão central da CAT-DOM-01.

### 18.2 Regras propostas

| Nova FK | Nullable | onDelete | Justificativa |
|---|:--:|---|---|
| `product_offer_faqs.product_offer_id` → `product_offers` | **NOT NULL** | **CASCADE** | A FAQ da oferta é **composição** da oferta: é o texto comercial daquele expositor sobre a venda dele. Sem a oferta ela não descreve nada e não pertence a ninguém. Some junto, como as condições comerciais |
| `product_questions.product_offer_id` → `product_offers` | **nullable** | **SET NULL** | A pergunta é conteúdo **do cliente**, não do vendedor. Tem valor público e histórico, e a resposta continua descrevendo o item. Ver §18.3 |
| `product_offers.images` (coluna, não FK) | nullable | — | Some com a linha da oferta. Arquivos órfãos em disco: ver §22, risco R-4 |

### 18.3 Por que `SET NULL` nas perguntas, e não `CASCADE` nem `RESTRICT`

**`CASCADE` foi rejeitado.** Apagaria pergunta de cliente e resposta pública
porque o vendedor removeu a oferta. É exatamente a destruição histórica que a
FIN-SEC-01B corrigiu em `order_items`, `order_splits` e `order_shippings` ao
trocar `CASCADE` por `SET NULL`. Repetir o padrão aqui contradiria D-FIN-02.

**`RESTRICT` foi rejeitado.** Bloquearia `DeleteProductOffer`, que hoje conclui
sempre que não há reserva ativa. Isso mudaria comportamento implementado na 02C,
e a 02D não altera fluxo.

**`SET NULL` preserva as duas coisas:** a exclusão continua possível, e a
pergunta sobrevive com `product_id` intacto — perdendo apenas o contexto
comercial, que de fato deixou de existir. É o mesmo tratamento e a mesma
justificativa de `order_items.product_offer_id`.

Consequência a documentar na 02E: pergunta com `product_offer_id` nulo é
**"pergunta sem oferta viva"** e não tem destinatário. Quem pode respondê-la — se
alguém — é decisão da 02F.

## 19. Integridade

Verificações obrigatórias após a implementação, comparadas com o baseline do §11:

| Verificação | Esperado |
|---|---|
| `products` antes = depois | 75 = 75 |
| `product_offers` antes = depois | 75 = 75 |
| `product_faqs` antes = depois | 0 = 0 (canônica permanece vazia) |
| `product_questions` antes = depois | 0 = 0 |
| Ofertas com `images` preenchido | 75 |
| `product_offer_faqs` órfãs | 0 |
| Perguntas com `product_offer_id` apontando para oferta inexistente | 0 |
| **Path de imagem compartilhado entre `products.images` e `product_offers.images`** | **0** |
| Arquivos referenciados pela oferta que não existem em disco | 0 |
| Produtos que perderam `images` ou `image_path` | 0 |
| Migrations pendentes | 0 |

A penúltima linha é a prova do §17 e deve ser uma consulta explícita, não uma
inspeção visual.

### 19.1 Paridade da FAQ — obrigatória antes do cutover

A tabela acima verifica a 02D. Antes de a 02E trocar readers e writers, a
reconciliação precisa provar que o destino é de fato a projeção exata da origem
(§16.1.1) — e essa prova é **por conteúdo, nunca por chave primária**, porque o
writer legado apaga e recria e os `product_faqs.id` não sobrevivem a um
salvamento.

Para cada `Product` com exatamente uma oferta:

| Verificação | Esperado |
|---|---|
| Contagem de FAQs na origem = contagem no destino | igual |
| Conjunto ordenado `(sort_order, question, answer)` da origem = do destino | idêntico |
| FAQs no destino sem correspondente na origem — **linhas obsoletas** | **0** |
| Ofertas cujo produto tem FAQ e que estão com o destino vazio | 0 |

E, globalmente:

| Verificação | Esperado |
|---|---|
| FAQs de produtos com 0 ou >1 ofertas — **não resolvidas** | **0**, senão **bloquear o cutover** |
| Violações de `UNIQUE(product_offer_id, sort_order)` | 0 (garantido pelo schema) |

A terceira linha do primeiro quadro é a que a versão anterior desta especificação
não teria detectado: um destino com linha obsoleta satisfaz "toda origem tem
destino" e ainda assim está errado.

## 20. Invariantes

| # | Invariante |
|---|---|
| **D1** | Uma imagem de oferta pertence a exatamente uma oferta |
| **D2** | Uma FAQ de oferta pertence a exatamente uma oferta |
| **D3** | Uma pergunta contextualizada identifica exatamente a oferta em que foi criada |
| **D4** | `product_questions.product_id` continua existindo e `NOT NULL` |
| **D5** | Nenhum dado canônico de `Product` é destruído, movido ou esvaziado |
| **D6** | A saída ou desativação do vendedor não destrói conteúdo canônico |
| **D7** | Backfill de imagem **não compartilha arquivo** entre canônico e oferta |
| **D8** | Nenhuma segunda oferta é habilitada pela 02D |
| **D9** | Nenhum guard de autorização muda na 02D |
| **D10** | Checkout, estoque e ciclo financeiro permanecem intocados |
| **D11-A** | O backfill normal é idempotente e **não sobrescreve** destino já preenchido |
| **D11-B** | Antes do cutover da 02E deve existir uma **reconciliação explícita** entre a fonte legada — ainda ativa — e a estrutura nova |
| **D11-C** | A reconciliação só pode **substituir ou apagar** conteúdo do destino enquanto estiver comprovado que **nenhum writer legítimo da 02E foi habilitado** |
| **D12** | Após o cutover da 02E, **nenhuma FAQ originada do fluxo comercial legado permanece em `product_faqs`** |
| **D13** | FAQ canônica **não nasce de cópia automática**: nasce vazia e só é criada ou promovida pela governança apropriada (D-CAT-16, D-CAT-18) |

D11 não estava na lista do escopo e foi acrescentado pela razão do §6: entre a
02D e a 02E o conteúdo continua sendo escrito no lugar antigo, e o backfill
precisa poder rodar de novo. A revisão externa mostrou que a formulação original
— "reexecutável sem sobrescrever" — era correta para a execução normal e
**inutilizava a reconciliação**, e por isso ele se desdobrou em A, B e C (§16.0).

D12 e D13 fecham a transição da FAQ: D12 é a obrigação da 02E, e D13 impede que
a FAQ canônica seja povoada por efeito colateral de migração. Os dois são
invariantes **do cutover**, não da 02D — mas ficam registrados aqui porque a
estrutura que a 02D cria é o que os torna possíveis ou impossíveis.

## 21. Gates

| Gate | Condição | Situação |
|---|---|:--:|
| **G-D1** | Schema atual documentado a partir das migrations e do banco real | ✅ |
| **G-D2** | Imagens auditadas — armazenamento, writers, readers, exclusão | ✅ |
| **G-D3** | FAQs auditadas — FK, cardinalidade, writers, readers, ordenação, autoria | ✅ |
| **G-D4** | Perguntas auditadas — criação, contexto, autorização, `answered_by` | ✅ |
| **G-D5** | Dados reais medidos | ✅ |
| **G-D6** | Cardinalidades futuras definidas | ✅ |
| **G-D7** | FKs definidas | ✅ |
| **G-D8** | `onDelete` definido e justificado por FK | ✅ |
| **G-D9** | Estratégia de imagem escolhida, com alternativas rejeitadas e condição de reversão | ✅ |
| **G-D10** | Estratégia de FAQ escolhida | ✅ |
| **G-D11** | Estratégia de pergunta escolhida | ✅ |
| **G-D12** | Backfill definido para os três conteúdos | ✅ |
| **G-D13** | Backfill de imagem sem path compartilhado, com verificação própria | ✅ |
| **G-D14** | Fronteira 02D / 02E / 02F congelada | ✅ |
| **G-D15** | Nenhuma decisão de multi-oferta antecipada | ✅ |
| **G-D16** | Plano 100% aditivo | ✅ |
| **G-D17** | Reconciliação pré-cutover definida para o *drift* da fonte legada, com premissa de propriedade explícita e regra de encerramento | ✅ |
| **G-D18** | Cutover e limpeza da FAQ comercial definidos: **sincronização exata *source* × *destination*** (criação, edição, remoção, redução, reordenação e limpeza total), **eliminação de linhas obsoletas** no destino, **paridade verificada por conteúdo** antes do cutover (§19.1) e **bloqueio do cutover** no caso não determinístico | ✅ |
| **G-D19** | Execução do backfill de filesystem separada da migration de schema, com justificativa | ✅ |

**Todos os dezenove fechados.** G-D17 a G-D19 nasceram da revisão externa da
primeira versão desta especificação: são riscos reais que ela não cobria, e
preferiu-se dezenove gates honestos a dezesseis artificialmente congelados. São
gates de *especificação*; os de implementação nascem com ela.

## 22. Riscos

| # | Risco | Sev. | Mitigação proposta |
|---|---|:--:|---|
| **R-1** | **Exclusão física de arquivo compartilhado** — remover imagem da oferta apaga a canônica | **ALTO** | D7 + §17: cópia física obrigatória; verificação de integridade dedicada (§19); teste que compara os paths dos dois lados |
| **R-2** | **Pergunta respondida pelo vendedor errado** | **ALTO** | A 02D só cria o contexto; a correção do guard é 02F. Até lá o risco permanece **latente**, porque multi-oferta continua desabilitada |
| **R-3** | **FK apagando histórico** — `CASCADE` em perguntas | **ALTO** | Rejeitado por §18.3; `SET NULL`, alinhado a D-FIN-02 e a `order_items` |
| **R-4** | **Arquivos órfãos em disco** ao excluir oferta com imagens | MÉDIO | Consequência aceita da alternativa A; limpeza pertence à 02E/02F. Registrado como dívida, não como bug novo |
| **R-5** | **Backfill não idempotente** ou duplicado | MÉDIO | D11 + §16: guarda `whereNull`/`isEmpty` em cada um dos três |
| **R-6** | **Conteúdo escrito entre a 02D e a 02E fica para trás — *drift* da fonte legada** | **ALTO** | **Reconciliação final pré-cutover** (§16.0), e não simples reexecução do backfill: `imagem` compara projeção × fonte e recopia quando divergiu (§16.4); `FAQ` é **sincronizada como projeção exata** — o destino passa a ser igual ao conjunto de origem, cobrindo criação, edição, **remoção, reordenação e limpeza total**; `updateOrCreate` seria cego para as três últimas (§16.1.1, §16.1.2); `pergunta` reconcilia por `WHERE product_offer_id IS NULL`, sem drift possível porque o writer legado nunca escreve a coluna (§16.2). Teste de drift obrigatório (§28) |
| **R-14** | **Reconciliação destrutiva rodando depois de a 02E habilitar writers** — apagaria arquivo enviado por lojista | **ALTO** | D11-C + regra de encerramento do §16.5: proibida após o primeiro writer; em dúvida, roda em modo não destrutivo e deixa órfão |
| **R-15** | **FAQ comercial permanecer em `product_faqs` após o cutover**, virando canônica por omissão | **ALTO** | D12 + passo 6 do §16.6; e bloqueio total do cutover se houver FAQ não determinística (§16.6) |
| **R-7** | **Mistura de writer/reader dentro da 02D** — a fase incha e vira 02E | MÉDIO | Fronteira congelada no §6; revisão do diff contra a lista de §5 |
| **R-8** | **FAQ atribuída ao produto errado** no backfill | BAIXO | Só migra quando há exatamente uma oferta; hoje 0 linhas |
| **R-9** | **Reintrodução do IDOR da SEC-02** | BAIXO na 02D | D9: nenhum guard muda. Vira ALTO na 02F, e lá exige teste A × B antes da alteração |
| **R-10** | **Habilitação acidental de multi-oferta** | BAIXO | D8 + §24: nenhuma superfície nova; teste existente de inalcançabilidade permanece |
| **R-11** | Impacto em AVA | BAIXO | §24 |
| **R-12** | Impacto em Catalog Intelligence | BAIXO | §25 |
| **R-13** | Impacto em checkout / FIN-SEC | BAIXO | §26 |

## 23. Impactos em SEC-02

**Nenhum.** A CAT-DOM-02D **não altera autorização**. Os guards permanecem
exatamente como a 02C os deixou: a `ProductPolicy` decide a autoridade canônica,
e os guards de oferta decidem o comercial.

O isolamento *"esta oferta é sua?"* sobre imagem, FAQ e pergunta pertence à
**CAT-DOM-02F**.

O que a 02D entrega para viabilizá-lo: a partir dela, **cada um dos três
conteúdos tem uma oferta identificável**, e a 02F pode perguntar "é sua?" sem
ambiguidade — hoje ela não teria a quem perguntar, porque o dado não guarda o
destinatário.

## 24. Impactos em AVA

`ava_courses.product_id` é **UNIQUE** e pende de `Product`. A 02D **não cria
nenhuma FK que toque AVA** — nem direta, nem indiretamente: imagens e FAQ novas
penduram em `product_offers`, e perguntas ganham uma coluna nullable.

**G-10 permanece aberto.** A autoria do curso continua sendo fase posterior, e um
`Product` digital continua não podendo ser compartilhado enquanto ela não for
decidida.

Registro de atenção: se a 02E vier a alterar a imagem exibida em telas do AVA
(certificado, e-mail, "Meu Aprendizado"), isso é decisão da 02E — as três telas
hoje leem `Product::expositor` e não leem imagem de produto.

## 25. Impactos em Catalog Intelligence

**Nenhum, e por verificação e não por suposição.** A varredura em
`app/CatalogIntelligence/` e `app/CustomerIntelligence/` não encontrou nenhuma
leitura de FAQ, pergunta ou imagem. O matcher da CAT-04 opera sobre texto —
`name`, `short_description`, `description` — e sobre `category_id`;
`catalog_product_knowledge` pende de `products`.

Fica reafirmado: **`Product` continua sendo o eixo canônico da inteligência**, e
**conteúdo de oferta não vira conhecimento canônico automaticamente**. A
promoção depende de curadoria (D-CAT-18) e pertence à 02F ou a fase posterior.

## 26. Impactos em checkout e financeiro

**Nenhum.** A 02D não toca `cart_items`, `orders`, `order_items`,
`order_splits`, reserva, consumo ou liberação de estoque, confirmação ou
reversão de pagamento. Nenhuma coluna comercial é criada, alterada ou removida.
`DeleteProductOffer` e a guarda de reserva órfã permanecem intactos.

A única interseção é indireta e desejada: as regras de `onDelete` propostas
(§18) seguem o precedente que a FIN-SEC-01B estabeleceu para conteúdo com valor
histórico.

---

## 27. Plano futuro de implementação

Migrations **100% aditivas**. Proibidos nesta fase: `DROP`, `RENAME`, mover
removendo a origem, e `NOT NULL` imediato sem backfill seguro. A origem
permanece para a 02E poder operar em transição.

| # | Passo | Natureza |
|---|---|---|
| 1 | Coluna `images` (`json NULL`) em `product_offers` | aditiva |
| 2 | Tabela `product_offer_faqs` com FK `NOT NULL` CASCADE e **`UNIQUE(product_offer_id, sort_order)`** | aditiva |
| 3 | Coluna `product_offer_id` (nullable, FK SET NULL) em `product_questions` + índice `(product_offer_id, answered_at, is_visible)` | aditiva |
| 4 | Backfill de imagens — **cópia física** | dados |
| 5 | Backfill de FAQ e de perguntas — determinístico; hoje no-op | dados |
| 6 | Validação de integridade (§19), MySQL real | verificação |

Estrutura antes de dados, e as três estruturas antes de qualquer backfill: se o
passo 4 falhar no meio, os passos 1–3 continuam válidos e reexecutáveis, e nada
precisa ser desfeito.

Models: acrescentar apenas o que o backfill e a integridade exigirem —
`ProductOffer::images` no `$fillable`/`casts`, o model `ProductOfferFaq` e a
relação `ProductOffer::faqs()`/`questions()` se o backfill precisar delas.
**Nenhuma relação nova em `Product`**, nenhum acessor de fallback: acessor de
fallback é 02E.

### 27.1 Onde o backfill roda — migration ou command

| | **A — migration faz schema + cópia de arquivos** | **B — migration faz schema; command controlado faz dados e arquivos** |
|---|---|---|
| Transação | O filesystem **não participa** da transação SQL | Mesma limitação, mas assumida e visível |
| Rollback | `down()` desfaz o banco e **não desfaz o disco** — a ilusão é o perigo | Nada sugere atomicidade; o tratamento de arquivo é explícito |
| Falha parcial | Migration marcada como não executada com arquivos já copiados; reexecutar duplica | O command relê o estado real e continua de onde parou |
| Reexecução | Só por `migrate:refresh`, que é destrutivo | Trivial — é a operação normal |
| Métricas antes/depois | Difíceis de extrair de dentro de uma migration | Naturais: o command imprime e pode falhar por divergência |
| Reconciliação pré-cutover (§16.4) | Impossível — migration já rodou | É o mesmo command, em outro modo |

**Recomendação: B.**

Não é preferência de estilo. A reconciliação pré-cutover do §16.4 é obrigatória e
acontece **semanas depois** da migration; se a cópia de arquivos morar dentro da
migration, ela simplesmente não tem como rodar de novo. E como o filesystem não é
transacional, embutir I/O de disco numa migration cria a aparência de rollback
atômico que o sistema não tem — que é pior do que não ter rollback, porque induz
a confiar nele.

Forma sugerida: um `Command` (ou `Action` invocada por ele) com dois modos —
`--inicial` e `--reconciliar` —, imprimindo contagem de ofertas processadas,
arquivos copiados, arquivos removidos e divergências encontradas, e falhando com
código diferente de zero se a integridade do §19 não fechar. As migrations dos
passos 1–3 ficam com schema puro.

Consequência para o passo 6: a validação de integridade é do command, não da
migration, e precisa poder rodar isoladamente.

### 27.2 `down()` e o filesystem

`down()` pode remover as três estruturas com segurança, porque **a origem
permanece intacta**: `products.images`, `products.image_path` e `product_faqs`
nunca são apagados pela 02D. Nenhum dado canônico se perde ao desfazer.

**O que `down()` não faz, e não deve fingir que faz: apagar os arquivos que o
backfill copiou.** `down()` é uma operação de schema. Se ela apagasse arquivos,
um rollback seguido de um novo `migrate` teria de recopiar tudo, e um rollback
executado por engano destruiria bytes que o banco não sabe restaurar.

**Decisão:** ao desfazer a 02D, os arquivos copiados **permanecem em disco como
órfãos**. É desperdício mensurável — 75 arquivos hoje — e não é perda. A limpeza,
se desejada, é uma operação separada e explícita do mesmo command, nunca um
efeito colateral de `down()`.

> Não existe rollback atômico de banco e disco neste sistema, e este documento
> não constrói a ilusão de que exista.

## 28. Testes obrigatórios da implementação futura

**Estrutura e backfill inicial.** Migration em SQLite e em MySQL 8.4 · backfill
idempotente (rodar duas vezes produz o mesmo estado) · **imagem copiada
fisicamente** · **path da imagem do produto ≠ path da imagem da oferta** ·
arquivo da oferta existe em disco · FAQ copiada para a oferta quando houver
exatamente uma · **`product_faqs` permanece intacta na 02D** · pergunta mantém
`product_id` · pergunta recebe `product_offer_id` quando determinístico e
permanece nula quando não · FKs válidas e 0 órfãos · dados antigos preservados na
íntegra (§19) · `down()` remove a estrutura sem tocar na origem · **multi-oferta
continua inalcançável** pelo cadastro · nenhum guard de autorização alterado.

**Drift de imagem — o teste que fecha o R-6.**

```text
Product.images = A
  → backfill inicial
  → ProductOffer.images = A'   (cópia física; path A' ≠ path A)

Product.images passa a B      (pelo caminho legado, o único ativo)
  → reconciliação pré-cutover

esperado:
  Product.images       = B                    (inalterado pela reconciliação)
  ProductOffer.images  = B'                   (cópia física nova)
  path B'             ≠ path B                (nunca compartilhado)
  A'                   não é mais referenciado
  nenhum path de Product coincide com path de ProductOffer
```

**Transição da FAQ — quatro cenários.**

| # | Cenário | Esperado |
|---|---|---|
| 1 | FAQ comercial criada **antes** da 02D | Existe em `product_offer_faqs`; **a origem continua em `product_faqs`** e legível pelo reader legado durante a 02D |
| 2 | FAQ criada **entre** a 02D e a 02E | A reconciliação final a leva para `product_offer_faqs` sem duplicar a do cenário 1 |
| 3 | **Após o cutover da 02E** | `product_offer_faqs` contém a FAQ comercial; **`product_faqs` não contém mais nenhuma FAQ comercial migrada** |
| 4 | FAQ **sem oferta determinística** | Não migra por heurística, não é apagada, e o cutover/limpeza é **bloqueado** |

Os cenários 3 e 4 são testes da **02E** — ficam registrados aqui porque provam
invariantes (D12, D13) que a estrutura da 02D torna possíveis.

**Reconciliação exata da FAQ — os sete casos obrigatórios.** Os dois primeiros
são os únicos que o *upsert* cobria; os demais são exatamente o que ele não via.

| Caso | Origem antes → depois | Destino esperado após reconciliação |
|---|---|---|
| **Criação** | `[]` → `[A]` | `[A]` — posição 0, sem depender de estado anterior |
| **Edição** | `[A]` → `[A']` (pergunta/resposta alteradas) | `[A']` — versão nova, sem duplicar |
| **Remoção** | `[A, B]` → `[A]` | `[A]` — **B desaparece do destino** |
| **Limpeza total** | `[A, B]` → `[]` | **destino vazio** |
| **Reordenação** | `[A@0, B@1]` → `[B@0, A@1]` | exatamente a nova ordem, sem violar a `UNIQUE` |
| **Idempotência** | qualquer estado, reconciliar **duas vezes** | estado após a 1ª = estado após a 2ª |
| **Não determinístico** | FAQ de `Product` com **0 ou >1 ofertas** | **não sincroniza, não apaga, não infere** — e a contagem > 0 **bloqueia o cutover** (§16.6) |

Remoção, limpeza total e redução falhariam com `updateOrCreate`; a reordenação
falharia com atualização posicional incremental por causa da `UNIQUE` (§14.2); e
o caso não determinístico é o único da tabela em que a resposta correta é **não
escrever nada**. Juntos, são a prova de que o destino é projeção e não
acumulação.

**Paridade source × destination.** Além dos sete casos, a suíte precisa provar a
verificação do §19.1 diretamente: para cada `Product` com exatamente uma oferta,
`COUNT(source) = COUNT(destination)` e o conjunto ordenado
`(sort_order, question, answer)` idêntico dos dois lados — **comparado por
conteúdo, nunca por chave primária**, porque o writer legado apaga e recria e os
`product_faqs.id` não sobrevivem a um salvamento.

## 29. Dívidas preservadas

M-04 (curso AVA / G-10) · **M-05 (imagens canônicas graváveis sem autoridade —
a 02D cria o destino, a proteção é 02E/02F)** · M-08 · M-09 · M-10 · M-12 ·
M-13 · M-14 · M-16 · M-17 · D-1 (12 espelhos comerciais, remoção na 02H) ·
F-12 (SoftDeletes) · ausência de contagem de referências no `ImageService` ·
ausência de superfície de curadoria (G-1).

## 30. Conflitos encontrados com decisões anteriores

**Nenhum conflito.** Duas observações de compatibilidade, registradas para que a
revisão não as confunda com desvio:

**A D-CAT-16 previu migrar a FAQ existente para a oferta; não há FAQ existente.**
A decisão não foi ignorada — o backfill que ela pede será escrito e será correto;
apenas processa zero linhas neste banco. A recomendação de §14 é uma leitura
literal da decisão, não uma substituição.

**A D-CAT-14 fala de "imagem canônica" e "imagem da oferta" sem prescrever
estrutura.** A escolha de coluna JSON (§13) é decisão de implementação dentro do
espaço que a 02B deliberadamente deixou aberto, e não altera nenhuma decisão
congelada.

Um ponto de **sequenciamento**, que não é conflito mas precisa estar visível: a
02C preservou M-05 de propósito, então as imagens canônicas continuam graváveis
por qualquer ofertante sem autoridade. A 02D cria o destino da imagem da oferta
mas **não fecha essa lacuna** — ela permanece aberta até a 02E/02F. Enquanto
multi-oferta estiver desabilitada, ninguém a alcança.

## 31. Critérios para autorizar a implementação da CAT-DOM-02D

1. Os dezenove gates G-D1 a G-D19 fechados — **estão**.
2. As três recomendações estruturais (§13, §14, §15) aceitas pela revisão externa,
   ou substituídas por decisão explícita.
3. As regras de `onDelete` de §18 aceitas — em especial `SET NULL` nas perguntas
   e `CASCADE` na FAQ da oferta.
4. A regra do §17 — cópia física, nunca path compartilhado — aceita como
   invariante de implementação, com verificação própria na suíte.
5. Confirmação de que a 02D entrega estrutura **sem consumidor**, e de que a 02E
   está prevista para em seguida (§6).
6. Nenhuma expectativa de que a 02D corrija M-05, o guard das perguntas ou a
   ausência de contagem de referências no `ImageService` — os três são fases
   posteriores.
7. A distinção entre **backfill inicial** e **reconciliação final pré-cutover**
   (§16.0) aceita, incluindo a premissa de propriedade exclusiva do §16.5 e a
   regra que proíbe reconciliação destrutiva após o primeiro writer da 02E.
8. A obrigação de cutover da FAQ (§16.6) aceita como compromisso da 02E — a 02D
   **não pode ser implementada sem que a 02E esteja comprometida com o passo 6**,
   sob pena de a FAQ comercial virar canônica por omissão.
9. A separação entre migration (schema) e command (dados e arquivos) do §27.1
   aceita, e o tratamento de filesystem em `down()` do §27.2 entendido: **não há
   rollback atômico de banco e disco**.
