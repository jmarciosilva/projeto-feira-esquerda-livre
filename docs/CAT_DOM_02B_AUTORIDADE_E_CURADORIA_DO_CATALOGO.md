# CAT-DOM-02B — Autoridade, Curadoria e Conteúdo do Catálogo

> **Projeto:** Feira Esquerda Livre
> **Trilha:** Catalog Intelligence — fase intermediária de domínio
> **Classificação:** decisão arquitetural e de domínio
> **Momento:** depois da CAT-DOM-01, da auditoria CAT-DOM-02 e da CAT-DOM-02A; antes de qualquer implementação da CAT-DOM-02C
> **Natureza desta fase:** exclusivamente decisória e documental. Nenhum código, nenhuma migration, nenhuma tabela, nenhum teste.
> **Baseline:** `1c5a6bd01cd69a9f48fefbedac491a909a918102`

---

## 1. Por que esta fase existe

A CAT-DOM-01 separou **o que um item é** de **quem o vende**, e essa separação está
completa e provada: preço, estoque, dimensões, destaque, ordem e dono vivem em
`ProductOffer`, e nenhuma superfície comercial lê essas colunas de `products`.

O que ela não respondeu — e não tinha como responder sem uma decisão de negócio —
é a pergunta seguinte:

> **Quem tem autoridade para alterar o `Product` global?**

Enquanto cada produto tem exatamente uma oferta, a pergunta não aparece. Quem
cadastrou é o único afetado por qualquer alteração que faça. No dia em que
existir

```text
Product #10
├── ProductOffer #100 → expositor A
├── ProductOffer #101 → expositor B
└── ProductOffer #102 → expositor C
```

a mesma pergunta passa a ter consequência imediata: hoje, **A altera o nome, a
descrição e as imagens do produto e B e C herdam a alteração**, sem que ninguém
tenha autorizado isso. Está registrado em teste
(`ProdutoMestreOfertaTest::test_produto_compartilhado_nao_da_acesso_a_oferta_alheia`)
como limite conhecido, e é a dívida D-2 da CAT-DOM-01.

Esta fase elimina a ambiguidade. Ela **decide**; não implementa.

---

## 2. O que já está decidido e não se reabre

| Princípio | Origem |
|---|---|
| `Product` é a identidade global do item; `ProductOffer` é a relação comercial entre `Product` e `Expositor` | CAT-DOM-01 |
| Expositor deixa de vender → a oferta é encerrada; o `Product` **não** é destruído por isso | CAT-DOM-01 |
| Um lojista é dono da **sua oferta**, nunca da identidade global | CAT-DOM-01 / SEC-02 |
| `expositor_id` da oferta jamais é recalculado a partir de quem está salvando | SEC-02 |
| Produtos artesanais semelhantes **não** são fundidos automaticamente | CAT-DOM-01 §9 |
| Customer Intelligence aprende primariamente sobre `Product` | CAT-DOM-01 §8 |
| Pedido é fato histórico; snapshot não é reescrito pelo catálogo vivo | FIN-SEC-01 |
| Estoque pertence à oferta | D-FIN-17 |

---

## 3. Evidências que sustentam as decisões

Levantadas na auditoria CAT-DOM-02 e reconferidas nesta fase sobre o baseline
`1c5a6bd`. As decisões abaixo se apoiam nelas, não em suposição.

**E-1 — Não existe nenhuma chave objetiva de identidade de produto.**
`products` não tem GTIN, EAN, SKU de fabricante, marca, modelo nem identificador
externo. As colunas são: `id`, `item_type`, `expositor_id`, `category_id`,
`name`, `slug`, `short_description`, `description`, `image_path`, `images`, mais
as treze colunas comerciais legadas, `is_active`, `is_digital` e as de tempo.
Dois itens só podem ser declarados "o mesmo produto" por juízo humano.

**E-2 — Não existe nenhuma superfície administrativa de produtos.** O painel
admin conta `Product::count()` no dashboard e nada mais. Não há CRUD, não há
tela de moderação de catálogo.

**E-3 — A permissão de curadoria já existe, sem superfície.**
`produtos.moderar` está declarada em `RolePermissionSeeder::PERMISSIONS` e
concedida a `administrador`, `gerente` e `supervisor`. `editor` **não** a possui.
A autoridade já está nomeada no modelo de permissões; falta a tela.

**E-4 — O compartilhamento de um `Product` é hoje inalcançável pela aplicação.**
`SaveProductWithOffer` só cria oferta junto com um produto novo; não há tela nem
endpoint de "passar a vender um item do catálogo". Provado por
`test_cadastro_sempre_cria_produto_novo_e_nunca_uma_segunda_oferta`.

**E-5 — FAQ e perguntas são exibidas na página da oferta, mas gravadas só com
`product_id`.** `resources/views/loja/produto.blade.php` é a página
`/loja/{loja}/{produto}` — que resolve uma oferta específica — e monta ali tanto
o bloco de FAQ (`$product->faqs`) quanto `<livewire:product-qand-a
:product="$product" />`. Nenhum dos dois carrega a oferta.

**E-6 — `answered_by` é um usuário, não uma loja.** `product_questions` guarda
quem respondeu como `user_id`. A autoria comercial da resposta não é registrada.

**E-7 — O guard de FAQ, perguntas e curso é "você tem uma oferta sobre este
produto?".** É a pergunta certa para o que é da oferta e a pergunta errada para
o que é do produto.

**E-8 — `ImageService::delete()` recebe caminhos crus, sem verificação de
propriedade**, e `ProdutoForm::removeImage()` apaga o arquivo do disco antes de
qualquer outra consideração.

**E-9 — O volume atual torna a migração barata.** 75 produtos, 75 ofertas, 14
expositores, **0 FAQs, 0 perguntas, 0 itens de carrinho, 0 itens de pedido, 0
associações de conhecimento**, 1 curso. Nenhuma reclassificação em massa de
conteúdo existente será necessária se a estrutura for definida agora.

---

## 4. Questão 1 — autoridade sobre `Product`

### 4.1 Classificação dos campos

| Campo | Classificação | Autoridade proposta | Seller edita direto? | Curadoria necessária? |
|---|---|---|:--:|:--:|
| `id` | G4 técnico | sistema | não | não |
| `created_at` · `updated_at` | G4 técnico | sistema | não | não |
| `name` | **G1** identidade canônica | plataforma | **sob delegação** (§4.3) | sem delegação válida |
| `slug` | **G1** identidade canônica | plataforma/sistema | não — derivado | não |
| `item_type` | **G3** classificação canônica | plataforma | **sob delegação** | sem delegação válida |
| `category_id` | **G3** classificação canônica | plataforma | **sob delegação** | sem delegação válida |
| `is_digital` | **G3** classificação canônica | plataforma | **sob delegação** | sem delegação válida |
| `short_description` | **G2** conteúdo canônico | plataforma | **sob delegação** | sem delegação válida |
| `description` | **G2** conteúdo canônico | plataforma | **sob delegação** | sem delegação válida |
| `images` | **H** híbrido — ver §6 | desdobra em dois conceitos | ver §6 | ver §6 |
| `image_path` | **L** legado (espelho de `images[0].medium`) | acompanha `images` | ver §6 | ver §6 |
| `is_active` | **G4** estado canônico global — ver §4.6 | **curadoria, exclusivo** | **não** | sim |
| `expositor_id` | **L** legado — ver §4.7 | proveniência, sem autoridade | não | não |
| `price` · `price_type` · `modality` · `duration_min` | **L** espelho comercial | oferta | n/a | não |
| `weight` · `height` · `width` · `length` | **L** espelho comercial | oferta | n/a | não |
| `has_stock` · `stock_quantity` | **L** espelho comercial | oferta | n/a | não |
| `is_featured` · `sort_order` | **L** espelho comercial | oferta | n/a | não |

"**Sob delegação**" na coluna *Seller edita direto?* remete à concessão de §4.3 —
um fato de governança —, e **nunca** a "o produto tem uma oferta só". A distinção
está em §4.3.1 e não é redacional: é o que impede a coluna de virar uma regra de
autorização por contagem.

Não existem `brand`, `tags`, `attributes` nem campos de SEO em `products` (E-1).
As perguntas do escopo sobre esses campos ficam sem objeto — e a ausência de
qualquer identificador de fabricante é ela própria uma evidência central,
tratada em §10.

### 4.2 Modelos avaliados

| Modelo | Descrição | Por que não |
|---|---|---|
| **A** | Primeiro expositor cria e permanece autoridade global | Contradiz a invariante de que o `Product` não pertence a expositor. Pior: o primeiro pode sair da Feira, e o produto ficaria com uma autoridade que não existe mais |
| **B** | Qualquer expositor com oferta edita o `Product` | É o estado atual e é exatamente a dívida D-2. B destrói o trabalho de A sem nenhum ato deliberado |
| **C** | Expositor propõe, curadoria aprova, sempre | Correto no limite e **inviável hoje**: não há superfície de curadoria (E-2), e submeter todo cadastro a fila humana travaria a operação de 14 lojas por um problema que ainda não existe (E-4) |
| **D** | Expositor edita enquanto houver uma única oferta; a autoridade muda quando surgir a segunda | Direção certa. Fraqueza: a regra mudaria "sozinha" sob os pés de quem escreveu tudo |
| **E** | Modelo próprio, derivado da auditoria | **Escolhido** — §4.3 |

### 4.3 Decisão — delegação explícita, com a curadoria como única porta do compartilhamento

O defeito do modelo D é a mudança silenciosa de regra. Ele desaparece quando se
observa E-1 e E-4 juntos: **um `Product` compartilhado não pode nascer por
acidente.** Não há chave de identidade que permita ao sistema concluir sozinho
que dois itens são o mesmo, e não há caminho na aplicação que crie uma segunda
oferta sobre produto existente. Compartilhar só pode ser um ato humano
deliberado.

Isso permite um modelo que é ao mesmo tempo seguro e utilizável:

1. **A autoridade sobre `Product` é sempre da plataforma.** Nunca de um
   expositor. Isso vale *de jure* em qualquer estágio, inclusive quando existe
   uma oferta só.
2. **A plataforma pode conceder ao expositor que originou o `Product` uma
   delegação para editar diretamente os campos G1/G2/G3 durante o estágio
   inicial não compartilhado.** É delegação — explícita, nomeada, revogável, e
   sem transferência de propriedade.
3. **A delegação termina quando o `Product` é formalmente compartilhado.** A
   partir daí, campos canônicos só mudam por proposta aprovada.
4. **Só a curadoria pode tornar um `Product` compartilhado.** Nunca o cadastro,
   nunca semelhança de nome, nunca automação.
5. **A curadoria pode revogar a delegação sobre um `Product` específico a
   qualquer momento**, sem depender de segunda oferta — por qualidade ou abuso.

Como o compartilhamento é um ato de curadoria, quem muda a regra é a mesma
pessoa que decide compartilhar. A mudança deixa de ser surpresa e passa a ser
consequência conhecida de uma decisão tomada por alguém.

#### 4.3.1 Autoridade não é cardinalidade

Esta é a fronteira central do modelo, e ela precisa ficar escrita antes de
qualquer implementação:

> **Autoridade canônica ≠ quantidade atual de ofertas.**
>
> A quantidade de ofertas é **estado comercial**. A autoridade sobre `Product` é
> **estado de governança**. São coisas diferentes e não podem ser confundidas.

O estágio inicial não compartilhado é a *ocasião* em que a plataforma concede a
delegação — **não o mecanismo que a concede**. A delegação, quando existir,
**deverá** ser um fato de governança declarado, e não uma condição recalculada a
cada requisição a partir do catálogo.

Portanto, a delegação:

- **não** representa *ownership* do `Product`;
- **não** decorre automaticamente da quantidade de ofertas;
- **não** pode ser inferida por `products.expositor_id` (D-CAT-11);
- **não** pode ser inferida apenas por uma contagem de `ProductOffer`;
- é **revogável** pela curadoria a qualquer momento;
- **termina** quando o `Product` é formalmente compartilhado;
- **não retorna automaticamente** se o número de ofertas voltar a um;
- **deverá possuir regra de governança explícita** quando for implementada.

Nenhuma regra futura de autorização deve ser escrita na forma "se o produto tem
uma oferta, então o expositor edita". Essa formulação confunde um sintoma
comercial com uma concessão de governança, e é exatamente a ambiguidade que esta
seção existe para eliminar.

#### 4.3.2 Cenário obrigatório — o retorno a uma oferta

O caso que distingue as duas leituras, e que qualquer implementação futura
**deverá** satisfazer:

```text
1.  Expositor A cria o Product P
    → A recebe delegação inicial sobre os campos canônicos de P.

2.  A curadoria vincula o Expositor B ao mesmo Product P
    → P torna-se formalmente compartilhado.
    → a delegação inicial de A TERMINA.

3.  B remove posteriormente a oferta dele
    → P volta a ter uma única oferta — a de A.

    Resultado:
    → A **NÃO** recupera autoridade canônica automaticamente.
    → P permanece um produto compartilhado sob autoridade da curadoria.
    → Qualquer retorno de delegação a A é um ato explícito de curadoria.
```

O passo 3 é o teste conceitual da decisão. Um modelo que deduzisse autoridade da
cardinalidade devolveria a A, em silêncio, o poder de reescrever a identidade de
um item que já foi compartilhado — e o faria sem que ninguém tivesse decidido
isso. O conhecimento acumulado, as propostas já aprovadas e o histórico de
curadoria de P não desaparecem porque B saiu.

#### 4.3.3 Como a delegação será representada

**Não é decidido aqui.** A CAT-DOM-02B não escolhe entre coluna, tabela, estado,
*policy*, *permission*, *workflow*, entidade de contribuição ou camada de
governança de catálogo. Essa escolha pertence às fases de implementação.

Fica registrado apenas o invariante que qualquer uma dessas opções terá de
respeitar:

> A implementação futura **deverá representar a autoridade de forma explícita** e
> **não poderá deduzi-la exclusivamente da cardinalidade de `ProductOffer`**.

> **Fica definido:** o expositor nunca é dono do `Product`. Quando editar campos
> canônicos, ele o faz sob delegação válida da plataforma — uma concessão
> operacional, revogável e explícita, não um direito adquirido nem uma
> consequência de quantos vendem o item.

### 4.4 Criação de produto novo

Quando um expositor cadastra algo que ainda não existe no catálogo:

- O `Product` **deverá** nascer imediatamente, ativo e utilizável. Não nasce como
  candidato, não entra em fila.
- A oferta **deverá** nascer junto e poder ser publicada na hora.
- O autor original **deverá** ser registrado como **proveniência** (§4.7), nunca
  como proprietário.
- A curadoria **não** participa do caminho de cadastro.

Essa é a resposta direta ao risco de gargalo (§5 desta seção e §15 do escopo):
**a curadoria não fica no caminho crítico da operação diária.** Ela atua sobre
compartilhamento, sobre conteúdo canônico de produto já compartilhado e sobre
exceções.

### 4.5 Produto já existente

Quando um expositor quiser vender um item que já está no catálogo — fluxo que
**será implementado em fase posterior**, e que hoje não existe (E-4):

| Ação | Permitido? |
|---|---|
| Criar `ProductOffer` sobre o `Product` existente | Sim — **mediante curadoria**, que é o ato que compartilha o produto |
| Editar `name` do `Product` | **Não diretamente.** Somente por proposta aprovada |
| Editar `description` / `short_description` | **Não diretamente.** Somente por proposta aprovada |
| Editar `category_id` / `item_type` / `is_digital` | **Não diretamente.** Somente por proposta aprovada |
| Editar a imagem canônica | **Não diretamente** (§6) |
| Definir preço, estoque, prazo, dimensões, destaque, ordem | **Sim** — é a oferta dele |
| Escrever conteúdo comercial próprio (imagem da oferta, FAQ da oferta) | **Sim** (§6, §7) |

### 4.6 `products.is_active` × `product_offers.is_active`

**Fica definido: `products.is_active` é exclusivo da curadoria. Nenhum expositor
o altera, em nenhuma circunstância — nem no estágio inicial não compartilhado,
nem sob delegação válida.** A delegação de §4.3 alcança os campos G1/G2/G3; não
alcança este.

Três razões, e a terceira é a que fecha o caso:

1. Outros expositores podem depender do `Product`.
2. A Catalog Intelligence mantém conhecimento associado a ele, e
   `FindSimilarProducts` filtra por `p.is_active`.
3. **O expositor já tem um interruptor completo que não afeta ninguém:**
   `product_offers.is_active`. Como `ProductOffer::scopeVigente()` exige oferta
   ativa, um item cuja última oferta foi desligada já desaparece de todas as
   vitrines. O expositor **nunca precisou** de `products.is_active` para obter o
   efeito que quer.

#### 4.6.1 Semântica formal — dois estados diferentes

| Campo | Significa | Autoridade |
|---|---|---|
| **`products.is_active`** | **Validade canônica do item no catálogo** — se a plataforma considera este item válido e publicável | Curadoria, exclusiva |
| **`product_offers.is_active`** | **Disponibilidade comercial daquela oferta específica** — se *aquele* expositor está vendendo *agora* | Expositor da oferta |

Não são graus do mesmo estado, e um não substitui o outro. O primeiro responde
*"este item existe legitimamente no catálogo?"*; o segundo, *"esta loja está
vendendo?"*.

#### 4.6.2 Casos

**Caso A — um expositor para de vender**

```text
products.is_active           = true
product_offers A.is_active   = false
product_offers B.is_active   = true
```

O `Product` continua válido. A não vende; B continua vendendo normalmente. Nada
no catálogo global se move.

**Caso B — todas as ofertas ficam inativas**

```text
products.is_active           = true
product_offers A.is_active   = false
product_offers B.is_active   = false
```

O `Product` **continua válido** no catálogo interno e na Catalog Intelligence.
Não há oferta pública vigente, e o item some das vitrines — por `scopeVigente`,
não por mudança no produto. Volta a aparecer quando alguém reativar ou criar uma
oferta.

**Caso C — a curadoria invalida o `Product`**

```text
products.is_active = false
```

Isso significa algo **semanticamente mais forte** que "sem estoque", "o expositor
saiu" ou "nenhuma oferta ativa":

> O item canônico **não deve ser considerado válido nem publicável pela
> plataforma** — independentemente de haver expositores dispostos a vendê-lo.

É uma afirmação sobre o item, não sobre o comércio ao redor dele.

#### 4.6.3 O que `products.is_active` não representa

**Fica registrado explicitamente.** `products.is_active` **não** representa:

- estoque;
- disponibilidade do expositor;
- pausa comercial;
- expositor ativo ou inativo;
- oferta ativa ou inativa.

Cada uma dessas responsabilidades pertence à entidade comercial correspondente —
`product_offers.stock_quantity`, `product_offers.is_active`,
`expositores.is_active`. Usar o campo canônico para qualquer uma delas
reintroduziria, por outro caminho, exatamente a confusão que a CAT-DOM-01
desfez.

#### 4.6.4 Independência entre validade e disponibilidade

Decorre de D-CAT-21 e precisa ser inequívoco nas duas direções:

| Afirmação | Vale? |
|---|:--:|
| zero ofertas ⟹ `products.is_active = false` | **Não** |
| `products.is_active = true` ⟹ há algo disponível para compra | **Não** |
| `products.is_active = false` ⟹ nenhuma oferta é vigente | **Sim** — `scopeVigente` exige produto ativo |
| todas as ofertas inativas ⟹ o `Product` deixa de existir | **Não** |

Um `Product` válido sem nenhuma oferta é um estado normal e esperado: é
precisamente o item que sobreviveu à saída de quem o vendia.

#### 4.6.5 A fronteira

> O expositor controla **a própria presença comercial** — `ProductOffer.is_active`.
> A curadoria controla **a validade global do item** — `Product.is_active`.

É a mesma fronteira de D-CAT-12: a curadoria manda no catálogo e nunca no
comércio. Ver D-CAT-10 para a decisão formal.

Consequência direta: `ProdutoIndex::toggleActive`, que hoje escreve os dois lados
em espelho, **deverá deixar de escrever em `products`** — bloqueador M-01,
programado para a CAT-DOM-02C.

### 4.7 Proveniência

**Fica definido: `products.expositor_id` significa proveniência — quem trouxe o
item para o catálogo — e nunca *ownership* atual.** Nenhuma autorização,
presente ou futura, pode olhar para essa coluna.

Isso já é verdade no código (a relação está marcada `@deprecated`, e a
CAT-DOM-02A removeu os dois últimos leitores dela nos painéis do lojista), mas
não estava formalizado como decisão. Fica.

A proveniência **é** informação útil e **deverá** ser preservada conceitualmente
— quem originou, quando, e a partir de qual contribuição. O destino estrutural
dessa informação (permanecer em `products`, migrar para um registro de
contribuição, ou ambos) **será decidido na fase que tratar da estrutura de
contribuição**, e não aqui.

---

## 5. Questão 2 — o papel da curadoria

### 5.1 Definição operacional

**Curadoria, neste sistema, é o conjunto de atos sobre o catálogo global que um
expositor não pode praticar sozinho porque afetam outros.** Não é revisão de
qualidade genérica, não é aprovação de cadastro, não é moderação de conteúdo
ofensivo — essas são outras funções, que podem coexistir.

| Item | Definição |
|---|---|
| **Quem executa** | Portadores de `produtos.moderar` — hoje `administrador`, `gerente` e `supervisor` (E-3). `editor` **não** exerce curadoria de catálogo |
| **Sobre quais dados** | Campos G1/G2/G3 de `Product`; `products.is_active`; imagem canônica; FAQ canônica; vínculo de uma oferta a um `Product` existente |
| **Quando intervém** | (a) ao compartilhar um `Product` entre dois expositores; (b) ao decidir proposta sobre `Product` compartilhado; (c) ao ativar/desativar `Product` global; (d) ao promover conteúdo de oferta a canônico; (e) ao revogar delegação por exceção |
| **Quais decisões pode tomar** | Aprovar, rejeitar ou editar diretamente. Não pode alterar oferta alheia, preço, estoque nem qualquer dado comercial de expositor |

> A curadoria **manda no catálogo e nunca no comércio.** Essa fronteira é o que
> impede que a fase enfraqueça a SEC-02 ou toque a FIN-SEC-01.

### 5.2 Curadoria não pode ser gargalo

O modelo de §4.3 mantém a curadoria **fora do caminho crítico**: cadastrar,
editar o próprio item, precificar, publicar e vender não passam por ela. Ela só é
acionada quando alguém decide que dois expositores vendem a mesma coisa — o que
hoje acontece zero vezes e, em artesanato, tende a continuar raro (§10).

Consequência prática relevante para o planejamento: **a superfície de curadoria
não é pré-requisito para as próximas fases de implementação.** Ela é
pré-requisito para habilitar multi-oferta, o que é bem depois.

### 5.3 Estados

**Fica definido que estados são necessários — mas para a *contribuição*, não para
o `Product`.**

- O `Product` **não** ganha máquina de estados. Ele é ativo ou inativo (§4.6), e
  isso basta.
- Uma **proposta de alteração de `Product` compartilhado** precisa distinguir
  `pendente`, `aprovada`, `rejeitada` e `superada` — sem isso não há como duas
  propostas concorrentes sobre o mesmo campo serem resolvidas, nem como mostrar
  ao expositor o que aconteceu com o que ele enviou.
- Nenhuma tabela, coluna ou enum é criada aqui. Fica registrado apenas que o
  conceito é necessário, e onde ele mora: na contribuição.

---

## 6. Questão 3 — imagem canônica × imagem da oferta

Nem "a imagem é sempre do `Product`" nem "a imagem é sempre da oferta" descrevem
este negócio. **São dois conceitos distintos e ambos deverão existir.**

### 6.1 Imagem canônica

Representa **o item em si**: uma foto de referência, neutra ou editorial, que
serve ao catálogo e não a uma loja.

| Pergunta | Resposta |
|---|---|
| Quem envia | O expositor de origem, no cadastro, sob delegação (§4.3); ou a curadoria |
| Quem aprova | A curadoria, quando o `Product` for compartilhado |
| Quem pode substituir | O expositor de origem, enquanto a delegação for válida; a curadoria, sempre |
| Quem nunca pode | Um expositor sobre `Product` compartilhado, diretamente |

### 6.2 Imagem da oferta

Representa **a peça que aquele expositor tem para vender**: a execução dele, a
embalagem dele, a variação dele, o serviço prestado por ele.

| Pergunta | Resposta |
|---|---|
| Expositor tem autoridade? | **Sim, plena.** É conteúdo comercial dele |
| Outro expositor pode alterar? | **Nunca** |
| Aparece na vitrine da loja? | **Sim, e é a imagem primária ali** |

### 6.3 Regra de fallback

**Fica definido:**

```text
a oferta tem imagem própria  →  usar a imagem da oferta
a oferta não tem             →  usar a imagem canônica do Product
nenhuma das duas             →  placeholder, como hoje
```

A ordem importa e é **deliberadamente o inverso** do que um marketplace de
produtos industriais faria. Em artesanato, **a foto é a peça**: um tapete de
crochê feito pela artesã A não é o objeto que a artesã B vai despachar, ainda que
o catálogo os trate como o mesmo `Product`. Mostrar a canônica por cima da foto
real faria a loja anunciar um objeto que não existe.

| Implicação | Efeito |
|---|---|
| **UX** | O cliente sempre vê o que vai receber. É o ganho principal |
| **SEO** | A página da oferta usa a imagem da oferta no `og:image`. Uma eventual página de produto canônico usaria a canônica — ver §11.2 |
| **Catalog Intelligence** | Nenhum. O matcher da CAT-04 opera sobre texto; imagem não entra em `catalog_product_knowledge` |
| **Armazenamento** | Duplicação de arquivos entre ofertas do mesmo produto. Irrelevante na escala atual (E-9) e aceitável adiante |
| **Moderação** | A imagem da oferta é conteúdo de expositor e responde ao regime de moderação dele; a canônica responde à curadoria |

### 6.4 Propriedade e exclusão

**Fica definido:**

1. Expositor remove a oferta → as imagens **daquela oferta** podem ser removidas
   junto.
2. Expositor remove a oferta → a **imagem canônica nunca é removida
   automaticamente**. Ela pertence ao catálogo, que sobrevive à saída dele.
3. Nenhum expositor pode remover imagem canônica nem imagem de oferta alheia, em
   nenhuma circunstância.

**Restrição técnica registrada agora, para não ser descoberta tarde:** hoje
`ImageService::delete()` recebe caminhos crus sem verificar propriedade, e
`ProdutoForm::removeImage()` apaga o arquivo do disco de imediato (E-8). Se o
backfill futuro fizer imagem canônica e imagem da oferta **apontarem para o mesmo
arquivo**, remover uma quebrará a outra silenciosamente.

> **Fica definido:** o backfill que separar imagem canônica de imagem de oferta
> **deverá copiar arquivos, nunca compartilhar caminhos.**

---

## 7. Questão 4 — FAQ

### 7.1 Os dois conceitos existem de fato

| FAQ canônica — descreve o item | FAQ da oferta — descreve a venda |
|---|---|
| "Que material é?" | "Qual o prazo de produção?" |
| "É artesanal?" | "Aceita personalização?" |
| "Como conservar?" | "Vocês entregam em Fortaleza?" |

A distinção não é teórica: a segunda coluna contém afirmações que só são
verdadeiras **para um expositor**. Publicá-las como verdade do catálogo faria a
plataforma prometer, em nome de B, um prazo que só A pratica.

### 7.2 Decisão

**Fica definido o modelo C — FAQ canônica e FAQ da oferta, separadas.**

E com um recorte que evita reclassificar conteúdo por adivinhação:

- **A FAQ que existe hoje é, por origem e por conteúdo, texto comercial do
  expositor**, escrito no formulário dele e exibido na página da loja dele (E-5).
  Ela **deverá** migrar para a oferta.
- **A FAQ canônica nasce vazia**, e só é povoada por curadoria — inclusive por
  promoção de uma FAQ de oferta a canônica, quando a afirmação for do item e não
  da loja.

Custo do backfill: **zero linhas** (E-9). É o momento mais barato que este
sistema terá.

### 7.3 Autoridade

| | Quem escreve | Curadoria |
|---|---|---|
| **FAQ da oferta** | Expositor, direto | Não |
| **FAQ canônica** | Expositor **propõe**; ou curadoria escreve/promove | **Sim** |

Consequência direta: `syncFaqs`, que hoje apaga todas as FAQs do produto e
regrava as de quem salvou, **deverá** passar a operar sobre a FAQ da oferta —
bloqueador M-03.

---

## 8. Questão 5 — perguntas de clientes

### 8.1 O fluxo real, não o FK

O FK atual (`product_questions.product_id`, sem oferta) sugere que a pergunta é
sobre o produto global. **O fluxo diz outra coisa.** A pergunta é feita em
`/loja/{loja}/{produto}` — a página de uma oferta específica —, o componente é
montado ali com `:product="$product"` (E-5), quem responde é o expositor daquela
loja, e o guard atual é "tem oferta sobre este produto" (E-7).

Ou seja: **o cliente escolheu um comerciante e falou com ele.** O que o registro
não guarda é justamente essa escolha.

Com multi-oferta, o defeito é grave e triplo: B veria a pergunta feita na página
de A; B poderia respondê-la e ocultá-la; e a resposta de B apareceria na página
de A para um cliente que nunca falou com B.

### 8.2 Conteúdo global × contexto comercial

O escopo distingue corretamente duas naturezas de pergunta:

- "Esse produto é vegano?" — o **conteúdo** é sobre o `Product`.
- "Você consegue entregar até sexta?" — o **conteúdo** é sobre a oferta.

**A decisão não segue o conteúdo, e sim o destinatário.** Mesmo a pergunta
vegana foi endereçada a um comerciante, na página dele, e é ele quem tem o dever
de responder. Classificar por conteúdo exigiria que o sistema interpretasse texto
livre — e erraria.

### 8.3 Decisão

**Fica definido que toda pergunta deverá carregar a oferta como contexto,
sempre**, independentemente do assunto:

```text
Question
├── product_id          → mantido: agrupamento canônico e Catalog Intelligence
├── product_offer_id    → o contexto e o destinatário da pergunta
├── autor (cliente)
└── respondente (expositor da oferta)
```

Os dois campos, não um ou outro. `product_id` continua servindo à leitura
canônica e à inteligência de catálogo; `product_offer_id` responde "para quem foi
perguntado".

Nenhuma coluna é criada aqui.

**Linhas legadas sem oferta** permanecem com o contexto nulo e seguem
respondíveis por qualquer ofertante — o comportamento atual. Inferir a oferta
retroativamente seria reescrever histórico por adivinhação. Hoje o caso é
vazio (E-9).

### 8.4 Quem responde

**Fica definido: um expositor responde e oculta somente perguntas dirigidas à sua
própria oferta.** Bloqueador M-06.

Fica registrado também que `answered_by` guarda um usuário, não uma loja (E-6).
Para exibição pública da autoria comercial da resposta, isso é insuficiente. O
tratamento **será decidido na fase que implementar o contexto de oferta** — não
aqui.

### 8.5 Promoção de resposta a conhecimento canônico

**Fica definido: uma resposta de expositor só se torna FAQ canônica ou
conhecimento de catálogo por ato de curadoria. Nunca automaticamente.**

A razão é a mesma de §7.1, e é séria: a resposta pode ser verdadeira apenas da
prática daquele expositor. Promovê-la sozinha faria a plataforma afirmar
globalmente algo falso sobre os demais. Esse ponto é diretamente relevante para a
Catalog Intelligence, que **deverá** tratar resposta de expositor como
candidata a conhecimento e não como conhecimento.

---

## 9. Edição com múltiplas ofertas

### 9.1 Matriz de autoridade

Cenário de referência: `Product #10` **compartilhado**, com ofertas `#100 → A`,
`#101 → B`, `#102 → C`. Valores: **cria · edita · propõe · aprova · somente lê ·
sem acesso**.

> A matriz abaixo descreve o `Product` **compartilhado** — a delegação de
> D-CAT-09 já terminou, e por isso A e B aparecem como *propõe* nos campos
> canônicos. Ela **não** é a matriz do estágio inicial: ver §9.1.1.

#### 9.1.1 As quatro posições possíveis

A posição de alguém diante de um campo canônico não depende de quantas ofertas o
`Product` tem, e sim de **qual das quatro posições de governança ele ocupa**:

| Posição | Sobre `Product` canônico | Sobre a própria oferta | Sobre oferta alheia |
|---|---|---|---|
| **Curadoria** | **autoridade final** — cria · edita · aprova | somente lê | somente lê |
| **Expositor de origem com delegação válida** (estágio inicial não compartilhado) | **edição delegada** | autoridade | sem acesso |
| **Expositor de `Product` compartilhado** (ou sem delegação válida) | **proposta** | autoridade | sem acesso |
| **Qualquer outro expositor** | proposta | — | **sem acesso** |

Um mesmo expositor pode passar da segunda linha para a terceira — e essa
transição é um ato de governança (compartilhamento ou revogação), **nunca uma
consequência de contagem** (§4.3.1). Ele **não** volta da terceira para a segunda
por o número de ofertas cair (§4.3.2).

| Dado | Entidade futura | Expositor A | Curadoria | Sistema | Expositor B |
|---|---|---|---|---|---|
| `name` | Product | propõe | cria · edita · aprova | somente lê | propõe |
| `slug` | Product | sem acesso | edita | **cria** (derivado) | sem acesso |
| `category_id` | Product | propõe | cria · edita · aprova | somente lê | propõe |
| `item_type` | Product | propõe | cria · edita · aprova | somente lê | propõe |
| `is_digital` | Product | propõe | cria · edita · aprova | somente lê | propõe |
| `short_description` | Product | propõe | cria · edita · aprova | somente lê | propõe |
| `description` | Product | propõe | cria · edita · aprova | somente lê | propõe |
| **imagem canônica** | Product | propõe | cria · edita · aprova | somente lê | propõe |
| **imagem da oferta** | ProductOffer | **cria · edita** | somente lê | somente lê | **sem acesso** |
| `price` | ProductOffer | **cria · edita** | somente lê | somente lê | sem acesso |
| `stock_quantity` · `has_stock` | ProductOffer | **cria · edita** | somente lê | somente lê | sem acesso |
| `reserved_quantity` | ProductOffer | somente lê | somente lê | **cria · edita** | sem acesso |
| `modality` · `duration_min` | ProductOffer | **cria · edita** | somente lê | somente lê | sem acesso |
| `weight` · `height` · `width` · `length` | ProductOffer | **cria · edita** | somente lê | somente lê | sem acesso |
| `is_featured` · `sort_order` | ProductOffer | **cria · edita** | somente lê | somente lê | sem acesso |
| `ProductOffer.is_active` | ProductOffer | **cria · edita** | somente lê | edita (expiração/estoque) | sem acesso |
| **`Product.is_active`** | Product | **sem acesso** | **cria · edita** | somente lê | **sem acesso** |
| **FAQ canônica** | Product | propõe | cria · edita · aprova | somente lê | propõe |
| **FAQ da oferta** | ProductOffer | **cria · edita** | somente lê | somente lê | sem acesso |
| **pergunta** | Question (produto + oferta) | somente lê | somente lê | **cria** (via cliente) | sem acesso |
| **resposta** | Question | **cria · edita** (só da própria oferta) | aprova promoção a canônico | somente lê | sem acesso |
| SEO da página da oferta | derivado | edita (indireto) | edita (indireto) | **cria** | sem acesso |
| `expositor_id` da oferta | ProductOffer | **sem acesso** | somente lê | cria (uma vez) | sem acesso |

Onde a tabela diz "sem acesso" para o expositor B, ela está descrevendo a SEC-02
estendida: hoje a proteção alcança preço, estoque e status da oferta; **deverá**
passar a alcançar também imagem, FAQ e pergunta.

### 9.2 Resumo em uma frase

- **A pode editar:** a oferta dele por inteiro — preço, estoque, prazo,
  dimensões, destaque, ordem, status da oferta, imagens da oferta, FAQ da oferta,
  respostas às perguntas da oferta dele.
- **A não pode editar:** as ofertas de B e C, o `Product` diretamente, a imagem
  canônica diretamente, a FAQ canônica diretamente, `Product.is_active`, nem
  perguntas dirigidas a outra oferta.

### 9.3 Quando A identifica erro no `Product`

Nome errado, categoria errada, descrição imprecisa, imagem canônica ruim:

```text
A envia proposta  →  curadoria avalia  →  aprovada  →  Product alterado
                                       →  rejeitada →  A é informado
```

**Fica registrado explicitamente:** uma alteração aprovada no `Product` passa a
valer **para todas as ofertas**, inclusive as de B e C, que não participaram da
proposta. É precisamente por isso que a autoridade precisa ser central — e é o
argumento que descarta em definitivo o modelo B.

### 9.4 Matriz de ciclo de vida

| Evento | `Product` | Oferta A | Oferta B | Conteúdo canônico |
|---|---|---|---|---|
| A entra (item novo) | criado, ativo; **delegação concedida a A** | criada, ativa | — | criado por A sob delegação |
| Curadoria vincula B (item existente) | **inalterado**; `Product` passa a **compartilhado**; **delegação de A termina** | inalterada | criada, ativa | inalterado; passa a exigir curadoria |
| A altera o preço | inalterado | alterada | inalterada | inalterado |
| A altera a descrição (compartilhado) | **inalterado** — vira proposta | inalterada | inalterada | inalterado até aprovação |
| A remove a oferta | **preservado** | removida; imagens da oferta podem ir junto | inalterada | **preservado** |
| B continua vendendo | ativo | ausente | ativa | preservado |
| **B remove a oferta — volta a 1 oferta (a de A)** | **preservado e ainda compartilhado**; **A NÃO recupera delegação** | ativa | removida | preservado; continua exigindo curadoria |
| Curadoria devolve delegação a A | delegação **concedida por ato explícito** | ativa | — | volta a ser editável por A |
| Última oferta é removida | **preservado, ativo, sem vitrine** | removida | removida | **preservado** |
| Curadoria desativa o `Product` | inativo | fica não vigente | fica não vigente | preservado |

Três linhas merecem leitura atenta.

"A altera a descrição" **não altera nada** quando o produto é compartilhado —
vira proposta.

**"B remove a oferta — volta a 1 oferta" é a linha que prova a correção de
§4.3.1.** Um modelo que deduzisse autoridade da cardinalidade devolveria a A, em
silêncio, o poder de reescrever a identidade de um item que já foi compartilhado.
Aqui não: o `Product` continua compartilhado e sob curadoria, e a única forma de
A voltar a editar é a linha seguinte — um ato explícito de quem governa o
catálogo.

"Última oferta removida" preserva tudo: é a decisão central da CAT-DOM-01, aqui
reafirmada.

### 9.5 `Product` sem oferta

**Fica definido:**

| Pergunta | Resposta |
|---|---|
| O `Product` é apagado? | **Não.** Nunca, por ausência de oferta |
| Fica visível ao público? | **Não.** `ProductOffer::scopeVigente()` já garante isso hoje |
| Fica indexável? | **Não** — por construção: a única URL pública é `/loja/{loja}/{produto}`, que exige oferta vigente |
| Continua no catálogo interno? | **Sim** |
| Continua na Catalog Intelligence? | **Sim** — é o ponto inteiro da CAT-DOM-01 |
| Volta a aparecer? | **Sim**, assim que alguém criar uma oferta vigente |

Motivos para não apagar: histórico de pedidos referencia `product_id`;
conhecimento acumulado está associado a ele; a URL pode já ter sido indexada e
compartilhada; e o item pode voltar a ser vendido.

---

## 10. Identidade, semelhança e fusão

### 10.1 Semelhança não é identidade

**Fica reafirmado e elevado a decisão formal:** produtos artesanais semelhantes
**não** são o mesmo `Product`. A curadoria **não pode** transformar semelhança em
identidade sem evidência.

O motor de similaridade da CAT-04 é **auxílio de descoberta**, nunca autoridade
de fusão. Ele sugere ao curador onde olhar; não conclui nada.

### 10.2 Critérios de identidade

Duas ofertas só pertencem ao mesmo `Product` quando houver evidência de que
vendem **o mesmo item**, e não item parecido, mesma categoria, nome parecido ou
imagem parecida.

| Critério | Disponível hoje? | Força |
|---|:--:|---|
| GTIN / EAN | **Não** (E-1) | Forte, se existisse |
| SKU de fabricante | **Não** | Forte |
| Marca + modelo | **Não** | Média |
| Identificador externo confiável | **Não** | Média |
| **Curadoria manual com evidência** | **Sim** | **Único disponível** |

**Consequência que precisa estar escrita:** como nenhuma chave objetiva existe,
**hoje o único critério de identidade admissível é o juízo humano documentado.**
Se identificadores forem introduzidos no futuro, poderão sustentar deduplicação
assistida — ainda assim com limiar de confiança e confirmação humana.

Para artesanato, a regra prática permanece: **na dúvida, `Product` distinto.**
Dois produtos separados são um incômodo de catálogo; uma fusão errada destrói
identidade de autoria e não tem desfazer barato.

### 10.3 Curadoria assistida no futuro

Deduplicação assistida, score de confiança e apoio de IA são evolução legítima e
**poderão** ser avaliados em fase própria. Uma restrição vale desde já e não
depende da tecnologia escolhida: **nenhuma automação funde identidade sem
confiança suficiente e sem confirmação humana.**

---

## 11. Dependências registradas — nada decidido aqui

### 11.1 Customer Intelligence

Segue aprendendo sobre `Product`, sem alteração nesta fase. Fica registrada a
distinção conceitual que orienta fases futuras:

| Sinal | Entidade |
|---|---|
| Interesse, afinidade, tendência pelo item | **`Product`** |
| Performance comercial — conversão, ticket, recorrência de um vendedor | **`ProductOffer`** |

A auditoria CAT-DOM-02 registrou que hoje os eventos de carrinho não gravam
`product_offer_id` nem `expositor_id` (M-13), o que torna a segunda linha
inderivável. Continua dívida aberta.

### 11.2 SEO

Nenhuma decisão de URL é tomada aqui. Ficam registradas as dependências que as
decisões acima criam:

- A única URL pública de item é `/loja/{loja}/{produto}`. Com um `Product`
  compartilhado, haverá **N URLs** para o mesmo item de catálogo.
- Hoje `title`, `meta description`, `og:title` e `og:description` vêm de
  `Product`; só o nome da loja vem da oferta. Sem `canonical` definido, N páginas
  quase idênticas seriam indexadas (M-12).
- A decisão de imagem (§6.3) já resolve o `og:image`: a página da oferta usa a
  imagem da oferta, o que diferencia as N páginas.
- `products.slug` permanece UNIQUE global e sem desambiguação — F-09 / M-10,
  dívida separada.

### 11.3 AVA

Arquitetura **não** alterada. Fica registrado que o curso depende de `Product`
por `ava_courses.product_id` **UNIQUE**, e que M-04 e M-16 continuam dívida.

Uma consequência precisa ficar explícita, porque ela vira gate: **um `Product`
digital não pode ser compartilhado enquanto a autoria do curso não for
decidida.** Com `UNIQUE`, dois expositores disputariam o mesmo curso, as mesmas
matrículas e os mesmos certificados. A CAT-DOM-02B **não** resolve o AVA
incidentalmente.

### 11.4 `ofertaVigente`

Nenhum algoritmo de melhor oferta é decidido aqui. M-09 permanece separada.

Fica registrada a dependência: quando um `Product` for compartilhado, alguma
regra terá de escolher **qual oferta** o catálogo, a home e a busca representam —
e essa regra passa a decidir também qual imagem e qual preço aparecem. Hoje
`Product::ofertaVigente` já é, de fato, "menor preço, desempate por id", sem que
nenhuma decisão de produto o tenha aprovado. Isso **deverá** ser decidido antes
de multi-oferta, em fase própria.

---

## 12. Decisões registradas

Sequência de IDs: o repositório usa `D-FIN-01`..`D-FIN-31` na trilha financeira e
**nunca registrou um `D-CAT-`**. Os únicos existentes são `D-CAT-01`..`D-CAT-08`,
emitidos como **propostas** no relatório de auditoria da CAT-DOM-02. O último
número real em uso é **08**; esta fase continua em **D-CAT-09** e não reutiliza
nenhum ID.

| ID | Decisão | Supersede a proposta |
|---|---|---|
| **D-CAT-09** | Autoridade sobre `Product` é da plataforma; delegação explícita ao expositor de origem, nunca deduzida da cardinalidade | D-CAT-01 |
| **D-CAT-10** | `products.is_active` é validade canônica, exclusiva da curadoria; distinta de `product_offers.is_active` | D-CAT-02 |
| **D-CAT-11** | `products.expositor_id` é proveniência, nunca *ownership* | — |
| **D-CAT-12** | Curadoria = portadores de `produtos.moderar`, fora do caminho crítico | — |
| **D-CAT-13** | Estados pertencem à contribuição, não ao `Product` | — |
| **D-CAT-14** | Imagem canônica e imagem da oferta são conceitos distintos | D-CAT-03 |
| **D-CAT-15** | Fallback oferta → canônica; canônica nunca removida automaticamente | D-CAT-03 |
| **D-CAT-16** | FAQ canônica e FAQ da oferta, separadas; a FAQ atual é da oferta | D-CAT-04 |
| **D-CAT-17** | Pergunta carrega produto **e** oferta; contexto é o destinatário | D-CAT-04 |
| **D-CAT-18** | Resposta vira conhecimento canônico só por curadoria | — |
| **D-CAT-19** | Matriz de edição em multi-oferta | — |
| **D-CAT-20** | Identidade exige evidência; semelhança nunca funde | — |
| **D-CAT-21** | `Product` sem oferta é preservado, invisível e não indexável | — |

---

### D-CAT-09 — Autoridade sobre `Product`

**Contexto.** A CAT-DOM-01 separou identidade de relação comercial, mas manteve
`SaveProductWithOffer` gravando os campos de identidade a partir do formulário de
qualquer ofertante.

**Problema.** Com múltiplas ofertas, um expositor reescreve a identidade global
que os outros exibem, sem que ninguém tenha autorizado.

**Alternativas.** (A) primeiro expositor é autoridade permanente; (B) qualquer
ofertante edita — estado atual; (C) toda edição por curadoria; (D) autoridade
deduzida da cardinalidade de ofertas; (E) modelo próprio.

**Decisão.** Modelo **E**, em quatro proposições:

1. O `Product` pertence ao **catálogo global da plataforma**. A autoridade final
   sobre seus campos canônicos é da plataforma/curadoria, em qualquer estágio.
2. O expositor que **originou** um `Product` pode **receber delegação** para
   editar diretamente seus campos canônicos durante o **estágio inicial não
   compartilhado**.
3. Essa delegação **não** representa *ownership*, **não** decorre automaticamente
   da quantidade de ofertas, **não** pode ser inferida por
   `products.expositor_id` (D-CAT-11) nem por uma contagem de `ProductOffer`, é
   **revogável** pela curadoria, **termina** com o compartilhamento formal do
   `Product` e **não retorna automaticamente** se o número de ofertas voltar a
   um.
4. **Compartilhar um `Product` é ato exclusivo de curadoria**, e a delegação,
   quando implementada, **deverá possuir regra de governança explícita**.

**Autoridade não é cardinalidade.** A quantidade de ofertas é *estado comercial*;
a autoridade sobre `Product` é *estado de governança*. O estágio inicial não
compartilhado é a **ocasião** em que a delegação é concedida, nunca o mecanismo
que a concede — ver §4.3.1. O cenário que separa as duas leituras está em §4.3.2.

**A forma técnica da delegação não é decidida aqui** (§4.3.3): nem coluna, nem
tabela, nem estado, nem *policy*, *permission*, *workflow* ou entidade de
contribuição. Fica apenas o invariante — **a autoridade deverá ser representada
explicitamente, e nunca deduzida exclusivamente da cardinalidade de
`ProductOffer`.**

**Justificativa.** (C) é correto no limite e inviável hoje: não há superfície de
curadoria (E-2) e submeter todo cadastro a fila humana travaria a operação. (D)
tem a direção certa e o mecanismo errado: deduzir autoridade de contagem
devolveria poder canônico em silêncio, sem que ninguém tivesse decidido — e a
objeção da mudança silenciosa de regra desaparece porque o compartilhamento só
nasce de ato humano deliberado (E-1, E-4): quem muda a regra é quem decide
compartilhar.

**Consequências positivas.** O expositor mantém a operação fluida de hoje; o caso
compartilhado fica seguro por construção; a autoridade passa a ser auditável,
porque é declarada e não recalculada; a fase não exige construir painel de
curadoria antes de qualquer entrega.

**Consequências negativas.** Existem dois regimes de permissão para o mesmo
campo — com e sem delegação —, mais difíceis de explicar e de testar que uma
regra única. E a delegação, por ser um fato próprio, exige que alguém a
represente e a governe; ela não sai de graça do modelo de dados existente.

**Riscos.** (a) Uma implementação futura pode, por atalho, reintroduzir a
dedução por contagem — é o risco principal, e a razão de §4.3.1 e §4.3.2
existirem. (b) Se algum caminho criar uma segunda oferta sem passar pela
curadoria, o `Product` fica compartilhado sem ato de governança. Mitigação: o
gate G-2 de §13 exige que a criação de oferta sobre produto existente seja
exclusiva da curadoria, e o gate G-1 exige mecanismo explícito de delegação.

**Impacto.** Determina a CAT-DOM-02C (fim do *write-through* de identidade) e a
fase de contribuição de catálogo, que **deverá** entregar a representação
explícita da autoridade.

---

### D-CAT-10 — `products.is_active`

**Contexto.** `ProdutoIndex::toggleActive` escreve `is_active` na oferta e no
produto, em espelho.

**Problema.** Um expositor desativando a própria oferta desativa o item de
catálogo inteiro — e, com multi-oferta, tira da vitrine as ofertas de todos os
outros, além de alterar o universo de `FindSimilarProducts`.

**Alternativas.** (A) exclusivo da curadoria; (B) derivar de "existe ao menos uma
oferta vigente"; (C) manter como está.

**Decisão.** **(A)** — exclusivo da curadoria. Nenhum expositor altera
`products.is_active`, em nenhum estágio e **nem sob delegação válida**: a
delegação de D-CAT-09 alcança os campos canônicos G1/G2/G3 e **não** alcança
este.

A semântica dos dois campos fica formalmente separada (§4.6.1):

| Campo | Significa |
|---|---|
| `products.is_active` | **Validade canônica** do item no catálogo — se a plataforma o considera válido e publicável |
| `product_offers.is_active` | **Disponibilidade comercial** daquela oferta — se *aquele* expositor está vendendo agora |

`products.is_active` **não** representa estoque, disponibilidade do expositor,
pausa comercial, expositor ativo/inativo nem oferta ativa/inativa (§4.6.3). E as
duas direções da independência valem (§4.6.4, D-CAT-21): zero ofertas **não**
implica `Product` inválido, e `Product` válido **não** implica algo disponível
para compra. Casos trabalhados em §4.6.2.

**Justificativa.** (C) é o bloqueador M-01. (B) é elegante, mas apaga a diferença
entre "ninguém vende" e "o catálogo retirou o item", e a Catalog Intelligence
precisa dessa diferença. O argumento decisivo é que o expositor **já tem** um
interruptor completo — `product_offers.is_active` — que produz o efeito que ele
quer sem afetar terceiros.

**Consequências positivas.** Fecha M-01; um expositor deixa de conseguir apagar o
item do catálogo alheio.

**Consequências negativas.** Retirar um item do catálogo passa a depender de
alguém interno — mas isso é raro e nenhum expositor precisa disso hoje.

**Riscos.** Baixo. O comportamento visível ao expositor não muda: desligar a
oferta continua tirando o item da vitrine.

**Impacto.** CAT-DOM-02C remove a escrita em espelho do `toggleActive`.

---

### D-CAT-11 — Proveniência, não *ownership*

**Contexto.** `products.expositor_id` nasceu como dono e sobreviveu à CAT-DOM-01
como coluna legada; a relação está `@deprecated` e a CAT-DOM-02A eliminou seus
dois últimos leitores de aplicação.

**Problema.** Enquanto a coluna existir sem semântica declarada, algum código
futuro voltará a autorizar por ela.

**Alternativas.** (A) proveniência sem autoridade; (B) *ownership* de fato; (C)
remover agora.

**Decisão.** **(A)**. A coluna significa quem trouxe o item ao catálogo. Nenhuma
autorização pode lê-la. (C) fica para a fase de remoção do legado.

**Referência cruzada com D-CAT-09.** Proveniência e delegação são fatos
distintos, e a coincidência entre eles é frequente sem nunca ser causal: o
expositor de origem costuma ser o delegado no estágio inicial, mas
`products.expositor_id` **não concede, não comprova e não restaura** delegação
nenhuma. A delegação é um fato de governança próprio (§4.3.1, §4.3.3); a
proveniência é registro histórico. Ler a segunda como se fosse a primeira é o
mesmo erro de inferência que §4.3.1 recusa na contagem de ofertas.

**Justificativa.** Contradizer isso reintroduziria o modelo que a CAT-DOM-01
descartou. A informação de origem tem valor próprio e **deverá** ser preservada
conceitualmente.

**Consequências positivas.** Elimina a ambiguidade que sobreviveu à CAT-DOM-01.

**Consequências negativas.** A coluna permanece com nome enganoso até a remoção.

**Riscos.** Código novo pode usá-la por hábito. Mitigação: gate G-8.

**Impacto.** O destino estrutural da proveniência será decidido na fase de
contribuição.

---

### D-CAT-12 — Papel da curadoria

**Contexto.** Não existe superfície administrativa de produtos (E-2), mas a
permissão `produtos.moderar` já existe e está concedida a três papéis (E-3).

**Problema.** "Curadoria" corria o risco de virar conceito abstrato e, pior, de
virar gargalo operacional se colocada no caminho do cadastro.

**Alternativas.** (A) curadoria aprova todo cadastro; (B) curadoria só sobre
catálogo compartilhado e conteúdo canônico; (C) sem curadoria.

**Decisão.** **(B)**. Curadoria é exercida por portadores de `produtos.moderar` —
`administrador`, `gerente`, `supervisor` — sobre campos canônicos de `Product`,
`products.is_active`, imagem canônica, FAQ canônica e o vínculo de uma oferta a
um `Product` existente. **Fica fora do caminho crítico de cadastro, edição,
precificação e venda.** A curadoria manda no catálogo e nunca no comércio.

**Justificativa.** (A) inviabiliza a escala de um cadastro que hoje é imediato.
(C) deixa o catálogo compartilhado sem autoridade. `editor` fica de fora porque
não possui a permissão — e a curadoria de catálogo não é edição de CMS.

**Consequências positivas.** A superfície de curadoria deixa de ser pré-requisito
das próximas fases de implementação; passa a ser pré-requisito apenas de
multi-oferta.

**Consequências negativas.** Enquanto a tela não existir, campos canônicos de um
`Product` compartilhado ficariam sem editor — razão pela qual o gate G-1 impede
compartilhar antes dela.

**Riscos.** A permissão existir sem tela pode dar falsa sensação de cobertura.

**Impacto.** Define o escopo da fase de contribuição de catálogo.

---

### D-CAT-13 — Estados pertencem à contribuição

**Contexto.** Uma proposta de alteração precisa de ciclo de vida.

**Problema.** Dar máquina de estados ao `Product` acrescentaria peso a uma
entidade que só precisa estar ativa ou inativa.

**Alternativas.** (A) estados no `Product`; (B) estados na contribuição; (C) sem
estados.

**Decisão.** **(B)**. `pendente`, `aprovada`, `rejeitada` e `superada` descrevem
a **contribuição**. O `Product` não ganha máquina de estados.

**Justificativa.** Sem estados não há como resolver propostas concorrentes sobre
o mesmo campo nem informar o expositor. Colocá-los no `Product` misturaria o
estado do item com o estado de um pedido de alteração.

**Consequências positivas.** `Product` permanece simples; o histórico de quem
propôs o quê fica auditável.

**Consequências negativas.** Uma entidade nova a construir na fase apropriada.

**Riscos.** Sobre-engenharia se a fila nunca tiver volume. Mitigação: só é
construída quando multi-oferta for de fato desejada.

**Impacto.** Fase de contribuição de catálogo.

---

### D-CAT-14 — Imagem canônica × imagem da oferta

**Contexto.** `products.images` é hoje global e gravável por qualquer ofertante;
`ProdutoForm::removeImage()` apaga o arquivo do disco.

**Problema.** A premissa "a imagem pertence ao `Product`" e a premissa oposta são
ambas falsas neste negócio.

**Alternativas.** (A) só canônica; (B) só da oferta; (C) as duas, separadas.

**Decisão.** **(C)**. A imagem canônica representa o item e responde à curadoria
(com delegação, §4.3). A imagem da oferta representa a peça daquele expositor e é
autoridade plena dele.

**Justificativa.** (A) faz a loja anunciar um objeto que não é o que será
despachado. (B) deixa o catálogo sem representação quando não há oferta.

**Consequências positivas.** Fecha M-05; o cliente vê a peça real.

**Consequências negativas.** Duas fontes de imagem, com regra de resolução —
mais complexidade em cards, página do item, OG image e API.

**Riscos.** Divergência entre as duas superfícies de leitura, se a regra de
fallback for reimplementada em vários lugares. Mitigação: a regra vive em um
ponto só, como `ProductOffer::scopeVigente()`.

**Impacto.** Fase de estrutura de conteúdo por oferta.

---

### D-CAT-15 — Fallback e exclusão de imagens

**Contexto.** Decorre de D-CAT-14.

**Problema.** Qual imagem exibir, e o que acontece com os arquivos quando uma
oferta some.

**Alternativas.** (A) canônica primeiro; (B) oferta primeiro; (C) só uma delas.

**Decisão.** **(B)**: oferta → canônica → placeholder. A imagem canônica **nunca**
é removida automaticamente pela saída de um expositor. Nenhum expositor remove
imagem canônica nem imagem de oferta alheia. **O backfill deverá copiar arquivos,
nunca compartilhar caminhos.**

**Justificativa.** Em artesanato a foto é a peça. A cláusula do backfill vem de
E-8: caminhos compartilhados mais exclusão incondicional em disco quebrariam a
imagem de outro registro silenciosamente.

**Consequências positivas.** O cliente sempre vê o que vai receber; o catálogo
sobrevive à saída do expositor com representação própria.

**Consequências negativas.** Duplicação de arquivos — irrelevante na escala atual.

**Riscos.** Se a regra do backfill for esquecida, a perda de imagem é silenciosa
e irreversível.

**Impacto.** Condiciona a migração de imagens e o `og:image` (§11.2).

---

### D-CAT-16 — FAQ canônica × FAQ da oferta

**Contexto.** `product_faqs` pende de `Product`, é escrita pelo expositor e
exibida na página da oferta (E-5). `syncFaqs` apaga tudo e regrava.

**Problema.** Com multi-oferta, B destrói as FAQs de A a cada salvamento (M-03);
e conteúdo verdadeiro só para A seria publicado como verdade do item.

**Alternativas.** (A) só `ProductFaq`; (B) só FAQ de oferta; (C) as duas,
separadas.

**Decisão.** **(C)**. A FAQ existente **deverá** migrar para a oferta — é, por
origem e conteúdo, texto comercial do expositor. A FAQ canônica nasce vazia e é
povoada apenas por curadoria, inclusive por promoção de uma FAQ de oferta.

**Justificativa.** (A) é o bloqueador. (B) perde a capacidade de afirmar algo
sobre o item. Migrar o conteúdo atual para a oferta evita reclassificar por
adivinhação, e custa zero linhas hoje (E-9).

**Consequências positivas.** Fecha M-03; o expositor mantém autonomia sobre o
próprio texto.

**Consequências negativas.** Duas listas a exibir na mesma página; a página do
item precisa decidir a ordem.

**Riscos.** Baixo — o volume atual é zero.

**Impacto.** Fase de estrutura de conteúdo por oferta.

---

### D-CAT-17 — Contexto de oferta nas perguntas

**Contexto.** A pergunta é feita na página da oferta e gravada só com
`product_id` (E-5).

**Problema.** Com multi-oferta, B vê, responde e oculta pergunta feita na página
de A, e a resposta de B aparece para um cliente que nunca falou com B.

**Alternativas.** (A) manter só `product_id`; (B) trocar por `product_offer_id`;
(C) os dois; (D) classificar por conteúdo — global × comercial.

**Decisão.** **(C)**. Toda pergunta **deverá** carregar `product_id` e
`product_offer_id`. O contexto é o **destinatário**, não o assunto. Linhas
legadas mantêm contexto nulo.

**Justificativa.** (D) exigiria interpretar texto livre e erraria. Mesmo uma
pergunta sobre o item foi endereçada a um comerciante específico, na página dele.
Manter `product_id` preserva o agrupamento canônico e a Catalog Intelligence.

**Consequências positivas.** Fecha M-06; a pergunta chega a quem foi perguntado.

**Consequências negativas.** A mesma dúvida pode ser feita e respondida N vezes,
uma por oferta.

**Riscos.** Repetição vira ruído se a promoção a FAQ canônica (D-CAT-18) não for
usada.

**Impacto.** Fase de estrutura de conteúdo por oferta; alimenta D-CAT-18.

---

### D-CAT-18 — Promoção de resposta a conhecimento canônico

**Contexto.** Respostas de expositor são conteúdo valioso para o catálogo e para
a Catalog Intelligence.

**Problema.** Uma resposta pode ser verdadeira apenas da prática de um expositor.

**Alternativas.** (A) promoção automática; (B) promoção por curadoria; (C) sem
promoção.

**Decisão.** **(B)**. Uma resposta só se torna FAQ canônica ou conhecimento de
catálogo por ato de curadoria.

**Justificativa.** (A) faria a plataforma afirmar globalmente algo falso sobre os
demais expositores — "entrego em três dias" é prática de quem respondeu. (C)
desperdiça conhecimento real.

**Consequências positivas.** O catálogo ganha conteúdo verificado; a Catalog
Intelligence recebe candidatas, não fatos.

**Consequências negativas.** Depende de trabalho humano; sem ele, nada é
promovido.

**Riscos.** Fila parada. Aceitável: o estado sem promoção é o estado atual.

**Impacto.** Alimenta a trilha Catalog Intelligence e a CAT-05.

---

### D-CAT-19 — Matriz de edição em multi-oferta

**Contexto.** SEC-02 protege hoje preço, estoque e status da oferta, perguntando
"você tem uma oferta sobre este produto?".

**Problema.** É a pergunta errada para imagem, FAQ, pergunta e identidade — E-7.

**Alternativas.** (A) manter o guard atual; (B) estender o isolamento a todo
conteúdo de oferta.

**Decisão.** **(B)**, conforme a matriz de §9.1. O guard **deverá** passar de
"tem oferta sobre este produto?" para "**esta oferta é sua?**" em imagem, FAQ,
perguntas e curso.

**Justificativa.** A pergunta atual autoriza corretamente o que é da oferta e
autoriza indevidamente o que é do produto e o que é de outra oferta.

**Consequências positivas.** SEC-02 estendida sem ser enfraquecida; fecha M-02,
M-03, M-05 e M-06.

**Consequências negativas.** Vários pontos de autorização a revisar, cada um com
risco próprio de regressão.

**Riscos.** **Reintroduzir o IDOR que a SEC-02 corrigiu.** É o maior risco de
toda a trilha. Mitigação: nenhuma alteração de guard sem teste A × B sobre
produto compartilhado, antes da mudança.

**Impacto.** Fase de isolamento; gate obrigatório de multi-oferta.

---

### D-CAT-20 — Identidade e regra de fusão

**Contexto.** Não existe nenhuma chave objetiva de identidade em `products`
(E-1). A CAT-04 fornece score de similaridade.

**Problema.** Similaridade é frequentemente confundida com identidade, e a fusão
errada é destrutiva.

**Alternativas.** (A) fusão por similaridade acima de limiar; (B) fusão por chave
externa; (C) fusão só por curadoria com evidência.

**Decisão.** **(C)**. Multi-oferta pressupõe que os expositores vendem
efetivamente **o mesmo item** — não item semelhante, mesma categoria, nome
parecido ou imagem parecida. O motor de similaridade é auxílio de descoberta,
nunca autoridade de fusão. Em artesanato, na dúvida, `Product` distinto.

**Justificativa.** (A) destruiria autoria: "Tapete de crochê" de duas artesãs não
é a mesma peça. (B) não é possível hoje — nenhum identificador existe. Se chaves
forem introduzidas, poderão sustentar deduplicação assistida, ainda com limiar e
confirmação humana.

**Consequências positivas.** Protege a identidade de autoria, que é o valor
central de um marketplace de artesanato.

**Consequências negativas.** O catálogo terá itens duplicados de fato. É o
incômodo aceito conscientemente.

**Riscos.** Baixo. O risco oposto — fusão indevida — não tem desfazer barato.

**Impacto.** Governa qualquer automação futura de deduplicação.

---

### D-CAT-21 — `Product` sem oferta

**Contexto.** A CAT-DOM-01 decidiu que o produto sobrevive à saída do expositor.

**Problema.** Faltava dizer o que acontece quando a **última** oferta some.

**Alternativas.** (A) apagar; (B) desativar; (C) preservar ativo e invisível.

**Decisão.** **(C)**. O `Product` é preservado, permanece ativo no catálogo
interno e na Catalog Intelligence, não é exibido ao público e não é indexável.
Volta a aparecer quando alguém criar uma oferta vigente.

**Justificativa.** (A) destruiria referência de histórico e conhecimento
acumulado. (B) confundiria "ninguém vende" com "o catálogo retirou o item" — a
distinção que D-CAT-10 preserva. A invisibilidade já é garantida hoje por
`scopeVigente`, e a não indexação é estrutural: a única URL pública exige oferta
vigente.

**Consequências positivas.** Nenhuma mudança de comportamento é necessária —
formaliza o que já acontece.

**Consequências negativas.** Acumulam-se produtos sem oferta ao longo do tempo.

**Riscos.** Baixo. Ruído de catálogo interno, tratável por curadoria.

**Impacto.** Nenhum imediato; fecha a matriz de ciclo de vida.

---

## 13. Gates obrigatórios antes de multi-oferta

Nenhuma segunda `ProductOffer` sobre um `Product` existente pode ser habilitada
enquanto **todos** os gates abaixo não estiverem satisfeitos.

| Gate | Condição | Fecha |
|---|---|---|
| **G-1** | Autoridade global implementada com **mecanismo explícito de governança/delegação** — nunca deduzida da cardinalidade de ofertas — e superfície de curadoria existente | D-CAT-09, D-CAT-12 |
| **G-2** | Criação de oferta sobre produto existente é exclusiva da curadoria | D-CAT-09, D-CAT-20 |
| **G-3** | Expositor não altera `Product` diretamente; existe caminho de proposta | M-02 |
| **G-4** | Conteúdo *seller-specific* isolado por oferta — imagem, FAQ | M-03, M-05 |
| **G-5** | Perguntas carregam contexto de oferta e só o destinatário responde | M-06 |
| **G-6** | `products.is_active` centralizado na curadoria, com a semântica de §4.6 separada de `product_offers.is_active` | M-01 |
| **G-7** | SEC-02 preservada e **estendida**, com teste A × B sobre produto compartilhado | D-CAT-19 |
| **G-8** | Nenhum leitor comercial no legado; nenhuma autorização por `products.expositor_id` | D-CAT-11 |
| **G-9** | Regra de escolha de oferta decidida e explícita | M-09 |
| **G-10** | Autoria do curso AVA decidida — ou `Product` digital excluído de multi-oferta | M-04 |
| **G-11** | Colisão de slug resolvida | M-10 / F-09 |

Os gates **não** exigem que as colunas legadas tenham sido removidas: a remoção é
higiene, não pré-requisito de segurança.

G-1 exige que a autoridade exista como **fato declarado e governável**. Ele
**não** exige qual será o mecanismo técnico — coluna, tabela, estado, *policy* ou
outro —, escolha que pertence às fases de implementação (§4.3.3). O que ele
recusa é apenas a autoridade **inferida**, seja por contagem de ofertas, seja por
`products.expositor_id`.

---

## 14. Sequência de implementação proposta

Ordenada por dependência real, não por conveniência de numeração. Nada aqui é
autorizado por este documento.

| Fase | Entrega | Toca banco? |
|---|---|---|
| **CAT-DOM-02C** | Autoridade de `Product`: fim do *write-through* de identidade e do espelho de escrita; `toggleActive` deixa de escrever em `products`; delegação explícita | Não |
| **CAT-DOM-02D** | Estrutura de conteúdo por oferta: imagem da oferta, FAQ da oferta, contexto de oferta nas perguntas — migrations **aditivas** e backfill 1:1 | Sim, aditivo |
| **CAT-DOM-02E** | Migração de *writers* e *readers* para o conteúdo por oferta, com *dual-read* e fallback canônico | Não |
| **CAT-DOM-02F** | Isolamento estendido — o guard passa a perguntar "esta oferta é sua?" — e contribuição de catálogo (proposta, estados, curadoria) | Sim, aditivo |
| **CAT-DOM-02G** | Prontidão para multi-oferta: gates G-9, G-10, G-11 e a superfície de vínculo por curadoria | A definir |
| **CAT-DOM-02H** | Remoção do legado: *drop* das treze colunas espelho e destino de `expositor_id`, sob o critério de remoção da auditoria | Sim, **destrutivo** |
| **CAT-DOM-02I** | *Hardening*: suíte, MySQL real, prova de que multi-oferta continua inalcançável e de que o ciclo financeiro não se moveu | Só leitura |

**02C é independente das demais** e pode ser autorizada isoladamente: fecha M-01
sem migration e sem decisão pendente.

Habilitar multi-oferta **não** pertence a esta trilha. Ela termina deixando o
modelo pronto; expor a funcionalidade é decisão de produto posterior.

---

## 15. Dívidas preservadas

Nenhuma foi resolvida nesta fase, que não escreveu código.

| Item | Situação |
|---|---|
| M-01 `products.is_active` | **Decidida** (D-CAT-10), não implementada |
| M-02 autoridade global em `SaveProductWithOffer` | **Decidida** (D-CAT-09), não implementada |
| M-03 destino das FAQs | **Decidida** (D-CAT-16), não implementada |
| M-04 `ava_courses.product_id` UNIQUE | **Aberta** — gate G-10 |
| M-05 imagens globais | **Decidida** (D-CAT-14, D-CAT-15), não implementada |
| M-06 perguntas sem contexto | **Decidida** (D-CAT-17), não implementada |
| M-08 API do carrinho por `product_id` | **Aberta** |
| M-09 estratégia de `ofertaVigente` | **Aberta** — gate G-9 |
| M-10 colisão de slug / F-09 | **Aberta** — gate G-11 |
| M-12 SEO e canonical | **Aberta** — dependências em §11.2 |
| M-13 CI sem `product_offer_id` nos eventos | **Aberta** |
| M-14 fallback de expositor no `ProductResource` | **Aberta** |
| M-16 leitura de `Product::expositor` no AVA | **Aberta** |
| M-17 `FindSimilarProducts` sem vigência | ✅ **Fechada na CAT-05B** (D-CAT-05B-2) |
| D-1 colunas comerciais legadas | **Aberta** — CAT-DOM-02H |
| Remoção de coluna | Critério da auditoria CAT-DOM-02 permanece obrigatório |

> **M-17.** Fechada fora desta trilha, pela **CAT-05B**, que era o destino que
> esta tabela e a CAT-DOM-01 já lhe davam. `FindSimilarProducts` deixou de
> filtrar `products.is_active` solto e passou a exigir a vigência de
> `ProductOffer::scopeVigente()` para quem é **oferecido** como semelhante — a
> origem da consulta continua sem filtro, preservando a D-CAT-21. Decisão,
> justificativa e o conflito que ela expôs num teste da 01G em
> [`CAT_05B_DECISOES_DE_PRODUTO_E_CONTRATOS.md`](CAT_05B_DECISOES_DE_PRODUTO_E_CONTRATOS.md).

---

## 16. O que esta fase deliberadamente não fez

Nenhuma migration, tabela, coluna, enum, model, action, service, controller,
endpoint, tela ou teste. Nenhuma alteração em `Product`, `ProductOffer`,
`ProductFaq`, `ProductQuestion`, AVA, checkout, estoque ou Customer Intelligence.
Multi-oferta não foi habilitada. Nenhuma coluna legada foi removida. A CAT-05 não
foi iniciada.

Este documento **decide**. A implementação começa na CAT-DOM-02C, e só mediante
autorização própria.
