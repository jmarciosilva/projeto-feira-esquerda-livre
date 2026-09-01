# CAT-05E — Antialucinação e `missing_information`

> **Subfase de implementação.** Fecha a pendência **P-4** (o que vira
> palavra-chave) e entrega a tradução que a §3.4 exige: de nome técnico de
> coluna para pedido em linguagem de lojista. Nenhuma migration, nenhum
> provider externo, nenhuma escrita.
>
> **Esta subfase altera uma subfase anterior já commitada.** O `ContextSanitizer`
> da CAT-05C e um teste dela mudaram — está detalhado no §3.

Decisões que a governam:
[`CAT_05B_DECISOES_DE_PRODUTO_E_CONTRATOS.md`](CAT_05B_DECISOES_DE_PRODUTO_E_CONTRATOS.md).
Subfases que ela consome:
[`CAT_05C_LISTING_CONTEXT_E_SANITIZER.md`](CAT_05C_LISTING_CONTEXT_E_SANITIZER.md) ·
[`CAT_05D_LISTING_ASSISTANT.md`](CAT_05D_LISTING_ASSISTANT.md).

---

## 1. Baseline

| Item | Valor |
|---|---|
| Branch | `main` |
| HEAD no início | `caeaba9e125ce25e94b8bd1dc7bacce7f9a86850` |
| Working tree no início | Limpo |
| Suíte no início | 1104 passed · 3318 assertions · 0 failures |
| Data | 2026-09-01 |

**Resultado:** **1116 passed · 3352 assertions · 0 failures** em 753,95s.
`+12` testes, `+34` asserções, nenhuma regressão.

Suíte executada **do zero sobre a versão final**, com os md5 dos seis arquivos
registrados antes e conferidos depois.

---

## 2. P-4 — quais termos viram palavra-chave

### 2.1 A CAT-03 já tinha respondido, e ninguém tinha lido

O docblock de `KnowledgeTermType`, escrito na CAT-03, endereça esta subfase
quase por nome:

> *"A distinção importa na hora de escrever texto: um sinônimo pode substituir o
> nome canônico numa frase, um termo comercial é como o público procura, e um
> alias costuma ser só grafia alternativa. **Quem for gerar descrição precisa
> saber qual é qual.**"*

A pergunta da P-4 — *"conceito, termo, ou os dois?"* — estava mal posta. A
resposta certa é **por tipo de termo**.

### 2.2 O que a base real mostra

30 termos sobre 28 conceitos, no MySQL de desenvolvimento:

| Tipo | Qtd | O que é, na prática |
|---|---|---|
| `synonym` | 16 | "trabalho manual" (Artesanato) · "argila" (Barro) · "combo" (Kit) · "lembrança" (Presente) · "gravura em madeira" (Xilogravura); e formas verbais: "crochetar", "tricotar", "bordar", "costurar", "tecer" |
| `alias` | 8 | `croche` · `ceramica` · `algodao` · `feito a mao` · `la` · `trico` · `decoracao` · `ervas` |
| `commercial_term` | 6 | "ajuste de roupa" (Costura) · "cerâmica artesanal" · "bordado à mão" · "ingredientes naturais" · "sementes amazônicas" · "tecido artesanal" |
| `keyword` | **0** | o tipo existe no enum e nenhum registro o usa |

**O achado que decidiu:** sete dos oito `alias` são a grafia **sem acento do
próprio nome canônico**. Só `ervas` (para "Ervas medicinais") é encurtamento
real. E há um agravante — o `KnowledgeNormalizer` **já remove acentos** para o
casamento, então esses aliases não acrescentam nem ao matching. Como
palavra-chave produziriam "Crochê" e "croche" lado a lado.

Na direção oposta, `commercial_term` é exatamente o que uma palavra-chave
deveria ser. **"Costura" não alcança quem digita "ajuste de roupa"** — e essa
frase não aparecia em lugar nenhum da sugestão até esta subfase.

### 2.3 Decisão

**Nome canônico + `commercial_term` + `synonym`. `alias` e `keyword` ficam
fora.**

`synonym` entra apesar de metade ser forma verbal, porque "argila" para Barro e
"trabalho manual" para Artesanato são vocabulário que um comprador usa de
verdade; o custo é meia dúzia de verbos numa lista de palavras-chave, que é o
pior caso possível de ruído aqui. `keyword` fica fora por ora **porque ninguém o
usa**: decidir o comportamento de um caso que nenhum registro exercita seria
escolher no escuro.

A regra mora em `ContextSanitizer::termosUteis()`, num lugar só. O assistente
apenas ordena e desduplica — e o nome canônico vem sempre primeiro, para que a
lista comece pelo que a curadoria nomeou.

---

## 3. Alteração em subfase anterior já commitada

Mesmo padrão de transparência usado quando a CAT-05B revisou um teste da
CAT-DOM-01.

### 3.1 `ContextSanitizer::conhecimento()` — CAT-05C, commit `f193b07`

**O que mudou.** A redução de `KnowledgeCandidate` a escalares passou a incluir
uma quarta chave:

```diff
  'name' => (string) $c->entry->name,
  'type' => $c->entry->type->value,
  'description' => $c->entry->description,
+ 'terms' => $this->termosUteis($c),
```

E um método privado novo, `termosUteis()`, com a regra do §2.3.

**Por que foi inevitável.** `palavrasChave()` vive no assistente e só enxerga o
que o contexto carrega. O contexto carregava nome, tipo e descrição — os termos
eram **descartados** na fronteira. Sem tocar o sanitizer, a decisão (d) não teria
por onde chegar ao assistente.

**O que não mudou.** A garantia central da CAT-05C continua valendo: nenhum
model Eloquent atravessa a fronteira. `terms` sai como `array<int, string>`, e
o `KnowledgeTerm` fica para trás como o `KnowledgeEntry` sempre ficou. A
proteção continua sendo **lista de permissão** — a chave nova foi declarada, não
aberta.

### 3.2 `ListingContextTest` — CAT-05C, commit `f193b07`

`test_conhecimento_entra_como_texto_e_nao_como_model` travava a forma exata:

```diff
- $this->assertSame(['name', 'type', 'description'], array_keys(...));
+ $this->assertSame(['name', 'type', 'description', 'terms'], array_keys(...));
+ $this->assertIsArray($contexto->knowledge[0]['terms']);
```

O teste continua provando o que dá nome a ele — que ali entra texto e não model
—, e ficou **mais forte**: agora afirma também que a chave nova é array de
escalares, e não a coleção de `KnowledgeTerm`.

### 3.3 Confirmação

Os **147 casos do módulo** `tests/Feature/CatalogIntelligence/` passam depois da
mudança. São 137 métodos de teste — `KnowledgeBaseTest` 38, `ProductSimilarityTest`
36, `ListingContextTest` 26, `ListingAssistantTest` 37 —, que executam como 147
casos porque dois deles usam data provider. A suíte completa fecha em 1116, sem
regressão.

---

## 4. `missing_information` em linguagem de lojista

### 4.1 O que a especificação pede, literal

> *"`missing_information` é o mecanismo antialucinação: em vez de inventar
> material, a inteligência devolve **'informe o material'**."* — §3.4

E a regra 1 das invioláveis: *"Na dúvida: **omitir e pedir a informação ao
lojista**."*

Até a CAT-05D, `missing_information` devolvia `short_description`,
`description`, `category`, `attributes`, `knowledge` — nome técnico de coluna.
Um lojista não sabe o que é `attributes`, e uma lista de nomes de campo é
diagnóstico interno, não pedido.

### 4.2 `ListingGap`, e por que enum

Cinco casos, um por lacuna, com `pedido()` devolvendo o texto.

O conjunto é **fechado** — vem de `ListingContext::lacunas()`, que enumera
exatamente cinco. Um `match` sobre enum falha quando alguém acrescentar a sexta;
um array associativo devolveria `null` em silêncio e a lacuna nova sumiria da
sugestão sem ninguém notar. `tryFrom()` liga os dois lados, e string
desconhecida é descartada em vez de virar pedido vazio.

Cada texto diz **o que informar** e, quando ajuda, **por que aquilo importa** —
um resumo existe para aparecer no card, uma categoria para o item ser
encontrado. Pedido sem motivo é cobrança, e a primeira coisa que se faz com
cobrança sem motivo é ignorá-la.

### 4.3 O ajuste que a pergunta sobre `lacunas()` provocou

**`lacunas()` continua sendo o insumo correto e não foi tocado** — ele é da
CAT-05C, diz o que o *item* não tem, e essa é a leitura certa. O que mudou é
como ele é **consumido**.

**Uma lacuna que a própria sugestão preenche deixa de virar pedido.** Se o
assistente está oferecendo um resumo, pedir "escreva um resumo" na mesma
resposta é ruído — e ruído faz o lojista desconfiar dos outros pedidos,
inclusive os que ele precisa mesmo atender.

Sobram os que dependem de uma pessoa: **categoria** é escolha dele, **atributo**
é fato que só ele sabe, **conhecimento** é trabalho da curadoria. Se não houver
descrição curada de onde compor, a lacuna de descrição **volta** a ser pedida —
há teste para isso.

A consequência de ordem: `compor()` agora monta o texto **antes** de apurar o
que falta. A ordem é o mecanismo, não detalhe de implementação.

---

## 5. Cobertura de teste

12 casos novos, todos por fixture.

| O que prova | Teste |
|---|---|
| Termo comercial entra | `test_termo_comercial_entra_nas_palavras_chave` — "Costura" + "ajuste de roupa", o caso que motivou a decisão |
| Sinônimo entra | `test_sinonimo_entra_nas_palavras_chave` — "Barro" + "argila" |
| **Grafia alternativa não entra** | `test_grafia_alternativa_nao_entra_nas_palavras_chave` — "Crochê" sim, `croche` não |
| Tipo `keyword` não entra por ora | `test_termo_do_tipo_keyword_nao_entra_por_ora` |
| Nome canônico vem primeiro | `test_o_nome_canonico_vem_antes_dos_termos` |
| Sem repetição | `test_palavras_chave_nao_repetem` — dois conceitos com o mesmo sinônimo |
| **Nenhum nome de campo cru vaza** | `test_o_que_falta_e_pedido_em_portugues_e_nao_nome_de_campo` — varre os cinco nomes técnicos |
| Toda lacuna tem pedido | `test_toda_lacuna_tem_pedido_e_nenhum_e_vazio` — percorre `ListingGap::cases()` |
| Lacuna preenchida não vira pedido | `test_lacuna_que_a_sugestao_preenche_nao_vira_pedido` |
| Lacuna que depende de pessoa continua pedida | `test_lacuna_que_depende_de_pessoa_continua_sendo_pedida` |
| Campo sem proposta volta a ser pedido | `test_campo_sem_proposta_volta_a_ser_pedido` |
| Sugestão vazia pede tudo em português | `test_sugestao_vazia_pede_tudo_em_linguagem_de_lojista` |

---

## 6. Observação de custo

`ContextSanitizer::termosUteis()` lê `$candidato->entry->terms`. A relação
**chega carregada**: `MatchProductKnowledge` é o único produtor de
`KnowledgeCandidate` e faz `->with('terms')`.

Um candidato vindo de outro caminho, sem eager-load, resolveria por lazy-load —
uma consulta por conceito. Não há hoje esse caminho, e não há teste de contagem
de consultas sobre o assistente. **Se a CAT-05G travar contagem, este é o ponto
a observar.**

---

## 7. Dívidas ao fim da subfase

| # | Item | Situação |
|---|---|---|
| **P-4** | `keywords` de conceito, termo, ou ambos | ✅ **Fechada** — nome + `commercial_term` + `synonym` |
| **P-1** | Backfill — 0 associações | **Aberta** — adiada para CAT-05H (D-CAT-05D-1) |
| **C-1** | `knownAttributes` por lista de proibição | **Aberta** — CAT-09 mapeia campo a campo |
| **C-2** | Texto livre não é redigido | **Aberta** — CAT-05F ou CAT-10, sem decisão |
| **B-4** | Corpus de seeder | **Aberta** — atinge a validação da CAT-05H |
| **G-1** | Sem superfície de curadoria | **Aberta** — CAT-08 |
| **E-1** | `KnowledgeTermType::Keyword` sem uso e sem decisão | **Aberta** — decidir quando algum registro o usar |

---

## 8. O que esta subfase deliberadamente não fez

Nenhuma migration, tabela, coluna ou tela. Nenhuma alteração em `Product`,
`ProductOffer`, `SaveProductWithOffer`, `ProductPolicy`, `ProdutoForm`,
`ProdutoController`, AVA, checkout, estoque ou Customer Intelligence.

Nenhum provider externo — `CatalogAiProvider`, `Fake`, `Null` e
`EmbeddingProvider` seguem inexistentes, e o teste que falha se alguém os criar
continua no lugar. A forma do `ListingSuggestion` (§3.4) **não foi reaberta**:
os sete campos e a ordem são os que a CAT-05D congelou.

`suggested_name` continua sempre nulo e `confidence` continua nula — as decisões
D-CAT-05D-2 e D-CAT-05D-3 não foram tocadas.

O backfill não foi executado. Pint rodou apenas nos seis arquivos desta subfase.

---

## 9. Decision log

| # | Decisão | Motivo |
|---|---|---|
| **D-CAT-05E-1** | `keywords` = nome canônico + `commercial_term` + `synonym` | Termo comercial é a frase que o público digita, e "Costura" não alcançava "ajuste de roupa". Sinônimo é vocabulário alternativo legítimo |
| **D-CAT-05E-2** | `alias` **fica fora** | Sete dos oito são a grafia sem acento do nome canônico; a normalização já cobre isso no casamento, e como palavra-chave seria duplicata visível |
| **D-CAT-05E-3** | `keyword` fica fora por ora | Nenhum registro usa o tipo. Decidir por um caso que ninguém exercita é escolher no escuro |
| **D-CAT-05E-4** | A regra de quais termos entram mora no `ContextSanitizer`, não no assistente | É decisão de fronteira, e é lá que o resto da minimização vive. O assistente ordena e desduplica |
| **D-CAT-05E-5** | `ListingGap` é enum, não array de tradução | O conjunto é fechado; `match` falha quando alguém acrescentar a sexta lacuna, um array devolveria `null` em silêncio |
| **D-CAT-05E-6** | Lacuna que a sugestão preenche **não** vira pedido | Pedir o que se está oferecendo é ruído, e ruído desacredita os pedidos que importam |
| **D-CAT-05E-7** | `lacunas()` permanece intocado; muda só quem o consome | Ele é da CAT-05C e responde corretamente "o que o item não tem". A subtração é do assistente |

---

## 10. Situação

```text
CAT-05E — IMPLEMENTAÇÃO CONCLUÍDA · AGUARDANDO REVISÃO DO DIFF
```

Suíte verde em 1116 testes sobre a versão final. Sem commit, sem push.

**Próxima:** CAT-05F — resiliência e fronteiras. Ela herda **C-2** como uma das
candidatas de destino.
