# CAT-DOM-02C — Autoridade de `Product` e fim do write-through legado

> **Projeto:** Feira Esquerda Livre
> **Trilha:** Catalog Intelligence — implementação das decisões da CAT-DOM-02B
> **Baseline:** `c7cc1d08353f82996a0e9afd3d43a907b100eb14`
> **Natureza:** primeira fase executável da CAT-DOM-02. Pequena, auditável e reversível.

---

## 1. Objetivo

A CAT-DOM-02B congelou treze decisões e não implementou nenhuma. Esta fase
executa as três que não dependem de mais nada:

1. **D-CAT-09** — autoridade canônica pertence à plataforma, exercida por
   curadoria ou por **delegação declarada**, nunca deduzida da cardinalidade de
   `ProductOffer` nem de `products.expositor_id`.
2. **D-CAT-10** — `products.is_active` é validade canônica e passa a ser
   exclusivo da curadoria; `product_offers.is_active` continua sendo do lojista.
3. **Fim do write-through** — os **doze** espelhos comerciais deixam de ser
   gravados em `products`. As colunas permanecem fisicamente; ninguém mais as
   alimenta.

> **`products.is_active` não é um espelho comercial.** Ele é campo canônico
> legítimo de `Product` — validade do item no catálogo (D-CAT-10) — e **não
> entra** na remoção da CAT-DOM-02H. O que a 02C encerrou foi a **cópia** do
> valor comercial da oferta para ele, não a coluna.

O que esta fase **não** faz está na §12.

---

## 2. Mecanismo escolhido para a delegação

### 2.1 Alternativas avaliadas

| # | Mecanismo | Avaliação |
|---|---|---|
| **A** | **Três colunas em `products`** | **Escolhido** |
| B | Tabela própria de delegação/governança | Recusado — ver abaixo |
| C | Policy sem estado, derivando de outro dado existente | Recusado: não existe dado que represente autoridade sem ser proveniência ou cardinalidade, e a D-CAT-09 proíbe os dois |

### 2.2 Por que a coluna, e não a tabela

A tabela daria histórico de todas as delegações já concedidas. Foi recusada por
dois motivos concretos.

**O invariante §18 sai de graça na coluna.** "No máximo uma delegação ativa por
produto" é verdade por construção quando o delegado é uma coluna — não cabe um
segundo valor. Numa tabela exigiria índice único parcial, que o MySQL 8.4 não
oferece, e cairia em coluna gerada ou em regra de aplicação: mais peça para
manter, e uma delas fora do banco.

**O histórico completo ainda não tem consumidor.** O que a fase precisa saber
está em §35 do escopo — quem detém a delegação, se está ativa, quando nasceu,
quando foi revogada — e cabe nas três colunas. A entidade de contribuição da
fase futura trata de *propostas*, que são outro objeto; ela não herda desta.

### 2.3 Schema final

```sql
`canonical_delegate_expositor_id` bigint unsigned DEFAULT NULL,
`canonical_delegated_at`          timestamp NULL DEFAULT NULL,
`canonical_delegation_revoked_at` timestamp NULL DEFAULT NULL,

KEY `products_canonical_delegate_expositor_id_foreign` (`canonical_delegate_expositor_id`),
CONSTRAINT `products_canonical_delegate_expositor_id_foreign`
  FOREIGN KEY (`canonical_delegate_expositor_id`)
  REFERENCES `expositores` (`id`) ON DELETE SET NULL
```

Sem `UNIQUE` — a unicidade da delegação ativa é estrutural, não de índice.
`SET NULL` é a semântica certa: sem expositor não há delegado, e o produto
continua no catálogo sob autoridade exclusiva da curadoria.

**Delegação ativa** é `canonical_delegate_expositor_id IS NOT NULL AND
canonical_delegation_revoked_at IS NULL`. Revogar não apaga o delegado nem a
data de concessão — sem isso, revogar destruiria a evidência de que alguém já
teve a delegação.

### 2.4 `canonical_delegate_expositor_id` × `expositor_id`

As duas colunas ficam lado a lado de propósito e significam coisas diferentes:

| Coluna | Significa | Autoriza? |
|---|---|:--:|
| `expositor_id` | **Proveniência** — quem trouxe o item ao catálogo (D-CAT-11) | **Nunca** |
| `canonical_delegate_expositor_id` | **Autoridade** — quem a plataforma autorizou a editar a identidade, agora | Sim |

Hoje apontam para o mesmo expositor nas 75 linhas, porque o backfill inicializou
uma a partir da outra. Divergem no primeiro ato de curadoria — e é por isso que
precisam ser colunas distintas desde já.

---

## 3. Backfill

Aditivo, determinístico e reexecutável (`whereNull` no destino não sobrescreve
delegação já movida). Em dois passos com valor ligado, e não
`COALESCE(created_at, NOW())`: `NOW()` não existe em SQLite, e a suíte roda lá.

`expositor_id` é usado **como fonte de inicialização histórica, e nunca como
regra de runtime**. Passada a migration, a autoridade é lida exclusivamente das
colunas novas.

Produto órfão — sem expositor — não recebe delegação: fica sob curadoria, que é
o estado correto para um item que ninguém trouxe e ninguém oferece.

### 3.1 Dados reais, MySQL 8.4

| Métrica | Antes | Depois |
|---|---:|---:|
| `products` | 75 | **75** |
| `product_offers` | 75 | **75** |
| `expositores` | 14 | **14** |
| Delegações ativas | — | **75** |
| Delegações revogadas | — | 0 |
| Produtos sem delegação | — | 0 |
| `canonical_delegated_at` nulo com delegado | — | 0 |
| Delegação para expositor inexistente | — | 0 |
| Delegado ≠ `expositor_id` | — | 0 |
| Produtos sem oferta | 0 | 0 |
| Produtos com > 1 oferta | 0 | 0 |
| `products.is_active = 0` | 0 | 0 |

Nenhum `Product` perdido, nenhuma `ProductOffer` perdida, nenhuma alteração
destrutiva. `migrate:status`: 0 pendentes.

---

## 4. Onde a autorização passou a viver

**`app/Policies/ProductPolicy.php`** — fonte única, com dois abilities:

| Ability | Quem passa |
|---|---|
| `updateCanonical` | curadoria **ou** delegação válida para o expositor do usuário |
| `updateStatus` | **somente** curadoria |

A curadoria é identificada por `$user->can('produtos.moderar')` — a permissão que
o projeto já declarava em `RolePermissionSeeder` para `administrador`, `gerente`
e `supervisor`, e que até agora não tinha nenhuma superfície que a exercesse.
`Gate::before` continua concedendo tudo a admin. **Nenhuma permissão nova foi
criada e a matriz de papéis não foi alterada.**

A Policy não pergunta pela quantidade de ofertas, não lê `products.expositor_id`
e não pergunta "o expositor tem uma oferta sobre este produto?" — essa última
continua valendo, e continua onde sempre esteve: nos guards da SEC-02, sobre a
oferta.

### 4.1 Campos

| Grupo | Campos | Quem edita |
|---|---|---|
| **Canônicos sob delegação** | `name`, `short_description`, `description`, `item_type`, `category_id`, `is_digital` | Curadoria ou delegado |
| **Validade canônica** | `is_active` | **Só curadoria** |
| **Derivado** | `slug` | Plataforma, na criação |
| **Comercial** | os 12 espelhos | Expositor, **só em `product_offers`** |
| **Fora do escopo desta fase** | `images`, `image_path` | Inalterado — CAT-DOM-02D |

### 4.2 A verificação é sobre mudança, não sobre presença

Os dois formulários reenviam o item inteiro a cada salvamento. Exigir autoridade
por o campo estar no payload impediria o lojista sem delegação de corrigir o
próprio preço — recusa que puniria o que ele pode fazer por causa do que ele não
mudou. A action compara os canônicos recebidos com os **atributos crus** do
banco (não com os do model: `item_type` chega string e sai enum, `is_digital`
chega bool e mora `0`/`1`) e só exige autoridade quando algo de fato muda.

---

## 5. Comportamento de `is_active`

| Campo | Significa | Efeito |
|---|---|---|
| `products.is_active` | Validade canônica do item no catálogo | `false` ⇒ nenhuma oferta é vigente |
| `product_offers.is_active` | Disponibilidade comercial daquela oferta | `false` ⇒ aquela oferta sai das vitrines |

`ProdutoIndex::toggleActive` passou a fazer **uma escrita só**, a da oferta. O
lojista não perde nada: `ProductOffer::scopeVigente()` exige oferta ativa, então
desligar a oferta já tira o item de todas as vitrines. O que ele deixa de
conseguir é retirar do catálogo um item de que outras lojas podem depender.

---

## 6. Writers alterados

| Arquivo | Antes | Depois |
|---|---|---|
| `SaveProductWithOffer::dadosDoProduto()` | `CAMPOS_DO_PRODUTO + CAMPOS_DA_OFERTA` | Só `CAMPOS_DO_PRODUTO`; sem `slug` no update; exige autoridade para mudar canônico |
| `SaveProductWithOffer::__invoke()` | — | Recebe o ator; concede delegação na criação |
| `ProdutoIndex::toggleActive()` | Escrevia oferta **e** `products.is_active`, em transação | Escreve só a oferta |
| `ProdutoForm::save()` | — | Passa o ator; trata `SemAutoridadeCanonica` com mensagem na tela |
| `ProdutoController::store/update()` | — | Passam o ator; a exceção vira **403** pelo `render()` |
| `ProductFactory::configure()` | Criava produto + oferta | Concede também a delegação, como a criação real |
| `SincronizaOfertaDoItem` | Sincronizava a oferta | Concede também a delegação ao item semeado |

Writers de `ProductOffer`: nenhum alterado. As três ações de estoque, o
`DeleteProductOffer` e a guarda de reserva órfã ficaram intocados.

---

## 7. Fim do write-through

Os **doze** espelhos que `products` deixou de receber — nomeados em
`SaveProductWithOffer::ESPELHOS_COMERCIAIS_LEGADOS`:

`price` · `price_type` · `modality` · `duration_min` · `weight` · `height` ·
`width` · `length` · `has_stock` · `stock_quantity` · `is_featured` ·
`sort_order`

**`is_active` está deliberadamente fora desta lista.** `CAMPOS_DA_OFERTA` tem
treze entradas porque `product_offers.is_active` de fato vem do formulário do
lojista; a lista de **espelhos legados** tem doze, porque `products.is_active`
não é cópia de nada — é validade canônica, tem significado próprio, e continua
sendo escrito e lido como campo legítimo do produto. A futura remoção de colunas
da CAT-DOM-02H **não inclui `products.is_active`**.

**As colunas continuam fisicamente em `products`** — nenhum `DROP`, `RENAME` ou
`ALTER` destrutivo. A remoção física é a CAT-DOM-02H.

Elas permanecem em `$fillable` e nos `casts` do model. Isso é compatibilidade,
**não autoridade**: presença em `$fillable` só diz que o atributo é atribuível
em massa se alguém o passar, e depois desta fase ninguém o passa. Retirá-las
agora seria antecipar a D-1 sem o backfill e sem a prova de paridade que o
critério de remoção exige.

### 7.1 Classificação dos writers dos doze espelhos

| Classe | Situação após a correção |
|---|---|
| **Runtime application writer** | **Nenhum.** `SaveProductWithOffer` escreve os doze só em `product_offers` |
| **Test fixture writer** | **Nenhum como autoridade.** A `ProductFactory` aceita a chave comercial como açúcar de entrada e a retira do modelo **antes da gravação**, no `afterMaking`: `products` não recebe o valor, a oferta recebe. `tests/Concurrency/harness.php` deixou de gravar `price` no produto |
| **Development seeder writer** | **Nenhum.** `SincronizaOfertaDoItem::semearItemComOferta()` divide o array do seeder: canônico em `products`, os doze em `product_offers`. Os cinco seeders foram migrados e nenhum chama mais `Product::updateOrCreate` com dado comercial |
| **Migration / backfill writer** | `2026_08_27_100002` (histórica, CAT-DOM-01) e `2026_08_30_100001` (delegação, não toca comercial). Permitidas por serem fatos históricos versionados |
| **Exceção deliberada** | `IntegridadeDoCatalogoTest::envelhecerEspelho()` grava `modality`/`duration_min` em `products` via `DB::table` **exatamente para provar que ninguém os lê**. Sem essa divergência artificial o teste passaria com o defeito no lugar |

### 7.1 Readers legados

Varredura nova em `app/`, `resources/` e `routes/`: **nenhum leitor comercial
runtime de `products`**. A CAT-DOM-02A já havia eliminado os últimos
(`modality`/`duration_min` na home, `Expositor::products()` nos painéis). As
únicas ocorrências de `->price` fora da oferta são `price_snapshot` de
`CartItem`, que é snapshot de pedido e não espelho.

Nenhum fallback `offer ?? product` foi introduzido — ele prolongaria a dívida.

---

## 8. SEC-02

Preservada e **estendida**. Os guards que perguntam "este lojista tem oferta
sobre este produto?" continuam válidos para a **oferta** e deixaram de ser
suficientes para o **produto**, que agora exige autoridade canônica.

`CatalogoIsolamentoTest` (21 testes) e `ProdutoMestreOfertaTest` seguem verdes
sem alteração de guard.

---

## 9. Testes

**Novo:** `tests/Feature/AutoridadeCanonicaTest.php` — 22 testes, 65 assertions.

| Prova | Teste |
|---|---|
| Delegado edita canônico | `test_delegado_edita_campo_canonico_do_proprio_item` |
| Sem delegação é recusado | `test_sem_delegacao_o_campo_canonico_e_recusado` |
| Ter oferta não concede autoridade | `test_ter_oferta_sobre_o_produto_nao_concede_autoridade_canonica` |
| **2 ofertas → 1 não restaura delegação** | `test_voltar_de_duas_ofertas_para_uma_nao_restaura_a_delegacao` |
| `expositor_id` não autoriza | `test_expositor_id_sozinho_nao_concede_autoridade` |
| Curadoria edita canônico | `test_curadoria_edita_campo_canonico` |
| Sem delegação, oferta própria segue editável | `test_delegado_sem_autoridade_ainda_altera_a_propria_oferta` |
| API e painel recusam igual | `test_api_recusa_alteracao_canonica_sem_delegacao` |
| Criação concede delegação | `test_cadastro_novo_nasce_com_delegacao_explicita` |
| Delegado não altera `is_active` | `test_lojista_com_delegacao_nao_altera_a_validade_canonica` |
| Curadoria altera `is_active` | `test_curadoria_altera_a_validade_canonica` |
| Lojista mantém `ProductOffer.is_active` | `test_seller_continua_dono_do_status_da_propria_oferta` |
| Fim do espelho (4 campos, data provider) | `test_campo_comercial_e_gravado_so_na_oferta` |
| Criação não alimenta o espelho | `test_item_novo_nao_alimenta_as_colunas_comerciais_legadas` |

**Alterados (5, nenhum removido):**

- `ProdutoMestreOfertaTest` — dois testes de espelho da CAT-DOM-01 foram
  **invertidos** (passaram a provar que o espelho não é mais alimentado e que
  alternar a oferta não toca a validade canônica) e
  `test_produto_compartilhado_nao_da_acesso_a_oferta_alheia` deixou de registrar
  a dívida D-2 como limite conhecido para provar que ela fechou.
- `AvaEnrollmentTest` e `EventTrackingTest` — liam `$product->price` para montar
  pedido e carrinho. A correção da factory os expôs: eram os dois últimos lugares
  da suíte tratando `products` como origem comercial. Passaram a ler a oferta.
- `tests/Concurrency/harness.php` deixou de gravar `price` no produto.

### 9.1 Provas mutation-like

Cada mutação foi aplicada, medida e revertida:

| Mutação | Resultado |
|---|---|
| Autoridade por `offers()->count() === 1` | **4 testes falham** |
| Autoridade por `products.expositor_id` | **4 testes falham** |
| Voltar a escrever o espelho comercial | **5 testes falham** |
| Delegado passa a alterar `Product.is_active` | **1 teste falha** |

### 9.2 Números

| | Baseline | Agora |
|---|---:|---:|
| Testes | 867 | **889** |
| Assertions | 2494 | **2559** |
| Falhas | 0 | **0** |

Reconciliação: 867 + 22 = **889**; 2494 + 65 = **2559**. Nenhum teste removido.
Os cinco arquivos alterados não mudam a contagem — mudam de onde leem o preço.

---

## 10. Riscos residuais

| Risco | Situação |
|---|---|
| **Não existe superfície de curadoria** | `products.is_active` e os canônicos de um item sem delegado só são editáveis por código. Aceitável hoje: 0 produtos inativos, 75 com delegação ativa. É o gate G-1 da 02B |
| Um caminho futuro pode reintroduzir a dedução por contagem | Coberto pelas provas mutation-like e pelo teste obrigatório de 2→1 |
| Lojista sem delegação tem o salvamento inteiro recusado ao mudar canônico | Deliberado: descartar em silêncio o que ele digitou seria pior. Alterar só o comercial continua passando |
| Colunas legadas seguem em `$fillable` | Compatibilidade, não autoridade: ninguém mais passa esses campos. Sai na 02H, com backfill e prova de paridade |

---

## 11. Gates de aceitação

| Gate | Situação |
|---|:--:|
| G-C1 autoridade com representação explícita | ✅ |
| G-C2 runtime não deduz por cardinalidade | ✅ |
| G-C3 runtime não usa `products.expositor_id` | ✅ |
| G-C4 seller sem delegação não altera canônico | ✅ |
| G-C5 delegado altera apenas os campos permitidos | ✅ |
| G-C6 `Product.is_active` exclusivo da curadoria | ✅ |
| G-C7 `ProductOffer.is_active` segue do seller | ✅ |
| G-C8 comercial escrito só em `ProductOffer` | ✅ |
| G-C9 nenhum writer alimenta os doze espelhos | ✅ **SATISFEITO** — runtime, fixture e seeder; só migrations históricas e a exceção deliberada de §7.1 |
| G-C10 colunas legadas preservadas | ✅ |
| G-C11 SEC-02 íntegra | ✅ |
| G-C12 teste 2 ofertas → 1 | ✅ |
| G-C13 criação cria delegação | ✅ |
| G-C14 migration aditiva e segura | ✅ |
| G-C15 multi-oferta desabilitada | ✅ |
| G-C16 imagens, FAQ, perguntas, AVA, slug e `ofertaVigente` fora do escopo | ✅ |

---

## 12. O que esta fase deliberadamente não fez

Multi-oferta **não** foi habilitada — não há tela nem endpoint que crie uma
segunda oferta sobre produto existente; os testes que constroem esse estado o
fazem direto no banco, como prova estrutural de isolamento.

Não foram tocados: imagens (`images`, `image_path`, `ImageService`,
`removeImage`), FAQ, perguntas e respostas, AVA, slug e colisão, `ofertaVigente`
e escolha de oferta, checkout, estoque e FIN-SEC. Nenhuma coluna foi removida.
Nenhuma fila de propostas, workflow editorial, dashboard de moderação ou sistema
de contribuição foi construído.

### 12.1 Dívidas preservadas

M-04 (curso AVA), M-05 (imagens globais), M-08 (API do carrinho), M-09
(`ofertaVigente`), M-10 (slug/F-09), M-12 (SEO), M-13 (CI sem oferta), M-14, M-16,
M-17 e D-1 (colunas legadas). Gates de multi-oferta ainda abertos: G-1 (falta a
superfície de curadoria), G-4, G-5, G-9, G-10, G-11.

## 13. Recomendação para a próxima fase

**CAT-DOM-02D** — estrutura de conteúdo por oferta: imagem da oferta, FAQ da
oferta e contexto de oferta nas perguntas, com migrations aditivas e backfill
1:1. O momento continua excepcionalmente barato: 0 FAQs, 0 perguntas, 0 itens de
carrinho e 0 itens de pedido no banco real.

A superfície de curadoria (G-1) pode ser feita antes ou depois da 02D — ela não
bloqueia nenhuma das duas, e só bloqueia multi-oferta.
