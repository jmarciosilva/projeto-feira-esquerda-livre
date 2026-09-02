# CAT-05G — Testes, custo de consulta e segurança

> **Subfase de verificação.** Não muda comportamento: nenhuma Action, Query,
> DTO ou Support teve lógica alterada. O que ela entrega é **medida** — o custo
> de consulta do assistente inteiro, que até aqui só existia pelas metades — e
> **duas fronteiras** que passam a acusar quando forem cruzadas.
>
> **Ela acrescenta um gate à CAT-06 e uma obrigação à CAT-09** — as duas são
> fases não-correntes, e o registro está no §6.

Decisões que a governam:
[`CAT_05B_DECISOES_DE_PRODUTO_E_CONTRATOS.md`](CAT_05B_DECISOES_DE_PRODUTO_E_CONTRATOS.md).
Subfases que ela verifica:
[`CAT_05C_LISTING_CONTEXT_E_SANITIZER.md`](CAT_05C_LISTING_CONTEXT_E_SANITIZER.md) ·
[`CAT_05D_LISTING_ASSISTANT.md`](CAT_05D_LISTING_ASSISTANT.md) ·
[`CAT_05E_ANTIALUCINACAO_E_MISSING_INFORMATION.md`](CAT_05E_ANTIALUCINACAO_E_MISSING_INFORMATION.md) ·
[`CAT_05F_RESILIENCIA_E_FRONTEIRAS.md`](CAT_05F_RESILIENCIA_E_FRONTEIRAS.md).

---

## 1. Baseline

| Item | Valor |
|---|---|
| Branch | `main` |
| HEAD no início | `4f1db6d` (`docs: promove CAT-05F a concluída`) |
| Working tree no início | Limpo |
| Suíte no início | 1126 passed · 3372 assertions · 0 failures |
| Data | 2026-09-02 |

**Resultado:** **1139 passed · 4028 assertions · 0 failures** em 241,34s.
`+13` testes, `+656` asserções, nenhuma regressão. Suíte executada do zero sobre
a versão final, com md5 conferidos antes e depois.

---

## 2. As três perguntas da subfase

1. **Quanto custa pedir uma sugestão?** A CAT-04 travou `MatchProductKnowledge`
   em ≤3 consultas e `FindSimilarProducts` em ≤3, **em separado**. Ninguém tinha
   medido o assistente inteiro, que é o que a CAT-09 vai chamar.
2. **Segurança: o que falta, e o que já está coberto por outra subfase?**
   Prompt injection não tem teste em lugar nenhum. A pergunta era se ele cabe
   aqui.
3. **Alguma dívida aberta foi resolvida de raspão pela CAT-05F?** Checagem, não
   fechamento.

---

## 3. O custo, medido

### 3.1 O número

Todas as contagens abaixo vêm de `DB::getQueryLog()`, com as consultas impressas
uma a uma e conferidas — não de estimativa por leitura de código.

| Cenário | Consultas |
|---|---|
| Item novo, nenhum conceito casado | 2 |
| Item novo, conceitos casados (com ou sem expansão por relação) | 3 |
| Item salvo — casamento + similaridade, 3 conceitos e 6 vizinhos | **6** |
| **Catálogo grande** — 32 conceitos, 21 produtos, 20 semelhantes | **6** |

As seis são, em ordem: `catalog_knowledge_entries`; o eager-load de
`catalog_knowledge_terms`; `catalog_knowledge_relations`; os conceitos do item
de origem; a varredura do pivot com a vigência por subconsulta; a hidratação
dos produtos vizinhos.

### 3.2 Por que o teto é 6, e não "por volta de 6"

`GenerateListingSuggestion` chama **uma vez** cada metade do motor e não
acrescenta consulta própria: compor resumo, descrição e palavras-chave acontece
sobre o que já veio em memória. O teto do todo é portanto a soma exata dos dois
tetos da CAT-04, `3 + 3`, e é essa igualdade que o teste protege.

Um teto folgado — 8, 10 — aceitaria a primeira regressão em silêncio, que é
precisamente o defeito que um teste de custo existe para pegar. Se o número
passar a 7, a consulta nova está ou dentro de um laço, ou numa terceira leitura
que ninguém decidiu fazer.

### 3.3 A observação de custo da CAT-05E: medida, e mais tranquila do que parecia

A CAT-05E registrou que `ContextSanitizer::termosUteis()` lê
`$candidato->entry->terms`, hoje sempre carregado porque `MatchProductKnowledge`
é o único produtor de `KnowledgeCandidate` e faz `->with('terms')`.

**A medição mostrou que o eager-load é da CAT-04, não da CAT-05E.** A consulta
de `catalog_knowledge_terms` sempre esteve dentro do teto de 3 do matcher — se
não estivesse, `test_matching_query_count_does_not_grow_with_the_knowledge_base`
teria caído quando a CAT-05E passou a consumir os termos, o que não aconteceu.
Ou seja: **`termosUteis()` não introduziu consulta nenhuma**, nem uma.

O N+1 hipotético, por outro lado, **é real e agora tem número**:

| Redução de 3 conceitos a texto | Consultas |
|---|---|
| Candidatos com `->with('terms')`, como o matcher entrega | **0** |
| Candidatos sem eager-load | **3** — uma por conceito |

Os dois lados estão travados: a **causa**
(`test_o_matcher_entrega_candidatos_com_os_termos_ja_carregados`, que exige
`relationLoaded('terms')` em todo candidato emitido) e a **consequência**
(`test_candidato_sem_eager_load_custa_uma_consulta_por_conceito`). Quem criar o
segundo produtor de `KnowledgeCandidate` precisa repetir o `->with('terms')`, e
agora isso é um teste, não um parágrafo.

### 3.4 Achado: montar o contexto custa, e o custo é de quem chama

`ListingContext::deProduct()` sobe a árvore de categorias por `$atual->parent`.
Com um `Product` cru, isso custa **uma consulta por ancestral**:

| Montagem do contexto | Consultas |
|---|---|
| `Product` cru, categoria com 3 níveis | 3 |
| `->with('category')`, categoria com 2 níveis | 1 |
| `->with('category.parent')` | **0** |
| Item sem categoria | 0 |

Esse custo **não aparece** no teto de 6 porque é pago antes de a Action ser
chamada. O caminho completo `deProduct()` + assistente, num item de dois níveis
de categoria e sem eager-load, mediu **8** consultas.

**Não é defeito da CAT-05C.** O DTO recebe um model e não tem como saber o que
quem chama carregou; o guarda de ciclo já limita a subida a 10 níveis. É
instrução para quem for montar contexto dentro de um formulário — a CAT-09 —, e
está travada nos dois sentidos: o custo sem eager-load e o zero com ele.

Fica como **observação de custo**, deliberadamente **não** como dívida numerada:
nenhum caminho de produção chama `deProduct()` hoje, então o custo é potencial,
e inflar o quadro de dívidas com o que dois testes já garantem enfraqueceria o
quadro.

### 3.5 Degradar não custa mais que funcionar

`test_a_degradacao_nao_custa_mais_que_o_caminho_normal` derruba
`catalog_knowledge_relations` e confirma que a captura da CAT-05F **não virou
retentativa**: o assistente falha uma vez, registra, segue. Sem esse teste, um
`retry` acrescentado de boa-fé numa fase futura passaria despercebido — e um
retry é exatamente o que costuma aparecer perto de um `catch`.

---

## 4. Segurança — o que já estava coberto, e o que faltava

Antes de escrever qualquer coisa, o levantamento do que outras subfases já
protegem, para não duplicar:

| § | Garantia | Onde |
|---|---|---|
| §5.1 | Minimização de campo; `knownAttributes` por denylist | `ListingContextTest` |
| §5.1 | O contexto não aceita `ProductOffer` nem `Expositor`, por construção | `ListingContextTest` |
| §5.3 | PII do lojista não vai para log | `ResilienciaDoAssistenteTest` (CAT-05F) |
| §3.4 | Antialucinação — nada além do contexto entra no texto | `ListingAssistantTest` (CAT-05E) |
| SEC-02 | A similaridade não concede escrita em item alheio | `ProductSimilarityTest` |
| — | Nenhum provider externo existe | `ListingAssistantTest` (CAT-05D) |
| **§5.2** | **Prompt injection** | **nada, em lugar nenhum** |

A única lacuna real era a §5.2. As outras estão cobertas, e reescrevê-las aqui
seria duplicação — não cobertura.

---

## 5. Prompt injection — gate da CAT-06 (dívida S-1)

### 5.1 A §5.2 já tinha decidido, e a citação é literal

> *"Separação explícita entre instrução do sistema, contexto recuperado e dado
> do usuário, em `PromptGuard`. **Terá teste dedicado quando existir provider
> externo.**"*

`PromptGuard` está previsto na §3.1 e **não existe como arquivo** — assim como
`SuggestionPolicy`, e pelo mesmo padrão que a CAT-05B aplicou aos providers: o
que a CAT-06 vai desenhar não é adiantado por palpite.

### 5.2 Por que um teste de injection agora seria segurança falsa

Não há prompt. `GenerateListingSuggestion` não monta uma única string de
instrução: concatena o nome do item, os nomes dos conceitos casados e as
descrições curadas, e devolve um DTO. Não existe interpretador de instrução no
caminho, e nada sai da aplicação.

Um teste que escrevesse *"ignore as instruções anteriores"* na descrição e
verificasse que a sugestão não muda **passaria por motivo errado** — e
continuaria passando no dia em que alguém acoplasse um provider sem guarda,
porque não olha para o prompt. **Um teste verde por ausência de mecanismo é pior
que teste nenhum**: dá a impressão de que a área está coberta.

### 5.3 O que foi feito, então: travar a precondição

`FronteiraDePromptTest` usa o mesmo instrumento de
`test_o_caminho_de_cadastro_nao_referencia_a_inteligencia` (CAT-05F) e de
`test_nenhuma_interface_de_provider_externo_existe` (CAT-05D): não prova que a
coisa é segura, **obriga a chegada dela a ser uma decisão consciente**.

| Verificação | Teste |
|---|---|
| `PromptGuard` e `SuggestionPolicy` ainda não existem | `test_prompt_guard_ainda_nao_existe_e_e_por_isso_que_nao_ha_teste_de_injection` |
| Nenhum dos 30 arquivos do módulo monta prompt ou nomeia fornecedor | `test_nenhum_arquivo_do_modulo_monta_prompt_ou_fala_com_provider` |
| Nenhuma classe do módulo importa cliente HTTP | `test_nenhuma_classe_do_modulo_depende_de_cliente_http` |
| Texto hostil atravessa como **dado** — mesma forma de resposta | `test_texto_hostil_do_lojista_atravessa_como_dado_e_nao_como_instrucao` |

A varredura procura marcas que **não aparecem em prosa** — `'role'`,
`'messages'`, `system_prompt`, `You are`, `Http::`, `GuzzleHttp`, nomes de
fornecedor —, conferido contra os docblocks longos e em português do módulo:
zero falso positivo. **A varredura foi verificada com um prompt inserido de
propósito num arquivo do módulo, e falhou como devia**; o arquivo foi
restaurado em seguida.

O último caso merece nota, porque o seu resultado não foi o esperado e o
esperado é que estava errado. A prova de que o texto é dado e não instrução é
**comparativa**: a sugestão do item hostil tem exatamente a mesma forma que a do
item inofensivo — mesmos campos propostos, mesmas palavras-chave, mesmos
pedidos, mesma fonte. O que muda é só o texto ecoado. Que é a definição
operacional de "dado".

### 5.4 A dívida, e por que o gate é a CAT-06

**S-1 — teste de prompt injection.** Nenhum provider externo entra em operação
sem que a separação instrução/contexto/dado exista em `PromptGuard` e tenha
teste. Hoje é inaplicável porque não há prompt; a CAT-06 é exatamente o momento
em que passa a haver.

Ela vai para a CAT-06 pelo mesmo raciocínio que a CAT-05F usou com a C-2:
**não é "pertence a", é "bloqueia"**. A CAT-10 continua listando "testes de
prompt injection" na sua descrição e **herda a verificação sob observabilidade**
— a autoria do gate é da CAT-06, do mesmo jeito que a autoria do teste da regra
3 ficou com a CAT-05F e a CAT-10 herdou a verificação com provider acoplado.

---

## 6. Alterações em fases não-correntes

### 6.1 CAT-06 ganhou um terceiro gate

O quadro de gates criado pela CAT-05F, com C-2 e F-1, recebe **S-1**. Os três
têm a mesma natureza: são coisas que só passam a existir quando o texto sair da
aplicação, e nenhuma delas pode ser descoberta depois de já estar saindo.

### 6.2 CAT-09 ganhou uma obrigação nomeada (dívida S-2)

**S-2 — a sugestão é conteúdo de usuário.** `shortDescription` e `description`
são compostos a partir do texto que o lojista digitou: o nome do item abre as
duas frases, sempre. O módulo não escapa nada, e não deve — escapar aqui
gravaria entidade HTML dentro de um campo que a CAT-09 pode aplicar a
`products.description`.

A obrigação é de quem renderiza: Blade escapa por padrão, então
`{{ $sugestao->description }}` está correto e nada precisa ser feito. O que não
pode acontecer é `{!! !!}`, `wire:ignore` com `innerHTML` ou `v-html`, sob o
raciocínio de que "o texto veio da inteligência". Não veio: veio do formulário,
deu uma volta e voltou.

Está escrita por extenso no docblock de `ListingSuggestion` — **mesma forma da
C-1**, que mora ao lado do parâmetro que a exige e não só num documento, pela
mesma razão: quem for escrever a tela lê o DTO, não necessariamente o roadmap.

**S-2 não é prompt injection.** A S-1 trata de texto do lojista virando
*instrução* para um provider, e só existe a partir da CAT-06. A S-2 trata de
texto do lojista virando *marcação* numa página, e existe a partir da primeira
tela que renderizar uma sugestão.

### 6.3 Sobre a numeração `S-`

O prefixo natural desta subfase seria `G-`, e ele **está ocupado**: `G-1` é a
ausência de superfície de curadoria, criada pela CAT-DOM-02B, aberta, e citada
no quadro de dívidas de todas as subfases da CAT-05. Reusar a letra criaria duas
`G-1` no mesmo quadro.

`S-` de segurança, que é o eixo das duas, evita a colisão e diz o que elas são.

---

## 7. Revisão das dívidas abertas — nenhuma foi fechada de raspão

A CAT-05F tocou **dois arquivos**: `GenerateListingSuggestion.php` e o teste que
criou. Não tocou `ContextSanitizer`, `ListingContext`, models, o comando de
backfill nem seeder — o que já limita o que ela poderia ter afetado.

| # | Situação | Como foi verificado |
|---|---|---|
| **C-1** | **Aberta**, intocada | `knownAttributes` segue por denylist; nenhum produtor real ainda — a busca só encontra o DTO e o sanitizer, nenhum formulário |
| **C-2** | **Aberta** — o estado formal mudou, a dívida não | Ver §7.1 |
| **F-1** | **Aberta**, criada pela própria 05F | Gate da CAT-06 |
| **P-1** | **Aberta**, intocada | Nenhum arquivo de backfill ou seeder no diff da 05F — CAT-05H |
| **B-4** | **Aberta**, intocada | CAT-05H |
| **G-1** | **Aberta**, intocada | CAT-08 |
| **E-1** | **Aberta**, intocada | `KnowledgeTermType::Keyword` continua sem uso em `app/` e em `database/`; aparece só em dois testes |

### 7.1 C-2 merece atenção, porque é fácil lê-la como fechada

A CAT-05F acrescentou `mensagemSegura()`, que impede `QueryException` de gravar
o texto do lojista em log. Isso fecha **um canal**, não a dívida: a C-2 é a
ausência de redação de PII em texto livre, e o `ContextSanitizer` continua
filtrando **campos**, não conteúdo.

O que mudou foi o estado formal: ela deixou de ser *"CAT-05F ou CAT-10, destino
em aberto"* e passou a ser **gate da CAT-06**. Continua aberta, e o roadmap já a
registra corretamente.

---

## 8. Cobertura de teste

**`CustoDoAssistenteTest`** — 9 casos:

| Garantia | Teste |
|---|---|
| **O teto do assistente inteiro é 6** | `test_o_assistente_inteiro_cabe_em_seis_consultas` |
| O custo não cresce com o catálogo | `test_o_custo_do_assistente_nao_cresce_com_o_catalogo` |
| Item não salvo custa só o casamento | `test_item_ainda_nao_salvo_custa_so_o_casamento` |
| **Degradar não custa mais que funcionar** | `test_a_degradacao_nao_custa_mais_que_o_caminho_normal` |
| A causa do teto: eager-load chega ao sanitizer | `test_o_matcher_entrega_candidatos_com_os_termos_ja_carregados` |
| A consequência, medida: 1 consulta por conceito | `test_candidato_sem_eager_load_custa_uma_consulta_por_conceito` |
| Com eager-load, a redução é puro PHP | `test_com_eager_load_reduzir_conceitos_a_texto_nao_consulta_nada` |
| Montar o contexto custa 1 por ancestral | `test_montar_o_contexto_custa_uma_consulta_por_ancestral_nao_carregado` |
| E zero com `with('category.parent')` | `test_com_a_categoria_e_o_pai_carregados_montar_o_contexto_nao_consulta` |

**`FronteiraDePromptTest`** — 4 casos, listados no §5.3.

---

## 9. Suíte

| | |
|---|---|
| Antes | 1126 passed · 3372 assertions · 0 failures |
| Depois | **1139 passed · 4028 assertions · 0 failures** |
| Delta | `+13` testes, `+656` asserções |

O salto de asserções é do `FronteiraDePromptTest`: a varredura assere cada marca
contra cada arquivo do módulo, `20 × 30`. É intencional — a alternativa seria
concatenar os arquivos e assertar uma vez, o que daria uma mensagem de falha que
não diz **qual** arquivo passou a montar prompt.

Suíte executada do zero sobre a versão final, em 241,34s, com md5 conferidos
antes e depois. `git diff --check` limpo. Pint rodou apenas nos três arquivos
desta subfase.

---

## 10. O que esta subfase deliberadamente não fez

**Nenhuma mudança de comportamento.** A única alteração em `app/` é um bloco de
docblock em `ListingSuggestion` — a dívida S-2, escrita ao lado do que a exige.
Nenhuma linha executável do módulo mudou.

Nenhuma migration, tabela, coluna ou tela. Nenhum provider, nenhum `PromptGuard`
adiantado. A forma do `ListingSuggestion` (§3.4) **não foi reaberta**. As
decisões congeladas da CAT-05D, da CAT-05E e da CAT-05F não foram tocadas.

Nenhuma dívida foi fechada — a §7 é checagem, e fechar exige decisão explícita.

Nenhuma otimização do custo de `deProduct()`: ela seria em quem chama, e quem
chama ainda não existe.

---

## 11. Decision log

| # | Decisão | Motivo |
|---|---|---|
| **D-CAT-05G-1** | O teto do assistente é **6**, a soma exata dos dois tetos da CAT-04 — e não um número folgado | Teto folgado aceita a primeira regressão em silêncio, que é o defeito que o teste existe para pegar |
| **D-CAT-05G-2** | **Prompt injection é gate da CAT-06 (S-1)**, não teste desta subfase | A §5.2 já condicionava o teste à existência de provider. Sem prompt no caminho, o teste passaria por motivo errado e continuaria passando com um provider sem guarda |
| **D-CAT-05G-3** | Em vez do teste de injection, trava-se a **precondição** | Mesmo instrumento da CAT-05D e da CAT-05F: não prova segurança, obriga a chegada do prompt a ser decisão consciente |
| **D-CAT-05G-4** | O eco do texto do lojista na sugestão vira **dívida S-2**, endereçada à CAT-09, escrita no docblock de `ListingSuggestion` | Mesma forma da C-1. É categoria diferente da S-1: marcação em página, não instrução a provider |
| **D-CAT-05G-5** | O custo de `deProduct()` fica como **observação medida**, não como dívida numerada | Nenhum caminho de produção o chama hoje; dois testes já travam os dois lados, e inflar o quadro enfraquece o quadro |
| **D-CAT-05G-6** | As dívidas novas usam o prefixo **`S-`**, não `G-` | `G-1` já é a ausência de superfície de curadoria (CAT-DOM-02B), citada em todos os quadros da trilha |

---

## 12. Situação

CAT-05G concluída. Resta a **CAT-05H** — validação real sobre os 75 itens e
documentação final —, que herda **P-1** (o backfill, adiado por decisão humana
na CAT-05D) como primeira pergunta a responder, e **B-4** logo atrás: validar
com corpus de seeder pode não ser validar.
