# CAT-DOM-02D — Implementação e Validação

**Estrutura de conteúdo por oferta: imagem, FAQ e contexto de pergunta.**

Este documento registra o que foi **construído** e o que foi **provado**. A
decisão está em
[`CAT_DOM_02D_ESTRUTURA_CONTEUDO_POR_OFERTA.md`](CAT_DOM_02D_ESTRUTURA_CONTEUDO_POR_OFERTA.md),
que permanece como especificação e não foi reescrito para virar relatório.

---

## 1. Baseline

| | |
|---|---|
| Repositório | `D:/projeto-feira-esquerda-livre/feira-esquerda-livre` |
| Branch | `main` |
| Commit da especificação | `9d29f48525fb165424a9aa43e07241c0c9d2059b` |
| Suíte antes da implementação | **889 passed · 2559 assertions · 0 failed** |
| MySQL | 8.4.11 (container `mysql` já ativo) |
| PHP / Laravel | 8.3.33 / 12.65.0 |

Os containers já estavam de pé; nenhum `docker compose down`, `build`, `up`,
`migrate:fresh`, `migrate:refresh` ou `db:wipe` foi executado.

## 2. O que a fase entrega — e o que deliberadamente não entrega

A 02D responde **onde o dado mora**. Ao fim dela a aplicação continua lendo e
escrevendo exatamente onde escrevia antes: `products.images` e `product_faqs`.

> **A estrutura nova existe, está populada e não tem consumidor.**

Isso não é um descuido — é o que torna a fase reversível, e é a fronteira que a
02E atravessa. Um teste dedicado (§7.3) falha se ela for atravessada aqui.

## 3. Schema

### 3.1 `product_offers.images`

```sql
`images` json DEFAULT NULL          -- after `expositor_id`
```

Formato idêntico ao de `products.images`: `[{"thumb": path, "medium": path}]`.

Coluna JSON e não tabela `product_offer_images`: são no máximo quatro imagens
sem metadado próprio, e o formato espelhado dá à 02E uma única forma a aprender
nos treze pontos de leitura.

**`image_path` não foi replicado.** Ele é espelho legado do primeiro thumb
(dívida D-1, removida na 02H); importá-lo para a estrutura nova seria criar hoje
o problema que a 02H existe para apagar. Há teste que falha se a coluna aparecer.

### 3.2 `product_offer_faqs`

```sql
CREATE TABLE `product_offer_faqs` (
  `id`               bigint unsigned NOT NULL AUTO_INCREMENT,
  `product_offer_id` bigint unsigned NOT NULL,
  `question`         varchar(255)    NOT NULL,
  `answer`           text            NOT NULL,
  `sort_order`       smallint unsigned NOT NULL DEFAULT '0',
  `created_at`       timestamp NULL,
  `updated_at`       timestamp NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `product_offer_faqs_product_offer_id_sort_order_unique`
      (`product_offer_id`, `sort_order`),
  CONSTRAINT `product_offer_faqs_product_offer_id_foreign`
      FOREIGN KEY (`product_offer_id`) REFERENCES `product_offers` (`id`)
      ON DELETE CASCADE
) ENGINE=InnoDB;
```

`CASCADE` porque a FAQ é composição da oferta: some com ela. Diferente da
pergunta do cliente, que é conteúdo de terceiro.

**`UNIQUE`, e não índice comum.** `sort_order` é posição, e duas FAQs não podem
ocupar a mesma posição dentro de uma oferta. Em `product_faqs` essa unicidade é
acidental — vem de o writer atribuir o índice do array, nunca de o banco exigir.
Aqui é invariante de schema, e é o que sustenta a idempotência da reconciliação.

A `UNIQUE` substitui o índice comum: serve às mesmas consultas
(`WHERE product_offer_id = ? ORDER BY sort_order`), sem índice redundante.

### 3.3 `product_questions.product_offer_id`

```sql
`product_offer_id` bigint unsigned DEFAULT NULL   -- after `product_id`
  FOREIGN KEY REFERENCES product_offers(id) ON DELETE SET NULL
INDEX (`product_offer_id`, `answered_at`, `is_visible`)
```

`product_id` permanece `NOT NULL` e com a FK que sempre teve. As duas colunas
convivem e nenhuma substitui a outra (D-CAT-17): `product_id` é o agrupamento
canônico e o eixo da Catalog Intelligence; `product_offer_id` é o contexto
comercial.

`SET NULL` porque a pergunta é conteúdo do cliente e tem valor histórico — o
mesmo tratamento que a FIN-SEC-01B deu a `order_items`, pela mesma razão.

`answered_by` **não foi tocado.**

### 3.4 Migrations

| Arquivo | Natureza |
|---|---|
| `2026_08_31_100001_add_images_to_product_offers.php` | aditiva |
| `2026_08_31_100002_create_product_offer_faqs_table.php` | aditiva |
| `2026_08_31_100003_add_product_offer_id_to_product_questions.php` | aditiva |

Três migrations, e não uma: se a segunda falhar, a primeira continua válida e
não precisa ser desfeita. Nenhum `DROP`, `RENAME` ou `NOT NULL` imediato — o
`--pretend` produz apenas `ADD COLUMN`, `CREATE TABLE` e `ADD CONSTRAINT`.

**`down()` desfaz schema, e só schema.** Os arquivos que o backfill copiou
permanecem em disco como órfãos. É desperdício mensurável — 75 arquivos hoje — e
não é perda: `down()` é operação de schema, e um rollback executado por engano
não pode destruir bytes que o banco não sabe restaurar. Não existe rollback
atômico de banco e disco neste sistema, e nada aqui finge que exista.

## 4. Models

Apenas o que o backfill e a integridade exigem.

| Model | Adição |
|---|---|
| `ProductOffer` | `images` em `$fillable` e `casts` (`array`); `offerFaqs()`; `questions()` |
| `ProductOfferFaq` | model novo — `$fillable` e `productOffer()` |
| `ProductQuestion` | `product_offer_id` em `$fillable`; `productOffer()` |

**Nenhuma relação nova em `Product`. Nenhum acessor de fallback.**
`Product::getMainImageUrlAttribute()` continua olhando só o produto, e
`Product::faqs()` continua apontando para `product_faqs`. Acessor de fallback é
02E.

`images` no `$fillable` de `ProductOffer` não abre caminho para escrita
acidental: `SaveProductWithOffer` reparte o array com
`Arr::only($data, CAMPOS_DA_OFERTA)`, e `images` não está na lista — segue indo
para o produto, como sempre foi.

## 5. Backfill

### 5.1 Onde ele mora

`App\Actions\Catalog\BackfillOfferContent` decide; o Artisan Command
`catalog:backfill-offer-content` é casca fina que escolhe o modo, aplica os
guards e imprime as métricas.

**Fora da migration**, e por três razões concretas: o filesystem não participa
da transação SQL, então um `down()` criaria a aparência de atomicidade que não
existe; uma migration parcialmente aplicada só se reexecuta por
`migrate:refresh`, que é destrutivo; e a reconciliação pré-cutover acontece
**semanas depois** — dentro de uma migration já executada, ela simplesmente não
teria como rodar.

### 5.2 Os dois modos

```text
catalog:backfill-offer-content --inicial
catalog:backfill-offer-content --reconciliar --confirmar-sem-writers-02e
                               [--simular]
```

| | `--inicial` | `--reconciliar` |
|---|---|---|
| Quando | execução da 02D | uma vez, imediatamente antes do cutover da 02E |
| Destino vazio | popula | popula |
| Destino preenchido | **preserva** | compara e, se divergiu, **substitui** |
| Apaga arquivo? | nunca | sim, e só cópias que ele próprio criou |
| Reexecução | no-op | converge para o mesmo estado |

`--simular` mede e relata sem tocar em banco nem em disco.

### 5.3 Guards da reconciliação destrutiva

A reconciliação só é segura enquanto nenhum writer da 02E existir: nesse
intervalo a estrutura nova é **propriedade exclusiva do backfill**, e apagar
suas linhas não destrói trabalho de ninguém (D11-C). Depois do primeiro writer,
o mesmo `delete` passaria a apagar arquivo enviado por um lojista.

Essa premissa **não é detectável pelo código** — não há marcador de proveniência,
e inventar um seria fingir uma certeza que não temos. Então ela é **declarada por
quem executa**:

- sem `--confirmar-sem-writers-02e`, o comando pergunta e o padrão é **não**;
- em execução não interativa sem a flag, ele **falha** com código 1 e não escreve
  nada — verificado;
- nenhuma migration, deploy, boot ou scheduler o invoca.

### 5.4 Imagens — cópia física e compensação

```text
para cada oferta determinística (produto com exatamente 1 oferta):
    copiar fisicamente cada arquivo de Product.images
    validar que o conjunto inteiro existe
    só então persistir ProductOffer.images
```

A ordem é a compensação. Banco e disco não compartilham transação, então o JSON
só é gravado depois de **todos** os arquivos existirem. Se algo falhar antes
disso, as cópias daquela tentativa são removidas e a oferta fica exatamente como
estava — nunca apontando para um conjunto parcialmente copiado como se estivesse
íntegro. **A origem jamais é tocada.**

Origem ausente não é escondida: conta em `imagens_fontes_ausentes`, entra na
lista de erros e faz o comando sair com código diferente de zero. O produto
simplesmente não é considerado projetado.

**Nomes de destino:** UUID novo, sufixo `_thumb`/`_medium`, mesmo diretório da
origem — padrão do `ImageService`, para que listagem e exclusão futuras não
precisem distinguir de onde o arquivo veio.

**A extensão é a da origem**, e a especificação diz o mesmo — depois de ter sido
corrigida por causa desta implementação. O histórico está no §10; a regra vigente
é a do §16.3 revisado: cópia byte-a-byte não é conversão de formato, e nomear um
PNG de `.webp` faria o servidor anunciar um `Content-Type` que o arquivo não tem.
Tudo o mais do padrão do `ImageService` — UUID, sufixo, diretório — é literal.

`thumb` e `medium` da mesma entrada apontando para o mesmo arquivo geram **uma**
cópia, reaproveitada nas duas chaves — a alternativa que a §16.3 já declarava
aceitável. O compartilhamento fica dentro da entrada e as duas morrem juntas; o
que o §17 proíbe é compartilhar entre o produto e a oferta.

**Reconciliação por conteúdo, nunca por path.** Os paths são diferentes por
construção, então compará-los não diria nada. O que se compara é o hash dos
arquivos da origem contra o dos arquivos que a oferta referencia. Divergiu:
copia novo, persiste, e **só então** remove as cópias antigas — e apenas as que
nem a projeção nova nem a fonte ainda referenciam. Sem prova de propriedade, o
arquivo fica: prefere-se órfão temporário a perda.

### 5.5 FAQ — projeção exata, não *upsert*

Na reconciliação, `product_offer_faqs` de uma oferta elegível torna-se projeção
exata do conjunto de FAQs do produto:

```text
DESTINATION = SOURCE        (igualdade semântica)
nunca apenas   SOURCE ⊆ DESTINATION
```

Dentro de uma transação, o conjunto do destino é **substituído inteiro**. Três
razões, e a terceira decide:

1. **Exata por construção.** Criação, edição, remoção, redução, reordenação e
   limpeza total caem no mesmo caminho, sem caso especial — origem vazia termina
   com destino vazio porque não há o que inserir.
2. **Mesmo formato do writer legado**, que já faz `delete()` + `create()`.
3. **Um diff posicional colidiria com a `UNIQUE`.** Trocar `A[0] B[1]` por
   `B[0] A[1]` exigiria pôr A em 1 enquanto B ainda o ocupa, e o MySQL valida
   unicidade por *statement*, não no commit.

Os `product_faqs.id` da origem não são identidade — o writer legado apaga e
recria a cada salvamento. O que importa é o conjunto ordenado atual.

No modo `--inicial` a regra é a oposta e deliberada: destino já povoado é
preservado, e `product_faqs` permanece intacta. A 02D termina com a FAQ comercial
existindo nos dois lugares. É estado de transição, e a limpeza é o passo 6 do
cutover da 02E.

### 5.6 Perguntas

`product_offer_id` é preenchido **só** onde a associação é determinística. O
filtro `WHERE product_offer_id IS NULL` é seguro porque o writer legado nunca
escreve a coluna: toda linha nula é, por construção, linha ainda não
reconciliada — e a segunda execução é naturalmente no-op.

Produto com 0 ou >1 ofertas: permanece nulo. **Nunca inferir** por
`expositor_id`, delegação canônica, usuário ou primeira oferta encontrada — uma
pergunta atribuída à loja errada faz um comerciante responder pelo outro.

### 5.7 Caso não determinístico

FAQ de produto com 0 ou mais de uma oferta é **FAQ LEGADA NÃO RESOLVIDA**: não
migra, não é apagada, não é inferida e não vira canônica por omissão. Conta em
`faq_nao_resolvidas`, e no modo `--reconciliar` qualquer contagem maior que zero
**faz o comando falhar**, bloqueando o cutover.

## 6. Execução real — MySQL 8.4

### 6.1 Métricas medidas, não presumidas

Os números da auditoria foram **remedidos** antes de qualquer escrita, e nada
está hardcoded em migration, command ou teste.

| Métrica | Antes | Depois |
|---|---:|---:|
| `products` | 75 | 75 |
| `product_offers` | 75 | 75 |
| Produtos com exatamente 1 oferta | 75 | 75 |
| Produtos com 0 ou >1 ofertas | 0 | 0 |
| `products.images` não nulos | 75 | 75 |
| `products.image_path` não nulos | 75 | 75 |
| `product_offers.images` preenchidos | 0 | **75** |
| `product_faqs` | 0 | 0 |
| `product_offer_faqs` | 0 | 0 |
| `product_questions` | 0 | 0 |
| Perguntas com `product_offer_id` | 0 | 0 |
| FAQs não resolvidas | 0 | 0 |
| Perguntas não resolvidas | 0 | 0 |
| Arquivos copiados | — | **75** |
| Arquivos de origem ausentes | 0 | 0 |
| Falhas de cópia | — | 0 |

75 arquivos e não 150 porque `thumb` e `medium` apontam para o mesmo arquivo em
toda a base atual, e a cópia é deduplicada dentro da entrada.

### 6.2 O invariante do §17, verificado sobre os paths reais

```text
paths distintos referenciados por products.images        : 75
paths distintos referenciados por product_offers.images  : 75
PATHS COMPARTILHADOS                                     :  0
arquivos da oferta ausentes em disco                     :  0
product_offer_faqs órfãs                                 :  0
migrations pendentes                                     :  0
```

Consulta explícita sobre os caminhos, não contagem de linhas.

### 6.3 Idempotência sobre os dados reais

| Execução | populadas | preservadas | copiados | removidos |
|---|---:|---:|---:|---:|
| `--inicial` (1ª) | 75 | 0 | 75 | 0 |
| `--inicial` (2ª) | 0 | 75 | **0** | 0 |
| `--reconciliar` | 0 | 75 | **0** | 0 |

A segunda execução não cria arquivo nenhum, e a reconciliação reconhece a
projeção como fiel por conteúdo e não recopia.

### 6.4 Constraints provadas no MySQL real

Executadas dentro de uma transação revertida — sem DDL, sem `ANALYZE TABLE`,
sem resíduo. Contagens conferidas após o `ROLLBACK`: 75 ofertas, 0 FAQs, 0
perguntas, exatamente como antes.

| Constraint | Resultado |
|---|---|
| `UNIQUE(product_offer_id, sort_order)` | recusou a duplicata (SQLSTATE 23000) |
| FK `product_offer_faqs` → `product_offers` `CASCADE` | FAQ removida com a oferta |
| FK `product_questions` → `product_offers` `SET NULL` | pergunta **preservada**, contexto anulado |
| `product_questions.product_id` | continua `NOT NULL` |
| `product_offers.images` | `json`, `NULL` permitido |

## 7. Testes

### 7.1 `ConteudoPorOfertaSchemaTest` — 12 testes

Coluna `images` existe, é nullable e converte para array · `image_path` **não**
existe na oferta · FAQ da oferta exige oferta e recusa oferta inexistente ·
`CASCADE` apaga a FAQ com a oferta · `UNIQUE` recusa duas FAQs na mesma posição ·
a mesma posição é livre em ofertas diferentes · pergunta nasce sem contexto ·
excluir a oferta **preserva a pergunta** e anula o contexto · `product_id`
continua obrigatório · a relação alcança a oferta nos dois sentidos.

Nenhum mock: cada constraint é provada escrevendo e esperando o erro real.

### 7.2 `BackfillConteudoPorOfertaTest` — 26 testes

**Imagens.** Produto sem imagem · produto sem oferta · produto com >1 oferta ·
destino vazio recebe cópia física · **produto e oferta nunca compartilham
arquivo** (desigualdade de path *e* existência dos bytes dos dois lados *e*
apagar um lado não leva o outro) · segunda execução preserva e não recopia ·
origem ausente falha sem gravar path quebrado · **falha parcial não deixa
projeção incompleta nem lixo em disco** · **drift corrigido pela reconciliação**
(A → A′, fonte vira B, reconciliar → B′, A′ removido, nada compartilhado) ·
reconciliar não recopia quando a projeção ainda é fiel.

**FAQ — os sete casos.** Criação · edição · remoção · limpeza total ·
reordenação · idempotência (reconciliar duas vezes = mesmo estado) · caso não
determinístico (0 e >1 ofertas, sem migrar e sem apagar). Mais: cópia sem tocar
a origem, `--inicial` não sobrescreve destino povoado, segunda execução não
duplica, e **paridade origem × destino** comparada por conteúdo.

**Perguntas.** Recebe oferta quando há exatamente uma · continua nula com 0 e com
>1 ofertas · linha já resolvida é preservada e a segunda execução é no-op.

**Simulação.** `--simular` não escreve no banco nem cria arquivo.

### 7.3 `FronteiraConteudoPorOfertaTest` — 4 testes

A prova de que a 02E não foi antecipada, exercitando o `ProdutoForm` real:

- upload pelo writer legado grava em `products.images`; `product_offers.images`
  **continua nula**;
- FAQ salva pelo formulário vai para `product_faqs`; `product_offer_faqs`
  **continua vazia**;
- pergunta criada não recebe `product_offer_id` — que é justamente o que torna
  `WHERE product_offer_id IS NULL` um filtro de reconciliação seguro;
- o cadastro continua produzindo **exatamente uma** oferta.

### 7.4 Suíte completa

| | Antes | Depois |
|---|---:|---:|
| Testes | 889 | 931 |
| Assertions | 2559 | 2657 |
| Falhas | 0 | **0** |

Regressões dirigidas reexecutadas sem alteração de comportamento:
`AutoridadeCanonicaTest`, `IntegridadeDoCatalogoTest`, `ProdutoMestreOfertaTest`,
`CatalogoIsolamentoTest`, `ProductFaqTest`, `ProductQandATest`,
`PreservacaoHistoricaTest`, `SnapshotComercialTest`, suíte financeira e AVA.

## 8. Fronteiras preservadas

| | |
|---|---|
| Writers runtime | **inalterados** — `SaveProductWithOffer`, `ProdutoForm`, `ProdutoController`, `syncFaqs`, `ProductQAndA`, `PerguntaIndex/Controller` |
| Readers runtime | **inalterados** — `ProductResource`, `CatalogoController`, views, checkout, carrinho, `ProductShareImageService`, `main_image_url` |
| Autorização | **inalterada** — nenhuma Policy, `Gate::before` ou `guardOwnership` tocado |
| Multi-oferta | **não habilitada** — nenhuma tela, endpoint ou bloqueio removido |
| `products.images` / `image_path` | preservados |
| `product_faqs` | preservada e intacta |
| `product_questions.product_id` | preservado, `NOT NULL` |
| `answered_by` | inalterado |
| FIN-SEC / AVA / Catalog Intelligence | intocados; `Product` continua o eixo canônico do CI |

Nenhum conteúdo por oferta alimenta o Catalog Intelligence — FAQ da oferta,
pergunta e imagem da oferta **não** viram conhecimento canônico automaticamente.

## 9. Gates de especificação × evidência de implementação

Gate fechado na especificação não prova implementação correta. Para cada gate
implementável, a evidência concreta:

| Gate | Evidência |
|---|---|
| **G-D1** a **G-D6** | especificação; remedidos em §6.1 antes de escrever |
| **G-D7** | FKs reais em §3.2 e §3.3, lidas do `information_schema` |
| **G-D8** | `CASCADE` e `SET NULL` provados no MySQL real (§6.4) |
| **G-D9** | coluna JSON criada; cópia física com compensação (§5.4); reversão em `down()` sem tocar disco (§3.4) |
| **G-D10** | `product_offer_faqs` criada com FK `NOT NULL` e `UNIQUE` (§3.2) |
| **G-D11** | `product_offer_id` nullable com `SET NULL` (§3.3) |
| **G-D12** | backfill dos três conteúdos, executado no banco real (§6.1) |
| **G-D13** | **0 paths compartilhados** sobre os dados reais (§6.2) + teste dedicado (§7.2) |
| **G-D14** | `FronteiraConteudoPorOfertaTest` (§7.3) |
| **G-D15** | teste de oferta única (§7.3); nenhuma superfície nova |
| **G-D16** | `--pretend` produz só DDL aditiva; suíte completa sem regressão (§7.4) |
| **G-D17** | modo `--reconciliar` com premissa declarada e guards que falham fechado (§5.3) |
| **G-D18** | sincronização exata origem × destino nos sete casos (§7.2); `divergenciasDeFaq()` como verificação; bloqueio no caso não determinístico (§5.7) |
| **G-D19** | backfill num Command, fora da migration, com justificativa (§5.1) |

## 10. Divergência encontrada e reconciliada antes do commit

**Uma, e está resolvida na especificação — não pendente.**

```text
o que a especificação previa   →  sufixo fixo _thumb.webp / _medium.webp
o que os arquivos reais são    →  conteúdo PNG nos 75 produtos
o que o backfill faz           →  copia bytes; não decodifica, não reencoda
```

Renomear um PNG para `.webp` produziria divergência objetiva entre a extensão
anunciada e o conteúdo binário real. Não é preferência de estilo: é o tipo de
incompatibilidade que a instrução da fase manda parar e reportar em vez de
improvisar.

**A correção foi documental, e veio antes do commit.** O §16.3 da especificação
passou a prescrever preservação da extensão de origem, com a distinção explícita
de que cópia byte-a-byte não constitui conversão de formato, e com o registro de
que uma eventual normalização para WebP exigiria decodificação e reencodificação
reais — decisão de outra fase, nunca efeito colateral de renomear arquivo.

`ImageService::store()` continua gravando `.webp` legitimamente, porque ele de
fato reencoda o upload. O backfill não reencoda, e por isso herda o padrão de
nomeação sem herdar a extensão.

**O comportamento implementado é aderente à especificação revisada**, e nenhuma
linha de código mudou por causa dessa reconciliação — a implementação já estava
correta; a norma é que descrevia o mundo errado.

Fora isso, nada divergiu. Todas as decisões congeladas — `ProductOffer.images` JSON nullable,
`product_offer_faqs` como FAQ da oferta, `product_faqs` como canônica,
`product_questions.product_offer_id` nullable com `SET NULL`,
`UNIQUE(product_offer_id, sort_order)`, `answered_by` inalterado, a fronteira
02D/02E/02F — foram implementadas literalmente.

## 11. Dívidas e riscos que continuam abertos

**M-05 permanece.** As imagens canônicas continuam graváveis por qualquer
ofertante sem autoridade. A 02D cria o destino da imagem da oferta e **não fecha
essa lacuna** — ela vai até a 02E/02F. Enquanto multi-oferta estiver desabilitada,
ninguém a alcança.

**R-2 permanece latente.** A 02D só cria o contexto da pergunta; a correção do
guard é 02F.

**`ImageService` continua sem contagem de referências.** A 02D não conserta —
apenas se recusa a construir sobre a fragilidade, copiando fisicamente.

**Arquivos órfãos.** Excluir uma oferta com imagens deixa arquivos em disco
(R-4), e `down()` também. Limpeza pertence à 02E/02F, como operação explícita.

**Pint:** `app/Models/ProductQuestion.php` tem uma violação `binary_operator_spaces`
**preexistente**, no bloco `casts()` que esta fase não tocou. Registrada e não
corrigida — reformatá-la poria no diff da 02D uma mudança que não é dela.

## 12. Pendências entregues para as próximas fases

### Para a CAT-DOM-02E

1. Rodar `catalog:backfill-offer-content --reconciliar --confirmar-sem-writers-02e`
   **antes** de habilitar qualquer writer novo — e antes da primeira parte, se a
   02E for entregue em partes.
2. Ordem obrigatória do cutover da FAQ: bloquear `syncFaqs` → reconciliar →
   validar destino determinístico → **parar se houver FAQ não resolvida** →
   trocar readers e writers → remover de `product_faqs` só as linhas comerciais
   migradas.
3. Migrar readers e writers de imagem, com fallback canônico e *dual-read*.
4. Depois do primeiro writer, a reconciliação destrutiva fica **proibida**
   (D11-C).

### Para a CAT-DOM-02F

Guard "esta oferta é sua?" sobre imagem, FAQ e pergunta; isolamento A × B;
autoria comercial da resposta, agora derivável por `question.offer.expositor`
sem coluna nova; curadoria e promoção a canônico.

---

**Status:** implementação concluída, validada em MySQL 8.4 real, suíte completa
verde. Nenhum writer ou reader runtime migrado, nenhuma autorização alterada,
multi-oferta não habilitada.
