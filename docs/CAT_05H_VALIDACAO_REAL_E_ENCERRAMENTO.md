# CAT-05H — Validação real sobre os 75 itens e encerramento da CAT-05

> **Subfase de validação e documentação.** Nenhum arquivo de `app/` foi criado
> ou alterado. Nenhum teste novo. Nenhuma migration. Nenhum comando artisan
> novo — consistente com a CAT-05A, que registrou esta subfase como
> *"Código? Não"*.
>
> O que ela entrega é **evidência sobre dado real** e o fechamento documental
> da CAT-05 inteira (A→H).

Decisões que a governam:
[`CAT_05B_DECISOES_DE_PRODUTO_E_CONTRATOS.md`](CAT_05B_DECISOES_DE_PRODUTO_E_CONTRATOS.md).
Subfases validadas:
[`CAT_05C`](CAT_05C_LISTING_CONTEXT_E_SANITIZER.md) ·
[`CAT_05D`](CAT_05D_LISTING_ASSISTANT.md) ·
[`CAT_05E`](CAT_05E_ANTIALUCINACAO_E_MISSING_INFORMATION.md) ·
[`CAT_05F`](CAT_05F_RESILIENCIA_E_FRONTEIRAS.md) ·
[`CAT_05G`](CAT_05G_CUSTO_DE_CONSULTA_E_FRONTEIRA_DE_PROMPT.md).

---

## 1. Baseline

| Item | Valor |
|---|---|
| Branch | `main` |
| HEAD no início | `95f8853` (`docs: promove CAT-05G a concluída`) |
| Working tree no início | Limpo |
| Suíte no início | 1139 passed · 4028 assertions · 0 failures |
| Ambiente da validação | `fel_mysql` — MySQL 8.4 de desenvolvimento |
| Data | 2026-09-02 |

---

## 2. P-1 — a decisão, e o que mudou desde a CAT-05D

### 2.1 Os três pilares do adiamento continuam de pé

A CAT-05D adiou o backfill por três razões, e todas foram reconferidas aqui:

1. **G-1 continua aberto** — nenhuma superfície de curadoria existe, então uma
   associação automática errada não teria tela para ser desfeita;
2. **`PESO_CONCEITO_DERIVADO = 4`** contra 6 dos dois lados humanos —
   `SimilarityScorer` intocado desde a CAT-04;
3. **B-4 confirmada e medida** — 42/75 itens mencionam *"expositor"*, 34/75
   mencionam *"demonstração"*, e as 75 `short_description` seguem vazias.

**Nem a CAT-05F nem a CAT-05G alteram qualquer um dos três.** Resiliência e
custo de consulta são ortogonais à irreversibilidade.

### 2.2 O que mudou, e que a CAT-05D não tinha como pesar

**Primeiro: não havia mais para onde adiar.** Na CAT-05D, adiar tinha destino
nomeado — esta subfase. Adiar de novo não seria adiar: seria encerrar a CAT-05
sem validação sobre dado real e converter P-1 em dívida sem fase.

**Segundo, e é o que destravou a decisão: *"irreversível na prática"* era
verdade para produção e não era verdade para o banco de desenvolvimento.** A
CAT-05D escreveu a frase sem separar os dois ambientes — corretamente, porque a
pergunta na época era *"rodar ou não"*, não *"rodar onde"*. Medido nesta
subfase, antes de qualquer gravação:

- o pivot tinha **0 linhas**, então o estado anterior era conhecido com exatidão
  — não "restaurar backup", e sim "voltar a vazio";
- havia **0 associações humanas**, então não existia nada a preservar num
  desfazer;
- `source` distingue `derived` de `human_curated`, e com zero humanas o desfazer
  é um `DELETE ... WHERE source='derived'` seletivo e completo;
- nenhuma outra tabela tem FK apontando para `catalog_product_knowledge`, então
  a reversão não deixa órfão.

O argumento da irreversibilidade **permanece inteiro para produção**, onde
haveria associação humana misturada e nenhuma forma de distinguir depois o que
uma pessoa fez.

### 2.3 D-CAT-05H-1 — rodar, validar e reverter

**Decisão humana desta subfase:** o backfill foi executado no MySQL de
desenvolvimento, a validação rodou sobre ele, e o estado foi **revertido** ao
final. O backfill do catálogo **de produção** permanece decisão humana separada
e **explicitamente pendente** — P-1 não foi fechada.

---

## 3. O ciclo do backfill — os quatro momentos

Registrado lado a lado para que qualquer pessoa que leia depois veja que o ciclo
fechou limpo.

| # | Momento | Linhas | `derived` | `human_curated` |
|---|---|---|---|---|
| 1 | Antes do backfill | **0** | 0 | 0 |
| 2 | Depois do backfill | **85** | 85 | 0 |
| 3 | Durante a validação (leitura pura) | **85** | 85 | 0 |
| 4 | Depois da reversão | **0** | 0 | 0 |

`LINHAS_APAGADAS = 85` — exatamente as 85 gravadas. Depois da reversão,
`FindSimilarProducts` voltou a devolver **0** semelhantes para o item #96, o que
confirma que o estado *observável* foi restaurado, e não só a contagem.

### 3.1 O efeito foi documentado **antes** de gravar

| | |
|---|---|
| Linhas previstas | **85** |
| Itens | **45** |
| Conceitos distintos | **20 de 28** |
| Conceitos por item | mín 1 · máx 5 · média 1,89 |
| Distribuição | 15 itens com 1 · 22 com 2 · 7 com 3 · 1 com 5 |

`15 + 44 + 21 + 5 = 85`. O comando gravou **85**, `Já existentes: 0`.

Conceitos e em quantos itens cada um entrou:

| Conceito | Itens | Conceito | Itens | Conceito | Itens |
|---|---|---|---|---|---|
| Artesanato | 13 | Cerâmica | 5 | Bolsa | 1 |
| Feito à mão | 11 | Ervas medicinais | 5 | Almofada | 1 |
| Kit | 10 | Natural | 3 | Vaso | 1 |
| Bem viver | 9 | Costura | 3 | Barro | 1 |
| Decoração | 7 | Algodão | 2 | Tigela | 1 |
| Presente | 7 | Bordado | 2 | Xilogravura · Sementes · Colar | 1 cada |

O dry-run desta subfase reproduziu **exatamente** o número da CAT-05D e da
CAT-04: 45/75, mesma lista, mesma ordem.

---

## 4. A validação — 75 itens, assistente completo

Script descartável, executado em `/tmp` dentro do container, **não versionado**
e removido ao final. Só leitura.

### 4.1 Contagem

| | |
|---|---|
| Itens processados | **75** |
| Com alguma proposta | **45** (60%) |
| Vazios | **30** (40%) |
| Com resumo proposto | **45** |
| **Com descrição proposta** | **0** |
| Com palavras-chave | 45 |
| Com itens semelhantes | **44** |

Antes do backfill, `similarItems` era vazio para **todos** os 75 — a
consequência que a CAT-05D declarou como assumida.

### 4.2 Resumos gerados

Média **81,8** caracteres, mín 36, máx 105 — folgadíssimo no `varchar(500)`. O
corte por conceito inteiro nunca precisou agir.

```
#94  Bolsa Tecida Artesanal. Algodão, Bolsa, Feito à mão, Crochê, Tricô.
#97  Tigela de Barro Nordestina. Cerâmica, Barro, Tigela, Artesanato, Vaso.
#98  Mel Puro do Cerrado 500g. Natural, Ervas medicinais.
#99  Kit Própolis + Pólen. Kit, Presente.
```

São honestos e pobres — nome seguido dos conceitos que o próprio texto
mencionava, exatamente o que a CAT-05D prometeu e nada além. **Nenhum inventou
material, origem, medida ou adjetivo.** A antialucinação da CAT-05E se sustentou
em 75 de 75.

### 4.3 `missing_information`

| Por item | Itens | | Frequência | Pedido |
|---|---|---|---|---|
| 1 pedido | 35 | | **75×** | Informe o material, as medidas e a origem… |
| 2 pedidos | 10 | | 30× | Escreva um resumo curto do item… |
| 3 pedidos | 30 | | 30× | Dê mais detalhe ao texto do item… |
| | | | 10× | Escolha a categoria do item… |

O pedido de atributos aparece em **75/75**, porque nada popula `knownAttributes`
ainda (dívida C-1, CAT-09). Os 30 "escreva um resumo" são os itens vazios: nos
45 com proposta a lacuna foi **suprimida**, como a CAT-05E decidiu. A supressão
funcionou em 45 casos reais, e não só em fixture.

### 4.4 Semelhantes — o que o backfill destravou, e o que revelou

44 dos 75 ganharam semelhantes, e **35 saturaram o teto de 5**.

A qualidade merece registro franco:

```
#99 Kit Própolis+Pólen  →  Kit Temperos Orgânicos | Kit Plantas Medicinais | Kit Bem-Estar
#98 Mel Puro do Cerrado →  Vaso de Cerâmica Esmaltado | Customização Completa de Peça
#96 Vaso de Cerâmica    →  Produto Artesanal de Demonstração - Cerâmica Viva | …
```

- **`#99` é bom**: quatro kits se encontram por um conceito específico.
- **`#98` é ruim**: mel e vaso de cerâmica só compartilham *"Natural"*, conceito
  genérico demais para significar semelhança.
- **`#96` é a B-4 se materializando na saída do algoritmo**: o sistema oferece
  *"Produto Artesanal de Demonstração"* como referência a outro lojista.

---

## 5. Os quatro achados que só o corpus real revelou

### 5.1 D-1 — o caminho da descrição não tem cobertura real

**Nenhuma descrição foi proposta em 75 itens.** A causa é legítima e é decisão
da CAT-05D — campo já preenchido não recebe proposta — e `description` está
preenchida em **75/75**.

A consequência é que **o caminho mais valioso do assistente nunca foi
exercitado contra dado real**. A CAT-05D descreve `descricaoSugerida()` como *"o
único ponto da fase em que texto escrito por uma pessoa da curadoria chega ao
lojista"*, e contra os 75 itens ele não roda uma vez.

Não é defeito: é o corpus que não tem o cenário. É o **inverso exato da B-4** —
`short_description` vazia em 75/75 fez o resumo ser sempre proposto;
`description` cheia em 75/75 fez a descrição nunca ser. A cobertura por fixture
existe e é boa (`ListingAssistantTest`), mas cobertura por fixture é o que esta
subfase existia para complementar.

**Destino proposto: CAT-09.** É lá que o cenário aparece naturalmente — um
lojista digitando item novo não tem descrição ainda, e é exatamente o caso que
`paraItemNovo()` atende. Validar antes disso exigiria fabricar o corpus, que é o
que a subfase se recusou a fazer.

### 5.2 D-2 — `palavrasChave()` não pondera por score

As palavras-chave têm média **13,4** por item e chegam a **31**.

A causa: `GenerateListingSuggestion::palavrasChave()` trata todos os conceitos do
contexto por igual, e o contexto inclui os alcançados **por relação**. Medido no
item #94:

```
Algodão      score=18  direta=SIM
Bolsa        score=10  direta=SIM
Feito à mão  score=8   direta=SIM
Crochê       score=6   direta=nao  (relação)
Tricô        score=3   direta=nao  (relação)
Moda         score=3   direta=nao  (relação)
Artesanato   score=3   direta=nao  (relação)
```

O item recebe as palavras-chave *"Crochê"* e *"Tricô"* sem que o texto as
mencione. **Não é alucinação** — está tudo no contexto, e a CAT-05E decidiu que
conceitos do contexto entram. É ruído mensurável, e um conceito de peso 3 produz
palavra-chave indistinguível de um de peso 10.

Nota de precisão: no pivot só entraram os **3 diretos** (`no_pivot=3`), como
`AssociateProductKnowledge` promete. A divergência é entre o que é **persistido**
e o que vai para o **contexto**, e as duas regras estão certas isoladamente.

**Destino proposto: CAT-07.** Ponderar exige um limiar, e escolher limiar sem
dado seria inventar precisão — o mesmo argumento que manteve `confidence` nula
na CAT-05D. A CAT-07 é a fase que registra o desfecho de cada sugestão, e é ela
que vai saber quais palavras-chave o lojista de fato aceita. **Alternativa:**
CAT-11, se a decisão for tratar como hardening em vez de esperar dado de
feedback.

### 5.3 D-3 — casamento por frase exata não alcança termo intercalado

O item **#104 "Ajuste e Reforma de Roupa"** veio **vazio**, e o conceito
`Costura` tem o termo comercial `"ajuste de roupa"` — cadastrado exatamente para
esse caso, e citado na CAT-05E como *a* justificativa da P-4.

```
haystack: "ajuste e reforma de roupa bainhas ajuste de caimento troca de ziper…"
contemFrase("ajuste de roupa") = false
contemFrase("costurar")        = false
contemFrase("costura")         = false
```

`contemFrase()` é substring literal cercada de espaços (CAT-04, §3B.2), e
*"ajuste **e reforma** de roupa"* tem duas palavras no meio. **A P-4 resolveu o
lado das palavras-chave; o lado do casamento continua limitado pela frase
exata** — e isso só apareceu contra dado real.

**Destino proposto: CAT-11.** Mexer no casamento reabre a CAT-04, que é
fundação, e exige decisão própria: casar por token ou por proximidade aumenta o
alcance **e** o falso positivo, e a regra da trilha é explícita — *"falso
negativo custa uma sugestão a menos; falso positivo entra na base e o sistema
passa a confirmar o próprio engano"*. Não é uma correção, é uma troca, e ela não
cabe dentro da CAT-05.

### 5.4 D-4 — oito conceitos sem evidência direta em nenhum item

`Casa` · **`Crochê`** · `Economia solidária` · `Lã` · `Moda` · `Tecelagem` ·
`Terapia integrativa` · `Tricô`

Dos 28 conceitos curados, **8 não casam diretamente com nenhum dos 75 itens** —
alguns aparecem no contexto por relação, mas nenhum por evidência própria.

O caso de **`Crochê`** merece nota: é o conceito que **toda a trilha usa como
exemplo canônico** — está em praticamente todos os testes de A a G — e nenhum
dos 75 itens reais o menciona. É um sinal de que o vocabulário dos testes e o do
catálogo real se cruzam menos do que a documentação sugere.

**Destino: CAT-08.** É curadoria, não código: ou os conceitos são pertinentes e
falta catálogo que os use, ou não são pertinentes e devem ser revistos. Nenhuma
das duas leituras se decide sem a superfície que o G-1 pede.

---

## 6. B-4 — a dívida medida, não resolvida

Confirmada e quantificada:

| Evidência | Valor |
|---|---|
| `short_description` preenchida | **0 de 75** |
| Itens que mencionam *"expositor"* | **42 de 75** |
| Itens que mencionam *"demonstração"* | **34 de 75** |
| Itens com *"Produto Artesanal"* no nome | 6 de 75 |
| Nomes distintos | **75 de 75** |
| Descrições distintas | **75 de 75** |

**Impacto concreto na qualidade**, com exemplos:

1. O sistema chegou a sugerir *"Produto Artesanal de Demonstração - Cerâmica
   Viva"* como item semelhante (§4.4) — a dívida saindo pela ponta do algoritmo.
2. `short_description` vazia em 75/75 é o que fez o resumo ser proposto em todos
   os 45 — número que **não se repetiria** num catálogo onde lojistas escrevem
   resumo.
3. Inversamente, `description` cheia em 75/75 zerou o caminho da descrição (D-1).

**O que esta validação prova, e o que não prova.** Ela prova o **mecanismo**
sobre texto realista: a composição não inventa, a supressão de lacuna funciona, o
resumo cabe no campo, a similaridade encontra vizinhos reais. Ela **não** prova
utilidade para um lojista de verdade, porque o corpus é sintético. Nenhuma
decisão desta subfase mudaria isso — só corpus real mudaria, e ele não existe.

Um detalhe que ameniza: 75 nomes e 75 descrições distintos. O corpus é
sintético, **não degenerado** — o seeder variou o texto em vez de repetir.

---

## 7. Dívidas ao fim da CAT-05 inteira

| # | Item | Situação | Endereçada a |
|---|---|---|---|
| **C-1** | `knownAttributes` por lista de proibição | Aberta | **CAT-09** |
| **C-2** | Texto livre não é redigido | Aberta | **CAT-06** (gate) |
| **F-1** | Sem sinal de modo degradado | Aberta | **CAT-06** (gate) |
| **S-1** | Teste de prompt injection | Aberta | **CAT-06** (gate) |
| **S-2** | A sugestão é conteúdo de usuário | Aberta | **CAT-09** |
| **P-1** | Backfill do catálogo de produção | **Aberta** — rodado e revertido em dev | Decisão humana, após **CAT-08** (G-1) |
| **B-4** | Corpus de seeder | Aberta — **medida** nesta subfase | Sem fase; depende de catálogo real |
| **G-1** | Sem superfície de curadoria | Aberta | **CAT-08** |
| **E-1** | `KnowledgeTermType::Keyword` sem uso | Aberta | Sem fase; decidir quando algum registro o usar |
| **D-1** | Caminho da descrição sem cobertura real | **Nova** | **CAT-09** (proposta) |
| **D-2** | `palavrasChave()` não pondera por score | **Nova** | **CAT-07** (proposta; alternativa CAT-11) |
| **D-3** | Casamento por frase exata | **Nova** | **CAT-11** (proposta) |
| **D-4** | 8 conceitos sem evidência direta | **Nova** | **CAT-08** |
| **P-4** | `keywords` por termo | ✅ Fechada na CAT-05E | — |
| **B-1 · B-2** | Decisões de produto | ✅ Fechadas na CAT-05B | — |
| **M-17** | Similaridade lia item não vigente | ✅ Fechada na CAT-05B | — |

**Nenhuma dívida foi fechada nesta subfase.** P-1 continua aberta: o que ela
pedia era decisão sobre o catálogo de produção, e o que aconteceu aqui foi um
ciclo controlado em desenvolvimento, revertido.

---

## 8. O que a CAT-05 entregou, A→H

| Subfase | Entrega | Código |
|---|---|---|
| **05A** | Auditoria de reconciliação; 6 blockers; subdivisão em A→H | Não |
| **05B** | D-CAT-05B-1 a 4; fechamento de M-17 | Mínimo |
| **05C** | `ListingContext` + `ContextSanitizer`; minimização estrutural | Sim |
| **05D** | `GenerateListingSuggestion`; `ListingSuggestion`; sem provider | Sim |
| **05E** | `ListingGap`; keywords por termo; pedidos legíveis | Sim |
| **05F** | Captura de falha do motor; guarda de log; fronteira do cadastro | Sim |
| **05G** | Teto de 6 consultas; fronteira de prompt | Só docblock |
| **05H** | Validação real sobre 75 itens; encerramento | **Não** |

**As três regras invioláveis, ao fim da fase:**

1. *Não inventa fatos objetivos* — provado por fixture (CAT-05E) e **medido em
   75 itens reais**: nenhum resumo trouxe material, origem ou medida que o texto
   não tivesse.
2. *Nada é salvo sem aprovação humana* — `GenerateListingSuggestion` não escreve
   uma linha (CAT-05D), com teste que confere produto, pivot, conceitos e
   ofertas antes e depois.
3. *Falha da inteligência não bloqueia cadastro* — teste explícito na CAT-05F,
   com a fronteira estrutural que impede o cadastro de sequer conhecer o módulo.

**A suíte foi de 1104 para 1139** ao longo de D→H, sem nenhuma regressão.

---

## 9. Suíte

| | |
|---|---|
| Antes | 1139 passed · 4028 assertions · 0 failures |
| Depois | **1139 passed · 4028 assertions · 0 failures** |
| Delta | **nenhum** — a subfase não tem código nem teste novo |

Suíte executada do zero sobre a versão final, em 337,01s, como o protocolo exige
— mesmo sem alteração em `app/` ou `tests/`, porque a subfase mexeu no **banco de
desenvolvimento** e a suíte é parte da verificação de que nada ficou para trás.
`git diff --check` limpo. Pint não rodou: nenhum arquivo PHP foi tocado.

---

## 10. O que esta subfase deliberadamente não fez

**Não corrigiu nenhum dos quatro achados.** D-1, D-2, D-3 e D-4 foram medidos e
endereçados, não resolvidos. Corrigir D-2 ou D-3 reabriria decisões congeladas da
CAT-05E e da CAT-04 dentro de uma subfase de validação — que é exatamente o tipo
de correção oportunista que o protocolo proíbe.

**Não fechou P-1.** O backfill de produção continua sendo decisão humana.

**Não deixou dado no banco.** O ciclo fechou em 0, conferido.

Nenhum comando artisan novo, nenhum arquivo em `app/`, nenhum teste. O script de
validação foi descartável e não versionado, por decisão registrada.

---

## 11. Decision log

| # | Decisão | Motivo |
|---|---|---|
| **D-CAT-05H-1** | Rodar o backfill em desenvolvimento, validar e **reverter** | Era a única forma de validar contra a distribuição real; e a irreversibilidade que sustentava o adiamento vale para produção, não para um pivot vazio sem associação humana |
| **D-CAT-05H-2** | P-1 **não** é fechada; o backfill de produção segue pendente | O gate G-1 continua aberto: sem tela de curadoria, associação automática errada não tem como ser desfeita por uma pessoa |
| **D-CAT-05H-3** | Os quatro achados viram dívida endereçada, não correção | Subfase de validação não altera comportamento; D-2 e D-3 reabririam decisões congeladas da CAT-05E e da CAT-04 |
| **D-CAT-05H-4** | Script de validação descartável, não versionado | A CAT-05A registra esta subfase como *"Código? Não"*; um comando artisan novo mudaria o escopo |

---

## 12. Situação

**CAT-05 concluída, A→H.** A próxima fase da trilha é a **CAT-06 — IA externa
(opcional)**, bloqueada por três gates: **C-2**, **F-1** e **S-1**.

Na prática, porém, a fase mais útil a seguir é a **CAT-08 — interface
administrativa**: ela fecha **G-1**, que por sua vez é o que destrava **P-1**
(backfill de produção) e **D-4** (os 8 conceitos sem uso). Qual das duas vem
antes é decisão de produto, e não desta subfase.
