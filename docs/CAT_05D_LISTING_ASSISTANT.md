# CAT-05D — `ListingAssistant` interno

> **Subfase de implementação.** Entrega o assistente de conteúdo com um caminho
> só, o interno. Nenhuma migration, nenhum provider externo, nenhuma escrita.
> Quatro arquivos novos; nenhum arquivo existente alterado.

Contexto e minimização que esta subfase consome:
[`CAT_05C_LISTING_CONTEXT_E_SANITIZER.md`](CAT_05C_LISTING_CONTEXT_E_SANITIZER.md).
Decisões que a governam:
[`CAT_05B_DECISOES_DE_PRODUTO_E_CONTRATOS.md`](CAT_05B_DECISOES_DE_PRODUTO_E_CONTRATOS.md).
Arquitetura de referência: `CATALOG_INTELLIGENCE.md` §3.2 e §3.4.

---

## 1. Baseline

| Item | Valor |
|---|---|
| Branch | `main` |
| HEAD no início | `cebb0bdeaef2a8f666d3d41f195007e4361604c6` |
| Working tree no início | Limpo |
| `origin/main` | `d70e224` — um commit atrás (`cebb0bd` pendente de push, por decisão) |
| Suíte no início | 1079 passed · 3258 assertions · 0 failures |
| Data | 2026-09-01 |

**Resultado:** **1104 passed · 3318 assertions · 0 failures** em 953,74s.
`+25` testes, `+60` asserções — exatamente o arquivo novo, nenhuma regressão.

Suíte executada **do zero sobre a versão final**, com os md5 dos quatro arquivos
registrados antes e conferidos depois.

---

## 2. P-1 — o dry-run atualizado, e a decisão de adiar

### 2.1 O número ainda vale

`catalog-intelligence:associate-products --dry-run`, executado nesta subfase
contra o MySQL de desenvolvimento:

```text
Itens analisados:            75
Com algum candidato:         45
Com evidência direta:        45
Sem nenhum candidato:        30

Conceitos mais encontrados (evidência direta):
  Artesanato   13    Decoração        7     Costura     3
  Feito à mão  11    Presente         7     Algodão     2
  Kit          10    Cerâmica         5     Bordado     2
  Bem viver     9    Ervas medicinais 5     Bolsa/Almofada/Vaso  1
```

**45/75 é exatamente o número que a CAT-04 registrou**, e a lista de conceitos
mais encontrados é a mesma, na mesma ordem. Nem a CAT-DOM-02 nem a correção de
vigência da CAT-05B (D-CAT-05B-2) mudaram o resultado.

Conforme instruído, a causa **não foi investigada** — o registro é só de que o
número se manteve. A explicação plausível, sem verificação, é que o matcher lê
identidade e a CAT-DOM-02 só mexeu no comercial; mas isso é hipótese e está
marcado como tal.

`catalog_product_knowledge` foi conferido **antes e depois** do comando:
**0 linhas** nos dois momentos. O `--dry-run` não gravou nada.

### 2.2 P-1 permanece pendente, adiado para a CAT-05H

**Decisão humana desta subfase:** a gravação do backfill fica para a CAT-05H.
Continua valendo o que a CAT-04 escreveu na §3B.10 — *"fica como decisão humana
informada"* —, e três consequências sustentam o adiamento:

1. Rodar o backfill grava `KnowledgeSource::Derived` em 45 produtos, e
   `AssociateProductKnowledge` nunca sobrescreve nem rebaixa associação humana —
   mas também não corrige uma associação automática errada. Quem a desfaz é uma
   pessoa, e **não existe tela para isso** (gate G-1, aberto).
2. Conceito compartilhado com algum lado automático vale 4 contra 6 quando os
   dois lados são humanos (`SimilarityScorer`). Quatro não é zero: 45 itens
   associados por inferência passariam a se sugerir mutuamente.
3. O corpus é de seeder — "para" (52), "expositor" (46), "demonstração" (28)
   lideram a contagem de palavras. Associar conceito a um item de demonstração
   grava conhecimento sobre algo que não existe comercialmente (dívida B-4).

**Consequência assumida:** enquanto o pivot estiver vazio, o assistente devolve
`similarItems` vazio para **todo** item real do banco de desenvolvimento. Isso
não afeta a correção do código — os 25 testes desta subfase provam o
comportamento por fixture — mas significa que **a validação sobre dado real é a
CAT-05H, e não aconteceu aqui**.

---

## 3. O que foi implementado

Quatro arquivos novos. Nenhum existente foi tocado.

| Arquivo | Linhas | Papel |
|---|---|---|
| `app/CatalogIntelligence/Enums/SuggestionSource.php` | 42 | `internal` \| `external` |
| `app/CatalogIntelligence/DTOs/ListingSuggestion.php` | 116 | A sugestão estruturada da §3.4 |
| `app/CatalogIntelligence/Actions/GenerateListingSuggestion.php` | 266 | O assistente |
| `tests/Feature/CatalogIntelligence/ListingAssistantTest.php` | 409 | 25 casos |

### 3.1 O fluxo

```text
ListingContext (CAT-05C)
      ↓  paraBuscaDeConhecimento()
MatchProductKnowledge          ← conceitos APROVADOS que o texto menciona
      ↓  comConhecimento()
      ↓  comSemelhantes()      ← só quando o item já está salvo
ListingContext completo
      ↓  compor()
ListingSuggestion
```

`comContexto()` devolve **a sugestão e o contexto que a produziu**. A entrada
volta junto com a saída porque a CAT-07 vai precisar registrar as duas: uma
sugestão sem o contexto que a gerou não é auditável, e recalcular o contexto
depois daria outro resultado se o texto do item tiver mudado no meio.

### 3.2 Um caminho só, o interno

A §3.2 desenha o assistente decidindo entre conhecimento interno e provider
externo. Nesta fase **não existe a segunda opção**: a D-CAT-05B-4 situa
`CatalogAiProvider`, `Fake` e `Null` na CAT-06. `source` é sempre `Internal`, e
a decisão de fallback — o *"conhecimento suficiente?"* do fluxograma da §1 — é a
primeira coisa que a CAT-06 acrescenta **aqui dentro**, não em quem chama.

Dois testes guardam isso: um verifica que nenhuma das quatro classes de provider
existe no código; outro percorre o construtor por reflexão exigindo que nenhum
parâmetro seja `Provider` ou `Http`.

### 3.3 O que "compor" significa, sem geração de linguagem

Sem provider não há geração. O que a fase entrega é **reorganização de material
que já existe**, e a distinção importa porque é ela que mantém a regra 1 da §1
— a inteligência não inventa fatos objetivos:

- os conceitos vêm do casamento com o texto do **próprio item**, então dizer
  "crochê" é repetir o que o lojista escreveu, não deduzir sobre a peça;
- a `description` curada de um conceito é **texto humano da curadoria**, e é o
  insumo mais valioso que a base tem — é o único ponto da fase em que texto
  escrito por uma pessoa da curadoria chega ao lojista;
- conceito sem descrição curada entra só pelo nome. Não se inventa explicação.

| Campo | Como é composto |
|---|---|
| `suggested_name` | **Sempre nulo** — ver §3.4 |
| `short_description` | Nome do item + até 5 conceitos encontrados, cortado por conceito inteiro para caber no `varchar(500)` da CAT-02 |
| `description` | Abertura com nome e categoria + as descrições **curadas** dos conceitos |
| `keywords` | Nomes dos conceitos aprovados |
| `missing_information` | `ListingContext::lacunas()`, cru |
| `source` | `Internal` |
| `confidence` | **Nulo** — ver §3.5 |

### 3.4 Por que o nome nunca é proposto

Renomear é o único campo que exige de fato **escrever algo novo**. Resumo e
descrição podem ser compostos a partir de conceitos que o próprio texto do
lojista trouxe; um nome melhor não está contido em lugar nenhum do que já
existe.

Concatenar conceitos num título — "Tapete — crochê, feito à mão, decoração" —
produziria uma etiqueta, não um nome, e o lojista aplicaria uma piora com ar de
sugestão. Devolver nulo é a resposta honesta de quem não tem base para preferir
um nome a outro.

O campo continua no DTO porque a §3.4 o nomeia e porque é exatamente o que a
CAT-06 terá condições de preencher.

### 3.5 Por que `confidence` fica nula

A CAT-03 tomou a mesma decisão para a coluna `confidence` de `KnowledgeEntry`:
*"atribuir 0,7 a uma origem hoje seria inventar precisão que ninguém mediu"*. O
argumento não mudou.

Um número aqui seria derivado do score da similaridade — que a própria CAT-04
declara servir para **ordenar**, não para ser lido como porcentagem. Converter
uma ordem em decimal e mostrá-lo a um lojista é falsa ciência com casa decimal.

### 3.6 Campo já preenchido não recebe proposta

Se o lojista escreveu a descrição, o assistente não oferece outra. Sem geração
real, substituir texto humano por concatenação de conceitos seria piorar com ar
de melhoria.

A consequência é deliberada: **a sugestão de um item bem preenchido pode vir
inteira nula**, e isso é resposta correta, não falha. `missing_information`
continua sendo devolvido, e `keywords` também — elas seguem úteis mesmo quando
não há texto a propor.

### 3.7 Semelhantes exigem item salvo

`FindSimilarProducts` compara pelo conhecimento **associado**, que só existe
para item que está no banco. Um cadastro em andamento não tem associação — nem
deveria, porque nada foi salvo.

Por isso o `Product` é parâmetro **opcional** do assistente: quando vem, a
similaridade roda; quando não vem, `similarItems` fica vazio e o assistente
segue funcionando com o conhecimento, que é o caso do lojista digitando um item
novo.

O model entra no assistente e **não** no `ListingContext` — a D-CAT-05B-3
mantém o contexto livre de Eloquent, e é o assistente que faz a ponte.

---

## 4. Cobertura de teste

25 casos, 60 asserções, todos por fixture. Os que carregam a fase:

| O que prova | Teste |
|---|---|
| **Gerar não escreve nada** | `test_gerar_nao_escreve_nada_em_lugar_nenhum` — compara produto, pivot, conceitos e ofertas antes e depois |
| **Gerar não afirma conhecimento** | `test_gerar_nao_associa_conhecimento_ao_produto` — o assistente tem os candidatos em mãos e não chama `AssociateProductKnowledge` |
| Nenhum provider existe | `test_nenhuma_interface_de_provider_externo_existe`, `test_o_assistente_nao_depende_de_nenhum_provider` |
| **Conceito que o texto não menciona não entra** | `test_conceito_que_o_texto_nao_menciona_nao_entra_na_sugestao` — a base tem "Couro" com descrição curada, e ele não aparece |
| Conceito em rascunho não alimenta nada | `test_conceito_nao_aprovado_nao_alimenta_a_sugestao` |
| Atributo não informado não aparece | `test_atributo_nao_informado_nao_aparece_no_texto` |
| Campo preenchido não recebe proposta | `test_campo_ja_preenchido_nao_recebe_proposta` |
| Nome nunca é proposto | `test_o_nome_nunca_e_proposto_no_caminho_interno` |
| Base vazia devolve sugestão vazia sem quebrar | `test_base_sem_conceito_devolve_sugestao_vazia_e_nao_quebra` |
| Funciona com item não salvo | `test_funciona_para_item_que_ainda_nao_existe` — assertando que sugerir não cria item |
| Semelhantes só com item salvo | `test_semelhantes_entram_no_contexto_quando_o_item_esta_salvo`, `test_sem_produto_salvo_nao_ha_semelhantes_e_isso_nao_quebra` |
| `confidence` não é inventada | `test_confidence_nao_e_inventada` |
| Forma da §3.4 preservada | `test_a_sugestao_tem_a_forma_da_secao_3_4` |

---

## 5. Dívidas e pendências ao fim da subfase

| # | Item | Situação |
|---|---|---|
| **P-1** | Backfill de `catalog_product_knowledge` — 0 associações | **Adiado para a CAT-05H**, por decisão humana desta subfase. Dry-run atualizado em §2 |
| **P-4** | `keywords` sai de conceito, de termo, ou dos dois | **Aberta** — CAT-05E. Esta subfase usa **só nomes de conceito**, sem termos, para não responder por conta própria |
| **C-1** | `knownAttributes` protegido por lista de proibição | **Aberta** — CAT-09 deve mapear campo a campo |
| **C-2** | Texto livre não é redigido | **Aberta** — destino entre CAT-05F e CAT-10, sem decisão |
| **B-4** | Corpus é de seeder | **Aberta** — atinge a validação da CAT-05H, não o código |
| **G-1** | Sem superfície de curadoria | **Aberta** — CAT-08 |
| **D-1 (05D)** | `missing_information` é nome de campo cru, não linguagem de lojista | **Aberta** — CAT-05E, que é a fase da antialucinação |

---

## 6. O que esta subfase deliberadamente não fez

Nenhuma migration, tabela, coluna ou tela. Nenhuma alteração em `Product`,
`ProductOffer`, `SaveProductWithOffer`, `ProductPolicy`, `ProdutoForm`,
`ProdutoController`, AVA, checkout, estoque ou Customer Intelligence.

`CatalogAiProvider`, `FakeCatalogAiProvider`, `NullCatalogAiProvider` e
`EmbeddingProvider` **não foram criados** (D-CAT-05B-4) — e há teste que falha se
alguém os criar antes da CAT-06.

O backfill **não foi executado**; só `--dry-run`. Nenhuma associação gravada.
O `CatalogIntelligenceServiceProvider` não mudou. Pint rodou apenas nos quatro
arquivos desta subfase.

Tradução de `missing_information` para a linguagem do lojista, termos em
`keywords` e qualquer decisão de antialucinação além da estrutural ficam para a
**CAT-05E**.

---

## 7. Decision log

| # | Decisão | Motivo |
|---|---|---|
| **D-CAT-05D-1** | O backfill fica para a **CAT-05H**; esta subfase roda só `--dry-run` | A gravação é irreversível na prática, sem tela de curadoria para desfazer (G-1), e realimenta a similaridade com peso 4. A CAT-04 já a registrava como decisão humana |
| **D-CAT-05D-2** | `suggested_name` é **sempre nulo** no caminho interno | Renomear exige escrever algo novo, e não há base para preferir um nome a outro. Concatenar conceitos produziria etiqueta, não nome |
| **D-CAT-05D-3** | `confidence` fica **nula** | Mesmo argumento da CAT-03: o score da CAT-04 serve para ordenar, não para virar porcentagem |
| **D-CAT-05D-4** | Campo já preenchido **não** recebe proposta | Sem geração real, substituir texto humano por concatenação é piorar com ar de melhoria |
| **D-CAT-05D-5** | `Product` é parâmetro **opcional do assistente**, nunca do contexto | A similaridade exige item salvo; a D-CAT-05B-3 mantém o contexto livre de Eloquent. A ponte é do assistente |
| **D-CAT-05D-6** | `SuggestionSource` nasce com os dois casos, embora `External` seja inalcançável | Diferente de `style`/`audience` na CAT-03: aqui o destinatário existe e está no roadmap, e a alternativa seria a CAT-06 inventar uma string solta |
| **D-CAT-05D-7** | `keywords` usa **só nomes de conceito**, sem termos | P-4 tem dono (CAT-05E). Acrescentar termos depois é barato; removê-los de um contrato publicado não é |
| **D-CAT-05D-8** | O assistente **não** chama `AssociateProductKnowledge`, apesar de ter os candidatos | Sugerir texto e afirmar conhecimento são atos diferentes. O segundo entra na base e volta reforçando outros itens |

---

## 8. Situação

```text
CAT-05D — IMPLEMENTAÇÃO CONCLUÍDA · AGUARDANDO REVISÃO DO DIFF
```

Suíte verde em 1104 testes sobre a versão final. Sem commit, sem push. A subfase
só passa a **concluída** depois da revisão humana do diff completo.

**Próxima:** CAT-05E — antialucinação e `missing_information`. Ela herda P-4 e a
tradução de `missing_information` para a linguagem do lojista.
