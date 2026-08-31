# CAT-DOM-02E — Writers, Readers e Cutover do Conteúdo por Oferta

**Quem escreve, de onde a aplicação lê, e como a troca aconteceu.**

A 02D respondeu *onde o dado mora* e entregou estrutura sem consumidor. Esta
fase é a que atravessa a fronteira: imagem, FAQ e contexto de pergunta deixam de
girar em torno de `Product` e passam a girar em torno de `ProductOffer`.

Estrutura e decisões congeladas:
[`CAT_DOM_02D_ESTRUTURA_CONTEUDO_POR_OFERTA.md`](CAT_DOM_02D_ESTRUTURA_CONTEUDO_POR_OFERTA.md)
·
[`CAT_DOM_02D_IMPLEMENTACAO_E_VALIDACAO.md`](CAT_DOM_02D_IMPLEMENTACAO_E_VALIDACAO.md)

---

## 1. Baseline

| | |
|---|---|
| Repositório | `D:/projeto-feira-esquerda-livre/feira-esquerda-livre` |
| Branch / HEAD | `main` · `3532bd151a2d662134ef05d777160a8493ffbbda` |
| Parent | `9d29f48525fb165424a9aa43e07241c0c9d2059b` |
| Suíte na árvore de `3532bd1` | **931 passed · 2657 assertions · 0 failed** |
| MySQL / PHP / Laravel | 8.4.11 · 8.3.33 · 12.65.0 |

Nenhum `docker compose down/build/up`, nenhum `migrate:fresh`, `migrate:refresh`
ou `db:wipe`. **Esta fase não criou nenhuma migration** — a estrutura já existia.

## 2. A regra que organiza tudo

> **Contexto comercial lê da oferta. Contexto canônico continua lendo do
> produto.**

Não é `grep Product.images && substituir`. A auditoria classificou cada ponto, e
a classificação está na §3. O que decidiu cada caso foi uma pergunta só: *esta
tela representa o item, ou representa a oferta de uma loja sobre o item?*

Na prática todos os readers vivos são comerciais, e todos já tinham a oferta em
mãos — a rota `loja.produto` resolve `$offer` desde a CAT-DOM-01, o painel do
lojista itera ofertas, o carrinho tem `product_offer_id` desde a 01C, e o
`ProductResource` já resolvia preço e estoque pela oferta. **Nenhum ponto
precisou inventar uma oferta para poder ler.**

## 3. Auditoria — writers

| Writer | Destino antes | Destino depois | Classificação |
|---|---|---|---|
| `SaveProductWithOffer` | `images`/`image_path` em `CAMPOS_DO_PRODUTO` | `images` em **`CAMPOS_DA_OFERTA`**; `image_path` fora do payload | comercial |
| `ProdutoForm::save()` | `products.images` + `image_path` | `product_offers.images` | comercial |
| `ProdutoForm::removeImage()` | apaga arquivo e grava `products.images` | apaga só arquivo da oferta e grava `product_offers.images` | comercial |
| `ProdutoForm::syncFaqs()` | `product_faqs` | **`product_offer_faqs`**, em transação | comercial |
| `ProdutoController::buildData()` | `products.images` + `image_path` | `product_offers.images` | comercial |
| `ProdutoController::syncFaqs()` | `product_faqs` | **`product_offer_faqs`**, em transação | comercial |
| `ProductQandA::submit()` | `product_id` só | `product_id` + **`product_offer_id`** da página | comercial |
| `ProductQuestionController::store()` | `product_id` só | `product_id` + **`product_offer_id`** resolvido | comercial |
| `DemoProductImageSeeder` | `products.images` | inalterado | **canônico** |
| `catalog:backfill-offer-content` | — | inalterado | migração |

O seeder de demonstração ficou de fora de propósito: ele povoa a imagem
**canônica** do catálogo, que é justamente o que a fase preservou.

## 4. Auditoria — readers

| Reader | Contexto | Antes | Depois |
|---|---|---|---|
| `catalogo/index.blade` | comercial (vitrine por eixo) | `$item->images` | `$oferta->urlDaImagemPrincipal('thumb')` |
| `loja/show.blade` | comercial (vitrine da loja) | `$product->images` | `$offer->urlDaImagemPrincipal('thumb')` |
| `loja/produto.blade` — galeria | comercial | `$product->images` | `$offer->imagensParaExibicao()` |
| `loja/produto.blade` — OG image | comercial | `$product->images` | `$offer->urlDaImagemPrincipal('medium')` |
| `loja/produto.blade` — outras ofertas | comercial | `$other->images` | `$otherOffer->urlDaImagemPrincipal('thumb')` |
| `loja/produto.blade` — FAQ | comercial | `$product->faqs` | **`$offer->offerFaqs`** |
| `welcome.blade` — destaques | comercial | `$product->image_path` | `$oferta->urlDaImagemPrincipal('medium')` |
| `cart-drawer.blade` | comercial | `$item->product->images` | `$item->offer->imagensParaExibicao()` |
| `checkout.blade` | comercial | `$item->product->images` | `$item->offer->imagensParaExibicao()` |
| `produto-index.blade` (painel) | comercial | `$product->main_image_url` | `$offer->urlDaImagemPrincipal('thumb')` |
| `ProductResource` | comercial (já resolvia oferta) | `$this->images` / `faqs` | `$oferta->imagensParaExibicao()` / `offerFaqs` |
| `ProductShareImageService` | comercial (já recebia oferta) | `$product->images` | `$offer->imagensParaExibicao()` |
| `lojista/ava/curso-index.blade` | comercial (curso do próprio lojista) | `$product->image_path` | `$item['offer']->urlDaImagemPrincipal('medium')` |
| `cliente/ava/aprendizado-index.blade` | apresentação (capa do curso matriculado) | `$product->image_path` | `$product->ofertaVigente?->urlDaImagemPrincipal('medium')` — ver §5.5 |
| `Product::getMainImageUrlAttribute()` | **canônico** | — | **inalterado** |
| `Product::faqs()` | **canônico** | — | **inalterado** |

### `main_image_url` não virou dependente de oferta

Era o risco do §15 do escopo: transformar um accessor de `Product` em algo que
escolhe um vendedor. `Product` não deve escolher vendedor — e não escolhe. O
accessor continua olhando só as colunas canônicas; quem precisa da imagem
comercial pergunta à oferta. `ProductResource` usa o accessor apenas como último
recurso, quando não há oferta resolvida.

## 5. Imagens

### 5.1 O fallback, centralizado

Duas funções em `ProductOffer`, e nenhuma lógica de fallback espalhada por
Blade, Resource, Controller ou Service:

```php
ProductOffer::imagensParaExibicao(): array
ProductOffer::urlDaImagemPrincipal(string $tamanho = 'medium'): ?string
```

```text
ProductOffer.images    imagem que o lojista enviou
→ Product.images       imagem canônica do item
→ Product.image_path   espelho legado do primeiro medium (dívida D-1)
→ []                   quem chama decide o placeholder
```

### 5.2 Fallback é leitura, nunca persistência

O ponto mais fácil de errar da fase inteira, e há teste dedicado a ele. Copiar o
path canônico para dentro de `ProductOffer.images` durante uma leitura
recriaria o compartilhamento de arquivo físico que o §17 da 02D proíbe — e
`ImageService::delete()` apaga por caminho **sem contar referências** (M-05),
então o lojista removendo a imagem dele levaria junto a do catálogo.

### 5.3 Remoção

`removeImage()` grava na oferta e só apaga do disco o arquivo que **nada mais
referencia**:

```text
o path da imagem removida também aparece em Product.images
ou em Product.image_path?
    → o arquivo FICA no disco (órfão temporário, não perda)
senão
    → o arquivo sai
```

O caso que isso protege é concreto: uma oferta que veio do backfill da 02D e
ainda referencia o mesmo arquivo da canônica. Sem a checagem, a primeira remoção
comercial apagaria a foto do item para todo mundo.

### 5.4 `image_path` continua existindo e parou de ser escrito

Nenhuma coluna foi removida, nenhuma migration destrutiva rodou, e
`product_offers.image_path` **não foi criada** — importar a dívida D-1 para a
estrutura nova seria criar hoje o problema que a 02H vai apagar.

O que acabou foi o efeito colateral: gravar a imagem da oferta não atualiza mais
`products.image_path`. Ela permanece como espelho canônico, lida pelo fallback.

### 5.5 As capas do AVA — e o que `ofertaVigente` significa ali

Parar o write-through em `products.image_path` teve uma consequência que só
aparece adiante: as duas telas do AVA liam essa coluna, e um produto digital
**criado depois do cutover** passaria a exibir card sem capa. Isso é regressão
introduzida por esta fase, e por isso foi corrigida aqui.

No **painel do lojista** não há ambiguidade: `CursoIndex` já carrega
`'offer' => $product->offers` escopado ao próprio expositor, então a capa vem da
oferta dele.

Na **área do aluno** o vínculo correto não existe: `AvaEnrollment` não guarda
qual oferta foi comprada, e criar esse vínculo seria redesenhar o AVA — fora do
escopo desta fase. A capa usa `ofertaVigente`, e o significado disso é
**estritamente limitado**:

> **`ofertaVigente` na área do aluno é apenas fallback de apresentação da capa.**
>
> Ela **não** significa:
>
> ```text
> a oferta originalmente comprada
> autoria histórica do conteúdo
> o vendedor da matrícula
> ```
>
> É ilustração do item, não afirmação sobre quem vendeu. Nenhuma decisão de
> autoria, autorização, financeiro ou histórico se apoia nesse valor.

**Dívida registrada: vínculo histórico matrícula → oferta.** Enquanto o AVA for
1:1 com o produto, a ausência do vínculo não produz erro visível. Se multi-oferta
ou o AVA passarem a exigir a verdade sobre *qual oferta originou a matrícula* —
para autoria, para suporte ou para o financeiro —, esse vínculo terá de ser
criado, e é assunto de fase futura. **O AVA não foi redesenhado aqui.**

## 6. FAQ

### 6.1 Writer

`syncFaqs` do painel e da API escrevem em `product_offer_faqs`. A semântica da
tela não mudou — substituição integral do conjunto, posição pelo índice do array
—, mas agora dentro de uma transação: `UNIQUE(product_offer_id, sort_order)`
transformaria um conjunto meio apagado em colisão na inserção seguinte.

O comportamento da API que distingue `null` (não falei de FAQ) de `[]` (não
quero nenhuma) foi preservado integralmente.

### 6.2 Reader — decisão normativa fechada

A D-CAT-16 separa os dois conjuntos por autoria, mas não prescrevia semântica de
exibição. A revisão externa fechou essa lacuna, e a regra abaixo passa a ser
**normativa** para esta trilha:

> **D-02E-1 — Semântica de exibição da FAQ.**
>
> ```text
> ProductFaq       = FAQ canônica          (afirmação do catálogo, curadoria)
> ProductOfferFaq  = FAQ comercial         (afirmação do vendedor, oferta)
> ```
>
> Na página comercial da oferta, o **reader primário é
> `ProductOffer.offerFaqs`**.
>
> É **proibido** o fallback automático `ProductOfferFaq → ProductFaq`, e é
> **proibido** concatenar silenciosamente os dois conjuntos numa lista só.
>
> **Razão:** FAQ canônica e FAQ comercial têm autoridade, autoria e semântica
> diferentes.
>
> Se uma fase futura decidir exibir a FAQ canônica numa página comercial, ela
> **deverá aparecer de forma explicitamente diferenciada** — seção própria e
> rotulada —, e **nunca como se fosse resposta do vendedor**.

O comportamento implementado já é aderente: nenhuma linha de código mudou por
causa desta decisão.

**Por que a assimetria com a imagem é legítima.** A foto do item é a mesma
independentemente de quem vende, então exibir a canônica quando a oferta não tem
uma não engana ninguém — é ilustração do item. Uma **resposta** não é assim:
"entrego em três dias" é prática de quem respondeu. Mostrar a afirmação do
catálogo no lugar da resposta do vendedor, sem o leitor saber a diferença, é
precisamente a confusão que a D-CAT-16 existiu para desfazer.

Referência às decisões congeladas: D-CAT-16 (separação dos dois conjuntos) e
D-CAT-18 (promoção só por curadoria), em
[`CAT_DOM_02B_AUTORIDADE_E_CURADORIA_DO_CATALOGO.md`](CAT_DOM_02B_AUTORIDADE_E_CURADORIA_DO_CATALOGO.md).
**A 02B não foi reaberta** — a D-02E-1 não contradiz nenhuma decisão dela;
resolve, no plano da apresentação, o que a 02B deliberadamente deixou em aberto.

Consequência prática hoje: sem superfície de curadoria (dívida G-1),
`product_faqs` permanece vazia, e a página comercial exibe exclusivamente o que
o lojista escreveu.

### 6.3 Nenhuma promoção, nenhuma sincronização

Não há nada que copie FAQ da oferta para a canônica. A promoção é ato de
curadoria (D-CAT-18) e pertence a outra fase.

## 7. Perguntas

### 7.1 Storefront

A rota `loja/{loja}/{produto}` já resolvia `$offer` pela URL. O componente
`ProductQandA` passou a recebê-la e a gravá-la. **Sem contexto, não grava**:
`abort(422)`. Nada de `first()`, `products.expositor_id` ou delegação canônica.

A **leitura** continua por produto, de propósito: quem abre a página quer ver o
que já foi perguntado sobre o item, e a resposta de outro lojista sobre o mesmo
item continua sendo informação útil. Quem *pode responder* é assunto da 02F.

### 7.2 API — o contrato

`POST /api/v1/produtos/{product}/perguntas` ganhou `product_offer_id`,
**opcional**:

```text
campo informado          → valida que a oferta é DESTE produto; se não for, 422
campo ausente + 1 oferta → resolve pela cardinalidade determinística
campo ausente + 0 ou >1  → 422, nunca infere
```

Opcional para não quebrar cliente algum. A compatibilidade é datada: quando a
aplicação habilitar multi-oferta, uma fase futura pode reavaliar torná-lo
obrigatório.

**`ofertaVigente` foi auditada e rejeitada para este uso.** Ela ordena por preço
e devolve a mais barata — resolução legítima para *exibir* preço, e péssima para
*endereçar* uma pergunta: mandaria o cliente falar com um vendedor que ele não
escolheu. Há teste que falha se alguém a reaproveitar aqui por simetria com o
resto da API.

### 7.3 `answered_by` inalterado

Continua sendo `users.id`. Nenhuma coluna de autoria comercial foi criada — isso
é 02F, e há teste que falha se aparecer.

## 8. O cutover

### 8.1 Como o writer legado foi cessado

Honestamente: **pela própria troca de código**. Este é um ambiente local de
desenvolvimento, com um operador só, sem tráfego público; os containers servem a
aplicação a partir do bind mount, então o writer legado deixou de existir no
instante em que os arquivos mudaram. Não há mecanismo distribuído a construir, e
inventar um seria arquitetura para um problema que este ambiente não tem.

O que **substitui** a garantia, e é verificável, está na §8.3.

### 8.2 Desvio de ordem operacional — declarado

**A sequência normativa, obrigatória para implantação com tráfego, é:**

```text
writer legado bloqueado
→ reconciliação final
→ validação
→ ativação dos writers da 02E
```

**O que aconteceu nesta implementação local:**

```text
writers da 02E alterados primeiro
→ bind mount tornou o código imediatamente disponível
→ dry-run posterior comprovou zero drift
```

A ordem prescrita estava disponível e **não foi seguida**. Isso fica registrado
como desvio, e não é apresentado como equivalente ao roteiro.

**Condições do ambiente no momento do cutover:**

| | |
|---|---|
| Operadores | um |
| Tráfego concorrente | nenhum |
| `product_offer_faqs` antes do cutover | **0 linhas** |
| Projeções de imagem | **75/75 byte-fiéis** à origem, conferidas por hash |
| Paths compartilhados produto ↔ oferta | **0** |
| Arquivos da oferta ausentes em disco | **0** |
| FAQ não resolvida | **0** |
| Dry-run da reconciliação | **0 substituições · 0 cópias · 0 remoções** |

**Conclusão registrada:**

> Não houve perda nem divergência de dados, **mas a ordem operacional prescrita
> não foi seguida**.
>
> Para esta execução local, a equivalência do estado pré-cutover foi comprovada
> por evidência objetiva.
>
> Isso **não altera** o roteiro obrigatório de uma implantação futura com
> tráfego real.

### 8.3 O guard não foi burlado nem enfraquecido

`--reconciliar` exige `--confirmar-sem-writers-02e`, que declara literalmente que
nenhum writer da 02E foi habilitado. **Depois da ativação dos writers essa
declaração já não seria semanticamente verdadeira**, e passá-la seria mentir para
um guard — ainda que o efeito fosse nulo.

Então ela **não foi passada**. Também **não** se alterou o guard para acomodar
esta execução, e **não** se enfraqueceu `BackfillOfferContent` de forma alguma: o
comando continua exatamente como a 02D o publicou, recusando execução não
interativa sem a flag.

**Esta exceção é local e não vira regra operacional.** O procedimento de produção
permanece o do §8.2: desligar o writer legado, rodar
`--reconciliar --confirmar-sem-writers-02e` enquanto a declaração ainda é
verdadeira, validar, e só então subir o código novo.

### 8.3.1 Status da reconciliação

```text
GATE DE INTEGRIDADE DA RECONCILIAÇÃO:
SATISFEITO POR EVIDÊNCIA OBJETIVA
```

Deliberadamente **não** se diz "reconciliação executada conforme roteiro", porque
isso não ocorreu. O que se afirma é o que foi provado: o estado do destino já era
idêntico ao que a reconciliação produziria, e a verificação de paridade do
próprio comando fechou (`Integridade verificada`).

### 8.4 Passo 6 — a FAQ comercial legada

`catalog:backfill-offer-content --limpar-faq-legada`.

Não é `DELETE FROM product_faqs`. A tabela **não guarda autoria**: nada na linha
diz se veio de um lojista ou da curadoria, então apagar por tabela destruiria FAQ
canônica legítima junto com a comercial.

A remoção é por **prova de correspondência**, linha a linha:

```text
para cada linha de product_faqs:
    produto tem exatamente uma oferta?  não → PRESERVA e reporta
    existe (question, answer) idêntico em product_offer_faqs dessa oferta?
        sim → remove
        não → PRESERVA e reporta (pode ser canônica)
```

O comando exige paridade fechada antes de apagar qualquer coisa: sem ela, sai com
código 1 e não toca no banco.

### 8.5 Sequência realmente executada

```text
1.  auditoria de writers e readers                      §3, §4
2.  código novo escrito (writers, fallback, readers)
3.  testes dirigidos verdes
4.  métricas pré-cutover no MySQL real                  §9
5.  dry-run da reconciliação → zero drift               §8.3
6.  prova de propriedade exclusiva do destino           §8.2
7.  --limpar-faq-legada --simular → 0 / 0
8.  --limpar-faq-legada (real)   → 0 removidas, 0 preservadas
9.  métricas pós-cutover                                §9
10. operação comercial real no MySQL, em transação revertida §10
11. suíte completa                                      §12
```

## 9. Métricas — MySQL real

| | Pré-cutover | Pós-cutover |
|---|---:|---:|
| `products` | 75 | 75 |
| `product_offers` | 75 | 75 |
| Produtos com exatamente 1 oferta | 75 | 75 |
| `products.images` preenchidos | 75 | **75** |
| `products.image_path` preenchidos | 75 | **75** |
| `product_offers.images` preenchidos | 75 | 75 |
| `product_faqs` | 0 | 0 |
| `product_offer_faqs` | 0 | 0 |
| `product_questions` | 0 | 0 |
| Perguntas com `product_offer_id` | 0 | 0 |
| **Paths compartilhados produto ↔ oferta** | **0** | **0** |
| Arquivos da oferta ausentes em disco | 0 | 0 |
| FAQs não resolvidas | 0 | 0 |
| Migrations pendentes | 0 | 0 |

A imagem canônica saiu intacta: os mesmos 75 registros de `images` e de
`image_path` de antes.

## 10. Operação comercial real

Executada no MySQL do Docker, dentro de uma transação revertida — sem DDL, sem
`ANALYZE TABLE`, sem resíduo:

```text
oferta #1 / produto #94
  lojista troca a imagem da oferta
  lojista escreve FAQ comercial
  cliente pergunta na página da oferta

products.images intacto        : SIM
products.image_path intacto    : SIM
product_faqs (canônica)        : 0
product_offer_faqs da oferta   : 1
pergunta product_id / offer_id : 94 / 1
imagem exibida pela oferta     : products/nova_m.webp

após ROLLBACK → offer_faqs 0 · questions 0 · product_faqs 0
```

## 11. Testes

### 11.1 Novos

| Arquivo | Testes | Cobre |
|---|---:|---|
| `ConteudoComercialDaOfertaTest` | 11 | fallback nos 4 estados · fallback não grava · upload não toca a canônica · zero path compartilhado · **remoção preserva a canônica** · remoção não apaga arquivo ainda referenciado · página exibe oferta e cai para canônica |
| `Api/V1/PerguntaContextoDaOfertaApiTest` | 8 | os 6 cenários do contrato · oferta inexistente · **nunca resolve pela mais barata** |
| `CutoverFaqComercialTest` | 9 | remove só o que tem par exato · preserva sem correspondente · correspondência parcial não autoriza · 0/>1 ofertas intocados · simulação · idempotência · comando recusa sem paridade |

### 11.2 Evoluídos, e por quê

**`FronteiraConteudoPorOfertaTest`** teve a expectativa **invertida**, não
apagada. Na 02D ele provava que a estrutura existia e ninguém a usava — era a
garantia daquela fase. A 02E é a fase que atravessa essa fronteira: manter as
asserções antigas exigiria que o cutover não tivesse acontecido, e apagá-las
perderia a prova de que ele aconteceu inteiro. Cada teste virou o seu oposto
exato, e ganhou dois: a FAQ canônica sobrevivendo à edição comercial, e a 02F não
antecipada.

`ProductFaqTest`, `ProductQandATest` e `CatalogoIsolamentoTest` tiveram as
asserções de destino atualizadas — `product_faqs` → `product_offer_faqs`,
`products.images` → `product_offers.images`. **Nenhuma asserção de autorização
mudou**; os 21 testes de isolamento do `CatalogoIsolamentoTest` passaram sem
alteração de expectativa, o que é a prova de que a SEC-02 não foi tocada.

## 12. Regressões e suíte

**Suíte completa: 962 passed · 2745 assertions · 0 failed** (559s), contra o
baseline de 931 / 2657 / 0 da árvore de `3532bd1` — 31 testes e 88 assertions a
mais.


| Trilha | Resultado |
|---|---|
| CAT-DOM-02C | `AutoridadeCanonicaTest`, `IntegridadeDoCatalogoTest`, `ProdutoMestreOfertaTest` — verdes, sem alteração |
| CAT-DOM-02D | schema, backfill, reconciliação, zero path compartilhado, projeção exata da FAQ, `SET NULL` — verdes |
| SEC-02 | `CatalogoIsolamentoTest` — 21 verdes, nenhuma expectativa de autorização alterada |
| FIN-SEC | ciclo, estoque, reversão, snapshot, conflitos — intocada |
| AVA | verde. Duas capas passaram a ler a oferta (§5.5) para não regredir com o fim do write-through; **nenhuma decisão G-10**, nenhum redesenho, nenhuma mudança em ownership, authoring ou `Product ↔ Course` |
| Catalog Intelligence | `Product` continua o eixo canônico; nada de FAQ, pergunta ou imagem da oferta virou conhecimento canônico |

## 13. Fronteiras preservadas

02F **não iniciada** · multi-oferta **não habilitada** · nenhum ownership novo ·
nenhum workflow de proposta ou contribuição · nenhuma promoção a canônico ·
nenhuma curadoria operacional · `answered_by` inalterado · `products.image_path`
não removido · `product_offers.image_path` não criado · nenhuma migration ·
FIN-SEC intacta · AVA não redesenhado (§5.5).

## 14. Riscos e dívidas que continuam abertos

**M-05 permanece.** As imagens canônicas continuam graváveis sem autoridade —
agora com menos superfície, porque o formulário do lojista não escreve mais nelas.
O guard é 02F.

**R-2 permanece latente.** A pergunta agora carrega o destinatário; *quem pode
responder* continua sendo a autorização de hoje. É a 02F que fecha.

**`ImageService` continua sem contagem de referências.** A 02E não conserta —
compensa, checando os paths canônicos antes de apagar.

**Arquivos órfãos.** Remover imagem cujo path a canônica ainda referencia deixa o
arquivo em disco. É desperdício e não perda; a limpeza é operação explícita de
outra fase.

**Semântica de exibição da FAQ canônica** — **fechada** pela D-02E-1 (§6.2). O
que continua aberto é apenas *se* uma fase futura vai exibir a FAQ canônica numa
página comercial; se exibir, terá de ser em seção explicitamente diferenciada.

**Vínculo histórico matrícula → oferta no AVA** — não existe, e a capa do aluno
usa `ofertaVigente` apenas como apresentação (§5.5). Vira necessário se
multi-oferta ou o AVA passarem a exigir a verdade sobre qual oferta originou a
matrícula.

**Pint:** `ProductQandA.php`, `ProductFaqTest.php`, `ProductQandATest.php` e
`ProductQuestion.php` têm violações de estilo **preexistentes**, verificadas
contra a árvore de `3532bd1`, em regiões que esta fase não tocou. Registradas e
não corrigidas — reformatá-las poria no diff da 02E mudanças que não são dela.

## 15. Pendências para a CAT-DOM-02F

1. Guard "esta oferta é sua?" sobre imagem, FAQ e pergunta — a estrutura agora
   permite perguntá-lo sem ambiguidade.
2. Isolamento A × B com teste antes da alteração (R-9).
3. Autoria comercial da resposta, derivável por `question.offer.expositor` sem
   coluna nova.
4. Fechar M-05: autoridade para escrever a imagem canônica.
5. Superfície de curadoria (G-1) — sem ela, `product_faqs` permanece vazia e a
   FAQ canônica não tem como nascer.

---

**Status:** writers e readers migrados, cutover concluído, FAQ comercial legada
tratada, `Product` preservado como verdade canônica e `ProductOffer` como verdade
comercial. Multi-oferta não habilitada, 02F não iniciada.

```text
FAQ                      — decisão normativa D-02E-1 fechada (§6.2)
CUTOVER                  — desvio de ordem operacional documentado (§8.2)
GATE DE INTEGRIDADE      — satisfeito por evidência objetiva (§8.3.1)
GUARD DO BACKFILL        — inalterado; exceção local não vira regra (§8.3)
SUÍTE COMPLETA           — 962 passed · 2745 assertions · 0 failed
```
