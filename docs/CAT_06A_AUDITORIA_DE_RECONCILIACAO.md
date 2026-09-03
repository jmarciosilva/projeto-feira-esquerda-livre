# CAT-06A — Auditoria de reconciliação

> **Subfase de auditoria da CAT-06.** Nenhum arquivo de código foi alterado,
> nenhuma migration criada, nenhum provider acoplado. O produto desta subfase é
> o diagnóstico abaixo — o estado real do módulo na véspera da IA externa, os
> três gates herdados da CAT-05 revisitados, os contratos exatos que a fase vai
> materializar, e as decisões de produto que a CAT-06B formaliza.

Executada em **2026-09-03**, sobre `main`, working tree limpo.

Roadmap da trilha:
[`ROADMAP_CATALOG_INTELLIGENCE.md`](ROADMAP_CATALOG_INTELLIGENCE.md).
Documento arquitetural: [`CATALOG_INTELLIGENCE.md`](CATALOG_INTELLIGENCE.md).
Fase anterior, que originou os três gates:
[`CAT_05H_VALIDACAO_REAL_E_ENCERRAMENTO.md`](CAT_05H_VALIDACAO_REAL_E_ENCERRAMENTO.md).
Decisões tomadas a partir daqui:
[`CAT_06B_DECISOES_DE_PRODUTO_E_CONTRATOS.md`](CAT_06B_DECISOES_DE_PRODUTO_E_CONTRATOS.md).

> ### 📄 Procedência deste documento
>
> A auditoria foi executada e revisada em sessão anterior, que **não gravou
> arquivo** — o produto era o diagnóstico, e ele existia só no contexto da
> conversa. Um travamento de container encerrou aquela sessão e o diagnóstico se
> perdeu com ela. Este documento é a **reconstrução** do que foi revisado e
> aprovado, ditada pelo dono do projeto em 2026-09-03 e gravada em disco
> **antes** de qualquer código da CAT-06B, exatamente para que a fase não
> dependa outra vez de contexto volátil.
>
> Todas as afirmações verificáveis foram reconferidas contra o código no momento
> da gravação; as que passaram por essa reconferência estão marcadas com ✓ e a
> forma da verificação está dita. As **§10 e §11 não foram recuperadas** — a
> numeração salta de propósito, e a lacuna fica registrada em vez de preenchida
> por palpite. Se a 06H precisar delas, serão reconstruídas como seção nova, não
> retroencaixadas aqui.

---

## 1. Baseline

| Item | Valor |
|---|---|
| Branch | `main` |
| HEAD | `0e96b664fbde56c236dc8d9cf9bcbadf7356bc68` |
| Working tree | Limpo, inclusive sem não-rastreados ✓ |
| **Suíte** | **1139 passed · 4028 assertions · 0 failures** ✓ |
| Módulo `App\CatalogIntelligence` | **30 arquivos** ✓ |
| Testes do módulo | **170**, em **7 arquivos** ✓ |

Distribuição dos 30 arquivos, conferida por `find`: **8 enums**, **3 models**,
**6 Actions**, **6 DTOs**, **4 Support**, **1 Query**, **1 Command**,
**1 Provider**.

O que **não existe** — e cuja ausência é a premissa da fase:

| Ausente | Verificação |
|---|---|
| `config/catalog-intelligence.php` | `test -f` ✓ |
| `App\CatalogIntelligence\Contracts\` | diretório inexistente ✓ |
| `App\CatalogIntelligence\Providers\` | diretório inexistente ✓ |
| `App\CatalogIntelligence\Services\` | diretório inexistente ✓ |
| Qualquer `*AiProvider*` | `find app -iname` sem resultado ✓ |
| `PromptGuard`, `SuggestionPolicy` | `find app -iname` sem resultado ✓ |

> **Nota de método sobre a contagem.** `grep -cE '^\s+public function test_'`
> devolve **160** nos sete arquivos; `php artisan test --list-tests` devolve
> **170**. A diferença de 10 é data provider, não teste faltando. O número que
> vale é o do `--list-tests`, e ele é medido a cada reconciliação — nunca
> reaproveitado de relatório anterior.

---

## 2. Os três gates revisitados

Os três nasceram na CAT-05 e têm a mesma natureza: **coisas que só passam a
existir quando o texto sair da aplicação**, e nenhuma delas descobrível depois
de já estar saindo. Não é "pertence a": é "bloqueia".

### C-2 — redação de texto livre

A CAT-05F **já fechou o canal de log**: `mensagemSegura()` existe em
`GenerateListingSuggestion.php:210` ✓ e a §5.3 proíbe o vazamento por ali. O que
resta em aberto é **só o canal de saída para provider** — que é precisamente o
canal que a CAT-06 abre.

O escopo do gate, portanto, encolheu em relação ao enunciado original da CAT-05C
§4: não é "redigir PII em todo lugar", é "redigir PII na fronteira de saída",
porque o outro caminho já está coberto.

### F-1 — sinal de modo degradado

**Este gate mudou de forma, e é o achado mais consequente da auditoria.**

O enunciado da CAT-05F §5 descrevia uma ambiguidade **binária**: quem recebe a
`ListingSuggestion` não distingue *"a base não conhece este item"* de *"a
inteligência falhou"* — as duas devolvem `vazia()`. Com um único modo de falha
real, um booleano resolveria.

A CAT-06 introduz **pelo menos três modos de falha**, e um deles não é falha:

| Situação | Natureza |
|---|---|
| Provider ausente por falta de credencial | **Estado normal**, não falha — a §3.3 o trata como operação sem IA externa |
| Provider disponível mas falhando | Falha real, transitória |
| Provider responde, resposta inválida | Falha real, de contrato |

Somando o caso original — *a base não conhece o item* —, são **pelo menos 4
estados a representar**. Um booleano não serve, e é por isso que a decisão da §5
abaixo não é "adicionar uma flag".

### S-1 — teste de prompt injection

**Sem mudança desde a CAT-05G.** A precondição segue travada em
`FronteiraDePromptTest`, que tem **4 testes** ✓.

O arquivo quebra **por projeto** no dia em que `PromptGuard` nascer, e a quebra é
o recado. O gatilho é **combinado**: **3 dos 4 testes caem** — são precondições
cuja razão de existir termina quando o mecanismo chega — e **1 sobrevive**,
evoluindo para o teste de injection real:
`test_texto_hostil_do_lojista_atravessa_como_dado_e_nao_como_instrucao`
(`FronteiraDePromptTest.php:189`) ✓.

---

## 3. Contratos exatos

Confirmados **literalmente** contra `docs/CATALOG_INTELLIGENCE.md` §3.3
(linhas 372-382) ✓ — transcritos daqui, não redigidos de novo:

```php
interface CatalogAiProvider
{
    public function isAvailable(): bool;
    public function suggest(ListingContext $context): ListingSuggestion;
}

interface EmbeddingProvider
{
    public function isAvailable(): bool;
    public function embed(string $text): array;
}
```

Invariantes que acompanham os contratos:

- `NullCatalogAiProvider` **nunca lança exceção**: devolve indisponibilidade via
  `isAvailable() === false`, e o assistente cai para conhecimento interno.
- `FakeCatalogAiProvider` é **determinístico** — é o que torna o contrato
  testável sem rede.
- O **domínio não conhece nome de fornecedor algum**. A spec é explícita: "não
  conhece OpenAI, Anthropic, Gemini nem nome de modelo".
- **Nenhuma chamada HTTP em Livewire, Controller ou Model.** A integração fica
  atrás do contrato "e ponto" (spec, linha 367).
- Os namespaces `Contracts/` e `Providers/` **nascem nesta fase**, por
  **D-CAT-05B-5** ✓ (`CAT_05B_DECISOES_DE_PRODUTO_E_CONTRATOS.md:399`).

### Lacuna real na especificação

`suggest()` declara retorno `ListingSuggestion`, mas **a spec não diz o que
fazer se o provider devolver JSON inválido ou campo fora do contrato**. Não é
omissão de leitura: o texto simplesmente não cobre o caso.

É **decisão desta fase**, mapeada para a **06D** — a subfase que materializa os
contratos é onde a validação de resposta tem de nascer, junto deles. Ver **B-4**
na §9.

---

## 4. Onde o threshold de fallback vive

Em **`Support/SuggestionPolicy`** — nome já previsto na §3.1 da spec, e por isso
não inventado aqui —, acionada por `GenerateListingSuggestion`.

A política **lê `ListingContext::lacunas()`** (`ListingContext.php:244`) ✓ e
**não deve recontar**: o método foi escrito na CAT-05C exatamente para isto.
Recontar lacuna dentro da política criaria duas fontes para a mesma pergunta, e
elas divergiriam no primeiro ajuste.

O limiar sai de **`config/catalog-intelligence.php`** — arquivo que ainda não
existe (§1) e que a **06C** cria.

---

## 5. F-1 — decisão já tomada: **(d)**

**DTO de desfecho ao lado da `ListingSuggestion`**, saindo de `comContexto()`.

O método já devolve a tupla `[sugestão, contexto]`
(`GenerateListingSuggestion.php:104-109`) ✓ — passa a devolver **o desfecho
junto**. O ponto de extensão, portanto, já existe: não é preciso abrir
assinatura nova nem inventar canal.

**A forma da `ListingSuggestion` NÃO é reaberta.** As sete chaves —
`suggested_name`, `short_description`, `description`, `keywords`,
`missing_information`, `source`, `confidence` — estão protegidas por
`test_a_sugestao_tem_a_forma_da_secao_3_4` (`ListingAssistantTest.php:546`) ✓, e
continuam protegidas.

**Justificativa.** É o mesmo princípio que a CAT-05E já aplicou ao **recusar
misturar `missing_information` com status do sistema**: o que o item precisa é
informação de catálogo; se o provider caiu é informação de operação. Enfiar o
segundo dentro do primeiro faria a UI ler falha de infraestrutura como lacuna de
cadastro, e o lojista tentaria consertar preenchendo campo. Os quatro estados da
§2 vivem no desfecho, não na sugestão.

---

## 6. C-2 — decisão já tomada

**Onde:** `Support/FreeTextRedactor` próprio, aplicado **na fronteira de saída
para o provider**.

**Não dentro do `ContextSanitizer`.** O sanitizer serve os **dois** caminhos — o
assistente interno e (a partir da CAT-06) o provider externo. Redigir ali
degradaria o texto que o assistente interno consome, e esse texto **nunca sai da
aplicação**. O interno perderia qualidade para proteger uma fronteira que ele
não atravessa.

### Linha de redação

| Categoria | Decisão | Razão |
|---|---|---|
| Telefone (qualquer formato BR), e-mail, CPF/CNPJ, CEP | **SEMPRE redigir** | PII, sem uso legítimo na descrição de um item |
| Medidas, preço, quantidade | **NUNCA redigir** | É **conteúdo do catálogo** — redigir aqui destruiria a sugestão |
| URL e `@handle` de rede social | **NÃO redigir por padrão** | Na maioria dos casos é divulgação comercial **intencional** do lojista |

Cada categoria — inclusive as duas de não-redação — é documentada **com teste
positivo e negativo**, no padrão de `ListingContextTest`. A categoria que diz
"nunca redigir" só é uma decisão se houver teste provando que ela se mantém.

---

## 7. S-1 — o que `PromptGuard` faz

Mantém **instrução, contexto e dado em canais estruturalmente separados**, nunca
concatenados em string. A separação é **estrutural**, não sintática: não há
delimitador a escapar porque não há string única onde os três se misturem.

`FronteiraDePromptTest` é **reescrito, não substituído**:

- **3 testes de precondição caem** e são trocados **pelo que vigiavam** — a
  precondição existia para obrigar a chegada do mecanismo a ser decisão
  consciente; chegado o mecanismo, o que vale é testá-lo.
- **`test_texto_hostil_do_lojista_atravessa_como_dado_e_nao_como_instrucao`
  sobrevive** e vira a **base do teste de injection real**.

Substituir o arquivo perderia a única asserção que já mede a coisa certa.

---

## 8. Subfases da CAT-06, aprovadas

| Subfase | Escopo | Status |
|---|---|---|
| **06A** | Auditoria de reconciliação (esta) | ✅ Concluída |
| **06B** | **Decisões de produto** — F-1 e C-2 formalizados | 🔍 **Próxima** |
| **06C** | `SuggestionPolicy` + `config/catalog-intelligence.php`, **sem provider** | ⬜ |
| **06D** | `Contracts/` + `NullCatalogAiProvider` + `FakeCatalogAiProvider` | ⬜ |
| **06E** | `FreeTextRedactor` — **fecha C-2** | ⬜ |
| **06F** | `PromptGuard` + reescrita do `FronteiraDePromptTest` — **fecha S-1** | ⬜ |
| **06G** | Fallback ligado + desfecho de F-1 | ⬜ |
| **06H** | Validação e encerramento | ⬜ |

> ### Por que 06E/06F vêm antes de 06G
>
> A ordem é **deliberada**: o redator e o guard **existem antes de a saída ser
> ligada**. É o mesmo princípio da CAT-05F — **resiliência antes do
> acoplamento**. Na ordem inversa haveria uma janela, ainda que só em `main` não
> publicada, em que o texto pode sair sem redator e sem guard; e o gate C-2 diz
> literalmente que nenhum provider entra em operação antes da redação existir
> **e ter teste**.

---

## 9. Blockers

| # | Assunto | Situação |
|---|---|---|
| **B-1** | Sinal de modo degradado (F-1) | ✅ **Decidido** na §5 — DTO de desfecho, opção (d) |
| **B-2** | Redação de texto livre (C-2) | ✅ **Decidido** na §6 — `FreeTextRedactor` na fronteira de saída |
| **B-3** | **`EmbeddingProvider` está órfão** | 🔎 **Registrado como achado, sem decidir agora** |
| **B-4** | Validação de resposta do provider | ➡️ **Mapeado para a 06D** (lacuna da §3) |
| **B-5** | Timeout | ➡️ Decisão na subfase que tocar o provider real (**06D ou 06G**) |
| **B-6** | Custo e rate limit | ⬜ **Em aberto, sem decisão** |

### B-3 — `EmbeddingProvider` órfão

O contrato está na especificação (`CATALOG_INTELLIGENCE.md` §3.3, e a linha 487
o descreve como "nível 3 — preparado, não acoplado"), mas **nenhuma fase do
roadmap o reivindica** ✓: a única ocorrência de "embeddings" em
`ROADMAP_CATALOG_INTELLIGENCE.md` é uma **restrição** ("nada de … embeddings",
linha 316), não uma entrega.

Fica **registrado como achado e não decidido nesta fase**. Decidir o destino de
um contrato órfão no meio da auditoria seria criar escopo por contabilidade.

### B-5 — por que timeout não herda a decisão da CAT-05F

A CAT-05F já enfrentou degradação parcial, mas o caso dela era **SQL**: consulta
local, falha rápida, sem espera indefinida. **Com HTTP isso muda** — a falha por
espera passa a ser um modo próprio, e o valor do timeout é decisão de produto,
não constante técnica. Por isso não se resolve por analogia aqui.

---

## 12. Fora de escopo — confirmado por duas citações

**Nenhum provider real** — OpenAI, Anthropic, Gemini, Bedrock, Ollama — é
integrado nesta fase **nem em nenhuma outra da trilha CAT**.

Confirmado em duas fontes independentes ✓:

1. `docs/CATALOG_INTELLIGENCE.md` §4, linhas 830-831: *"Não escolhe nem contrata
   fornecedor de IA. Não cria credencial nem versiona segredo."*
2. Entrada da CAT-06 no `ROADMAP_CATALOG_INTELLIGENCE.md`: *"Sem credencial, sem
   fornecedor, sem segredo versionado."*

O que a fase entrega: **contrato + Fake + Null + threshold + redator + guard**.

> **Ao fim da CAT-06, nenhum texto sairá da aplicação.** A fase constrói a
> fronteira e a guarnece; atravessá-la é decisão posterior, de quem contratar
> fornecedor.

### Divergências de nomenclatura

`ListingAssistant` (spec) × `GenerateListingSuggestion` (código), e as demais do
mesmo tipo: **reconciliar na 06H**, não agora. Renomear no meio de uma fase que
ainda vai criar seis arquivos novos garantiria um segundo passe de renome.

---

## 14. Encerramento

A auditoria não alterou código. Os três gates seguem **abertos** e permanecem
gates. Dois blockers de produto (**B-1**, **B-2**) foram decididos aqui e são
**formalizados na 06B**; **B-4** e **B-5** têm destino; **B-3** e **B-6** ficam
explicitamente em aberto, registrados em vez de resolvidos por conveniência.

O achado de maior consequência é o da §2: **F-1 deixou de ser binário**. Um
booleano de "degradado" resolveria o enunciado da CAT-05F e estaria errado para
a CAT-06 — são pelo menos quatro estados, e um deles não é falha.
