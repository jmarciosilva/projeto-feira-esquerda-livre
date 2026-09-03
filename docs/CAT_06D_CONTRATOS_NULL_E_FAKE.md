# CAT-06D — Contratos, `Null`, `Fake` e a validação de resposta

> **A fronteira é construída nesta subfase, e continua fechada.** Entrega o
> contrato `CatalogAiProvider`, as duas implementações que não falam com a rede,
> e a decisão da dívida **B-4**. Nenhuma implementação real, nenhuma credencial,
> nenhum binding — e nenhum texto sai da aplicação.

Decisões a que esta subfase se subordina:
[`CAT_06B_DECISOES_DE_PRODUTO_E_CONTRATOS.md`](CAT_06B_DECISOES_DE_PRODUTO_E_CONTRATOS.md)
§4.3 e §4.4. Auditoria da fase:
[`CAT_06A_AUDITORIA_DE_RECONCILIACAO.md`](CAT_06A_AUDITORIA_DE_RECONCILIACAO.md).
Subfase anterior:
[`CAT_06C_SUGGESTION_POLICY_E_CONFIG.md`](CAT_06C_SUGGESTION_POLICY_E_CONFIG.md).

---

## 1. Baseline

| Item | Valor |
|---|---|
| Branch | `main` |
| HEAD no início | `9d946d0` (promoção da 06B/06C) |
| Working tree no início | Limpo |
| Suíte no início | 1151 passed · 4102 assertions · 0 failures |
| Data | 2026-09-03 |

---

## 2. A segunda trava — e a auditoria errou pela mesma razão

**Vem primeiro porque toca arquivo de outra fase, como na 06C.**

`ListingAssistantTest::test_nenhuma_interface_de_provider_externo_existe`,
escrito na CAT-05D, prendia **quatro** nomes:

```php
foreach (['CatalogAiProvider', 'FakeCatalogAiProvider', 'NullCatalogAiProvider', 'EmbeddingProvider'] as $classe) {
    $this->assertFalse(class_exists(…) || interface_exists(…) || class_exists(…));
}
```

Esta subfase entrega **três** deles. A trava disparou.

### É a mesma falha de leitura da CAT-06A, pela segunda vez

A CAT-06A §7 catalogou o gatilho do `FronteiraDePromptTest` e contou seus testes
corretamente, mas **leu o corpo de um deles por alto** — não viu que a
`SuggestionPolicy` também estava presa ali, e a 06C descobriu na prática. Agora
a mesma coisa: a auditoria não catalogou esta trava do `ListingAssistantTest`,
que é de outro arquivo e de outra fase.

**O padrão é o mesmo e vale registrar:** a auditoria enumerou *arquivos de
teste* e *contagens*, e não *asserções*. Uma trava mora dentro do corpo de um
método, e contagem de teste não a revela. Fica como recomendação para a **06H**,
quando ela reconciliar: varrer `assertFalse(class_exists` no módulo inteiro é
uma linha de `grep`, e teria achado as duas de uma vez.

### O que foi feito

Idêntico à 06C, e pelo mesmo raciocínio: **o gatilho cumpriu a função**, então
registra-se a decisão e soltam-se as linguetas que venceram — sem contornar.

- **As três linguetas que venceram foram soltas**, e substituídas pelas
  garantias positivas de `ContratosDeProviderTest`: em vez de *"o contrato não
  existe"*, agora se afirma *"o contrato existe, e nem ele nem as implementações
  sabem falar com fora"*.
- **`EmbeddingProvider` continua preso** — e agora a trava guarda **só** ele,
  que é exatamente o que ainda não foi decidido. A **B-3** segue em aberto: o
  contrato está na spec §3.3, nenhuma fase do roadmap o reivindica, e a CAT-06A
  §9 o registrou como achado sem decisão. O teste foi renomeado para
  `test_o_embedding_provider_continua_sem_existir_porque_a_b3_segue_em_aberto`,
  para que o nome diga o motivo.
- **`test_o_assistente_nao_depende_de_nenhum_provider` não foi tocado** — e
  passou a valer *mais*: antes afirmava que o construtor não tem tipo `Provider`
  num mundo onde nenhum existia; agora afirma o mesmo num mundo onde três
  existem.

> **Alteração em arquivo de fase anterior, sinalizada.** `ListingAssistantTest`
> é da CAT-05D. Diff completo no relatório; os outros 37 casos do arquivo
> continuam passando. `CAT_05D_*.md` **não foi reescrito**.

---

## 3. O contrato

`App\CatalogIntelligence\Contracts\CatalogAiProvider` — **transcrito** da spec
§3.3, não redigido de novo:

```php
interface CatalogAiProvider
{
    public function isAvailable(): bool;
    public function suggest(ListingContext $context): ListingSuggestion;
}
```

`test_o_contrato_existe_com_a_assinatura_da_spec` verifica por reflexão que a
interface tem **exatamente esses dois métodos** — nem mais, nem menos — e os
tipos de retorno e de parâmetro. Um método a mais no futuro quebra o teste, que
é o ponto: a interface é a fronteira, e fronteira que cresce em silêncio deixa
de ser fronteira.

### `EmbeddingProvider` não foi criado

Por instrução explícita e por coerência: a **B-3** nunca foi decidida, e criar
interface sem consumidor é o erro que a CAT-05B recusou para os providers de
conteúdo (D-CAT-05B-4 — *"provider sem quem o injete é estética"*).

---

## 4. `NullCatalogAiProvider`

O caminho de produção enquanto não houver credencial (**D-CAT-06B-5**), e —
importante — **não é modo degradado**: operar sem inteligência externa é estado
normal.

### O que `suggest()` faz se for chamado

Devolve `ListingSuggestion::vazia()`.

Chamar `suggest()` sem checar `isAvailable()` é erro de quem chama, mas erro de
chamador **não pode virar exceção em produção**. As três alternativas foram
consideradas e recusadas:

| Alternativa | Por que não |
|---|---|
| **Lançar** | Viola a invariante da spec §3.3, e no caminho mais comum de produção |
| **Devolver `null`** | Mudaria o tipo do contrato e obrigaria todo consumidor a testar nulo — a armadilha que `vazia()` evitou na CAT-05D |
| **Devolver texto** | Inventar conteúdo é o que a trilha inteira existe para não fazer |

`test_o_null_nunca_lanca_em_nenhum_caminho` exercita nome vazio, nome de 8 KB,
quebra de linha, marcação, chamadas repetidas e `isAvailable()` chamado cinco
vezes seguidas.

> **A vazia carrega `source: Internal`, e isso é verdade** — nada externo
> contribuiu. A consequência é que passá-la pelo validador acusa procedência
> incorreta, o que está **certo** e está testado explicitamente
> (`test_a_vazia_do_null_acusa_procedencia_porque_nao_e_resposta_externa`). Não
> é um caso que a 06G produza, porque ela checa `isAvailable()` antes.

---

## 5. `FakeCatalogAiProvider`

Determinístico: mesma entrada, mesma saída, entre chamadas, instâncias e
execuções. Sem `rand()`, sem `now()`, sem contador influenciando o conteúdo — a
resposta padrão deriva **só** do `name` do contexto.

### As quatro situações do desfecho de F-1, todas alcançáveis

| Situação (D-CAT-06B-1) | Construtor |
|---|---|
| Provider ausente | `::indisponivel()` |
| Responde bem | `::disponivel()` |
| Responde inválido | `::respondendo($dtoForaDoContrato)` |
| Falha | `::queFalha()` |

`queFalha()` é a **exceção deliberada** à regra do `Null`: aquele nunca lança
porque é caminho de produção; este lança porque **simular falha é o trabalho
dele**, e sem isso a 06G não teria como exercitar o terceiro estado.

### `chamadas()`

Conta as invocações de `suggest()`. Existe para a 06G provar o que **não**
aconteceu: que a política decidiu não consultar e nada foi consultado. Uma
asserção sobre o veredito sozinha não distingue *"não consultou"* de
*"consultou e ignorou"*.

### `source: External` no padrão

Um dublê que se declarasse `Internal` passaria nos testes da 06D e falharia no
primeiro uso real, porque o validador exige procedência externa.

---

## 6. B-4 — a decisão sobre validação de resposta

**O ponto de partida: `suggest()` já é tipado.** O PHP garante a **classe** do
retorno e o tipo de cada propriedade. Revalidar isso seria cerimônia — se o
valor chegasse errado, o construtor já teria lançado.

### O que o tipo não garante — e é exatamente a lista implementada

| Passa pelo tipo | Por quê | Violação |
|---|---|---|
| `shortDescription: '   '` | `?string` não olha conteúdo | `TextoEmBranco` |
| `keywords: ['a' => [1,2]]` | `array` não olha o interior | `KeywordsMalformadas` |
| `missingInformation: [42]` | idem | `MissingInformationMalformada` |
| `confidence: 42.0` | `?float` não tem faixa | `ConfiancaForaDeFaixa` |
| `source: Internal` numa resposta externa | enum garante o conjunto, não o certo | `ProcedenciaIncorreta` |

O `@param array<int, string>` do DTO é documentação para análise estática, e
**análise estática não roda sobre a resposta de um serviço em produção**.

O caso do texto em branco é o mais traiçoeiro: `''` e `'   '` passam por
`?string` **e** fazem `temAlgoAPropor()` responder `true` — a CAT-09 ofereceria
ao lojista um campo vazio como se fosse proposta.
`test_o_dto_de_fato_aceita_texto_em_branco` prova que o DTO aceita, para que a
premissa do validador não vire fé.

A procedência não é cosmética: é a CAT-07 que vai gravar isso como histórico, e
procedência errada **corrompe a auditoria** da sugestão.

### Devolve violações, não booleano — e nunca lança

`violacoes()` devolve `array<int, ProviderResponseViolation>`; vazio significa
utilizável. `ehUtilizavel()` é o atalho para quem só decide.

A lista é o que alimenta o **quarto estado** do desfecho de F-1 — *"provider
respondeu, resposta inválida"*. Devolver `false` seria suficiente para
descartar, e insuficiente para **registrar por quê** — e a 06G precisa registrar.
Enum, e não string, pelo mesmo motivo de `ListingGap`: a 06G decide por
identidade, e um `match` falha em compilação quando surgir a sexta violação.

Nunca lança, pela mesma escolha do `Null` e da degradação parcial da CAT-05F:
resposta ruim de provider é **evento previsto**, não excepcional.

### O que ficou deliberadamente de fora

- **Comprimento de texto.** O limite é o da coluna, e a coluna é da CAT-02.
  Trazê-lo para cá acoplaria o validador ao schema e criaria segunda fonte para
  o mesmo limite. Quem escreve é a CAT-09.
- **Conteúdo do texto** — PII é a C-2 (06E), instrução é a S-1 (06F), marcação é
  a S-2. Este validador olha **forma**, não conteúdo.

---

## 7. Cobertura de teste

**`ContratosDeProviderTest`** — 14 casos, sem banco e sem rede:

| Caso | O que fixa |
|---|---|
| `test_o_contrato_existe_com_a_assinatura_da_spec` | Dois métodos exatos, tipos por reflexão |
| `test_as_duas_implementacoes_cumprem_o_contrato` | `instanceof` |
| `test_o_null_nunca_lanca_em_nenhum_caminho` | A invariante da §3.3, em 5 formatos de entrada |
| `test_o_null_devolve_sempre_a_mesma_coisa` | Resposta independe do contexto |
| `test_o_fake_e_deterministico_entre_chamadas_e_instancias` | 10 instâncias + 2 chamadas |
| `test_contextos_diferentes_dao_respostas_diferentes_mas_estaveis` | Deriva do contexto, estável |
| `test_o_fake_simula_os_estados_de_ausencia_sucesso_e_falha` | Três dos quatro estados |
| `test_o_fake_devolve_a_resposta_fixada_inclusive_invalida` | O quarto — resposta inválida |
| `test_o_fake_conta_as_chamadas` | Instrumento da 06G |
| `test_o_indisponivel_nao_e_consultado_por_acidente` | Indisponível não é chamado |
| `test_nenhuma_das_tres_classes_importa_cliente_http` | Fronteira de rede |
| `test_nenhuma_das_tres_classes_nomeia_fornecedor_real` | 15 nomes, **inclusive em comentário** |
| `test_nenhuma_das_tres_le_credencial_ou_endpoint` | `env(`, `Bearer`, `https://`… |
| `test_o_service_provider_nao_registra_binding_de_provider` | D-CAT-06B-6: acoplamento é 06G |

**`ValidacaoDeRespostaDoProviderTest`** — os casos da B-4, incluindo um data
provider de 9 formas de lista malformada (associativo, índices furados,
aninhado, número, nulo, string vazia, só espaço, objeto, booleano), as bordas
`0.0`/`1.0` da confiança, acumulação das cinco violações juntas, e o validador
não lançando sobre uma resposta absurda.

### Controles negativos

Quatro invariantes verificadas **falhando sem o que guardam**, com `md5sum` e
`diff` confirmando restauração exata dos quatro arquivos:

| Controle | Sabotagem | Resultado |
|---|---|---|
| **A** | `Null::suggest()` lança `LogicException` | `test_o_null_nunca_lanca` **falhou** |
| **B** | `Fake` concatena `mt_rand()` na resposta | `test_o_fake_e_deterministico` **falhou** |
| **C** | Nome de fornecedor num docblock do contrato | **2 testes falharam** — o novo e o `test_nenhum_arquivo_do_modulo_monta_prompt_ou_fala_com_provider` da CAT-05G |
| **D** | Verificação de `keywords` removida do validador | **10 testes falharam** — os 9 do data provider mais o de acumulação |

O controle C é o mais informativo: a proteção contra nome de fornecedor tem
**duas camadas independentes**, uma escrita na CAT-05G varrendo o módulo inteiro
e outra escrita aqui olhando os três arquivos da subfase. Nenhuma das duas
sozinha foi projetada contando com a outra.

---

## 8. O que esta subfase deliberadamente não fez

- **Nada foi ligado a `GenerateListingSuggestion`.** É 06G, depois de 06E e 06F.
- **Nenhum binding no `CatalogIntelligenceServiceProvider`** — nem para o
  `Null`. Está testado que ele não conhece nenhum dos três nomes.
- **`EmbeddingProvider` não foi criado** — B-3 segue sem decisão.
- **Nenhum `FreeTextRedactor`** (06E) e **nenhum `PromptGuard`** (06F).
- **Nenhum gate foi fechado.** C-2, F-1 e S-1 seguem abertos.
- **`CAT_05D_*.md` não foi reescrito** — a alteração no teste daquela fase está
  registrada aqui, na §2.

---

## 9. Decision log

| # | Decisão | Fecha |
|---|---|---|
| **D-CAT-06D-1** | `NullCatalogAiProvider::suggest()` devolve `ListingSuggestion::vazia()` e **nunca lança**, mesmo chamado sem checar `isAvailable()`. Erro de chamador não vira exceção no caminho mais comum de produção | §4 |
| **D-CAT-06D-2** | A validação da **B-4** cobre só o que o tipo não garante: texto em branco, listas malformadas, confiança fora de `[0,1]` e procedência incorreta. Revalidar o que o PHP já impõe seria cerimônia | **B-4**, §6 |
| **D-CAT-06D-3** | O validador **devolve a lista de violações** e nunca lança. Booleano bastaria para descartar e não para registrar o motivo — e o quarto estado do desfecho de F-1 carrega o motivo | **B-4**, §6 |
| **D-CAT-06D-4** | Comprimento de texto **não** é validado aqui: o limite é da coluna (CAT-02) e quem escreve é a CAT-09. Validar aqui criaria segunda fonte para o mesmo número | §6 |
| **D-CAT-06D-5** | `FakeCatalogAiProvider::queFalha()` **lança de propósito** — exceção deliberada à regra do `Null`, porque simular falha é a função dele | §5 |
| **D-CAT-06D-6** | O `Fake` conta chamadas, para que a 06G possa provar que **não** houve consulta — asserção sobre veredito não distingue "não consultou" de "consultou e ignorou" | §5 |
| **D-CAT-06D-7** | A trava da CAT-05D solta as três linguetas vencidas e passa a guardar **só o `EmbeddingProvider`**, que é o único ainda sem decisão (B-3) | §2 |
| **D-CAT-06D-8** | **`EmbeddingProvider` não é criado** — B-3 continua em aberto; interface sem consumidor é o que a D-CAT-05B-4 recusou | §3 |

---

## 10. Recomendação para a 06H

A auditoria da 06A não catalogou nenhuma das duas travas de precondição — nem a
da `SuggestionPolicy` (06C), nem esta. Ambas foram descobertas ao quebrarem.

**Antes de a 06H reconciliar, varra as asserções, não só os arquivos:**

```
grep -rn "assertFalse(\s*class_exists\|interface_exists" tests/
```

Uma trava mora no corpo de um método; contagem de teste não a revela.

---

## 11. Encerramento

A fronteira existe e está fechada. Há contrato, há as duas implementações que
não falam com a rede, e há o que fazer quando a resposta vier fora do formato.
Nenhum fornecedor foi escolhido, nenhuma credencial criada, nenhum binding
registrado — e nenhum texto sai da aplicação.

O item que mais merece revisão é o da §2, pelo mesmo motivo da 06C: toca um
arquivo de outra fase. E, desta vez, revela um padrão na auditoria que a 06H
precisa corrigir.
