# CAT-06B — Decisões de produto e contratos

> **Subfase de decisão.** O produto desta subfase é o que está escrito aqui:
> duas decisões de produto que a CAT-06A levantou como blockers (**B-1** e
> **B-2**), e os contratos que a CAT-06C em diante vai implementar.
> **Nenhum código foi alterado** — nem uma classe, nem um teste, nem uma linha
> de config. Diferente da CAT-05B, esta subfase não carrega dívida nominal
> alguma: não havia nada endereçado a ela antes da auditoria.

Auditoria que originou esta subfase:
[`CAT_06A_AUDITORIA_DE_RECONCILIACAO.md`](CAT_06A_AUDITORIA_DE_RECONCILIACAO.md).
Decisões de produto a que este documento se subordina:
[`CAT_05B_DECISOES_DE_PRODUTO_E_CONTRATOS.md`](CAT_05B_DECISOES_DE_PRODUTO_E_CONTRATOS.md).
Documento arquitetural: [`CATALOG_INTELLIGENCE.md`](CATALOG_INTELLIGENCE.md).

---

## 1. Baseline

| Item | Valor |
|---|---|
| Branch | `main` |
| HEAD no início | `0e96b664fbde56c236dc8d9cf9bcbadf7356bc68` |
| Working tree no início | Limpo |
| Suíte no início | 1139 passed · 4028 assertions · 0 failures |
| Data | 2026-09-03 |

---

## 2. D-CAT-06B-1 — O desfecho é um DTO ao lado da sugestão

**Fecha B-1 (gate F-1).**

Quem consome uma `ListingSuggestion` passa a receber, junto dela, um **DTO de
desfecho** que diz **em que condição aquela sugestão foi produzida**.

### 2.1 Por que não um booleano

O enunciado original do F-1, na CAT-05F §5, era binário: *"a base não conhece"*
× *"a inteligência falhou"*. A CAT-06A §2 mostrou que a CAT-06 quebra essa
premissa. São **quatro estados**, e o primeiro deles **não é falha**:

| Estado | É falha? | Como o consumidor deve tratar |
|---|---|---|
| Provider ausente por falta de credencial | **Não** — operação normal sem IA externa | Silêncio; não é anomalia, não vira aviso |
| A base não conhece o item | **Não** — é lacuna de catálogo | Já coberto por `missing_information` |
| Provider disponível, mas falhou | **Sim** — transitória | Sinalizar degradação; convidar a repetir |
| Provider respondeu, resposta inválida | **Sim** — de contrato | Sinalizar degradação; **não** convidar a repetir |

Um booleano `degradado` colapsaria a linha 1 com as linhas 3 e 4, e a operação
normal do sistema — que é rodar **sem** IA externa, por
[D-CAT-06B-5](#8-decision-log) — passaria a se anunciar como avaria permanente.

As duas últimas linhas também não são a mesma coisa: falha transitória melhora
sozinha, falha de contrato não. Repetir a chamada na segunda só queima custo.

### 2.2 Onde o desfecho entra

`GenerateListingSuggestion::comContexto()` já devolve a tupla
`[sugestão, contexto]` — o ponto de extensão **existe** e não precisa ser
inventado. Passa a devolver **o desfecho junto**.

O `__invoke()` continua devolvendo só a `ListingSuggestion`: quem não se
importa com o desfecho não paga por ele, e nenhum chamador atual quebra.

### 2.3 O que esta decisão proíbe

**A forma da `ListingSuggestion` não é reaberta.** As sete chaves —
`suggested_name`, `short_description`, `description`, `keywords`,
`missing_information`, `source`, `confidence` — seguem congeladas desde a
CAT-05D e protegidas por `test_a_sugestao_tem_a_forma_da_secao_3_4`.

**O desfecho não entra em `missing_information`.** É o mesmo princípio que a
CAT-05E já aplicou ao recusar misturar `missing_information` com status do
sistema: *o que o item precisa* é informação de catálogo; *se o provider caiu* é
informação de operação. Misturados, a UI leria falha de infraestrutura como
lacuna de cadastro — e o lojista tentaria consertar preenchendo campo.

> **Nota de fronteira.** O desfecho descreve **a condição da produção**, nunca o
> fornecedor. Nenhum campo dele nomeia provider, modelo ou endpoint — nem em
> valor de enum, nem em mensagem. A regra da §3 vale aqui inteira.

### 2.4 Nota de nomenclatura — candidata, não decidida

> **Não é decisão desta subfase.** Fica registrada como observação para a 06G,
> que é quem cria o DTO e batiza o que estiver dentro dele.

A spec §3.1 (linha 331) lista **`SuggestionOutcome`** entre os enums de
`App\CatalogIntelligence\Enums`, ao lado de `KnowledgeOrigin`, `KnowledgeKind` e
`TrustLevel`. É um nome **candidato** para o enum dos quatro estados da §2.1.

**E o peso dessa evidência é fraco — mensuravelmente fraco.** A lista da §3.1 é
a **especificação original da CAT-01**, e o código divergiu dela em quase todo
nome próprio ao longo de oito subfases. A linha `Enums/` daquela lista nomeia
quatro enums. **Nenhum dos quatro existe:**

| Nome na §3.1 | No código |
|---|---|
| `KnowledgeOrigin` | ❌ — existe `KnowledgeSource` |
| `KnowledgeKind` | ❌ — existe `KnowledgeEntryType` |
| `TrustLevel` | ❌ — sem equivalente |
| `SuggestionOutcome` | ❌ — é o nome em questão |

`Services/ListingAssistant`, da mesma lista, também nunca existiu: virou
`Actions/GenerateListingSuggestion`, divergência que a CAT-06A §12 registra para
reconciliação na 06H.

**Taxa de acerto da linha `Enums/` da §3.1 até aqui: 0 de 3 decididos.**
`SuggestionOutcome` é o quarto, e o histórico não o recomenda — estar naquela
lista tem exatamente o peso que `ListingAssistant` tinha, e esse peso já se
provou insuficiente três vezes.

> **Por que isso difere de `SuggestionPolicy` (§4.1) e das assinaturas (§4.3).**
> Naqueles dois casos o que se preservou não foi *um nome que a spec sugeriu*:
> foi **o nome que a CAT-05 já vinha usando em prosa e em decisão** para se
> referir à coisa (`SuggestionPolicy`, citado na CAT-05G e no
> `FronteiraDePromptTest`), e **assinaturas de interface transcritas
> literalmente** de um bloco de código da spec. Nenhum dos dois é "consta de uma
> lista de diretórios".

O **invólucro** — o DTO que carrega o desfecho ao lado da sugestão — não tem nome
previsto em lugar algum, e batizá-lo também é da 06G.

---

## 3. D-CAT-06B-2 — A redação de texto livre vive na fronteira de saída

**Fecha B-2 (gate C-2).**

A redação de PII em texto livre vai em **`Support/FreeTextRedactor`**, classe
própria, aplicada **na fronteira de saída para o provider** — e em nenhum outro
ponto.

### 3.1 Por que não dentro do `ContextSanitizer`

O `ContextSanitizer` serve **dois** consumidores: o assistente interno (hoje) e
o provider externo (a partir da CAT-06). Só um deles atravessa a fronteira da
aplicação.

Redigir dentro do sanitizer degradaria o texto que o assistente interno
consome — texto que **nunca sai** — para proteger uma fronteira que ele não
atravessa. O interno perderia qualidade sem ganho de privacidade algum.

A divisão de trabalho fica:

| Classe | Pergunta que responde | Serve |
|---|---|---|
| `ContextSanitizer` | *Quais **campos** podem compor o contexto?* | Ambos os caminhos |
| `FreeTextRedactor` | *Que **conteúdo** não pode atravessar a fronteira?* | Só a saída externa |

Uma filtra estrutura, a outra filtra texto. Sobrepor as duas faria cada mudança
de política exigir raciocínio sobre o caminho que ela não deveria afetar.

### 3.2 A linha de redação

| Categoria | Decisão | Razão |
|---|---|---|
| Telefone, em qualquer formato BR | **SEMPRE redigir** | PII; nenhum uso legítimo na descrição de um item |
| E-mail | **SEMPRE redigir** | idem |
| CPF / CNPJ | **SEMPRE redigir** | idem |
| CEP | **SEMPRE redigir** | idem |
| Medidas (cm, m, kg, ml…) | **NUNCA redigir** | **Conteúdo do catálogo** — redigir destruiria a sugestão |
| Preço | **NUNCA redigir** | idem |
| Quantidade | **NUNCA redigir** | idem |
| URL | **NÃO redigir por padrão** | Na maioria dos casos é divulgação comercial **intencional** |
| `@handle` de rede social | **NÃO redigir por padrão** | idem |

**As três faixas são decisões de igual peso.** "Nunca redigir preço" não é
omissão do redator: é uma escolha que pode ser quebrada por um regex de
telefone ganancioso, e por isso precisa de guarda tanto quanto a redação em si.

### 3.3 Cobertura exigida

Cada categoria — **inclusive as de não-redação** — é documentada com **teste
positivo e negativo**, no padrão de `ListingContextTest`:

- **positivo:** o que deve ser redigido, é;
- **negativo:** o que não deve ser redigido, **sobrevive intacto**.

Uma categoria de não-redação sem teste negativo não é decisão, é acidente
esperando um regex.

### 3.4 A distinção entre URL e PII, explicitada

`URL` e `@handle` ficam de fora **por padrão**, e a palavra é deliberada: é uma
decisão de produto sobre **intenção**, não sobre natureza do dado. Um telefone
na descrição raramente é intencional; um Instagram da loja quase sempre é.

Se a política mudar, muda **aqui e no config** — não no regex. Fica registrado
como o ponto de reversão, para que a próxima pessoa não trate a ausência como
esquecimento.

---

## 4. Contratos que a CAT-06C em diante implementa

### 4.1 `SuggestionPolicy` — CAT-06C

Mora em `Support/`, nome já previsto na §3.1 da spec. Acionada por
`GenerateListingSuggestion`.

- **Lê `ListingContext::lacunas()`** e **não reconta**. O método foi escrito na
  CAT-05C exatamente para isto; recontar criaria duas fontes para a mesma
  pergunta, e elas divergiriam no primeiro ajuste.
- O limiar sai de **`config/catalog-intelligence.php`**, criado nesta subfase.
- **Não conhece provider.** A 06C decide *se valeria consultar*; *consultar* é
  06D em diante. É o que permite testar a política sem nenhum dublê.

### 4.2 `config/catalog-intelligence.php` — CAT-06C

Nasce na 06C, com o limiar de fallback. **Sem chave de credencial, sem nome de
fornecedor, sem segredo** — o arquivo é de política, não de conexão.

### 4.3 `Contracts/` e `Providers/` — CAT-06D

Nascem nesta fase, por **D-CAT-05B-5**. As assinaturas são as da spec §3.3,
transcritas na CAT-06A §3 e **não redigidas de novo**:

```php
interface CatalogAiProvider
{
    public function isAvailable(): bool;
    public function suggest(ListingContext $context): ListingSuggestion;
}
```

Invariantes:

- `NullCatalogAiProvider` **nunca lança exceção**: `isAvailable() === false`.
- `FakeCatalogAiProvider` é **determinístico**.
- **Nenhuma chamada HTTP em Livewire, Controller ou Model.**
- O domínio **não conhece nome de fornecedor algum**.

`EmbeddingProvider` **não é implementado na 06D** — ver §6.

### 4.4 Validação de resposta — CAT-06D

A spec declara o retorno de `suggest()` mas **não diz o que fazer com resposta
inválida**. A CAT-06A §3 registrou a lacuna; a 06D a fecha, e o resultado dessa
validação é justamente o quarto estado do desfecho da §2.1 — as duas decisões
se encontram aí, e é por isso que a validação não pode ficar para depois da 06G.

### 4.5 `FreeTextRedactor` — CAT-06E

Conforme §3. Existe **antes** de qualquer saída ser ligada.

### 4.6 `PromptGuard` — CAT-06F

Mantém **instrução, contexto e dado em canais estruturalmente separados**, nunca
concatenados em string. A separação é **estrutural, não sintática**: não há
delimitador a escapar porque não existe string única onde os três se misturem.

`FronteiraDePromptTest` é **reescrito, não substituído** — 3 precondições caem e
são trocadas pelo que vigiavam; `test_texto_hostil_do_lojista_atravessa_como_dado_e_nao_como_instrucao`
sobrevive e vira a base do teste de injection real.

### 4.7 Ordem, e por que ela não é negociável

**06E e 06F antes de 06G.** Redator e guard existem antes de a saída ser
ligada — resiliência antes do acoplamento, como na CAT-05F. O gate C-2 diz
literalmente que nenhum provider entra em operação antes de a redação existir
**e ter teste**; ligar o fallback antes disso violaria o gate ainda que só em
`main` não publicada.

---

## 5. O que esta subfase deliberadamente não fez

- **Não escreveu código.** Nenhuma classe, nenhum teste, nenhum config. O
  `SuggestionPolicy` decidido na §4.1 não foi adiantado — é 06C.
- **Não escolheu formato do DTO de desfecho** além do que a §2 exige: quatro
  estados, fora da `ListingSuggestion`. **Nenhum nome foi decidido** — nem o do
  enum nem o do invólucro. `SuggestionOutcome` fica como candidata registrada
  (§2.4), com a ressalva de que a lista de onde ela vem já falhou antes. Nome e
  chaves são da 06G, que é quem vai consumi-los.
- **Não escreveu regex algum.** A §3.2 decide **o que** é redigido; **como** é
  06E.
- **Não decidiu timeout** (B-5) nem **custo e rate limit** (B-6).
- **Não reconciliou nomenclatura.** `ListingAssistant` × `GenerateListingSuggestion`
  fica para a 06H.

---

## 6. Decisões ainda pendentes

| # | Assunto | Destino |
|---|---|---|
| **B-3** | `EmbeddingProvider` órfão — está na spec, nenhuma fase o reivindica | **Segue em aberto.** Registrado, não decidido |
| **B-4** | Validação de resposta do provider | **06D** — §4.4 |
| **B-5** | Timeout | **06D ou 06G**, a que tocar o provider real. Não herda a CAT-05F: o caso dela era SQL, e HTTP muda o modo de falha |
| **B-6** | Custo e rate limit | **Em aberto**, sem subfase atribuída |

**B-3 não é decidido aqui de propósito.** Reivindicar um contrato órfão dentro
de uma subfase de decisão sobre *outros dois* blockers seria criar escopo por
contabilidade — o mesmo erro que a FIN-SEC evitou ao não abrir uma 01H só para
guardar dívida.

---

## 7. Fora de escopo, confirmado

**Nenhum provider real** — OpenAI, Anthropic, Gemini, Bedrock, Ollama — é
integrado nesta subfase nem em qualquer outra da trilha CAT. Confirmado em
`CATALOG_INTELLIGENCE.md` §4 (*"Não escolhe nem contrata fornecedor de IA. Não
cria credencial nem versiona segredo"*) e na entrada da CAT-06 no roadmap
(*"Sem credencial, sem fornecedor, sem segredo versionado"*).

**Ao fim da CAT-06, nenhum texto sairá da aplicação.**

---

## 8. Decision log

| # | Decisão | Fecha |
|---|---|---|
| **D-CAT-06B-1** | O desfecho da sugestão é **DTO próprio**, devolvido por `comContexto()` ao lado da sugestão e do contexto. Representa **4 estados**, um dos quais (provider ausente por falta de credencial) **não é falha**. A forma da `ListingSuggestion` não é reaberta, e o desfecho não entra em `missing_information` | **B-1**, gate **F-1** |
| **D-CAT-06B-2** | A redação de PII em texto livre vive em `Support/FreeTextRedactor`, na **fronteira de saída para o provider** — não no `ContextSanitizer`, que serve os dois caminhos. Telefone/e-mail/CPF/CNPJ/CEP sempre redigidos; medida/preço/quantidade nunca; URL e `@handle` não por padrão. Cada faixa com teste positivo **e** negativo | **B-2**, gate **C-2** |
| **D-CAT-06B-3** | `SuggestionPolicy` **lê** `ListingContext::lacunas()` e não reconta; limiar em `config/catalog-intelligence.php`; a política **não conhece provider** | Contrato da 06C |
| **D-CAT-06B-4** | Validação de resposta do provider nasce **junto dos contratos**, na 06D — não depois. É ela que produz o quarto estado do desfecho | **B-4** |
| **D-CAT-06B-5** | Operar **sem** IA externa é **estado normal**, não degradado. `NullCatalogAiProvider` é o caminho padrão de produção enquanto não houver credencial, e não se anuncia como avaria | Premissa de D-CAT-06B-1 |
| **D-CAT-06B-6** | 06E e 06F **antes** de 06G: redator e guard existem antes de a saída ser ligada | Ordem das subfases |

**Nomenclatura do desfecho não entra no decision log.** `SuggestionOutcome` é
**candidata registrada na §2.4**, não decisão fechada: a lista da §3.1 que a
sugere é a intenção da CAT-01, e o código já a contrariou em quase todo nome
próprio. Quem decide é a **06G**, quando o DTO existir.

---

## 9. Encerramento

Os dois blockers de produto da CAT-06 estão decididos e escritos.
**B-1** e **B-2** saem da lista; **B-4** ganhou subfase e razão;
**B-3**, **B-5** e **B-6** seguem abertos, registrados em vez de resolvidos por
conveniência.

Os três gates **continuam abertos** — esta subfase não fecha nenhum, e não era
para fechar. Ela produz o texto contra o qual a 06C em diante será revisada.

Nenhum código foi alterado. A suíte permanece em **1139 passed · 4028
assertions · 0 failures**.
